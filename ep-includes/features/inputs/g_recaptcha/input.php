<?php

function input_g_recaptcha(string $type_form, array $Attr = [])
{
    extract($Attr);

	$res = "<div class='g-recaptcha' data-sitekey='". env('G_RECAPTCHA_SECRETKEY') ."'></div>";
	add_asset('footer', "<script src='https://www.google.com/recaptcha/api.js' async defer></script>");

    $res = fieldset($type_form, $Attr, $res);
	return $res ?? '';
}
