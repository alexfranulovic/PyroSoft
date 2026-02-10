<?php

function input_hidden(string $type_form, array $Attr = [])
{
    extract($Attr);
	return "<input $attributes type='hidden' name='$name' id='$input_id' value='{$Value}'>";
}