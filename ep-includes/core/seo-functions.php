<?php
if(!isset($seg)) exit;


// if (function_exists('structured_data_version')) return;

/**
 * Get the version information for the structured data functionality.
 *
 * @return string The version information in the format '(ALPHA) v1.0.0'.
 */
function structured_data_version()
{
    return '(ALPHA) v1.0.0';
}

function generate_sitemap()
{
    global $info, $config;

    $pages = get_results("SELECT title, slug, seo, page_type, page_area, status_id, created_at, updated_at FROM tb_pages WHERE status_id = 1");

    if (empty($pages)) return false;

    $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
      xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";


    $pages_areas = array_unique(array_column($pages, 'page_area'));
    foreach ($pages_areas as $page_area) {
        $areas_info[$page_area] = load_area_info($page_area);
    }

    foreach ($pages as $page)
    {
        $seo = $page['seo'];

        $area_info = $areas_info[$page['page_area']];
        if (!empty($area_info['seo']['never_map']) AND $area_info['seo']['never_map']) {
            continue;
        }

        if (!isset($seo['robots']) || strpos($seo['robots'], 'index') === false) {
            continue;
        }

        $url = get_url_page($page['slug'], 'full');

        $priority_map = [
            'essential'    => '1.0',
            'not_essential'=> '0.8',
            'landingpage'  => '1.0',
        ];
        $priority = $priority_map[$page['page_type']] ?? '0.8';

        $changefreq = $seo['changefreq'] ?? 'weekly';
        $created_at = date('Y-m-d\TH:i:sP', strtotime($page['created_at']));
        $lastmod = date('Y-m-d\TH:i:sP', strtotime($page['updated_at']));

        if ($page['page_type'] === 'article')
        {
            $sitemap .= "  <url>\n";
            $sitemap .= "    <loc>{$url}</loc>\n";
            $sitemap .= "    <news:news>\n";
            $sitemap .= "      <news:publication>\n";
            $sitemap .= "        <news:name>" . htmlspecialchars($page['title']) . "</news:name>\n";
            $sitemap .= "        <news:language>pt</news:language>\n";
            $sitemap .= "      </news:publication>\n";
            $sitemap .= "      <news:publication_date>{$created_at}</news:publication_date>\n";
            $sitemap .= "      <news:title>" . htmlspecialchars($page['title']) . "</news:title>\n";
            $sitemap .= "    </news:news>\n";
            $sitemap .= "  </url>\n";
        }

        else {
            $sitemap .= "  <url>\n";
            $sitemap .= "    <loc>{$url}</loc>\n";
            $sitemap .= "    <lastmod>{$lastmod}</lastmod>\n";
            $sitemap .= "    <changefreq>{$changefreq}</changefreq>\n";
            $sitemap .= "    <priority>{$priority}</priority>\n";
            $sitemap .= "  </url>\n";
        }
    }

    $sitemap .= '</urlset>';

    file_put_contents(__BASE_DIR__ . 'sitemap.xml', $sitemap);
}


function available_structured_data()
{
    global $available_structured_data;
    return $available_structured_data;
}


function common_attributes_structured_data(string $key, $data = null)
{
    // Review
    if ($key == 'review')
    {
        foreach ($data as $review)
        {
            if (!empty($review['author']) && !empty($review['reviewBody'])) continue;

            $reviews[] = [
                "@type" => "Review",
                "author" => [
                    "@type" => "Person",
                    "name" => $review['author'],
                ],
                "reviewRating" => [
                    "@type" => "Rating",
                    "ratingValue" => $review['ratingValue'] ?? '',
                    "bestRating" => $review['bestRating'] ?? '5',
                ],
                "reviewBody" => $review['reviewBody'],
            ];
        }

        return $reviews;
    }

    // Contact Point
    if ($key === 'ContactPoint')
    {
        $contacts = [];

        foreach ((array)$data as $item)
        {
            $cp = ['@type' => 'ContactPoint'];

            if (!empty($item['contact_type'])) {
                $cp['contactType'] = $item['contact_type'];
            }
            if (!empty($item['phone'])) {
                $cp['telephone'] = $item['phone'];
            }
            if (!empty($item['email'])) {
                $cp['email'] = $item['email'];
            }
            if (!empty($item['country'])) {
                $cp['areaServed'] = $item['country'];
            }
            if (!empty($item['language']))
            {
                $lang = $item['language'];
                $cp['availableLanguage'] = is_array($lang)
                    ? $lang
                    : array_map('trim', explode(',', (string)$lang));
            }

            if (count($cp) > 1) {
                $contacts[] = $cp;
            }
        }

        return $contacts;
    }

    // Parent Organization
    if ($key == 'parentOrganization')
    {
        return [
            '@type' => "Organization",
            'name'  => $data,
        ];
    }

    // Departaments
    if ($key == 'department')
    {
        foreach ($data as $item)
        {
            if (empty($item)) continue;
            $department[] = [
                '@type' => "Organization",
                'name'  => $item,
            ];
        }

        return $department;
    }

    // Divisions
    if ($key == 'subOrganization')
    {
        foreach ($data as $item)
        {
            if (empty($item)) continue;
            $division[] = [
                '@type' => "Organization",
                'name'  => $item,
            ];
        }

        return $division;
    }

    // Opening Hours
    if ($key === 'openingHoursSpecification')
    {
        $opening_hours = [];

        foreach ((array)$data as $day => $details)
        {
            // Skip invalid day keys
            if (empty($day)) continue;

            // Normalize: accept either one object {opens, closes} or an array of such objects
            $ranges = (is_array($details) && array_key_exists('opens', $details))
                ? [ $details ]                     // single range
                : (is_array($details) ? $details : []); // multiple ranges or invalid

            foreach ($ranges as $range)
            {
                $opens  = $range['opens']  ?? null;
                $closes = $range['closes'] ?? null;
                if (!$opens || !$closes) continue;

                $opening_hours[] = [
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day,            // e.g. "Monday", "Tuesday", ...
                    'opens'     => $opens,          // "HH:MM" in local time
                    'closes'    => $closes,         // "HH:MM" in local time
                ];
            }
        }

        return $opening_hours;
    }

    // SameAS
    if ($key == 'sameAs')
    {
        foreach ($data as $item)
        {
            if (empty($item['url'])) continue;
            $sameAs[] = $item['url'];
        }

        return $sameAs;
    }

    // Address
    if ($key == 'address')
    {
        return [
            "@type" => "PostalAddress",
            "streetAddress" => $data['street'] ?? '',
            "addressLocality" => $data['city'] ?? '',
            "addressRegion" => $data['state'] ?? '',
            "postalCode" => $data['zipcode'] ?? '',
            "addressCountry" => $data['country'] ?? '',
        ];
    }

    if ($key === 'founding')
    {
        $foundationData = [];
        $founders       = [];
        $founding       = (array) $data;

        // Build founders as Person (name required; url/jobTitle optional)
        if (!empty($founding['founder']) && is_array($founding['founder']))
        {
            foreach ($founding['founder'] as $item)
            {
                $item = (array) $item;
                if (empty($item['name'])) continue;

                $person = [
                    '@type' => 'Person',
                    'name'  => $item['name'],
                ];

                // Optional official page for the person
                if (!empty($item['url'])) {
                    $person['url'] = $item['url'];
                }

                // Optional role/title; accept both jobTitle and jobtitle
                if (!empty($item['jobTitle']) || !empty($item['job_title'])) {
                    $person['jobTitle'] = $item['jobTitle'] ?? $item['job_title'];
                }

                $founders[] = $person;
            }
        }

        if (!empty($founders)) {
            $foundationData['founder'] = $founders;
        }

        // Founding date (ISO 8601 recommended: YYYY-MM-DD)
        if (!empty($founding['date'])) {
            $foundationData['foundingDate'] = $founding['date'];
        }

        // Founding location (as Place)
        if (!empty($founding['location']))
        {
            $foundationData['foundingLocation'] = [
                '@type' => 'Place',
                'name'  => $founding['location'],
            ];
        }

        return $foundationData;
    }

    // Employees
    if ($key == 'employee')
    {
        $employees = 0;
        foreach ($data as $item)
        {
            if (empty($item['name'])) continue;

            $employeesData[] = [
                '@type' => "Person",
                'name'  => $item['name'],
            ];

            $employees++;
        }

        if (!empty($employeesData))
        {
            $employeesStructure['employee'] = $employeesData;
            $employeesStructure['numberOfEmployees'] = $employees;
        }

        return $employeesStructure;
    }

    // Accessibility
    if ($key == 'accessibility')
    {
        $accessibility = $data['accessibility'];
        return [
            "accessibilityControl" => $accessibility['accessibilityControl'] ?? '',
            "accessibilityFeature" => $accessibility['accessibilityFeature'] ?? '',
            "accessibilityHazard" => $accessibility['accessibilityHazard'] ?? '',
        ];
    }

    // Provider
    if ($key == 'provider')
    {
        $provider = $data['provider'];
        return [
            "@type" => "Organization",
            "name" => $provider['name'],
            "sameAs" => $provider['sameAs'],
        ];
    }

    // Encoding
    if ($key == 'encoding')
    {
        $encoding = $data['encoding'];
        return [
            "@type" => "MediaObject",
            "contentUrl" => $encoding['contentUrl'],
            "encodingFormat" => $encoding['encodingFormat'],
        ];
    }

    // Geo Coordinates
    if ($key == 'geo')
    {
        return [
            "@type" => "GeoCoordinates",
            "latitude" => $data['latitude'],
            "longitude" => $data['longitude'],
        ];
    }

    return "This KEY does not exist.";
}


/**
 * Generates structured data for an organization in JSON-LD format.
 *
 * @param array $data An associative array containing the organization's data.
 * @return array The structured data for the organization.
 */
function organization_structured_data($data)
{
    $structure = [
        "@type" => "Organization",
        "name" => $data['name'] ?? '',
        "alternateName" => $data['short_name'] ?? '',
        "url" => pg,
        "logo" => $data['favicon'],
    ];

    // Contact Point
    if (!empty($data['contact']))
    $structure['ContactPoint'] = common_attributes_structured_data('ContactPoint', $data['contact']);

    // Put Address
    if (!empty($data['address']['street']))
    $structure['address'] = common_attributes_structured_data('address', $data['address']);


    // Put Social Media
    if (!empty($data['social_media']))
    $structure['sameAs'] = common_attributes_structured_data('sameAs', $data['social_media']);


    // About Foundation
    if (!empty($data['founding']))
    {
        $founding = common_attributes_structured_data('founding', $data['founding']);
        $structure += $founding;
    }

    // Organization Chart
    if (!empty($data['organization_chart']))
    {
        $chart = $data['organization_chart'];

        // Parent Organization
        if (!empty($chart['parent']))
        $structure['parentOrganization'] = common_attributes_structured_data('parentOrganization', $chart['parent']);

        // Departaments
        if (!empty($chart['department']))
        $structure['department'] = common_attributes_structured_data('department', $chart['department']);

        // Divisions
        if (!empty($chart['division']))
        $structure['subOrganization'] = common_attributes_structured_data('subOrganization', $chart['division']);

    }

    // Employees
    if (!empty($data['employee']))
    {
        $employee = common_attributes_structured_data('employee', $data['employee']);
        $structure += $employee;
    }

    // Optional parameters
    if (!empty($data['taxID']))
    $structure['taxID'] = $data['taxID'];

    if (!empty($data['duns']))
    $structure['duns'] = $data['duns'];

    if (!empty($data['globalLocationNumber']))
    $structure['globalLocationNumber'] = $data['globalLocationNumber'];

    if (!empty($data['isicV4']))
    $structure['isicV4'] = $data['isicV4'];

    if (!empty($data['naics']))
    $structure['naics'] = $data['naics'];

    if (!empty($data['vatID']))
    $structure['vatID'] = $data['vatID'];

    return $structure;
}


/**
 * Generates structured data for an article in JSON-LD format.
 *
 * @param array $data An associative array containing the article's data.
 * @return array The structured data for the article.
 */
function article_structured_data($data)
{
    $structure = [
        "@type" => "Article",
        "headline" => $data['title'] ?? '',
        "description" => $data['description'] ?? '',
        "articleSection" => $data['categoria'] ?? '',
        "keywords" => $data['keywords'] ?? '',
        "copyrightHolder" => $data['name'] ?? '',
        "datePublished" => date(DATE_ISO8601, strtotime($data['created_at'] ?? '')),
        "isFamilyFriendly" => $data['isFamilyFriendly'] ?? true,
        "isAccessibleForFree" => $data['isAccessibleForFree'] ?? true,
        "educationalUse" => $data['educationalUse'] ?? false,
        "mainEntityOfPage" => [
            "@type" => "WebPage",
            "@id" => canonical
        ],
        "url" => canonical,
    ];

    // Author
    $author = $data['author'];
    $structure['author'] = [
       "@type" => "Person",
       "name" => $author['name'] ?? $data['name'],
       "url" => $author['url'] ?? pg,
    ];

    // Editor
    if (!empty($data['updated_at']))
    $structure['editor'] = $structure['author'];


    // Publisher
    if (!empty($data['publisher']))
    {
        $publisher = $data['publisher'];
        $structure['publisher'] = [
            "@type" => "Organization",
            "name" => $publisher['name'] ?? '',
            "logo" => $publisher['image'] ?? $data['favicon'],
        ];
    }

    // Thumbnail
    $thumbnail = !empty($image)
        ? pg."/uploads/images/thumbnails/{$data['featured_image']}"
        : $data['favicon'];

    $structure += [
        "image" => $thumbnail,
        "thumbnailUrl" => $thumbnail,
    ];


    // Optional parameters
    if (!empty($data['word_count']))
    $structure['wordCount'] = $data['word_count'];

    if (!empty($data['alternative_title']))
    $structure['alternativeHeadline'] = $data['alternative_title'];

    if (!empty($data['commentCount']))
    $structure['commentCount'] = $data['commentCount'];

    if (!empty($data['pagination']))
    $structure['pagination'] = $data['pagination'];

    if (!empty($data['expires']))
    $structure['expires'] = $data['expires'];

    if (!empty($data['updated_at']))
    $structure['dateupdated_at'] = date(DATE_ISO8601, strtotime($data['updated_at'] ?? ''));

    if (!empty($data['article_body']))
    $structure['articleBody'] = $data['article_body'];

    if (!empty($data['text']))
    $structure['text'] = $data['text'];

    if (!empty($data['audio']))
    $structure['audio'] = $data['audio'];

    if (!empty($data['video']))
    $structure['video'] = $data['video'];

    // mentions
    if (!empty($data['mentions']))
    {
        $structure['mentions'] = $data['mentions'] ?? [
            "@type" => "Thing",
            "name" => "",
            "url" => ""
        ];
    }

    // hasPart
    if (!empty($data['hasPart']))
    {
        $structure['hasPart'] = $data['hasPart'] ?? [
            "@type" => "CreativeWork",
            "name" => "",
            "url" => ""
        ];
    }

    // isBasedOn
    if (!empty($data['isBasedOn']))
    {
        $structure['isBasedOn'] = $data['isBasedOn'] ?? [
            "@type" => "CreativeWork",
            "name" => "",
            "url" => ""
        ];
    }

    // isPartOf
    if (!empty($data['isPartOf']))
    {
        $structure['isPartOf'] = $data['isPartOf'] ?? [
            "@type" => "CreativeWork",
            "name" => $data['parte_de']['name'] ?? '',
            "description" => $data['parte_de']['description'] ?? '',
            "url" => $data['parte_de']['url'] ?? ''
        ];
    }

    // Provider
    if (!empty($data['about']['name']))
    {
        $about = $data['about'];
        $structure['about'] = [
            "@type" => "Thing",
            "name" => $about['name'],
            "url" => $about['url'],
        ];
    }

    // Provider
    if (!empty($data['provider']['name']))
    $structure['provider'] = common_attributes_structured_data('provider', $data['provider']);

    // Encoding
    if (!empty($data['encoding']['contentUrl']))
    $structure['encoding'] = common_attributes_structured_data('encoding', $data['encoding']);

    // Accssibility
    if (!empty($data['accessibility']))
    $structure += common_attributes_structured_data('encoding', $data['encoding']);;

    return $structure;
}


/**
 * Generates structured data for a local business in JSON-LD format.
 *
 * @param array $data An associative array containing the local business data.
 * @return array The structured data for the local business.
 */
function local_business_structured_data($data)
{
    $structure = [
        "@type" => "LocalBusiness",
        "name" => $data['name'] ?? '',
        "alternateName" => $data['short_name'] ?? '',
        "url" => pg,
        "logo" => $data['favicon'],
        "telephone" => $data['telephone'] ?? '',
        "image" => $data['image'] ?? $data['favicon'],
    ];

    // Contact Point
    if (!empty($data['contact']))
    $structure['ContactPoint'] = common_attributes_structured_data('ContactPoint', $data['contact']);

    // Put Address
    if (!empty($data['address']['street']))
    $structure['address'] = common_attributes_structured_data('address', $data['address']);

    // Geo Coordinates
    if (!empty($data['geo']))
    $structure['geo'] = common_attributes_structured_data('geo', $data['geo']);

    // Put Social Media
    if (!empty($data['social_media']))
    $structure['sameAs'] = common_attributes_structured_data('sameAs', $data['social_media']);

    // About Foundation
    if (!empty($data['founding']))
    {
        $founding = common_attributes_structured_data('founding', $data['founding']);
        $structure += $founding;
    }

    // Employees
    if (!empty($data['employee']))
    {
        $employee = common_attributes_structured_data('employee', $data['employee']);
        $structure += $employee;
    }

    // Organization Chart
    if (!empty($data['organization_chart']))
    {
        $chart = $data['organization_chart'];

        // Parent Organization
        if (!empty($chart['parent']))
        $structure['parentOrganization'] = common_attributes_structured_data('parentOrganization', $chart['parent']);


        // Departaments
        if (!empty($chart['department']))
        $structure['department'] = common_attributes_structured_data('department', $chart['department']);


        // Divisions
        if (!empty($chart['division']))
        $structure['subOrganization'] = common_attributes_structured_data('subOrganization', $chart['division']);


        // opening Hours
        if (!empty($chart['opening_hours']))
        $structure['openingHoursSpecification'] = common_attributes_structured_data('openingHoursSpecification', $chart['opening_hours']);
    }

    // Review (if any)
    if (!empty($data['review']))
    $structure['review'] = common_attributes_structured_data('review', $data['review']);

    // Price Range
    if (!empty($data['price_range']))
    $structure['priceRange'] = $data['price_range'];

    return $structure;
}


function video_structured_data(array $data)
{
    $video = [
        "@type" => "VideoObject",
        "name" => $data['name'] ?? '',
        "description" => $data['description'] ?? '',
        "thumbnailUrl" => $data['thumbnailUrl'] ?? '',
        "uploadDate" => $data['uploadDate'] ?? '',
    ];

    if (!empty($data['contentUrl']))
    $video['contentUrl'] = $data['contentUrl'];

    if (!empty($data['embedUrl']))
    $video['embedUrl'] = $data['embedUrl'];

    if (!empty($data['duration']))
    $video['duration'] = $data['duration'];

    if (!empty($data['publisher']))
    {
        $publisher = $data['publisher'];
        $structure['publisher'] = [
            "@type" => "Organization",
            "name" => $publisher['name'] ?? '',
            "logo" => $publisher['image'] ?? $data['favicon'],
        ];
    }

    if (!empty($data['transcript']))
    $video['transcript'] = $data['transcript'];

    if (!empty($data['views_count']) OR !empty($data['analytics']))
    {
        $video['views_count'] = [
            "@type" => "InteractionCounter",
            "interactionType" => [
                "@type" => "http://schema.org/WatchAction"
            ],
            "userInteractionCount" => $data['views_count'],
        ];
        // "interactionStatistic": [
        //   {
        //     "@type": "InteractionCounter",
        //     "interactionType": "http://schema.org/WatchAction",
        //     "userInteractionCount": "21 918"
        //   },
        //   {
        //     "@type": "InteractionCounter",
        //     "interactionType": "http://schema.org/LikeAction",
        //     "userInteractionCount": "102"
        //    }
          // ]
    }

    if (!empty($data['regionsAllowed']))
    $video['regionsAllowed'] = $data['regionsAllowed'];

    if (!empty($data['expires']))
    $video['expires'] = $data['expires'];

    return $video;
}


/**
 * Generates structured data in JSON-LD format for SEO purposes.
 *
 * @param array $data An associative array containing the data for generating structured data.
 * @param string $type The type of structured data to generate (default: 'organization').
 * @return string The JSON-LD structured data script tag.
 */
function seo_structred_data($data, bool $mix = true, string $type = 'organization', bool $debug = false)
{
    global $config;
    global $info;
    global $page;

    if ($mix)
    {
        $data = array_replace_recursive($info, $config, $data);

        $function = "{$type}_structured_data";

        if (!function_exists($function)) return var_dump('This model of structured data does not exist.');

        $data = $function($data);
    }

    $payload['@context'] = "https://schema.org";
    $payload            += $data;

    $structure = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    if ($debug) echo $structure;

    return "<script type='application/ld+json'>$structure</script>";
}


/**
 * Generates an HTML form for SEO settings.
 *
 * @param array $args Array of arguments with keys:
 *                    - 'type_form': The type of form ('insert' or 'update').
 *                    - 'seo': An array of SEO values (optional).
 * @return string The generated HTML form.
 */
function SEO_form(string $type_form = 'insert', $args = [])
{
    global $config, $available_structured_data;

    $seo        = $args['value'] ?? [];
    $access_count = $args['access_count'] ?? 0;
    $name       = $args['name'] ?? 'seo';
    $mode       = $args['mode'] ?? 'common';

    $isFamilyFriendly = ($type_form=='update')
        ? ($seo['isFamilyFriendly'] ?? '')
        : ($config['seo']['isFamilyFriendly'] ?? '');

    $isAccessibleForFree = ($type_form=='update')
        ? ($seo['isAccessibleForFree'] ?? '')
        : ($config['seo']['isAccessibleForFree'] ?? '');

    $educationalUse = ($type_form=='update')
        ? ($seo['educationalUse'] ?? '')
        : ($config['seo']['educationalUse'] ?? '');

    // $IsAdultContent = ($type_form=='update')
    //     ? ($seo['IsAdultContent'] ?? '')
    //     : ($config['seo']['IsAdultContent'] ?? '');


    $basic = input(
        'basic',
        $type_form,
        [
            'size' => 'col-md-12',
            'label' => 'Palavras chaves',
            'name' => $name .'[keywords]',
            'Value' => ($type_form=='update')
                        ? ($seo['keywords'] ?? '')
                        : ($config['seo']['keywords'] ?? ''),
        ]
    ) . input(
        'upload',
        $type_form,
        [
            'type' => 'images',
            'size' => 'col-12',
            'label' => 'Imagem destaque',
            'attributes' => 'accept:(image/*);',
            'name' => $name ."[image]",
            'Src' => 'seo',
            'Alert' => 'A imagem será redimensionada para 1200x675, e, caso esse campo não seja preenchdido, será automaticamente definida pela imagem padrão de SEO',
            'Value' => ($type_form=='update')
                ? ($seo['image'] ?? '')
                : ($config['seo']['image'] ?? ''),
        ]
    ) . input(
        'basic',
        $type_form,
        [
            'size' => 'col-md',
            'label' => 'Indexagem',
            'name' => $name .'[robots]',
            'Value' => ($type_form=='update')
                        ? ($seo['robots'] ?? '')
                        : ($config['seo']['robots'] ?? ''),
        ]
    );
    $basic.= input(
        'selection_type',
        $type_form,
        [
            'size' => 'col-md-6',
            'label' => 'Tipo de dado estruturado',
            'name' => $name .'[type]',
            'Options' => $available_structured_data,
            'Value' => ($type_form=='update')
                        ? ($seo['type'] ?? '')
                        : ($config['seo']['type'] ?? ''),
        ]
    );
    $basic.= input(
        'basic',
        $type_form,
        [
            'size' => 'col-12',
            'label' => 'Descrição',
            'name' => $name .'[description]',
            'Value' => ($type_form=='update')
                        ? ($seo['description'] ?? '')
                        : ($config['seo']['description'] ?? ''),
        ]
    );


    $advanced = input(
        'basic',
        $type_form,
        [
            'size' => 'col-md',
            'label' => 'Autor',
            'name' => $name .'[author]',
            'Value' => ($type_form=='update')
                        ? ($seo['author'] ?? '')
                        : ($config['seo']['author'] ?? ''),
        ]
    );
    if ($mode == 'content')
    {
        $advanced.= input(
            'basic',
            $type_form,
            [
                'size' => 'col-md-6',
                'label' => 'Quantidade de acessos',
                'type' => 'number',
                'name' => 'access_count',
                'Value' => ($type_form=='update')
                            ? ($access_count ?? 0)
                            : 0,
            ]
        );
        $advanced.= input(
            'basic',
            $type_form,
            [
                'size' => 'col-12',
                'label' => 'URL canônica',
                'name' => $name .'[canonical]',
                'Value' => ($type_form=='update')
                            ? ($seo['canonical'] ?? '')
                            : ($config['seo']['canonical'] ?? ''),
                'Alert' => 'Se vazio, a URL do site será atribuída.',
            ]
        );
    }
    $advanced.= input(
        'selection_type',
        $type_form,
        [
            'size' => 'col-12',
            'label' => 'Atributos',
            'variation' => 'inline',
            'type' => 'switch',
            'name' => $name .'[author]',
            'Options' => [
                ['name' => $name .'[isFamilyFriendly]', 'value' => 1, 'display' => 'Amigável a família', 'checked' => $isFamilyFriendly ],
                ['name' => $name .'[isAccessibleForFree]', 'value' => 1, 'display' => 'Acesso gratuito', 'checked' => $isAccessibleForFree ],
                ['name' => $name .'[educationalUse]', 'value' => 1, 'display' => 'Uso educacional', 'checked' => $educationalUse ],
                // ['name' => $name .'[IsAdultContent]', 'value' => 1, 'display' => 'Conteúdo +18', 'checked' => $IsAdultContent ],
            ],
        ]
    );


    $tabs = [
        [
            'id'     => 'seo-basic',
            'title'  => 'SEO',
            'active' => true,
            'body'   => "<div class='form-row'>{$basic}</div>",
        ],
        [
            'id'    => 'seo-advanced',
            'title' => 'Avançado',
            'body'  => "<div class='form-row'>{$advanced}</div>",
        ],
    ];

    $nav = block('navtabs', [
        'id'       => 'nav-seo',
        'variation'=> 'navtabs_folder', // mantém o markup <nav> + data-bs-toggle
        'class'    => 'seo-form',
        'contents' => $tabs,
    ]);

    return $nav;
}


function generate_SEO_meta()
{
    global $config, $info, $page;

    $thumbnail = !empty($page['seo']['image'])
        ? site_url("/uploads/images/seo/{$page['seo']['image']}")
        : null;

    $config_thumbnail = !empty($config['seo']['image'])
        ? site_url('/uploads/images/seo/'.$config['seo']['image'])
        : null;

    $is_adult_content = (!empty($page['seo']['isFamilyFriendly']) AND $page['seo']['isFamilyFriendly'])
        ? true
        : false;

    $config_is_adult_content = (!empty($config['seo']['isFamilyFriendly']) AND $config['seo']['isFamilyFriendly'])
        ? true
        : false;

    $page_type = $page['page_type'] ?? 'website';

    echo "
    <!-- SEO -->
    <meta name='robots' content='". ($page['seo']['robots'] ?? ($config['seo']['robots'] ?? '')) ."' />
    <meta name='keywords' content='". ($page['seo']['keywords'] ?? ($config['seo']['keywords'] ?? '')) ."'>
    <meta name='description' content='". ($page['seo']['description'] ?? ($config['seo']['description'] ?? '')) ."'>
    <meta name='theme-color' media='(prefers-color-scheme: light)' content='". ($info['brand_colors']['primary'] ?? '') ."'>
    <meta name='thumbnail' content='". ($thumbnail ?? ($config_thumbnail ?? $info['favicon'])) ."'>
    <meta name='author' content='". ($page['seo']['author'] ?? ($config['seo']['author'] ?? '')) ."'>";

    if (!$is_adult_content || !$config_is_adult_content)
    echo "
    <meta name='rating' content='RTA-5042-1996-1400-1577-RTA' />
    <meta name='rating' content='adult'/>";

    echo "\n
    <!-- Open Graph -->
    <meta property='og:title' content='". ($page['title'] ?? $info['title']) ."'>
    <meta property='og:description' content='". ($page['description'] ?? '') ."'>
    <meta property='og:site_name' content='". ($info['name']) ."'>
    <meta property='og:url' content='". ($page['seo']['canonical'] ?? canonical) ."'>
    <meta property='og:type' content='". (($page_type == 'article') ? 'article' : 'website') ."'>
    <meta property='og:locale' content='pt_BR'>
    <meta property='og:image' content='". ($thumbnail ?? ($config_thumbnail ?? $info['favicon'])) ."'>

    <!-- Twitter -->
    <meta name='twitter:title' content='". ($page['title'] ?? $info['title']) ."'>
    <meta name='twitter:description' content='". ($page['description'] ?? '') ."'>
    <meta name='twitter:site' content='". ( $page['seo']['canonical'] ?? canonical) ."'>
    <meta name='twitter:image' content='". ($thumbnail ?? ($config_thumbnail ?? $info['favicon'])) ."'>
    <meta name='twitter:creator' content='". ($page['seo']['author'] ?? ($config['seo']['author'] ?? '')) ."'>\n";
}
