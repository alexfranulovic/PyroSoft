<?php
if(!isset($seg)) exit;

set_time_limit(0);
ini_set('memory_limit', '512M');


/**
 * Base directory for all sitemap files.
 */
define('SITEMAP_BASE_DIR', __BASE_DIR__ . 'sitemaps/');

/**
 * Base URL that maps to SITEMAP_BASE_DIR.
 * Adjust this to your real public URL.
 *
 * Example:
 *   SITEMAP_BASE_DIR = /var/www/site/public/sitemaps/
 *   SITEMAP_BASE_URL = https://site.com/sitemaps/
 */
define('SITEMAP_BASE_URL', 'https://example.com/sitemaps/');

/**
 * Maximum number of <url> entries per shard file.
 */
define('SITEMAP_MAX_URLS_PER_FILE', 30);




/**
 * Resolve directory path for a given category and optional subcategory.
 * subcategory is only a folder context (ex.: "shared").
 *
 * @param array{category:string, subcategory?:string} $params
 * @return string
 */
function sitemap_resolve_dir(array $params): string
{
    $category    = trim($params['category'] ?? '', "/\\");
    $subcategory = isset($params['subcategory']) ? trim((string)$params['subcategory'], "/\\") : null;

    $dir = rtrim(SITEMAP_BASE_DIR, "/\\") . DIRECTORY_SEPARATOR . $category;

    if (!empty($subcategory)) {
        $dir .= DIRECTORY_SEPARATOR . $subcategory;
    }

    return $dir;
}

/**
 * Ensure directory exists.
 *
 * @param string $dir
 * @return void
 */
function sitemap_ensure_dir(string $dir): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

/**
 * Build filename for a shard file:
 *   sitemap-{category}-{index}.xml
 *
 * @param array{category:string, index:int} $params
 * @return string
 */
function sitemap_resolve_shard_filename(array $params): string
{
    $category = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($params['category']));
    $index    = (int)($params['index'] ?? 0);

    return "sitemap-{$category}-{$index}.xml";
}

/**
 * Build filename for a category index file:
 *   sitemap-{category}.xml
 *
 * @param array{category:string} $params
 * @return string
 */
function sitemap_resolve_category_index_filename(array $params): string
{
    $category = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($params['category']));
    return "sitemap-{$category}.xml";
}

/**
 * Convert a local filesystem path (inside SITEMAP_BASE_DIR)
 * into a public URL using SITEMAP_BASE_URL.
 *
 * @param string $filePath
 * @return string
 */
function sitemap_path_to_url(string $filePath): string
{
    $baseDir = rtrim(SITEMAP_BASE_DIR, "/\\") . DIRECTORY_SEPARATOR;
    $rel = str_replace($baseDir, '', $filePath);
    $rel = str_replace(DIRECTORY_SEPARATOR, '/', $rel);

    return rtrim(site_url('/sitemaps/'), '/') . '/' . ltrim($rel, '/');
}

/**
 * Get info about the last shard file for a category/subcategory.
 * Shards pattern: sitemap-{category}-{N}.xml
 *
 * @param array{category:string, subcategory?:string} $params
 * @return array|null [ path, index, url_count ]
 */
function sitemap_get_last_shard_info(array $params): ?array
{
    $dir = sitemap_resolve_dir($params);

    if (!is_dir($dir)) {
        return null;
    }

    $category = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($params['category']));
    $pattern  = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . "sitemap-{$category}-*.xml";

    $files = glob($pattern);
    if (empty($files)) {
        return null;
    }

    natsort($files);
    $lastFile = array_values($files)[count($files) - 1];

    $filename = basename($lastFile);
    $index    = 0;
    if (preg_match('/-(\d+)\.xml$/', $filename, $m)) {
        $index = (int)$m[1];
    }

    $urlCount = 0;
    if (is_readable($lastFile)) {
        $xml = @simplexml_load_file($lastFile);
        if ($xml !== false) {
            $urlCount = count($xml->url);
        }
    }

    return [
        'path'      => $lastFile,
        'index'     => $index,
        'url_count' => $urlCount,
    ];
}

/**
 * Create a new shard file (urlset) for a category/subcategory.
 *
 * @param array{category:string, subcategory?:string, index:int} $params
 * @return string Absolute file path
 */
function sitemap_create_new_shard(array $params): string
{
    $dir = sitemap_resolve_dir($params);
    sitemap_ensure_dir($dir);

    $filename = sitemap_resolve_shard_filename($params);
    $path     = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . $filename;

    // Root already prepared for default + image + video + news
    $xmlTemplate = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
    xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"
    xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
</urlset>
XML;

    file_put_contents($path, $xmlTemplate);

    return $path;
}


/**
 * Append a <url> node to an existing shard file.
 *
 * Supports content types:
 * - default
 * - image  (extra['images'])
 * - video  (extra['videos'])
 * - news   (extra['news'])
 *
 * For "video", the expected structure in $params['extra']['videos'] is:
 *
 * [
 *   [
 *     'thumbnail_loc'    => 'https://...',
 *     'title'            => '...',
 *     'description'      => '...',
 *     'publication_date' => '2025-09-25T15:51:42+00:00',
 *     'family_friendly'  => 'no', // or 'yes'
 *     'uploader'         => [
 *         'name' => 'John Doe',
 *         'info' => 'https://example.com/model/lucibel-a'
 *     ],
 *     // Optional extras:
 *     'content_loc'      => 'https://cdn.example.com/video.mp4',
 *     'player_loc'       => 'https://player.example.com/embed/xyz',
 *     'player_loc_attr'  => [ 'allow_embed' => 'yes', 'autoplay' => 'ap=1' ],
 *     'duration'         => 123, // seconds
 *     'rating'           => 4.3,
 *     'view_count'       => 12345,
 *     'categories'       => ['Cat 1', 'Cat 2'],
 *     'tags'             => ['tag1', 'tag2'],
 *   ],
 *   // ...
 * ]
 *
 * @param array{
 *   file_path:string,
 *   loc:string,
 *   extra?:array,
 *   content_type?:string
 * } $params
 * @return bool
 */
function sitemap_append_url_to_shard(array $params): bool
{
    $filePath    = $params['file_path'];
    $loc         = $params['loc'];
    $extra       = $params['extra'] ?? [];
    $contentType = $params['content_type'] ?? 'default';

    if (!is_readable($filePath)) {
        return false;
    }

    $xml = @simplexml_load_file($filePath);
    if ($xml === false) {
        return false;
    }

    // Base <url> node
    $url = $xml->addChild('url');
    $url->addChild('loc', htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8'));

    if (!empty($extra['lastmod'])) {
        $url->addChild('lastmod', $extra['lastmod']);
    }
    if (!empty($extra['changefreq'])) {
        $url->addChild('changefreq', $extra['changefreq']);
    }
    if (!empty($extra['priority'])) {
        $url->addChild('priority', $extra['priority']);
    }

    // --------------------------------------
    // Content-type specific handling
    // --------------------------------------
    switch ($contentType) {
        case 'image':
            // extra['images'] can be a string or array of strings
            $images = $extra['images'] ?? [];
            if (!is_array($images)) {
                $images = [$images];
            }

            $imageNs = 'http://www.google.com/schemas/sitemap-image/1.1';

            foreach ($images as $imgLoc) {
                $imgLoc = trim((string)$imgLoc);
                if ($imgLoc === '') {
                    continue;
                }

                $imageNode = $url->addChild('image:image', null, $imageNs);
                $imageNode->addChild(
                    'image:loc',
                    htmlspecialchars($imgLoc, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                    $imageNs
                );
            }
            break;

        case 'video':
            // extra['videos'] can be:
            // - one associative array
            // - or a list of associative arrays
            $videos = $extra['videos'] ?? [];

            if (!is_array($videos)) {
                $videos = [];
            } elseif (isset($videos['thumbnail_loc']) || isset($videos['title'])) {
                // normalize single video entry into an array of one
                $videos = [ $videos ];
            }

            $videoNs = 'http://www.google.com/schemas/sitemap-video/1.1';

            foreach ($videos as $videoData)
            {
                $thumb = $videoData['thumbnail_loc'] ?? null;
                $title = $videoData['title'] ?? null;
                $desc  = $videoData['description'] ?? null;

                if (empty($thumb) || empty($title) || empty($desc)) {
                    // Skip invalid entries
                    continue;
                }

                $videoNode = $url->addChild('video:video', null, $videoNs);

                // Required-ish fields for rich video
                $videoNode->addChild(
                    'video:thumbnail_loc',
                    htmlspecialchars($thumb, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                    $videoNs
                );
                $videoNode->addChild(
                    'video:title',
                    htmlspecialchars($title, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                    $videoNs
                );
                $videoNode->addChild(
                    'video:description',
                    htmlspecialchars($desc, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                    $videoNs
                );

                // Optional: publication date
                if (!empty($videoData['publication_date'])) {
                    $videoNode->addChild(
                        'video:publication_date',
                        $videoData['publication_date'],
                        $videoNs
                    );
                }

                // Optional: content_loc (direct file URL)
                if (!empty($videoData['content_loc'])) {
                    $videoNode->addChild(
                        'video:content_loc',
                        htmlspecialchars($videoData['content_loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                        $videoNs
                    );
                }

                // Optional: player_loc (embed URL) + attributes
                if (!empty($videoData['player_loc'])) {
                    $player = $videoNode->addChild(
                        'video:player_loc',
                        htmlspecialchars($videoData['player_loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                        $videoNs
                    );

                    if (!empty($videoData['player_loc_attr']) && is_array($videoData['player_loc_attr'])) {
                        foreach ($videoData['player_loc_attr'] as $attrName => $attrValue) {
                            $player->addAttribute($attrName, $attrValue);
                        }
                    }
                }

                // Optional: duration (in seconds)
                if (!empty($videoData['duration'])) {
                    $videoNode->addChild(
                        'video:duration',
                        (int)$videoData['duration'],
                        $videoNs
                    );
                }

                // Optional: rating
                if (isset($videoData['rating'])) {
                    $videoNode->addChild(
                        'video:rating',
                        (float)$videoData['rating'],
                        $videoNs
                    );
                }

                // Optional: view_count
                if (isset($videoData['view_count'])) {
                    $videoNode->addChild(
                        'video:view_count',
                        (int)$videoData['view_count'],
                        $videoNs
                    );
                }

                // Optional: family_friendly (yes|no)
                if (isset($videoData['family_friendly'])) {
                    $videoNode->addChild(
                        'video:family_friendly',
                        $videoData['family_friendly'],
                        $videoNs
                    );
                }

                // Optional: uploader
                if (!empty($videoData['uploader'])) {
                    $uploaderData = $videoData['uploader'];

                    // can be a simple string or array with [name, info]
                    if (is_array($uploaderData)) {
                        $uploaderName = $uploaderData['name'] ?? '';
                        $uploaderInfo = $uploaderData['info'] ?? null;
                    } else {
                        $uploaderName = (string)$uploaderData;
                        $uploaderInfo = null;
                    }

                    if ($uploaderName !== '') {
                        $uploaderNode = $videoNode->addChild(
                            'video:uploader',
                            htmlspecialchars($uploaderName, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                            $videoNs
                        );

                        if (!empty($uploaderInfo)) {
                            $uploaderNode->addAttribute('info', $uploaderInfo);
                        }
                    }
                }

                // Optional: categories
                if (!empty($videoData['categories'])) {
                    $categories = $videoData['categories'];
                    if (!is_array($categories)) {
                        $categories = [$categories];
                    }

                    foreach ($categories as $cat) {
                        $cat = trim((string)$cat);
                        if ($cat === '') {
                            continue;
                        }

                        $videoNode->addChild(
                            'video:category',
                            htmlspecialchars($cat, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                            $videoNs
                        );
                    }
                }

                // Optional: tags
                if (!empty($videoData['tags'])) {
                    $tags = $videoData['tags'];
                    if (!is_array($tags)) {
                        $tags = [$tags];
                    }

                    foreach ($tags as $tag) {
                        $tag = trim((string)$tag);
                        if ($tag === '') {
                            continue;
                        }

                        $videoNode->addChild(
                            'video:tag',
                            htmlspecialchars($tag, ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                            $videoNs
                        );
                    }
                }
            }
            break;

        case 'news':
            // extra['news'] basic Google News:
            // - publication_name
            // - language
            // - title
            // - publication_date
            $news = $extra['news'] ?? null;

            if (is_array($news)) {
                $newsNs  = 'http://www.google.com/schemas/sitemap-news/0.9';
                $newsNode = $url->addChild('news:news', null, $newsNs);
                $pubNode  = $newsNode->addChild('news:publication', null, $newsNs);

                if (!empty($news['publication_name'])) {
                    $pubNode->addChild(
                        'news:name',
                        htmlspecialchars($news['publication_name'], ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                        $newsNs
                    );
                }
                if (!empty($news['language'])) {
                    $pubNode->addChild(
                        'news:language',
                        htmlspecialchars($news['language'], ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                        $newsNs
                    );
                }
                if (!empty($news['title'])) {
                    $newsNode->addChild(
                        'news:title',
                        htmlspecialchars($news['title'], ENT_XML1 | ENT_COMPAT, 'UTF-8'),
                        $newsNs
                    );
                }
                if (!empty($news['publication_date'])) {
                    $newsNode->addChild(
                        'news:publication_date',
                        $news['publication_date'],
                        $newsNs
                    );
                }
            }
            break;

        default:
            // default: nothing extra
            break;
    }

    // Save with indentation
    $dom                     = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;
    $dom->loadXML($xml->asXML());

    return (bool)$dom->save($filePath);
}



/**
 * Add a URL into the correct shard (auto-creates new shard if needed),
 * then rebuilds the category index and (optionally) the global index.
 *
 * @param array{
 *   category:string,
 *   subcategory?:string,
 *   loc:string,
 *   extra?:array,
 *   build_global_index?:bool
 * } $params
 * @return bool
 */
function sitemap_add_url(array $params): bool
{
    $info = sitemap_get_last_shard_info($params);

    if ($info === null) {
        // No shard yet: start at index 0 (Fatalmodel-style)
        $params['index'] = 0;
        $filePath        = sitemap_create_new_shard($params);
    } else {
        $params['index'] = $info['index'];
        $filePath        = $info['path'];

        if ($info['url_count'] >= SITEMAP_MAX_URLS_PER_FILE) {
            $params['index']++;
            $filePath = sitemap_create_new_shard($params);
        }
    }

    $params['file_path'] = $filePath;
    $ok = sitemap_append_url_to_shard($params);

    if ($ok) {
        // After adding a URL, rebuild the category index.
        sitemap_build_category_index($params);

        // Optionally rebuild the global index.
        if (!empty($params['build_global_index'])) {
            sitemap_build_global_index([]);
        }
    }

    return $ok;
}

/**
 * Find a specific URL within all shards for a category/subcategory.
 *
 * @param array{category:string, subcategory?:string, loc:string} $params
 * @return array|null [ file, xml, url_node ]
 */
function sitemap_find_url(array $params): ?array
{
    $dir = sitemap_resolve_dir($params);
    if (!is_dir($dir)) {
        return null;
    }

    $category = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($params['category']));
    $pattern  = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . "sitemap-{$category}-*.xml";

    $files = glob($pattern);
    if (empty($files)) {
        return null;
    }

    $target = trim($params['loc']);

    foreach ($files as $file) {
        if (!is_readable($file)) {
            continue;
        }

        $xml = @simplexml_load_file($file);
        if ($xml === false) {
            continue;
        }

        foreach ($xml->url as $urlNode) {
            $nodeLoc = trim((string)$urlNode->loc);
            if ($nodeLoc === $target) {
                return [
                    'file'     => $file,
                    'xml'      => $xml,
                    'url_node' => $urlNode,
                ];
            }
        }
    }

    return null;
}

/**
 * Update a specific URL inside shards.
 *
 * @param array{
 *   category:string,
 *   subcategory?:string,
 *   loc:string,
 *   extra?:array
 * } $params
 * @return bool
 */
function sitemap_update_url(array $params): bool
{
    $found = sitemap_find_url($params);
    if ($found === null) {
        return false;
    }

    /** @var SimpleXMLElement $xml */
    $xml     = $found['xml'];
    $urlNode = $found['url_node'];
    $file    = $found['file'];
    $extra   = $params['extra'] ?? [];

    if (!empty($extra['loc'])) {
        $urlNode->loc = htmlspecialchars($extra['loc'], ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
    if (!empty($extra['lastmod'])) {
        if (isset($urlNode->lastmod)) {
            $urlNode->lastmod = $extra['lastmod'];
        } else {
            $urlNode->addChild('lastmod', $extra['lastmod']);
        }
    }
    if (!empty($extra['changefreq'])) {
        if (isset($urlNode->changefreq)) {
            $urlNode->changefreq = $extra['changefreq'];
        } else {
            $urlNode->addChild('changefreq', $extra['changefreq']);
        }
    }
    if (!empty($extra['priority'])) {
        if (isset($urlNode->priority)) {
            $urlNode->priority = $extra['priority'];
        } else {
            $urlNode->addChild('priority', $extra['priority']);
        }
    }

    $dom                     = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;
    $dom->loadXML($xml->asXML());

    $ok = (bool)$dom->save($file);

    if ($ok) {
        // URL updated: keep category index up-to-date.
        sitemap_build_category_index($params);
    }

    return $ok;
}

/**
 * Delete a specific URL inside shards.
 *
 * @param array{category:string, subcategory?:string, loc:string} $params
 * @return bool
 */
function sitemap_delete_url(array $params): bool
{
    $found = sitemap_find_url($params);
    if ($found === null) {
        return false;
    }

    /** @var SimpleXMLElement $xml */
    $xml     = $found['xml'];
    $urlNode = $found['url_node'];
    $file    = $found['file'];

    $domNode = dom_import_simplexml($urlNode);
    if (!$domNode) {
        return false;
    }

    $domNode->parentNode->removeChild($domNode);

    $dom                     = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;
    $dom->loadXML($xml->asXML());

    $ok = (bool)$dom->save($file);

    if ($ok) {
        sitemap_build_category_index($params);
    }

    return $ok;
}

/**
 * Build or rebuild category index file:
 *   sitemap-{category}.xml
 * This index lists all shards: sitemap-{category}-N.xml
 *
 * @param array{category:string, subcategory?:string} $params
 * @return bool
 */
function sitemap_build_category_index(array $params): bool
{
    $dir = sitemap_resolve_dir($params);
    if (!is_dir($dir)) {
        return false;
    }

    $category = preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($params['category']));
    $pattern  = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . "sitemap-{$category}-*.xml";

    $files = glob($pattern);
    if (empty($files)) {
        return false;
    }

    $xml = new SimpleXMLElement(
        '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>'
    );

    foreach ($files as $filePath) {
        $sitemap = $xml->addChild('sitemap');
        $loc = sitemap_path_to_url($filePath);

        $sitemap->addChild('loc', htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
        $sitemap->addChild('lastmod', date('c', filemtime($filePath)));
    }

    // Category index filename
    $filename  = sitemap_resolve_category_index_filename($params);
    $indexPath = rtrim($dir, "/\\") . DIRECTORY_SEPARATOR . $filename;

    $dom                     = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;
    $dom->loadXML($xml->asXML());

    return (bool)$dom->save($indexPath);
}


/**
 * Build global sitemap index:
 *   sitemap.xml
 *
 * It scans all subdirectories under SITEMAP_BASE_DIR and collects
 * files named "sitemap-*.xml" that are NOT shards (no "-N.xml").
 *
 * @param array $params (kept for future use / interface consistency)
 * @return bool
 */
function sitemap_build_global_index(array $params = []): bool
{
    $baseDir = rtrim(SITEMAP_BASE_DIR, "/\\") . DIRECTORY_SEPARATOR;

    // Recursive iterator to find all category index files.
    $rii = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($baseDir, FilesystemIterator::SKIP_DOTS)
    );

    $indexFiles = [];
    /** @var SplFileInfo $fileInfo */
    foreach ($rii as $fileInfo) {
        if (!$fileInfo->isFile()) {
            continue;
        }

        $name = $fileInfo->getFilename();

        // Accept only "sitemap-*.xml" that are NOT "-N.xml" (no trailing dash + number)
        if (preg_match('/^sitemap-(.+)\.xml$/', $name) && !preg_match('/-\d+\.xml$/', $name)) {
            $indexFiles[] = $fileInfo->getPathname();
        }
    }

    if (empty($indexFiles)) {
        return false;
    }

    $xml = new SimpleXMLElement(
        '<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>'
    );

    foreach ($indexFiles as $filePath) {
        $sitemap = $xml->addChild('sitemap');
        $loc     = sitemap_path_to_url($filePath);

        $sitemap->addChild('loc', htmlspecialchars($loc, ENT_XML1 | ENT_COMPAT, 'UTF-8'));
        $sitemap->addChild('lastmod', date('c', filemtime($filePath)));
    }

    $globalPath = __BASE_DIR__ . 'sitemap.xml';

    $dom                     = new DOMDocument('1.0', 'UTF-8');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput       = true;
    $dom->loadXML($xml->asXML());

    return (bool)$dom->save($globalPath);
}
