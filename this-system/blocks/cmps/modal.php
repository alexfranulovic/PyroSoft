<?php
if(!isset($seg)) exit;

/**
 * Generate a Bootstrap modal HTML structure.
 *
 * This function creates the HTML structure for a Bootstrap modal with customizable attributes.
 *
 * @param array $Attr - An associative array containing modal attributes.
 *   - 'id' (string, optional): The ID attribute for the modal (default is 'common-modal').
 *   - 'title' (string, optional): The title of the modal.
 *   - 'static' (bool, optional): If true, prevents closing the modal when clicking outside it (default is false).
 *   - 'close_button' (bool, optional): If true, adds a close button to the modal header (default is false).
 *   - 'body' (string): The content to be displayed in the modal body.
 *   - 'footer' (string, optional): The content to be displayed in the modal footer.
 *
 * @return string|null - An HTML structure for the modal or null if the body is empty.
 */
function modal(array $Attr = [])
{
  if (!empty($Attr['body']))
  {
    $id         = $Attr['id'] ?? 'common-modal';
    $delay      = $Attr['delay'] ?? '0';
    $class      = $Attr['class'] ?? '';
    $variation  = $Attr['variation'] ?? 'modal_default';
    $dialog     = $Attr['dialog'] ?? '';

    $animation = isset($Attr['animation'])
      ? $Attr['animation']
      : 'fade';

    $size = !empty($Attr['size'])
      ? "modal-{$Attr['size']}"
      : '';


    $attributes = !empty($Attr['attributes'])
      ? parse_html_tag_attributes($Attr['attributes'])
      : null;

    $static = (isset($Attr['static']) && $Attr['static'] == true)
      ? "data-bs-backdrop='static' data-bs-keyboard='false'"
      : '';

    $tag = (!empty($Attr['form']['active']) AND $Attr['form']['active'])
      ? 'form'
      : 'div';

    $form = ($tag == 'form')
      ? $Attr['form']
      : [];

    $form_attributes = !empty($form['attributes'])
      ? parse_html_tag_attributes($form['attributes'])
      : null;

    $res = "
    <article class='modal $animation' id='$id' tabindex='-1' delay='$delay' role='dialog' aria-labelledby='$id' aria-hidden='true' $static $attributes >
    <$tag $form_attributes class='modal-dialog $size $dialog'>";

    $res.= variation($variation, $Attr);

    $res.= "
    </$tag>
    </article>";

    return $res;
  }
}
