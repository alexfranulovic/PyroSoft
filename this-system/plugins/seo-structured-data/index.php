<?php
if (!isset($seg)) exit;

global $available_structured_data;
$available_structured_data = [
    [ 'value' => 'organization', 'display' => 'Organização'],
    [ 'value' => 'video', 'display' => 'Vídeo'],
    [ 'value' => 'article', 'display' => 'Artigo'],
    [ 'value' => 'local_business', 'display' => 'Negócio local'],
];
