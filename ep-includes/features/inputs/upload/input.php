<?php

function upload_progress(bool $hide = true)
{
    $d_none = $hide ? 'd-none' : '';

    $progress = "
    <div class='upload-progress $d_none'>
    <div class='progress $d_none'>
        <div class='progress-bar progress-bar-striped progress-bar-animated' role='progressbar' style='width:0%'></div>
    </div>
    <small class='upload-meta'>Envio em 1% <br> Tempo: 0s</small>
    </div>";

    return $progress;
}

function input_upload(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    // for debug
    // $Required = ($type_form == 'update') ? '' : $Required;
    $type = $type ?? 'image';
    $capture = $capture ?? null;
    $multiple = $multiple ?? null;
    $variation = $variation ?? null;
    $upload_to_s3 = $upload_to_s3 ?? false;

    $value = !empty($Value)
        ? $Value
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


    $accept = !empty($accepted_extensions)
        ? 'accept="' . htmlspecialchars($accepted_extensions) . '"'
        : '';

    if (!empty($capture)) {
        $attributes .= ' capture';
    }

    if (!empty($multiple)) {
        $attributes .= ' multiple';
    }

    /**
     * Hide/Show add file button when is multiple and empty.
     * Exception: variation = profile.
     */
    $attr_label = '';
    if (!empty($value))
    {
        $attr_label = 'style="display: none;"';
        if ($variation == 'profile'|| $multiple) {
            $attr_label = '';
        }
    }



    /**
     *
     * Treat the input label
     *
     */
    unset($Attr['input_id']);
    $res.= $label = input_label($Attr);


    /**
     *
     * Build the input.
     *
     */
    $res.= "
    <div class='files'>";


    if ($type != 'audios')
    {
        $appearance =
        [
            'images' => [
                'icon' => 'fas fa-images',
                'title' => 'Adicionar imagem',
                'trigger' => 'data-upload-image',
                'trigger_delete' => 'delete-image-from-preview',
            ],
            'archives' => [
                'icon' => 'fas fa-file-import',
                'title' => 'Adicionar arquivos',
                'trigger' => 'data-upload-archive',
                'trigger_delete' => 'delete-archive-from-preview',
            ],
            'videos' => [
                'icon' => 'fas fa-file-video',
                'title' => 'Adicionar vídeo',
                'trigger' => 'data-upload-video',
                'trigger_delete' => 'delete-video-from-preview',
            ],
        ];
        $appearance = $appearance[$type];

        $res.= "
        <div class='previewer'>";

            if ($type == 'images' && $variation == 'profile')
            {
                $photo = $value[0] ?? '';
                $photo_path = file_url("{$type}/{$Src}", $upload_to_s3, $photo);
                $photo_d_none = empty($photo) ? 'd-none' : '';

                $template_path = file_url("images/users", false, 'preview_img.jpg');
                $template_d_none = !empty($photo) ? 'd-none' : '';

                $res.= "
                <label class='box profile' $attr_label for='$input_id'>
                <div class='photo'>
                    <img class='template $template_d_none' loading='lazy' src='$template_path' alt='Imagem não carregada'>
                    <img class='$photo_d_none' loading='lazy' src='$photo_path' alt='Imagem não carregada'>
                ".icon("fas fa-pencil")."
                </div>
                <button class='btn btn-danger $photo_d_none' delete-image-from-preview file-name='$photo' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                <input class='d-none' {$appearance['trigger']} type='file' {$accept} $attributes id='$input_id' field='$field_id'>
                ". upload_progress() ."
                </label>";
            }

            else
            {
                $res.= "
                <label class='file-container add-file' $attr_label for='$input_id'>
                <div>
                    <p>".icon($appearance['icon'])."</p>
                    <p>{$appearance['title']}</p>
                    <small>Máx. ". ini_get('upload_max_filesize') ."</small>
                </div>
                <input class='d-none' {$appearance['trigger']} type='file' {$accept} $attributes id='$input_id' field='$field_id'>";

                /**
                 *
                 * Build the template
                 *
                 */
                $res.= "<div class='file-container template'>";
                if ($type == 'images') {
                    $res.= "<div class='box'><img loading='lazy' src=' ' alt='Imagem não carregada'></div>";
                }

                elseif ($type == 'videos') {
                    $res.= "<div class='box'>". block('video_player', [ 'src' => ' ', ]) ."</div>";
                }

                elseif ($type == 'archives')
                {
                    // archive template
                    $res.= "
                    <div class='file-container template' data-template-kind='archive'>
                        <div class='box'>
                        <div class='archive'>
                            <div class='file-type'><p>__icon_example__</p></div>
                            <p>__filename_example__</p>
                            <small>__ext_example__</small>
                        </div>
                        </div>
                        <button class='btn btn-danger' file-name='__filename_example__' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                        ". upload_progress() ."
                    </div>";

                    // image template
                    $res.= "
                    <div class='file-container template' data-template-kind='image'>
                        <div class='box'>
                            <img loading='lazy' src=' ' alt='Imagem não carregada'>
                        </div>
                        <button class='btn btn-danger' file-name='__filename_example__' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                        ". upload_progress() ."
                    </div>";

                    // video template
                    $res.= "
                    <div class='file-container template' data-template-kind='video'>
                        <div class='box'>
                            ". block('video_player', ['src' => ' ']) ."
                        </div>
                        <button class='btn btn-danger' file-name='__filename_example__' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                        ". upload_progress() ."
                    </div>";
                }

                $res.= "
                    <button class='btn btn-danger' file-name='__filename_example__' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                    ". upload_progress() ."
                </div>";

                $res.= "</label>";


                /**
                 *
                 * Bring the values
                 *
                 */
                if (!empty($value))
                {
                    foreach ($value as $file)
                    {
                        if (is_array($file)) continue;

                        $path = file_url("{$type}/{$Src}", $upload_to_s3, $file);

                        $res.= "<div class='file-container'>";

                        if ($type == 'images' OR $type == 'image' OR $type == 'img') {
                            $res.= "<div class='box'><img loading='lazy' src='{$path}' alt='Imagem não carregada'></div>";
                        }

                        elseif ($type == 'videos') {
                            $res.= "<div class='box'>". block('video_player', [ 'src' => $path ]) ."</div>";
                        }

                        elseif ($type == 'archives')
                        {
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

                            $imgExt = ['jpg','jpeg','png','gif','webp','svg','bmp','avif'];
                            $vidExt = ['mp4','webm','ogg','mov','m4v','avi','mkv'];

                            if (in_array($ext, $imgExt, true)) {
                                $res.= "<img loading='lazy' src='{$path}' alt='Imagem não carregada'>";
                            }
                            elseif (in_array($ext, $vidExt, true)) {
                                $res.= block('video_player', [ 'src' => $path ]);
                            }
                            else {
                                // mantém seu bloco .archive atual (o que você já tinha)
                                $extension = $ext;

                                switch ($extension)
                                {
                                    case 'csv':
                                    case 'xls':
                                    case 'xlsx': $extType = 'sheet'; break;

                                    case 'ppt':
                                    case 'pptx':
                                    case 'pps':
                                    case 'ppsx': $extType = 'slide'; break;

                                    case 'doc':
                                    case 'docx':
                                    case 'odt':
                                    case 'rtf': $extType = 'doc'; break;

                                    case 'zip':
                                    case 'rar':
                                    case '7z': $extType = 'compact'; break;

                                    case 'pdf': $extType = 'pdf'; break;

                                    default: $extType = 'generic'; break;
                                }

                                $extensions = [
                                    'sheet' => ['icon'=>'fas fa-table',      'class'=>'sheet',   'type'=>'Planilha'],
                                    'slide' => ['icon'=>'fas fa-photo-film', 'class'=>'slide',   'type'=>'Apresentação'],
                                    'compact'=>['icon'=>'fas fa-file-zipper','class'=>'compact', 'type'=>'Compactado'],
                                    'pdf'   => ['icon'=>'fas fa-file-pdf',   'class'=>'pdf',     'type'=>'PDF'],
                                    'doc'   => ['icon'=>'fas fa-file-word',  'class'=>'doc',     'type'=>'Documento'],
                                    'generic'=>['icon'=>'fas fa-file',       'class'=>'generic', 'type'=>'Arquivo'],
                                ];
                                $cfg = $extensions[$extType] ?? $extensions['generic'];

                                $res.= "
                                <div class='box'>
                                <div class='archive'>
                                    <div class='file-type {$cfg['class']}'><p>".icon($cfg['icon'])."</p></div>
                                    <p>$file</p>
                                    <small>{$cfg['type']} (.".($extension ?: 'file').")</small>
                                    <a href='$path' target='_blank' download>Baixar ".icon('fas fa-arrow-right')."</a>
                                </div>
                                </div>";
                            }
                        }

                        $res.= "
                            <button {$appearance['trigger_delete']} class='btn btn-danger' file-name='$file' input-id='$input_id' type='button'>".icon('fas fa-xmark')."</button>
                            ". upload_progress(true) ."
                        </div>";
                    }
                }
            }

        $res.= "</div>";
    }

    elseif ($type == 'audios')
    {
        $min_seconds = $min_seconds ?? MIN_TIME_AUDIO;
        $max_seconds = $max_seconds ?? MAX_TIME_AUDIO;

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

                foreach ($value as $file)
                {
                    if (is_array($file)) continue;

                    $path = file_url("{$type}/{$Src}", $upload_to_s3, $file);

                    $res.= block('audio_player', [
                        'src' =>  $path,
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


    $initial_files = [];
    if (!empty($value)) {
        $initial_files = is_array($value) ? array_values($value) : [$value];
    }
    $res.= "<input type='hidden' input-files data-type='{$type}' name='$name' field='$field_id' value='". json_encode($initial_files, JSON_UNESCAPED_SLASHES) ."' $Required>";

    $res.= "</div>";

    add_asset('footer', "<script src='".base_url."/dist/scripts/filesPreviewer.js' defer></script>");

    $res = fieldset($type_form, $Attr, $res);

    return $res;
}
