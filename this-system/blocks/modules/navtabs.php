<?php
if (!isset($seg)) exit;

/**
 * Builds a Bootstrap tabs wrapper with four visual variations:
 * - navtabs_default: classic tabs (nav-tabs)
 * - navtabs_pills: pills (horizontal, scrollable)
 * - navtabs_folder: tabs with bordered content container
 * - navtabs_underline: underline style (Bootstrap .nav-underline)
 *
 * Expected $Attr keys:
 *   - contents: array of items [ 'id'?, 'title'?, 'body'?, 'active'?, 'disabled'? ]
 *   - variation: 'navtabs_default' | 'navtabs_pills' | 'navtabs_folder' | 'navtabs_underline'
 *   - id: base id prefix for this group (default: 'nav-tab')
 *   - class: extra classes on wrapper
 */
function navtabs(array $Attr = [])
{
  global $animations;

  if (empty($Attr['contents']) || !is_array($Attr['contents'])) return '';

  $class     = $Attr['class'] ?? '';
  $variation = $Attr['variation'] ?? 'navtabs_default';
  $id_nav    = $Attr['id'] ?? 'nav-tab';

  // Variation configuration (DOM container + toggle type + classes)
  $variants = [
    'navtabs_default' => [
      'containerTag' => 'nav',
      'toggleType'   => 'tab',
      'classList'    => ['nav', 'nav-tabs'],
      'useLi'        => false,
    ],
    'navtabs_pills' => [
      'containerTag' => 'ul',
      'toggleType'   => 'pill',
      'classList'    => ['nav', 'nav-pills'],
      'useLi'        => true, // <ul><li><button/></li></ul>
    ],
    'navtabs_folder' => [
      'containerTag' => 'nav',
      'toggleType'   => 'tab',
      'classList'    => ['nav', 'nav-tabs'],
      'useLi'        => false,
    ],
    // New: underline tabs
    'navtabs_underline' => [
      'containerTag' => 'nav',
      'toggleType'   => 'tab',
      'classList'    => ['nav', 'nav-underline'],
      'useLi'        => false,
    ],
  ];

  // Safe fallback
  $cfg = $variants[$variation] ?? $variants['navtabs_default'];

  // Helper: class list to string
  $cls = function(array $arr) {
    return implode(' ', array_filter(array_unique($arr)));
  };

  // Wrapper
  $res  = "<div class='navtabs {$variation} ". ($animations ? 'animate-bottom' : '') ." {$class}'>";

  // Header container (nav/ul)
  $containerTag = $cfg['containerTag'];
  $res .= "<{$containerTag} id='".htmlspecialchars($id_nav, ENT_QUOTES)."' role='tablist' class='".$cls($cfg['classList'])."'>";

  // Build tab buttons
  $i = 1;
  foreach ($Attr['contents'] as $content)
  {
    $content    = (array)$content;
    $rawTitle   = isset($content['title']) ? (string)$content['title'] : ('Tab '.$i);
    $titleSafe  = htmlspecialchars($rawTitle, ENT_QUOTES);
    // Use sanitize_string() over title (preferred) or explicit id if you want to override
    $baseKeyRaw = !empty($content['id']) ? (string)$content['id'] : $rawTitle;
    $tabKey     = function_exists('sanitize_string') ? sanitize_string($baseKeyRaw) : preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($baseKeyRaw));
    if ($tabKey === '' ) $tabKey = 'tab-'.$i; // robust fallback

    $isActive     = !empty($content['active']);
    $isDisabled   = !empty($content['disabled']);

    $btnClasses   = ['nav-link', $isActive ? 'active' : ''];
    $ariaSelected = $isActive ? 'true' : 'false';
    $disabledAttr = $isDisabled ? 'disabled' : '';

    // When using <ul>, each button is wrapped by <li>
    if (!empty($cfg['useLi'])) {
      $res .= "<li class='nav-item' role='presentation'>";
    }

    // IDs and targets: #{$id_nav}-{$tabKey}
    $btnId      = htmlspecialchars($id_nav, ENT_QUOTES) . '-' . htmlspecialchars($tabKey, ENT_QUOTES) . '-tab';
    $paneId     = htmlspecialchars($id_nav, ENT_QUOTES) . '-' . htmlspecialchars($tabKey, ENT_QUOTES);
    $toggleType = htmlspecialchars($cfg['toggleType'], ENT_QUOTES);

    $res .= "<button class='".$cls($btnClasses)."'"
          . " id='{$btnId}'"
          . " data-bs-toggle='{$toggleType}'"
          . " data-bs-target='#{$paneId}'"
          . " type='button' role='tab'"
          . " aria-controls='{$paneId}'"
          . " aria-selected='{$ariaSelected}'"
          . ($disabledAttr ? " {$disabledAttr}" : "")
          . ">{$titleSafe}</button>";

    if (!empty($cfg['useLi'])) {
      $res .= "</li>";
    }

    $i++;
  }

  $res .= "</{$containerTag}>";

  // Tab content container
  $res .= "<div class='tab-content' id='".htmlspecialchars($id_nav, ENT_QUOTES)."Content'>";

  // Build panes
  $i = 1;
  foreach ($Attr['contents'] as $content)
  {
    $content    = (array)$content;
    $rawTitle   = isset($content['title']) ? (string)$content['title'] : ('Tab '.$i);
    $baseKeyRaw = !empty($content['id']) ? (string)$content['id'] : $rawTitle;
    $tabKey     = function_exists('sanitize_string') ? sanitize_string($baseKeyRaw) : preg_replace('/[^a-z0-9\-]+/i', '-', strtolower($baseKeyRaw));
    if ($tabKey === '' ) $tabKey = 'tab-'.$i; // robust fallback

    $body        = $content['body'] ?? '';
    $activeClass = !empty($content['active']) ? 'show active' : '';

    $paneId  = htmlspecialchars($id_nav, ENT_QUOTES) . '-' . htmlspecialchars($tabKey, ENT_QUOTES);
    $labelId = $paneId . '-tab';

    $res .= "<div class='tab-pane fade {$activeClass}'"
          . " id='{$paneId}' role='tabpanel'"
          . " aria-labelledby='{$labelId}' tabindex='0'>{$body}</div>";

    $i++;
  }

  $res .= "</div>"; // .tab-content
  $res .= "</div>"; // .navtabs wrapper

  return $res;
}
