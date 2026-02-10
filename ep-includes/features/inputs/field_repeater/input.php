<?php

$type = $Attr['type'] ?? 'image';

$Required = ($type_form == 'update') ? '' : $Required;

$value = !empty($Attr['Value'])
    ? $Attr['Value']
    : [];

if (!empty($value))
{
    if (is_json($value)) {
        $value = json_decode($value);
    }

    if (!is_array($value)) {
        $value = [$value];
    }
}

$capture = $Attr['capture'] ?? null;
$multiple = $Attr['multiple'] ?? null;

$accept = !empty($Attr['accepted_extensions'])
    ? 'accept="' . htmlspecialchars($Attr['accepted_extensions']) . '"'
    : '';

if (!empty($capture)) {
    $attributes .= ' capture';
}

if (!empty($multiple)) {
    $attributes .= ' multiple';
}

$attr_label = '';
if (!empty($value))
{
    $attr_label = 'style="display: none;"';
    if ($multiple) {
        $attr_label = '';
    }
}

$res.= "
<div class='files'>";

if ($type == 'images' OR $type == 'image' OR $type == 'img')
{
    $res.= "
    <div class='previewer'>

        <label class='img-container add-file' $attr_label for='$input_id'>
        <div>
            <p>".icon('fas fa-images')."</p>
            <p>Adicionar imagem</p>
            <small>Máx. ". ini_get('upload_max_filesize') ."</small>
        </div>
        <input class='d-none' data-upload-image type='file' {$accept} $attributes id='$input_id' field='$field_id'>
        </label>";

        if (!empty($value))
        {
            foreach ($value as $image)
            {
                if (is_array($image)) continue;

                $res.= "
                <div class='img-container'>
                    <img loading='lazy' src='". base_url ."/uploads/images/{$Attr['Src']}/$image' alt='Imagem não carregada'>
                    <button delete-image-from-preview class='btn btn-danger' image-name='$image' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                </div>";
            }
        }

    $res.= "
    </div>";
}

elseif ($type == 'videos')
{
    $res.= "
    <div class='previewer'>

        <label class='img-container add-file' $attr_label for='$input_id'>
        <div>
            <p>".icon('fas fa-film')."</p>
            <p>Adicionar vídeo</p>
            <small>Máx. ". ini_get('upload_max_filesize') ."</small>
        </div>
        <input class='d-none' data-upload-video type='file' {$accept} $attributes id='$input_id' field='$field_id'>
        </label>";

        if (!empty($value))
        {
            foreach ($value as $video)
            {
                if (is_array($video)) continue;

                $res.= "
                <div class='video-container'>".
                    block('video_player', [
                        'src' => base_url ."/uploads/videos/{$Attr['Src']}/$video",
                    ])."
                    <button delete-video-from-preview class='btn btn-danger' video-name='$video' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                </div>";
            }
        }

    $res.= "
    </div>";
}


elseif ($type == 'audios')
{
    $min_seconds = $Attr['min_seconds'] ?? MIN_TIME_AUDIO;
    $max_seconds = $Attr['max_seconds'] ?? MAX_TIME_AUDIO;

    $template_player = block('audio_player', [
        'class' => 'template-player',
        'src' => '__scr_example__',
        'remove' => true,
    ]);

    $res .= "
    <div class='previewer'>
        <div class='audio-recorder' $attributes data-min='{$min_seconds}' data-max='{$max_seconds}' data-name='{$name}' data-id='{$input_id}'>
            <div class='audio-controls'>
                <div class='panel d-none'>
                    <button type='button' class='stop-recording'>" . icon('fas fa-trash') . "</button>
                    <p class='recording-timer'></p>
                </div>
                <div class='volume-circle' style='display:none;'></div>
                <button type='button' class='confirm-recording d-none'>" . icon('fas fa-check') . "</button>
                <p class='description'>Gravar áudio</p>
                <button type='button' class='start-recording'>" . icon('fas fa-microphone') . "</button>
            </div>
            <div class='audio-preview'>";

            foreach ($value as $audio)
            {
                if (is_array($audio)) continue;

                $res.= block('audio_player', [
                    'src' =>  base_url ."/uploads/audios/{$Attr['Src']}/{$audio}",
                    'remove' => true,
                ]);
            }

            $res.= "
            </div>
            <!-- <input type='hidden' name='{$name}' id='{$input_id}' field='$field_id'/> -->
            $template_player
        </div>
    </div>";
}


else
{
    $res.= "
    <div class='previewer'>

        <label class='img-container add-file' $attr_label for='$input_id'>
        <div>
            <p>".icon('fas fa-file-import')."</p>
            <p>Adicionar arquivos</p>
            <small>Máx. ". ini_get('upload_max_filesize') ."</small>
        </div>
        <input class='d-none' data-upload-file type='file' {$accept} $attributes id='$input_id' field='$field_id'>
        </label>

    </div>";

    $res.= "<ul class='file-list'>";
    if (!empty($value))
    {

        foreach ($value as $file)
        {
            if (is_array($file)) continue;

            $res.= "
            <li class='file-item'>
                <button delete-file-from-preview class='btn btn-danger' file-name='$file' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                <span class='file-name'>$file</span>
            </li>";
        }

    }
    $res.= "</ul>";
}

$initial_files = [];
if (!empty($value)) {
    $initial_files = is_array($value) ? array_values($value) : [$value];
}
$res.= "<input type='hidden' input-files name='$name' field='$field_id' value='". json_encode($initial_files, JSON_UNESCAPED_SLASHES) ."' $Required>";

$res.= "</div>";

add_asset('footer', "<script src='".base_url."/dist/scripts/filesPreviewer.js' defer></script>");
