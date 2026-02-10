 <?php
if(!isset($seg)) exit;

/**
 * Generates vertical text with left and right content.
 *
 * @param array $Attr An associative array containing the attributes for the vertical text.
 */
function vertical_text(array $Attr = [])
{
  $left  = $Attr['left'] ?? '';
  $right = $Attr['right'] ?? '';

  $result = "
  <span class='vertical-text'>
    <span class='text left'>$left</span>
    <span class='text right'>$right</span>
  </span>";

  return $result;
}