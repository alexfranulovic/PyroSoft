<?php
if(!isset($seg)) exit;

function video_player(array $params = [])
{
  // Básico
  $class        = $params['class']        ?? '';
  $remove       = $params['remove']       ?? false;
  $src          = $params['src']          ?? '';         // string (atalho) OU deixe vazio e use "sources"
  $sources      = $params['sources']      ?? [];         // [['src'=>'...', 'type'=>'video/mp4'], ...]
  $tracks       = $params['tracks']       ?? [];         // [['src'=>'...', 'kind'=>'subtitles','srclang'=>'pt','label'=>'Português','default'=>true], ...]

  // Atributos comuns do <video>
  $controls     = $params['controls']     ?? true;       // mostrar controles
  $autoplay     = $params['autoplay']     ?? false;
  $muted        = $params['muted']        ?? false;      // recomendado com autoplay
  $loop         = $params['loop']         ?? false;
  $playsinline  = $params['playsinline']  ?? true;       // bom p/ mobile
  $preload      = $params['preload']      ?? 'metadata'; // 'auto' | 'metadata' | 'none'
  $poster       = $params['poster']       ?? '';         // thumbnail
  $width        = $params['width']        ?? null;       // ex: 640
  $height       = $params['height']       ?? null;       // ex: 360

  // Atributos avançados / compatibilidade
  $controlsList = $params['controlsList'] ?? '';         // ex: 'nodownload noplaybackrate'
  $crossorigin  = $params['crossorigin']  ?? '';         // '' | 'anonymous' | 'use-credentials'
  $disablePiP   = $params['disablepictureinpicture'] ?? false;
  $disableRemote= $params['disableremoteplayback'] ?? false;

  // Botão remover (opcional)
  $btn_remove = $remove ? "<button type='button' class='btn btn-danger'>✕</button>" : '';

  // Se não veio src nem sources, não renderiza
  $hasSingleSrc = is_string($src) && strlen($src);
  $hasSources   = is_array($sources) && !empty($sources);
  if (!$hasSingleSrc && !$hasSources) return '';

  // Escapes
  $esc = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

  // Monta atributos do <video>
  $attrs = [];
  if ($controls)     $attrs[] = 'controls';
  if ($autoplay)     $attrs[] = 'autoplay';
  if ($muted)        $attrs[] = 'muted';
  if ($loop)         $attrs[] = 'loop';
  if ($playsinline)  $attrs[] = 'playsinline';
  if ($disablePiP)   $attrs[] = 'disablepictureinpicture';
  if ($disableRemote)$attrs[] = 'disableremoteplayback';

  if ($preload !== null) $attrs[] = 'preload="'.$esc($preload).'"';
  if ($poster)           $attrs[] = 'poster="'.$esc($poster).'"';
  if ($width)            $attrs[] = 'width="'.$esc($width).'"';
  if ($height)           $attrs[] = 'height="'.$esc($height).'"';
  if ($controlsList)     $attrs[] = 'controlsList="'.$esc($controlsList).'"';
  if ($crossorigin)      $attrs[] = 'crossorigin="'.$esc($crossorigin).'"';

  // Data-attr espelhando o src principal (útil no JS, como no audio)
  $dataVideo = $hasSingleSrc ? " data-video=\"{$esc($src)}\"" : '';

  // Fontes (<source>)
  $sourcesHtml = '';
  if ($hasSources) {
    foreach ($sources as $s) {
      $sSrc  = $esc($s['src']  ?? '');
      if (!$sSrc) continue;
      $sType = $esc($s['type'] ?? ''); // ex: video/mp4, video/webm
      $typeAttr = $sType ? " type=\"{$sType}\"" : '';
      $sourcesHtml .= "<source src=\"{$sSrc}\"{$typeAttr}>\n";
    }
  } else {
    // fallback: usa o src simples sem type
    $sourcesHtml = "<source src=\"{$esc($src)}\">\n";
  }

  // Tracks (<track>)
  $tracksHtml = '';
  if (!empty($tracks)) {
    foreach ($tracks as $t) {
      $tSrc     = $esc($t['src']     ?? '');
      if (!$tSrc) continue;
      $tKind    = $esc($t['kind']    ?? 'subtitles'); // subtitles | captions | descriptions | chapters | metadata
      $tLang    = $esc($t['srclang'] ?? '');          // pt, en, es...
      $tLabel   = $esc($t['label']   ?? '');
      $tDefault = !empty($t['default']) ? ' default' : '';
      $tracksHtml .= "<track src=\"{$tSrc}\" kind=\"{$tKind}\"".
                     ($tLang ? " srclang=\"{$tLang}\"" : '').
                     ($tLabel ? " label=\"{$tLabel}\"" : '').
                     "{$tDefault}>\n";
    }
  }

  // Render
  $res = "
    <video class='video-el {$esc($class)}' {$dataVideo} ".implode(' ', $attrs).">
      {$sourcesHtml}{$tracksHtml}
      Seu navegador não suporta vídeos HTML5.
    </video>";

  return $res;
}
