<?php
if(!isset($seg)) exit;


/**
 * Clean temporary uploads (local and S3) using the same max_age rule.
 *
 * - Local: /uploads/temp/*
 * - S3: {site_prefix}/temp/**
 *
 * @return void
 */
function clean_temp_uploads(): void
{
    global $config_storage;

    feature('aws-s3');

    // Max age in seconds
    $max_age = TIME_TO_DELETE_TEMP_FILES * 24 * 60 * 60;
    $now     = time();

    /**
     * 1) CLEAN LOCAL TEMP FILES
     */
    $localPath = __BASE_DIR__ . '/uploads/temp';

    if (is_dir($localPath))
    {
        $files = scandir($localPath);

        foreach ($files as $file)
        {
            if ($file === '.' || $file === '..') continue;

            $filePath = $localPath . DIRECTORY_SEPARATOR . $file;

            if (!is_file($filePath)) continue;

            $mtime = filemtime($filePath);
            if ($mtime !== false && ($now - $mtime) > $max_age) {
                @unlink($filePath);
            }
        }
    }

    /**
     * 2) CLEAN S3 TEMP FILES
     */
    try
    {
        $client      = s3_client();
        $site_prefix = s3_site_prefix();
        $bucket      = $config_storage['disks']['s3']['bucket'];

        if ($site_prefix === '') return;

        $prefix = $site_prefix . '/temp/';

        $params = [
            'Bucket' => $bucket,
            'Prefix' => $prefix,
        ];

        do
        {
            $result = $client->listObjectsV2($params);

            if (empty($result['Contents'])) break;

            foreach ($result['Contents'] as $obj)
            {
                if (empty($obj['Key']) || empty($obj['LastModified'])) continue;

                $lastModified = strtotime((string)$obj['LastModified']);
                if (!$lastModified) continue;

                if (($now - $lastModified) > $max_age)
                {
                    $client->deleteObject([
                        'Bucket' => $bucket,
                        'Key'    => $obj['Key'],
                    ]);
                }
            }

            // Pagination
            $params['ContinuationToken'] = $result['NextContinuationToken'] ?? null;

        } while (!empty($params['ContinuationToken']));

    } catch (Throwable $e) {
        // Silently fail — temp cleanup must never break the system
    }
}


/**
 * Deletes a folder and its contents recursively.
 *
 * @param string $dir The directory path.
 * @return bool True on success, false on failure.
 */
function delete_folder(string $dir)
{
    $files = array_diff(scandir($dir), ['.','..']);
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delete_folder("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}


/**
 * Retrieves PHP files in a directory.
 *
 * @param string $dir The directory path.
 * @param bool $ForSelects Whether to format the files for selects or not.
 * @return mixed|string|array|null The formatted string or array of PHP files, or null if the directory doesn't exist.
 */
function get_php_files_in(string $dir, bool $ForSelects = false)
{
    // Check if the directory exists
    if (is_dir($dir)) {

        // Get the directory contents
        $arr = scandir($dir);

        $files = [];
        foreach ($arr as $file)
        {
            if ($file != "." && $file != "..")
            {
                $file_info = pathinfo($file);
                if (isset($file_info['extension']) AND $file_info['extension'] == "php")
                {
                    //if ($file == 'common.php') continue;
                    $files[] = $file;
                }
            }
        }

        return $files;
    }

    return;
}


/**
 * Uploads an image file, resizes it if necessary, and saves it to the specified folder.
 *
 * @param array $image The image file to upload ($_FILES['image']).
 * @param string $folder The folder directory to save the uploaded image.
 * @param string|null $size The desired size of the image in the format 'widthxheight' (e.g., '300x200'). Defaults to the original size if not provided.
 * @param bool $debug Indicates whether to display the uploaded image for debugging purposes. Defaults to false.
 * @return string|false The full path of the uploaded image if successful, false otherwise.
 */
function media_upload_temp(array $params = [])
{
    $files        = $params['files'] ?? [];
    $folder       = $params['folder'] ?? 'uploads/temp/';
    $size         = $params['size'] ?? null; // only for images
    $debug        = $params['debug'] ?? false;
    $allowed_exts = $params['allowed_exts'] ?? [];
    $force_webp   = isset($params['force_webp']) ? (bool)$params['force_webp'] : false;

    $upload_to_s3 = !empty($params['upload_to_s3']);
    $visibility   = $params['visibility'] ?? DEFAULT_FILES_VISIBILITY;

    // Normalize allowed_exts
    if (is_string($allowed_exts)) {
        $allowed_exts = explode(',', str_replace(' ', '', $allowed_exts));
    }
    $allowed_exts = array_values(array_filter(array_map('strtolower', (array)$allowed_exts)));

    // Ensure folder ends with "/"
    if ($folder !== '' && substr($folder, -1) !== '/' && substr($folder, -1) !== '\\') {
        $folder .= '/';
    }

    if (!is_dir($folder)) {
        @mkdir($folder, 0755, true);
    }

    // Normalize single -> multiple
    if (isset($files['name']) && !is_array($files['name'])) {
        $files = [
            'name'     => [$files['name']],
            'type'     => [$files['type'] ?? ''],
            'tmp_name' => [$files['tmp_name']],
            'error'    => [$files['error']],
            'size'     => [$files['size']],
            'value'    => (!empty($files['value']) ? [$files['value']] : false),
        ];
    }

    $filenames = [];

    // Reuse "value"
    if (!empty($files['value']) && $files['value'] != false) {
        foreach ($files['value'] as $name) {
            if (is_json($name)) {
                $filenames = array_merge($filenames, (array)json_decode($name, true));
            } elseif (is_string($name) && $name !== '[]') {
                $filenames[] = $name;
            } elseif (is_array($name)) {
                $filenames = array_merge($filenames, $name);
            }
        }
    }

    $upload_max = convert_to_bytes(ini_get('upload_max_filesize'));
    $post_max   = convert_to_bytes(ini_get('post_max_size'));
    $max_upload = min($upload_max, $post_max);

    $infer_media_type = function (string $folderPath, string $fallback) {
        $media_type = $fallback;
        $clean = trim(str_replace('\\', '/', $folderPath), '/');
        if ($clean === '') return $media_type;

        $parts = explode('/', $clean);
        $last  = end($parts);
        if (!empty($last)) {
            $last = strtolower(preg_replace('/[^a-z0-9\-_]+/i', '-', $last));
            $last = trim($last, '-_');
            if ($last !== '') $media_type = $last;
        }
        return $media_type;
    };

    $is_image_mime = function (string $mime) {
        $mime = strtolower(trim($mime));
        return (strpos($mime, 'image/') === 0);
    };

    $mime_to_ext = function (string $mime) {
        $mime = strtolower(trim($mime));
        $map = [
            'image/jpeg' => 'jpg',
            'image/pjpeg'=> 'jpg',
            'image/png'  => 'png',
            'image/x-png'=> 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/bmp'  => 'bmp',
            'application/pdf' => 'pdf',
            'text/plain' => 'txt',
            'application/zip' => 'zip',
            'video/mp4'  => 'mp4',
            'video/webm' => 'webm',
            'audio/mpeg' => 'mp3',
            'audio/webm' => 'webm',
            'audio/ogg'  => 'ogg',
            'audio/wav'  => 'wav',
        ];
        return $map[$mime] ?? '';
    };

    if (!empty($files['name'])) {
        $total = count($files['name']);

        foreach ($files['name'] as $key => $value) {
            if (empty($value)) continue;

            $file = [
                'name'     => (string)($files['name'][$key] ?? ''),
                'type'     => (string)($files['type'][$key] ?? ''),
                'tmp_name' => (string)($files['tmp_name'][$key] ?? ''),
                'error'    => (int)($files['error'][$key] ?? 0),
                'size'     => (int)($files['size'][$key] ?? 0),
            ];

            if (empty($file['tmp_name']) || !is_file($file['tmp_name'])) continue;
            if ($file['size'] > $max_upload) continue;
            if ($file['error'] !== UPLOAD_ERR_OK) continue;

            // Detect mime from file (more reliable than client)
            $mimeDetected = '';
            if (class_exists('finfo')) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeDetected = (string)($finfo->file($file['tmp_name']) ?: '');
            }
            $mime = $mimeDetected !== '' ? $mimeDetected : $file['type'];

            // Extension
            $origExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ($origExt === '') {
                $origExt = $mime_to_ext($mime);
            }
            if ($origExt === '') $origExt = 'bin';

            // allowed_exts check (if set)
            if (!empty($allowed_exts) && !in_array(strtolower($origExt), $allowed_exts, true)) {
                continue;
            }

            // TEMP naming (always)
            $base = pathinfo(basename($file['name']), PATHINFO_FILENAME) ?: 'file';
            $base = sanitize_string($base);

            // If multiple, add index to base (optional but useful)
            if ($total > 1) $base .= '-' . ($key + 1);

            // $tempName = build_temp_filename($base, $origExt);
            // $destPath = $folder . $tempName;

            $final_name = isset($params['final_name']) ? (string)$params['final_name'] : '';

            $baseSource = ($final_name !== '')
                ? $final_name
                : ($file['name'] ?: 'file');

            // If multiple files, suffix index on base (keeps your old behavior)
            $base = pathinfo(basename($baseSource), PATHINFO_FILENAME) ?: 'file';
            if ($total > 1) {
                $base .= '-' . ($key + 1);
            }

            $tempName = build_temp_filename($base, $origExt ?: 'bin', $force_webp);
            $destPath = $folder . $tempName;

            // IMAGE FLOW
            if ($is_image_mime($mime)) {
                $saved = save_image_temp([
                    'tmp_name'   => $file['tmp_name'],
                    'mime'       => $mime,
                    'dest_path'  => $destPath,
                    'size'       => $size,
                    'force_webp' => $force_webp,
                ]);

                if (!$saved) continue;

            } else {
                // ARCHIVE FLOW
                if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                    continue;
                }
            }

            if ($debug) {
                echo "<p>Saved: {$destPath}</p>";
            }

            // Upload to S3 (optional)
            if ($upload_to_s3) {
                feature('aws-s3');

                $media_type   = $infer_media_type($folder, $is_image_mime($mime) ? 'images' : 'archives');
                $content_type = $mime ?: (function_exists('mime_content_type') ? mime_content_type($destPath) : null);

                $upload = s3_media_upload([
                    'filename'     => basename($destPath), // IMPORTANT: keep EXACT temp name
                    'source_path'  => $destPath,
                    'media_type'   => $media_type,
                    'visibility'   => $visibility,
                    'content_type' => $content_type ?: null,
                    'metadata'     => [
                        'original_name' => (string)($file['name'] ?? ''),
                    ],
                ]);

                if (($upload['code'] ?? '') === 'success') {
                    @unlink($destPath);
                    $filenames[] = $upload['key'];
                } else {
                    $filenames[] = basename($destPath);
                }

                continue;
            }

            $filenames[] = basename($destPath);
        }
    }

    return (count($filenames) == 1) ? $filenames[0] : $filenames;
}

/**
 * Saves an image to a temp path, optionally resizing and/or forcing webp.
 * Returns true on success.
 */
function save_image_temp(array $p): bool
{
    $tmp_name   = (string)($p['tmp_name'] ?? '');
    $mime       = strtolower((string)($p['mime'] ?? ''));
    $dest_path  = (string)($p['dest_path'] ?? '');
    $size       = $p['size'] ?? null;
    $force_webp = !empty($p['force_webp']);

    if ($tmp_name === '' || $dest_path === '') return false;

    switch ($mime) {
        case 'image/jpeg':
        case 'image/pjpeg':
            $im = @imagecreatefromjpeg($tmp_name);
            break;
        case 'image/png':
        case 'image/x-png':
            $im = @imagecreatefrompng($tmp_name);
            if ($im) { imagealphablending($im, false); imagesavealpha($im, true); }
            break;
        case 'image/webp':
            $im = @imagecreatefromwebp($tmp_name);
            break;
        case 'image/gif':
            $im = @imagecreatefromgif($tmp_name);
            break;
        case 'image/bmp':
            $im = @imagecreatefrombmp($tmp_name);
            break;
        default:
            return false;
    }

    if (!$im) return false;

    $orig_w = imagesx($im);
    $orig_h = imagesy($im);

    if ($size) {
        [$new_w, $new_h] = array_map('intval', explode('x', (string)$size));
        if ($new_w <= 0 || $new_h <= 0) { $new_w = $orig_w; $new_h = $orig_h; }
    } else {
        $new_w = $orig_w;
        $new_h = $orig_h;
    }

    $resized = imagecreatetruecolor($new_w, $new_h);

    if (in_array($mime, ['image/png', 'image/x-png', 'image/gif'], true)) {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);
    }

    imagecopyresampled($resized, $im, 0, 0, 0, 0, $new_w, $new_h, $orig_w, $orig_h);

    // Determine output ext by dest_path + force_webp (optional)
    $ext = strtolower(pathinfo($dest_path, PATHINFO_EXTENSION)) ?: 'jpg';
    if ($force_webp && $mime !== 'image/gif') {
        $ext = 'webp';
        $dest_path = preg_replace('/\.[a-z0-9]+$/i', '.webp', $dest_path);
    }

    $ok = false;
    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $ok = imagejpeg($resized, $dest_path);
            break;
        case 'png':
            $ok = imagepng($resized, $dest_path);
            break;
        case 'webp':
            $ok = imagewebp($resized, $dest_path, 100);
            break;
        case 'gif':
            $ok = imagegif($resized, $dest_path);
            break;
        case 'bmp':
            $ok = imagebmp($resized, $dest_path);
            break;
    }

    imagedestroy($im);
    imagedestroy($resized);

    return (bool)$ok;
}


function zip_files($params)
{
    $zip = new ZipArchive();

    if (!isset($params['files']) || !isset($params['filename'])) return false;

    $files    = $params['files'];
    $filename = $params['filename'];
    $folder   = !empty($params['folder']) ? "{$params['folder']}/" : '';

    if ($zip->open($filename, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) return false;

    foreach ($files as $file)
    {
        if (file_exists($file))
        {
            $filename = basename($file);
            $zip->addFile($file, $folder.$filename);
        }
    }

    $zip->close();

    return true;
}


function force_download(string $filename = '')
{
    if (empty($filename)) return false;

    header("Pragma: public");
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private", false);
    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=\"" . basename($filename) . "\";");
    header("Content-Transfer-Encoding: binary");
    header("Content-Length: " . filesize($filename));
    readfile($filename);

    return true;
}


function random_name()
{
    return time().'-'.rand(0000001, 9999999);
}

/**
 * Build a temp filename using the required pattern:
 * "{original-name}-TEMP{uniqueId}.{ext}"
 *
 * @param string $original_name original client filename or desired base name
 * @param string $extension     without dot (e.g. "mp4", "webp")
 * @return string
 */
function build_temp_filename(string $original_name, string $extension, bool $force_webp = false): string
{
    $extension = strtolower(ltrim(trim($extension), '.'));
    if ($extension === '') $extension = 'bin';

    if ($force_webp) $extension = 'webp';

    $base = pathinfo(basename($original_name), PATHINFO_FILENAME);
    $base = trim((string)$base);
    if ($base === '') $base = 'file';

    // Always sanitize base name
    $base = sanitize_string($base);

    // Unique token (short, URL-safe)
    $token = bin2hex(random_bytes(6)); // 12 chars

    return "{$base}-(tempfile-{$token}).{$extension}";
}


function build_final_filename(string $currentFilename, string $final_name = ''): string
{
    $currentExt = strtolower(pathinfo($currentFilename, PATHINFO_EXTENSION)) ?: 'bin';

    /**
     * 1) If final_name is provided, it ALWAYS wins
     */
    if ($final_name !== '') {
        $base = pathinfo(basename($final_name), PATHINFO_FILENAME) ?: 'file';
        $base = sanitize_string($base);

        $forcedExt = strtolower(pathinfo(basename($final_name), PATHINFO_EXTENSION));
        $useExt = $forcedExt !== '' ? $forcedExt : $currentExt;

        return "{$base}.{$useExt}";
    }

    /**
     * 2) No final_name → decode tempfile suffix automatically
     *    pattern: name-(tempfile-abcdef123456).ext
     */
    $base = pathinfo($currentFilename, PATHINFO_FILENAME) ?: 'file';

    // remove -(tempfile-...)
    $base = preg_replace('/-\(tempfile-[a-f0-9]+\)$/i', '', $base);

    $base = sanitize_string($base);

    return "{$base}.{$currentExt}";
}


function is_temp_filename(string $filename): bool
{
    $name = pathinfo($filename, PATHINFO_FILENAME);
    return (bool) preg_match('/-\(tempfile-[a-f0-9]+\)$/i', $name);
}


/**
 * Move a temp file to its final destination.
 * - If $item is an S3 key, performs S3 copy (temp -> final) and deletes temp.
 * - If $item is a local filename, performs local rename.
 *
 * @param string $item              Filename (local) OR S3 key
 * @param array  $context           Context data
 * @return bool
 */
function move_temp_file_to_final(string $item, array $context = []): bool
{
    if ($item === '') return false;

    if (!is_temp_filename($item)) return false;

    // print_r($item);
    // print_r($context);

    $storage    = $context['storage']   ?? 'local'; // local | s3
    $temp_dir   = rtrim($context['temp_dir'] ?? '', '/');
    $dest_base  = rtrim($context['dest_base'] ?? '', '/');
    $final_id   = $context['final_id']  ?? null;
    $type       = $context['type']      ?? 'archives';
    $src        = trim((string)($context['Src'] ?? ''), '/');
    $related    = $context['related_to'] ?? 'table';
    $final_name = (string)($context['final_name'] ?? '');

    $get_dest_filename = function(string $currentFilename) use ($final_name): string
    {
        $currentExt  = strtolower(pathinfo($currentFilename, PATHINFO_EXTENSION)) ?: 'bin';
        $currentBase = pathinfo($currentFilename, PATHINFO_FILENAME) ?: 'file';

        // If final_name is provided, force it (sanitized)
        if ($final_name !== '') {
            $base = pathinfo(basename($final_name), PATHINFO_FILENAME) ?: 'file';
            $base = sanitize_string($base);

            $forcedExt = strtolower(pathinfo(basename($final_name), PATHINFO_EXTENSION));
            $useExt = $forcedExt !== '' ? $forcedExt : $currentExt;

            return "{$base}.{$useExt}";
        }

        $base = preg_replace('/-\(tempfile-[a-f0-9]+\)$/i', '', $currentBase);

        // Otherwise, keep the current filename, but sanitize its base
        $base = sanitize_string($base);
        return "{$base}.{$currentExt}";
    };

    /**
     * S3 FLOW
     */
    $looks_like_s3_key = (strpos($item, '/') !== false);
    if ($storage === 's3' || $looks_like_s3_key)
    {
        feature('aws-s3');

        $prefix = s3_site_prefix();

        $tempKey = $looks_like_s3_key
            ? ltrim($item, '/')
            : ($prefix . '/temp/' . basename($item));

        $tempFilename = basename($tempKey);
        $destFilename = $get_dest_filename($tempFilename);

        $finalKey = s3_build_media_key([
            'subdir'     => $dest_base,
            'filename'   => $destFilename,
        ]);

        if (s3_copy_object($tempKey, $finalKey)) {
            s3_media_delete(['key' => $tempKey]);
            return true;
        }

        return false;
    }

    /**
     * LOCAL FLOW
     */
    $originFilename = basename($item);
    $origin = ($temp_dir !== '' ? ($temp_dir . '/') : '') . $originFilename;

    if (!file_exists($origin)) return false;

    $dest_base = __BASE_DIR__ .'uploads/'. $dest_base;
    $destFilename = $get_dest_filename($originFilename);
    $target = ($dest_base !== '' ? ($dest_base . '/') : '') . $destFilename;

    if (!is_dir(dirname($target))) {
        @mkdir(dirname($target), 0755, true);
    }

    return @rename($origin, $target);
}
