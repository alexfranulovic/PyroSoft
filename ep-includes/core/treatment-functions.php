<?php
if(!isset($seg)) exit;


function dump($value)
{
    echo "<pre>". json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ."</pre>";
    return true;
}


/**
 * Clean a URL.
 *
 * @param string $content The URL to be cleaned.
 * @return string The cleaned URL.
 */
function clean_url($content)
{
    $format_a = '"!@#$%*()+{[}];:,\\\'<>°ºª';
    $format_b = '____________________________';
    $content_ct = strtr($content, $format_a, $format_b);
    $content_br = str_ireplace(" ", "", $content_ct);
    $content_st = strip_tags($content_br);
    $content_lp = trim($content_st);

    return $content_lp;
}


/**
 * Extracts a substring from a string between specified start and end delimiters.
 *
 * @param string $string The input string from which to extract the substring.
 * @param string $start The delimiter marking the start of the substring.
 * @param string $end The delimiter marking the end of the substring.
 * @param int $pos The position of the start delimiter to use if there are multiple occurrences.
 * @return string The extracted substring, or an empty string if not found.
 */
function in_string($string, $start, $end, $pos)
{
  $str = explode($start, $string);
  $str = @explode($end, $str[$pos]);
  return $str[0] ?? '';
}


/**
 * Substitui $(variável[key], default) por seus valores correspondentes.
 *
 * @param string $content O texto contendo $(...) para ser substituído.
 * @return string O texto com as variáveis substituídas.
 */
function replace_variables(string $content): string
{
    $content = (string)($content ?? '');

    $pattern = '/\$(\w+(?:\.[A-Za-z0-9_]+)*)/';

    return preg_replace_callback($pattern, function ($m) {
        $raw = $m[0];

        $value = is_function_or_var($raw);

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return (string)($value ?? '');
    }, $content);
}


/**
 * Formats text by replacing triggers (ex: **bold**, __italic__, ~~wrong~~) markup with HTML tags.
 *
 * @param string $text The text to be formatted.
 * @return string The formatted text with HTML markup.
 */
function format_text($content = '', $mode = 'encode')
{
    // Normalize input for PHP 8.x (avoid null in string functions)
    $content = (string)($content ?? '');

    global $info, $page, $current_user;

    // From (') to (")
    $content = preg_replace("/'/", '"', $content);

    if ($mode == 'encode')
    {
        $content = replace_variables($content);

        // From ** to <strong>
        $content = preg_replace_callback('/\*\*(.*?)\*\*/', function($matches) {
            return !empty(trim($matches[1])) ? '<strong>' . $matches[1] . '</strong>' : $matches[0];
        }, $content);

        // From -- to <i>
        $content = preg_replace_callback('/--(.*?)--/', function($matches) {
            return !empty(trim($matches[1])) ? '<i>' . $matches[1] . '</i>' : $matches[0];
        }, $content);

        // From __ to <u>
        $content = preg_replace_callback('/__(.*?)__/', function($matches) {
            return !empty(trim($matches[1])) ? '<u>' . $matches[1] . '</u>' : $matches[0];
        }, $content);

        // From ~~ to <s>
        $content = preg_replace_callback('/~~(.*?)~~/', function($matches) {
            return !empty(trim($matches[1])) ? '<s>' . $matches[1] . '</s>' : $matches[0];
        }, $content);

        // From && && to <code>
        $content = preg_replace_callback('/&&(.*?)&&/', function($matches) {
            return !empty(trim($matches[1])) ? '<code>' . $matches[1] . '</code>' : $matches[0];
        }, $content);

        // From a() e icon()
        $patternLink = '/a\((.*?)\)\[(.*?)\](\{(.*?)\})?/';
        $content = preg_replace_callback($patternLink, function ($matches) {
            return a($matches[0]);
        }, $content);

        // icon()
        $patternLink = '/icon\((.*?)\)/';
        $content = preg_replace_callback($patternLink, function ($matches) {
            return icon($matches[1]);
        }, $content);

        // From line breaks to <br>
        $content = preg_replace("/-br/", '<br>', $content);
    }

    elseif ($mode == 'decode')
    {
        // From <strong> to **
        $content = preg_replace_callback('/<strong>(.*?)<\/strong>/', function($matches) {
            return '**' . $matches[1] . '**';
        }, $content);

        // From <i> to --
        $content = preg_replace_callback('/<i>(.*?)<\/i>/', function($matches) {
            return '--' . $matches[1] . '--';
        }, $content);

        // From <u> to __
        $content = preg_replace_callback('/<u>(.*?)<\/u>/', function($matches) {
            return '__' . $matches[1] . '__';
        }, $content);

        // From <s> to ~~
        $content = preg_replace_callback('/<s>(.*?)<\/s>/', function($matches) {
            return '~~' . $matches[1] . '~~';
        }, $content);

        // From <code> to &&
        $content = preg_replace_callback('/<code>(.*?)<\/code>/', function($matches) {
            return '&&' . $matches[1] . '&&';
        }, $content);

        // From <a> to markdown a(text)[url]{attributes}
        $content = preg_replace_callback('/<a href="([^"]+)"([^>]*?)>(.*?)<\/a>/', function($matches)
        {
            $url = $matches[1] ?? '';
            $text = !empty($matches[3])
                ? trim($matches[3])
                : $url;

            $attributes = $matches[2] ?? '';
            $attributes = !empty($attributes)
                ? '{'. trim(parse_html_tag_attributes($attributes, 'decode')) .'}'
                : '';

            return "a({$text})[{$url}]{$attributes}";
        }, $content);

        // From <i> to markdown icon(icon)
        $content = preg_replace_callback('/<i class="([^"]+)"><\/i>/', function($matches) {
            $icon = preg_replace("/icon/", '', $matches[1]);
            return "icon(". trim($icon) .")";
        }, $content);

        // From <br> to breakline -br
        $content = preg_replace('/<br\s*\/?>/', '-br', $content);
    }

    $content = preg_replace_callback('/(<input[^>]*value=")([^"]+)(")/', function($matches) {
        $decoded_value = format_text($matches[2], 'decode');
        return $matches[1] . $decoded_value . $matches[3];
    }, $content);

    $content = preg_replace_callback('/(<textarea[^>]*>)([^<]+)(<\/textarea>)/', function($matches) {
        $decoded_value = format_text($matches[2], 'decode');
        return $matches[1] . $decoded_value . $matches[3];
    }, $content);

    return $content;
}


function generate_social_share_link($platform = '', $attr = [])
{
    $url = isset($attr['url']) ? urlencode($attr['url']) : '';
    $text = isset($attr['text']) ? urlencode($attr['text']) : '';
    $hashtags = isset($attr['hashtags']) ? urlencode($attr['hashtags']) : '';

    switch ($platform) {
        case "facebook":
            return "https://www.facebook.com/sharer/sharer.php?u={$url}&quote={$text}";
        case "whatsapp":
            return "https://api.whatsapp.com/send?text={$text} {$url}";
        case "twitter":
            return "https://twitter.com/intent/tweet?text={$text}&url={$url}&hashtags={$hashtags}";
        case "pinterest":
            return "https://www.pinterest.com/pin/create/button/?url={$url}&media=&description={$text}";
        case "messenger":
            return "https://www.messenger.com/t/?link={$url}";
        case "linkedin":
            return "https://www.linkedin.com/shareArticle?mini=true&url={$url}&title={$text}&summary=&source=";
        case "email":
            return "mailto:?subject={$text}&body={$url}";
        default:
            return '#';
    }
}


/**
 * Sanitize a string.
 *
 * @param string $string The string to be sanitized.
 * @return string The sanitized string.
 */
function sanitize_string(string $string)
{
    $original = 'ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜüÝÞßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿRr"!@#$%&*()_-+={[}]/?;:,\\\'<>°ºª';
    $substituir = 'aaaaaaaceeeeiiiidnoooooouuuuuybsaaaaaaaceeeeiiiidnoooooouuuyybyRr                                ';

    $string_es = strtr(utf8_decode($string), utf8_decode($original), $substituir);

    $string_br = str_replace(' ', '-', $string_es);
    $string_tr = str_replace(array('----', '---', '--'), '-', $string_br);

    $string_mi = strtolower($string_tr);

    return $string_mi;
}


/**
* Filter empty values from an array, including sub-arrays.
*
* @param array $data The input array.
* @return array|false The filtered array or false if it is empty.
*/
function filter_empty_values_new(array $data)
{
   // Recursive callback function to trim and filter values
   $filter_recursive = function($value) use (&$filter_recursive)
   {
       if (is_array($value))
       {
           $value = array_map($filter_recursive, $value);
           $value = array_filter($value, function($item)
           {
               return !is_null($item) && $item !== '' && $item !== [] && $item !== false;
           });
       }
       return $value;
   };

   $filtered_data = array_map($filter_recursive, $data);
   $filtered_data = array_filter($filtered_data, function($item)
   {
       return !is_null($item) && $item !== '' && $item !== [] && $item !== false;
   });

   return empty($filtered_data) ? false : $filtered_data;
}

function filter_empty_values(array $data)
{
   $trim_recursive = function($value) use (&$trim_recursive) {
       return is_array($value) ? array_map($trim_recursive, $value) : trim($value);
   };

   $trimmed_data = array_map($trim_recursive, $data);
   $filtered_data = array_filter($trimmed_data);
   return empty($filtered_data) ? [] : $filtered_data;
}


/**
 * Removes all non-numeric characters from the given string.
 *
 * @param string $str The string to clean.
 * @return string The cleaned string, with all non-numeric characters removed.
 */
function clean_number(string $str) {
    return preg_replace("/[^0-9]/", "", $str);
}


/**
 * Checks if a given value is a valid JSON string.
 *
 * @param mixed $value The value to check for JSON validity.
 * @return bool True if the value is a valid JSON string, false otherwise.
 */
function is_json($value): bool
{
    if (!is_string($value) || $value === '') {
        return false;
    }

    json_decode($value);
    return json_last_error() === JSON_ERROR_NONE;
}


/**
 * Retrieves a nested value from an array using a pointer array.
 *
 * This function traverses a multi-dimensional array using a sequence of keys
 * (pointer) to extract the final value. If any key along the path is missing or invalid,
 * it returns null instead of throwing an error.
 *
 * @param array $source The source array from which the value should be retrieved.
 * @param array $pointer An ordered array of keys representing the path to the desired value.
 * @return mixed The value found at the end of the pointer path, or null if not found.
 */
// function get_value_from_pointer(array $source, array $pointer)
// {
//     foreach ($pointer as $key)
//     {
//         if (!is_array($source) || !array_key_exists($key, $source)) {
//             return null;
//         }

//         $source = $source[$key];
//     }

//     return $source;
// }



/**
 * Recursively converts JSON strings in an array or object into PHP data structures.
 *
 * @param array|object $data The array or object containing JSON strings to be converted.
 * @return array|object The array or object with JSON strings converted to PHP data structures.
 */
function convert_json_items($data = [])
{
    if (!is_array($data) && !is_object($data)) {
        return $data;
    }

    foreach ($data as $key => $value)
    {
        // Decode only if the value is a JSON string
        if (is_string($value) && is_json($value)) {
            $value = json_decode($value, true);
        }

        if (is_float($value)) {
            $value = (string) number_format($value, 2, ".", "");
        }

        // Recursive pass
        $value = convert_json_items($value);

        // Reassign
        if (is_array($data)) {
            $data[$key] = $value;
        } else {
            $data->$key = $value;
        }
    }

    return $data;
}



/**
 * Converts data between arrays and objects.
 *
 * @param array|object $data The data to convert.
 * @param string $turn_into The type to convert the data into ('array' or 'object').
 * @return array|object|string The converted data or an error message if conversion fails.
 */
function array_and_object_converter($data = [], string $turn_into = 'default')
{
    // First, normalize JSON items inside the structure
    $data = convert_json_items($data);

    // Convert only if needed
    if ($turn_into === 'array') {
        return is_object($data) ? json_decode(json_encode($data), true) : $data;
    }

    elseif ($turn_into === 'object') {
        return is_array($data) ? json_decode(json_encode($data)) : $data;
    }

    return $data;
}



/**
 * Parses a string of HTML tag attributes into a valid HTML attribute string.
 *
 * @param string $attributes The string of attributes to be parsed, formatted as key:(value) pairs separated by semicolons.
 * @return string A valid HTML attribute string.
 */
function parse_html_tag_attributes($attributes = '', $mode = 'encode')
{
    if ($mode == 'encode')
    {
        $attributesArr = [];
        $length = strlen((string) $attributes);
        $key = '';
        $value = '';
        $inKey = true;
        $inValue = false;

        for ($i = 0; $i < $length; $i++)
        {
            $char = $attributes[$i];

            if ($inKey)
            {
                if ($char === ':') {
                    $inKey = false;
                    $inValue = true;
                } else {
                    $key .= $char;
                }
            }

            elseif ($inValue)
            {
                if ($char === '(')
                {
                    $parenCount = 1;
                    $i++;
                    while ($i < $length && $parenCount > 0)
                    {
                        $char = $attributes[$i];
                        if ($char === '(') {
                            $parenCount++;
                        } elseif ($char === ')') {
                            $parenCount--;
                        }
                        if ($parenCount > 0) {
                            $value .= $char;
                        }
                        $i++;
                    }
                    $i--;
                    $attributesArr[trim($key)] = trim($value);
                    $key = '';
                    $value = '';
                    $inKey = true;
                    $inValue = false;

                    while ($i < $length && $attributes[$i] !== ';') {
                        $i++;
                    }
                }
            }
        }

        $attrString = '';
        foreach ($attributesArr as $key => $value) {
            $attrString .= htmlspecialchars($key) . "='" . htmlspecialchars($value) . "' ";
        }

        return trim($attrString);
    }

    elseif ($mode == 'decode')
    {
        // $pattern = '/(\S+)\s*=\s*"([^"]+)"/';
        $pattern = '/(\S+)\s*=\s*"([^"]*)"/';

        // Replace the format from 'data-item="AAA"' to 'data-item: (AAA);'
        $result = preg_replace_callback($pattern, function($matches) {
            return $matches[1] . ':(' . $matches[2] . ');';
        }, $attributes);

        return $result;
    }
}


/**
 * Generates an HTML img tag with given parameters and a placeholder if the image source is invalid.
 *
 * @param array $params The parameters for the image tag, including:
 *                      - 'src': The source of the image.
 *                      - 'size': An array with 'width' and 'height' of the image.
 *                      - 'placeholder': The source of the placeholder image.
 *                      - 'alt': The alt text for the image.
 *                      - 'attributes': Additional attributes for the image tag.
 * @return string The generated HTML img tag.
 */
function img(array $params = [])
{
    $defaults = [
        'src' => null,
        'size' => 'nullXnull',
        'placeholder' => site_url('/uploads/images/preview_img.jpg'),
        'alt' => 'Imagem não carregada',
        'attributes' => ''
    ];

    $params = array_merge($defaults, $params);
    $attributes = !empty($params['attributes'])
        ? parse_html_tag_attributes($params['attributes'])
        : '';

    $alt         = $params['alt'];
    $src         = $params['src'];
    $size        = explode('X', strtoupper($params['size']));
    $placeholder = $params['placeholder'];
    $width       = $size[0] ?? '';
    $height      = $size[1] ?? '';


    if (strpos($src, pg) !== false)
    {
        $temp_src = __BASE_DIR__.str_replace(pg, '', $src);
        $imageSize = file_exists($temp_src) && is_file($temp_src) ? getimagesize($temp_src) : false;
    }

    elseif (filter_var($src, FILTER_VALIDATE_URL))
    {
        $imageSize = false;
        $headers = @get_headers($src, 1);
        if (strpos($headers[0], '200') !== false && strpos($headers['Content-Type'] ?? '', 'image/') === 0)
        $imageSize = getimagesize($src);
    }

    else {
        $imageSize = file_exists($src) && is_file($src) ? getimagesize($src) : false;
    }

    if (!$imageSize) {
        $imageSize = getimagesize($placeholder);
        $src = $placeholder;
    }
    else {
        $width = $width ?: $imageSize[0];
        $height = $height ?: $imageSize[1];
    }

    return "<img loading='lazy' $attributes src='$src' width='$width' height='$height' alt='$alt'>";
}


/**
 * Generates an HTML anchor (a) tag from a formatted string.
 *
 * @param string $str The formatted string to parse, containing:
 *                    - Text within parentheses (e.g., (Link Text)).
 *                    - URL within square brackets (e.g., [http://example.com]).
 *                    - Attributes within curly braces (e.g., {class:(link-class); target:(_blank)}).
 * @return string The generated HTML anchor tag.
 */
function a($str = '')
{
    $str = preg_replace("/'/", '"', $str);

    $patternText = '/\((.*?)\)/';
    $patternUrl = '/\[(.*?)\]/';
    $patternAttributes = '/\{(.*?)\}/';

    preg_match($patternText, $str, $matchText);
    preg_match($patternUrl, $str, $matchUrl);
    preg_match($patternAttributes, $str, $matchAttributes);

    $text = !empty($matchText[1])
        ? htmlspecialchars($matchText[1])
        : '';
    $url = !empty($matchUrl[1])
        // ? 'href="' . htmlspecialchars($matchUrl[1]) . '"'
        ? "href='" . htmlspecialchars($matchUrl[1]) . "'"
        : '';
    $attributes = !empty($matchAttributes[1])
        ? trim(parse_html_tag_attributes($matchAttributes[1]))
        : '';

    return "<a $url $attributes>$text</a>";
}


/**
 * Generates an HTML element with the icon.
 *
 * @param array $params An associative array containing the parameters for generating the HTML element.
 * @return string Returns the generated HTML elements as a string.
 */
function icon($icon = '')
{
    return !empty($icon)
        ? "<i class='icon $icon'></i>"
        : '';
}


/**
 * Parses a "function call" string into callable name and argument list.
 *
 * Supported syntaxes:
 *   - "fn({all})"             → passes the entire $data array as a single argument
 *   - "fn()"                  → no arguments
 *   - "fn(arg1, arg2, ...)"   → comma-separated arguments where each token may be:
 *        • {field}            → replaced by $data['field'] (or null if missing)
 *        • 'string' / "str"   → quoted strings (supports simple backslash unescape via stripcslashes)
 *        • true | false | null
 *        • number             → integer or float
 *        • bareword           → treated as raw string literal (no quotes)
 *
 * Notes:
 *   - Simple comma split: does NOT support commas inside quoted strings.
 *   - Returns a tuple: [string $name, ?array $args, bool $parsed]
 *     • $parsed=false means we didn’t find explicit parentheses → caller can fall back to legacy behavior.
 *
 * @param string $fnString The raw function string (e.g., "price_format({value}, 'BRL')")
 * @param array  $data     Current row/record data to resolve {field} placeholders
 * @param string $key      The current field key (not used here, kept for signature parity)
 * @return array           [$name, $args, $explicitlyParsed]
 */
function _parse_fn_call(string $fnString, array $data, string $key): array
{
    $fn = trim($fnString);

    // "({all})" → pass the entire $data array as a single argument
    if (strpos($fn, '({all})') !== false) {
        $fn = explode('(', $fn, 2)[0];
        return [$fn, [$data], true];
    }

    // Has parentheses? Extract name + raw args
    if (preg_match('/^([a-zA-Z_][\w]*)\s*\((.*)\)\s*$/', $fn, $m)) {
        $name   = $m[1];
        $argsRaw = trim($m[2]);

        // Empty argument list "fn()"
        if ($argsRaw === '') return [$name, [], true];

        // Simple split by comma (no support for commas inside quotes)
        $tokens = preg_split('/\s*,\s*/', $argsRaw);
        $args   = [];

        foreach ($tokens as $t) {
            if ($t === '') continue;

            // "{field}" → resolve from $data
            if (preg_match('/^\{([^}]+)\}$/', $t, $mm)) {
                $args[] = $data[$mm[1]] ?? null;
                continue;
            }

            // Quoted strings: '...' or "..."
            if ((strlen($t) >= 2) && (
                ($t[0] === "'" && substr($t, -1) === "'") ||
                ($t[0] === '"' && substr($t, -1) === '"')
            )) {
                $args[] = stripcslashes(substr($t, 1, -1));
                continue;
            }

            // Booleans / null
            $tl = strtolower($t);
            if ($tl === 'true')  { $args[] = true;  continue; }
            if ($tl === 'false') { $args[] = false; continue; }
            if ($tl === 'null')  { $args[] = null;  continue; }

            // Number (int or float)
            if (preg_match('/^-?\d+(\.\d+)?$/', $t)) {
                $args[] = $t + 0; // implicit cast to int/float
                continue;
            }

            // Fallback: bareword → treat as unquoted string
            $args[] = $t;
        }

        return [$name, $args, true];
    }

    // No parentheses → not explicitly parsed; let caller use legacy behavior
    return [$fn, null, false];
}


/**
 * VIEW: Backward compatible renderer for values in list/detail views.
 *
 * Behavior:
 *   1) If $function_name has explicit parentheses (e.g., "fn(...)" or "fn({all})"):
 *        - Parse arguments, call the function if it exists.
 *        - If function does not exist, fallback to first argument (or "-").
 *   2) If no explicit parentheses are found (legacy mode):
 *        - Call $fn($param) where $param = $data[$key] (decoded if JSON string).
 *        - If function does not exist, return $param.
 *   3) Normalize empty result to "-".
 *
 * Requirements/Assumptions:
 *   - Helper function is_json(string): bool must exist in your codebase.
 *
 * @param string     $function_name Function signature or name. Ex: "money({price}, 'BRL')" or "money"
 * @param string     $key           Field key to fetch default param from $data in legacy mode
 * @param array|object|null $data   Row data
 * @return mixed                    Rendered value for view
 */
function function_view($function_name = '', string $key = '', $data = null)
{
    $data = is_array($data) ? $data : (array)$data;

    // Try explicit "fn(...)" parsing first
    [$fn, $args, $explicit] = _parse_fn_call((string)$function_name, $data, $key);

    if ($explicit) {
        if (function_exists($fn)) {
            $res = call_user_func_array($fn, $args);
        } else {
            // Fallback: show first argument or "-"
            $res = $args[0] ?? '-';
        }
        return ($res === '' || $res === null) ? '-' : $res;
    }

    // Legacy behavior: single parameter from the field value
    $param = $data[$key] ?? '-';
    $param = (is_string($param) && is_json($param)) ? json_decode($param, true) : $param;

    $res = function_exists($fn) ? $fn($param) : $param;
    return (empty($res) && $res !== '0') ? '-' : $res;
}


/**
 * PROCESS: Back-end processor that supports both legacy placeholders and explicit literals.
 *
 * Call resolution order:
 *   1) Explicit "fn(...)" or "fn({all})"
 *        - Parse args (supports {field}, quoted strings, booleans, null, numbers, barewords).
 *        - Call fn with parsed args; if fn missing, return first arg or null.
 *   2) Legacy placeholders without parentheses:
 *        - If string contains "{field}" tokens, collect them in order and call fn(...params).
 *        - If fn missing, return first param (or null).
 *   3) Legacy "({all})":
 *        - Pass entire $data as a single array argument.
 *   4) Final fallback:
 *        - Pass only the field value ($data[$key]) to the function_name if it exists; otherwise return the raw value.
 *
 * Notes:
 *   - The function name is not renamed; "function_proccess" spelling retained for backward compatibility.
 *   - Helper function is_json(string): bool must exist in your codebase.
 *
 * @param string           $function_name Function signature or name (supports placeholders and explicit literals)
 * @param string           $key           Field key to fetch default value when falling back
 * @param array|object|null $data         Row data
 * @return mixed                          Processed value
 */
function function_proccess($function_name = '', string $key = '', $data = null)
{
    $data = is_array($data) ? $data : (array)$data;

    // Normalize to avoid PHP 8.x deprecations (null -> string)
    $function_name = (string)($function_name ?? '');
    $fn_name = trim($function_name);

    // No function configured: return field value (legacy behavior)
    if ($fn_name === '')
    {
        $param = $data[$key] ?? $key;
        if (is_string($param) && is_json($param)) {
            $param = json_decode($param, true);
        }
        return $param;
    }

    // 1) Explicit parsing: "fn(...)" with literals, {field}, and ({all})
    [$fn, $args, $explicit] = _parse_fn_call($fn_name, $data, $key);
    if ($explicit)
    {
        $fn = (string) $fn;
        return ($fn !== '' && function_exists($fn))
            ? call_user_func_array($fn, $args)
            : ($args[0] ?? null);
    }

    // 2) Legacy placeholders without parentheses → collect {field} in order
    if (preg_match_all('/\{([\w\-]+)\}/', $fn_name, $matches))
    {
        $fn = preg_replace('/\(.+\)/', '', $fn_name); // safety: strip any accidental "(...)"
        $fn = trim($fn);

        $params = [];
        foreach ($matches[1] as $param_key) {
            $params[] = $data[$param_key] ?? null;
        }

        return ($fn !== '' && function_exists($fn))
            ? call_user_func_array($fn, $params)
            : ($params[0] ?? null);
    }

    // 3) Legacy: "({all})" → pass the entire $data array
    if (strpos($fn_name, '({all})') !== false) {
        $fn = trim(explode('(', $fn_name, 2)[0]);

        return ($fn !== '' && function_exists($fn))
            ? call_user_func_array($fn, [$data])
            : $data;
    }

    // 4) Final legacy fallback: pass only the field value
    $param = $data[$key] ?? $key;
    if (is_string($param) && is_json($param)) $param = json_decode($param, true);

    return function_exists($fn_name)
        ? call_user_func_array($fn_name, [$param])
        : $param;
}



/**
 * Safely traverse nested arrays/objects using a simple "." pointer path.
 *
 * Examples:
 *   get_value_from_pointer($_SERVER, ['REQUEST_TIME_FLOAT'])
 *   get_value_from_pointer($user, ['profile','name','first'])
 *   get_value_from_pointer($payload, ['items','0','id']) // numeric index supported
 *
 * Traversal rules:
 *   - If current node is an array: uses array key (including numeric string "0").
 *   - If current node is an object: tries typed property / public property via isset() or property_exists().
 *   - If any hop is missing, returns $default immediately (null by default).
 *
 * @param mixed       $root
 * @param array       $pointer       List of segments already split (e.g. explode('.', 'a.b.c')).
 * @param mixed|null  $default
 * @return mixed
 */
function get_value_from_pointer($root, array $pointer, $default = null)
{
    $node = $root;

    foreach ($pointer as $seg) {
        if ($seg === '' || $seg === null) {
            return $default; // invalid segment
        }

        // Array access
        if (is_array($node)) {
            if (array_key_exists($seg, $node)) {
                $node = $node[$seg];
                continue;
            }
            // Allow numeric index (e.g., "0") even if $seg is numeric string
            if (ctype_digit((string)$seg)) {
                $idx = (int)$seg;
                if (array_key_exists($idx, $node)) {
                    $node = $node[$idx];
                    continue;
                }
            }
            return $default;
        }

        // Object access
        if (is_object($node)) {
            // Prefer isset for performance but fall back to property_exists for null properties
            if (isset($node->{$seg}) || property_exists($node, $seg)) {
                $node = $node->{$seg};
                continue;
            }
            return $default;
        }

        // Scalar reached before finishing path
        return $default;
    }

    return $node;
}


/**
 * Returns the resolved value for a token:
 * - Function: any string containing '(' is treated as a function call and passed to function_proccess().
 * - Variable: first char is '$' → read from $GLOBALS.
 * - Constant: if defined($token), return constant($token).
 * - Otherwise: null.
 *
 * Optional $data/$key let you resolve placeholders in function_proccess (e.g. my_fn({slug}, 10)).
 */
function is_function_or_var(string $value)
{
    $raw = trim($value);

    // Function call → delegate full string to function_proccess()
    if (strpos($raw, '(') !== false) {
        return function_proccess($raw, '', []);
    }

    // Variable from $GLOBALS with optional pointer path via '.'
    if ($raw !== '' && $raw[0] === '$') {
        // Split "$var->a->b" into ["$var", "a", "b"]
        $parts = explode('.', $raw);
        $varToken = array_shift($parts);          // e.g., "$user"
        $varName  = ltrim($varToken, '$');        // "user"

        if (!array_key_exists($varName, $GLOBALS)) {
            return null;
        }

        $root = $GLOBALS[$varName];

        // No pointer? return the variable value directly
        if (empty($parts)) {
            return $root;
        }

        // Traverse pointer path on top of the root variable
        return get_value_from_pointer($root, $parts, null);
    }

    // Constant
    if ($raw !== '' && defined($raw)) {
        return constant($raw);
    }

    // Unknown token
    return null;
}


/**
 * Retrieves the value of the "id" parameter from the query string in a URL.
 *
 * @param string $value The parameter name to retrieve.
 * @return string|null The value of the "id" parameter if it exists, otherwise null.
 */
function id_by_get()
{
    return !empty($_GET['id']) ? $_GET['id'] : null;
}


/**
 * Retrieves the value of the "id" parameter from the post.
 *
 * @param string $value The parameter name to retrieve.
 * @return string|null The value of the "id" parameter if it exists, otherwise null.
 */
function id_by_post()
{
    return !empty($_POST['id']) ? $_POST['id'] : null;
}


function convert_to_bytes($value)
{
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int) $value;

    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }

    return $value;
}

function detect_media_type_from_path(string $path): string
{
    $ext = strtolower(pathinfo(parse_url($path, PHP_URL_PATH) ?? '', PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','webp','bmp'], true)) return 'image';
    if (in_array($ext, ['mp4','webm','ogg'], true)) return 'video';
    if (in_array($ext, ['mp3','wav','ogg','webm'], true)) return 'audio';

    return 'archive';
}

$max_upload = min(
    convert_to_bytes(ini_get('upload_max_filesize')),
    convert_to_bytes(ini_get('post_max_size'))
);
