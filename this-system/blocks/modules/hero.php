<?php
if(!isset($seg)) exit;

if(!function_exists('hero')) {
  /**
   * Generates a full-page banner section based on the given attributes.
   *
   * @param array $Attr An array of attributes for the full-page banner.
   */
  function hero(array $Attr = [])
  {
    global $class, $effect, $animations, $section_attributes, $counter, $container, $align, $background, $contents;

    $variation = $Attr['variation'] ?? 'hero_default';
    $contents  = $Attr['contents'] ?? null;

    return !variation($variation, $Attr) ? '<span>This module does not exist.<span>' : variation($variation, $Attr);
  }
}
