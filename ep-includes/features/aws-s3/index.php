<?php
if(!isset($seg)) exit;

require_once __BASE_DIR__ . '/vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

global $config_storage;
$config_storage =
[
    'default_disk' => 's3',
    'disks' => [
        's3' => [
            'key'         => env('AWS_ACCESS_KEY_ID'),
            'secret'      => env('AWS_SECRET_ACCESS_KEY'),
            'region'      => env('AWS_DEFAULT_REGION'),
            'bucket'      => env('AWS_BUCKET'),
            'base_url'    => env('AWS_S3_BASE_URL'),
            'site_prefix' => s3_site_prefix(),
        ],
    ],
];


function s3_site_prefix(): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'unknown-site';

    // remove port if present
    $host = preg_replace('/:\d+$/', '', $host);

    // normalize
    $host = strtolower(trim($host));

    // allow domain chars only
    $host = preg_replace('/[^a-z0-9\.\-]+/', '-', $host);
    $host = trim($host, '.-');

    return $host !== '' ? $host : 'unknown-site';
}


/**
 * Get a singleton S3 client instance based on config-storage.php.
 *
 * @return S3Client
 */
function s3_client(): S3Client
{
    static $client = null;

    if ($client instanceof S3Client) {
        return $client;
    }

    global $config_storage;

    $disk = $config_storage['disks']['s3'] ?? null;
    if (!$disk) {
        throw new RuntimeException('S3 disk configuration not found.');
    }

    $client = new S3Client([
        'version'     => 'latest',
        'region'      => $disk['region'],
        'credentials' => [
            'key'    => $disk['key'],
            'secret' => $disk['secret'],
        ],
        'http' => ['verify' => false]
    ]);

    return $client;
}


/**
 * Build the S3 key (path) for a given file.
 *
 * Expected $params:
 * - media_type   (string)  images|videos|docs|archives (folder name)
 * - extension    (string)  file extension without dot (e.g. 'jpg')
 * - site_prefix  (string)  optional override
 * - filename     (string)  optional fixed filename WITHOUT path (e.g. "avatar.webp")
 *
 * Output:
 *   {site_prefix}/{media_type}/{filename}.{ext}
 *   or random filename if not provided.
 */
function s3_build_media_key(array $params): string
{
    global $config_storage;

    $disk        = $config_storage['disks']['s3'] ?? [];
    $site_prefix = $params['site_prefix'] ?? ($disk['site_prefix'] ?? 'unknown-site');

    $media_type = preg_replace('/[^a-z0-9_\-]+/i', '-', $params['media_type'] ?? '');
    $media_type = strtolower($media_type);
    if (!empty($media_type)) {
        $media_type = '/' . $media_type;
    }

    $subdir = trim((string)($params['subdir'] ?? ''), '/');
    $subdir = $subdir !== '' ? $subdir . '/' : '';

    // filename é obrigatório agora
    $filename = trim((string)($params['filename'] ?? ''));
    if ($filename === '') {
        throw new InvalidArgumentException('filename is required for S3 media key.');
    }

    // NÃO altera o nome
    // apenas remove barras por segurança
    $filename = basename($filename);

    return $site_prefix . $media_type . '/' . $subdir . $filename;
}



function s3_presigned_url(string $key, int $expiresSeconds = 600): ?string
{
    global $config_storage;

    try {
        $bucket = $config_storage['disks']['s3']['bucket'];
        $client = s3_client();

        $cmd = $client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        $request = $client->createPresignedRequest($cmd, '+' . $expiresSeconds . ' seconds');
        return (string) $request->getUri();
    } catch (Throwable $e) {
        return null;
    }
}


function s3_copy_object(string $fromKey, string $toKey): bool
{
    global $config_storage;

    try {
        $bucket = $config_storage['disks']['s3']['bucket'];
        $client = s3_client();

        $client->copyObject([
            'Bucket'     => $bucket,
            'CopySource' => "{$bucket}/{$fromKey}",
            'Key'        => $toKey,
        ]);

        return true;
    } catch (Throwable $e) {
        return false;
    }
}


/**
 * Upload a media file to S3.
 *
 * Expected $params:
 * - source_path   (string) Absolute local file path
 *      OR
 * - uploaded_file (array) Single $_FILES[...] entry
 *
 * - media_type    (string) e.g. 'images', 'videos', 'docs' (used in path)
 * - visibility    (string) 'public' or 'private' (default: 'public')
 * - content_type  (string) optional, MIME type
 * - metadata      (array)  optional, S3 object metadata
 *
 * @param array $params
 * @return array
 *   - code       success|error
 *   - key        S3 object key
 *   - url        Public URL (if public)
 *   - message    Human readable message
 *   - exception  Raw exception message (only for debugging)
 */
function s3_media_upload(array $params): array
{
    global $config_storage;

    $disk = $config_storage['disks']['s3'] ?? null;
    if (!$disk) {
        return [
            'code'      => 'error',
            'message'   => 'S3 disk configuration not found.',
            'exception' => null,
        ];
    }

    $bucket     = $disk['bucket'];
    $base_url   = rtrim($disk['base_url'], '/');
    $visibility = strtolower($params['visibility'] ?? 'public');
    $filename   = $params['filename'] ?? random_name();

    /**
     * 1. Resolve local file path
     */
    $localPath = $params['source_path'] ?? '';


    if (empty($localPath) && !empty($params['uploaded_file']) && is_array($params['uploaded_file'])) {
        $file = $params['uploaded_file'];

        if (!empty($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
            $localPath = $file['tmp_name'];
        }
    }

    if (empty($localPath) || !file_exists($localPath)) {
        return [
            'code'      => 'error',
            'message'   => 'Local file not found or invalid upload.',
            'exception' => null,
        ];
    }

    /**
     * 2. Detect extension and content type
     */
    $extension    = $params['extension'] ?? pathinfo($localPath, PATHINFO_EXTENSION);
    $content_type = $params['content_type'] ?? null;

    if (empty($content_type)) {
        // Basic fallback; you can improve this with finfo_file if available
        $mime = function_exists('mime_content_type') ? mime_content_type($localPath) : null;
        $content_type = $mime ?: 'application/octet-stream';
    }

    /**
     * 3. Build S3 key (path in bucket)
     */
    $key = s3_build_media_key([
        'media_type' => $params['media_type'] ?? 'archives',
        'filename' => $filename,
        'extension' => $extension,
        'site_prefix' => $params['site_prefix'] ?? null,
    ]);

    /**
     * 4. Prepare S3 putObject params
     */
    $acl = ($visibility === 'private') ? 'private' : 'public-read';

    $putParams = [
        'Bucket'      => $bucket,
        'Key'         => $key,
        'SourceFile'  => $localPath,
        // 'ACL'         => $acl,
        'ContentType' => $content_type,
    ];

    if (!empty($params['metadata']) && is_array($params['metadata'])) {
        $putParams['Metadata'] = $params['metadata'];
    }

    /**
     * 5. Upload to S3
     */
    try
    {
        $client = s3_client();
        $client->putObject($putParams);

        // print_r($putParams);

        $url = ($visibility === 'public')
            ? $base_url . '/' . $key
            : null;

        return [
            'code'      => 'success',
            'key'       => $key,
            'url'       => $url,
            'message'   => 'File uploaded to S3 successfully.',
            'exception' => null,
        ];

    } catch (AwsException $e) {
        return [
            'code'      => 'error',
            'message'   => 'Error uploading file to S3.',
            'exception' => $e->getMessage(),
        ];
    } catch (Throwable $e) {
        return [
            'code'      => 'error',
            'message'   => 'Unexpected error uploading file to S3.',
            'exception' => $e->getMessage(),
        ];
    }
}


/**
 * Delete a media object from S3 by key.
 *
 * Expected $params:
 * - key (string) S3 object key (path inside the bucket)
 *
 * @param array $params
 * @return array
 */
function s3_media_delete(array $params): array
{
    global $config_storage;

    $disk = $config_storage['disks']['s3'] ?? null;
    if (!$disk) {
        return [
            'code'      => 'error',
            'message'   => 'S3 disk configuration not found.',
            'exception' => null,
        ];
    }

    $bucket = $disk['bucket'];
    $key    = trim($params['key'] ?? '');

    if ($key === '') {
        return [
            'code'      => 'error',
            'message'   => 'S3 key is required to delete an object.',
            'exception' => null,
        ];
    }

    try {
        $client = s3_client();
        $client->deleteObject([
            'Bucket' => $bucket,
            'Key'    => $key,
        ]);

        return [
            'code'      => 'success',
            'message'   => 'File deleted from S3 successfully.',
            'exception' => null,
        ];

    } catch (AwsException $e) {
        return [
            'code'      => 'error',
            'message'   => 'Error deleting file from S3.',
            'exception' => $e->getMessage(),
        ];
    } catch (Throwable $e) {
        return [
            'code'      => 'error',
            'message'   => 'Unexpected error deleting file from S3.',
            'exception' => $e->getMessage(),
        ];
    }
}
