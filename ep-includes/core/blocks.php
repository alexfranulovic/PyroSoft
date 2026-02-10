<?php
if(!isset($seg)) exit;

/*
 *
 * Welcome to the PHP documentary introducing the library for visual blocks!
 * In this documentary, you will learn all about this amazing library that is transforming the way developers create user interfaces for their projects.
 * The PHP library for visual blocks is a collection of blocks and visual widgets that can be easily integrated into any PHP project.
 * These blocks and widgets are designed to be highly customizable and flexible, allowing you to create stunning and interactive user interfaces for your applications and websites.
 * The library is composed of several blocks, each with its own unique functionality.
 *
 */


/** Modules **/
require_once __BASE_DIR__."/this-system/blocks/modules/addable_content.php";

/** Components **/
// require_once __BASE_DIR__."/this-system/blocks/cmps/inputs.php";
feature('inputs');
require_once __BASE_DIR__."/this-system/blocks/cmps/crud_parts.php";


/**
 * Retrieve and display a stored message from the session.
 *
 * Example usage:
 *   // In the first page or script, store a message in the session.
 *   $_SESSION['msg'] = 'This is a success message!';
 *
 *   // In the next page or script, call the function to display the message.
 *   echo write_msg_return(); // Output: This is a success message!
 *
 * @return string|null The stored message from the session, or null if no message is present.
 */
function write_msg_return()
{
  if (isset($_SESSION['msg'])) {
    $msg = $_SESSION['msg'];
    unset($_SESSION['msg']);
    return $msg;
  }
}


/**
 * Generate a formatted view of different media types.
 *
 * This function is used to create a formatted view of different media types, such as icons, images, URLs, and archives.
 *
 * Example usage:
 *   view_media('fas fa-envelope', 'icon');
 *   view_media('https://example.com/image.jpg', 'image');
 *   view_media('https://example.com/document.pdf', 'archive');
 *
 * @param string $content The content representing the media.
 * @param string $type The type of media to be displayed.
 * @return string The formatted HTML representation of the media type.
 */
function view_media($content, $type, $where = 'view')
{
  if ($content === '' || $content === null) return '-';

  $type  = strtolower((string)$type);
  $limit = defined('MAX_MEDIA_ITEMS_IN_LIST') ? MAX_MEDIA_ITEMS_IN_LIST : 5;

  if ($type === 'icon') {
    $txt = htmlspecialchars((string)$content, ENT_QUOTES, 'UTF-8');
    return icon($txt) . ': ' . $txt;
  }

  if ($type === 'url') {
    $url = htmlspecialchars((string)$content, ENT_QUOTES, 'UTF-8');
    return "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\">{$url}</a>";
  }

  $items = is_array($content) ? $content : [$content];

  $render = function(string $item) use ($type)
  {
    if ($type === 'images') {
      return "<img src=\"{$item}\" alt=\"Imagem\">";
    }
    if ($type === 'videos') {
      return block('video_player', ['src' => $item]);
    }
    if ($type === 'audios') {
      return block('audio_player', ['src' => $item]);
    }

    if ($type === 'archives')
    {
      $t = detect_media_type_from_path($item);
      if ($t === 'image') return "<img src=\"{$item}\" alt=\"Imagem\">";
      if ($t === 'video') return block('video_player', ['src' => $item]);
      if ($t === 'audio') return block('audio_player', ['src' => $item]);

      $name = htmlspecialchars(basename(parse_url($item, PHP_URL_PATH)), ENT_QUOTES, 'UTF-8');
      return "<a href=\"{$item}\" target=\"_blank\" rel=\"noopener noreferrer\">{$name}</a>";
    }

    return "<a href=\"{$item}\" target=\"_blank\" rel=\"noopener noreferrer\">{$item}</a>";
  };

  $out = [];
  foreach ($items as $item)
  {
    if (!is_string($item) || $item === '') continue;
    if ($where === 'list' && count($out) >= $limit) break;

    $out[] = $render($item);
  }

  return $out ? '<div class="media-list">'.implode('', $out).'</div>' : '-';
}


function headerBig($header, $big = null)
{
  return "
  <div class='row justify-content-center'>
  <div class='col-md-12 heading-section text-center' style='margin-bottom: 25px;'>
    <h1 class='big m-0'>$big</h1>
    <h2>$header</h2>
  </div>
  </div>";
}


/**
 * Generates various types of input fields based on the specified type_block.
 *
 * @param string $type_block The type of input to generate. Possible values are:
 * @param string $type_form The type of form (e.g., "create", "update").
 * @param array $Attr An array of attributes for the input field.
 *
 * @return string The HTML markup for the generated input field.
 */
function block(string $type_block, $Attr = [])
{
  global $animations;
  global $commands;
  global $counter;
  global $background;
  global $effect;
  global $info;
  global $page;
  global $seg;
  global $url;

  $animations           = $Attr['animations'] ?? '';
  $url                  = $Attr['url'] ?? '';
  $align                = $Attr['align'] ?? '';

  if ($type_block == 'breadcrumbs' OR !empty($Attr))
  {
    $background         = $Attr['background'] ?? [];
    $footer             = $Attr['footer'] ?? [];
    $footer             = array_and_object_converter($footer, 'array');
    $size               = $Attr['size'] ?? 'col-12';
    $class              = $Attr['class'] ?? '';
    $class              = "$class $size";
    $counter            = $Attr['counter'] ?? 1;
    $commands           = (isset($Attr['commands']) && $Attr['commands'] == true) ? true : false;
    $container          = (isset($Attr['container']) && $Attr['container'] == true) ? true : false;
    $image              = $Attr['image'] ?? '';
    $effect             = $Attr['effect'] ?? 'to left, #ffffff00, #ffffff00';

    $section_attributes = !empty($Attr['section_attributes'])
        ? parse_html_tag_attributes($Attr['section_attributes'])
        : '';

    $bg_style = build_background_style($Attr);
    $class              .= " ".$bg_style['class'];
    $section_attributes .= " style='{$bg_style['style']}'";


    // Converting type_block's '_' to '-'.
    $m_class = explode("_", $type_block);
    $m_class = implode("-", $m_class);


    // Include block.
    if (file_exists(__BASE_DIR__."/this-system/blocks/modules/$type_block.php"))
    {
      require_once __BASE_DIR__."/this-system/blocks/modules/$type_block.php";

      $res = "
      <section class='m-$m_class $class' id='section-$counter' $section_attributes >";
      if($container) $res.= "<div class='container'>";


      if ($type_block != 'hero' AND $type_block != 'huge_button')
      {
        if (!empty($Attr['pre_title']) OR !empty($Attr['title']) OR !empty($Attr['subtitle']))
        {

          $pre_title = !empty($Attr['pre_title'])
            ? "<span class='pre-title ". ($animations ? 'animate-top' : '') ." $align' aria-hidden='true'>". format_text($Attr['pre_title']) ."</span>"
            : '';

          $title = !empty($Attr['title'])
            ? "<h2 class='title ". ($animations ? 'animate-top' : '') ." $align'>". format_text($Attr['title']) ."</h2>"
            : '';

          $subtitle = !empty($Attr['subtitle'])
            ? "<p class='". ($animations ? 'animate-top' : '') ." $align'>". format_text($Attr['subtitle']) ."</p>"
            : '';

          $res.= "
          <div class='m-header'>
            {$pre_title}
            {$title}
            {$subtitle}
          </div>";
        }
      }

      $res.= function_exists($type_block)
        ? $type_block($Attr)
        : '<div>This module does not exist.</div>';


      // Add a link or button in the footer.
      if ($type_block != 'hero' AND $type_block != 'huge_button')
      {
        if (!empty($footer['content']) OR !empty($footer['link_button']))
        {
          $mode         = $footer['mode'] ?? 'custom';
          $button_align = $footer['align'] ?? 'start';

          $res.= "<div class='m-footer ". ($animations ? 'animate-bottom' : '') ." content-$button_align'>";
          $res.= ($mode=="link_button") ? link_button($footer['link_button']) : $footer['content'];
          $res.= "</div>";
        }
      }


      if($container) $res.= "</div>";
      $res.= "</section>";
    }


    // Include components.
    elseif (file_exists(__BASE_DIR__."/this-system/blocks/cmps/$type_block.php"))
    {
      require_once __BASE_DIR__."/this-system/blocks/cmps/$type_block.php";
      $res = function_exists($type_block) ? $type_block($Attr) : '<div>This module does not exist.</div>';
    }


    // I'm sorry about that, but there is not any component or module with this $type_block.
    else $res = false;


    return $res;
  }

  return false;
}


function variation(string $type, $Attr = [])
{
  global $info;
  global $seg;

  // Include variations.
  if (file_exists(__BASE_DIR__."/this-system/blocks/variations/$type.php"))
  {
    require_once __BASE_DIR__."/this-system/blocks/variations/$type.php";
    $res = function_exists($type) ? $type($Attr) : [];
  }

  else $res = false;

  return $res;
}

/**
 * Builds background classes and style attributes for components.
 *
 * @param array $Attr  Input attribute array containing the 'background' configuration.
 * @return array       ['class' => string, 'style' => string]
 */
function build_background_style(array $Attr = [])
{
    $background = $Attr['background'] ?? [];
    $image_folder = $Attr['image_folder'] ?? null;

    $class = $Attr['class'] ?? '';
    $bg_style_parts = [];

    // Early return: no background configuration
    if (empty($background)) {
        return [
            'class' => trim($class),
            'style' => ''
        ];
    }

    // Effect Layer (gradient)
    if (!empty($background['effect'])) {
        $bg_effect = "linear-gradient({$background['effect']})";
        $bg_style_parts[] = $bg_effect;
    }

    $bg_type = $background['type'] ?? 'color';

    // --- Type: Color ---
    if ($bg_type === 'color') {
        $color = $background['color'] ?? '';
        if ($color !== '') {
            $class .= " bg-{$color}";
        }
    }

    // --- Type: Image ---
    elseif ($bg_type === 'image') {
        $img_name = $background['image'] ?? '';
        $bg_mode  = $background['mode'] ?? 'light';

        // Add mode class
        $class .= " {$bg_mode}-mode";

        // Build complete image path
        if ($img_name !== '') {
            $path = $image_folder
                ? pg . "/uploads/images/{$image_folder}/{$img_name}"
                : $img_name;

            $bg_style_parts[] = "url({$path})";
        }
    }

    // Join linear-gradient + url()
    $style_full = implode(', ', $bg_style_parts);

    return [
        'class' => trim($class),
        'style' => $style_full !== '' ? "background-image: {$style_full};" : ''
    ];
}
