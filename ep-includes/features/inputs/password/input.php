<?php

function input_password(string $type_form, array $Attr = [])
{
    global $info, $login_settings;

    extract($Attr);
    $res = '';

    $type = $type ?? 'default';
    $name = $name ?? 'password';
    // $Required = !empty($Required) ? true : false;
    $can_genarate = !empty($can_genarate) ? true : false;
    $Value = '';

    add_asset('footer', "<script src='".base_url."/dist/scripts/passwordInput.js' defer></script>");

    /**
     *
     * Type: New password
     * According with password must and confirmation below
     *
     */
    if ($type == 'new-password')
    {
        // New password form.
        $password_must = '';

        // Must has number
        if (!empty($login_settings['password_must']['has_number']))
        $password_must.= "<span class='text-danger has-number'>Deve possuir um número</span>";

        // Must has upper
        if (!empty($login_settings['password_must']['has_upper']))
        $password_must.= "<span class='text-danger has-upper'>Deve possuir uma letra maíuscula</span>";

        // Must has special_characters
        if (!empty($login_settings['password_must']['has_special_characters']))
        $password_must.= "<span class='text-danger has-special-characters'>Deve possuir um caracter especial</span>";

        // Must has a min and a max length
        if (!empty($login_settings['password_must']['length']))
        {
            $length = $login_settings['password_must']['length'];
            $password_must.= "<span class='text-danger length'>Deve ter entre {$length['min']} e {$length['max']} caracteres</span>";
        }

        $res = input(
          'password',
          'insert',
          [
            'size' => 'col-12',
            'label' => 'Digite a nova senha',
            'Placeholder' => 'Senha',
            'attributes' => "data-password-must:(); minlength:({$length['min']}); maxlength:({$length['max']}); autocomplete:(new-password);",
            'name' => "{$name}[value]",
            'can_genarate' => true,
            'Required' => $Required,
            'Alert' => $password_must,
          ]
        ) .
        input(
          'password',
          'insert',
          [
            'size' => 'col-12',
            'label' => 'Repita a nova senha',
            'Placeholder' => 'Senha',
            'name' => "{$name}[repeat]",
            'attributes' => 'data-repeat-password:(); autocomplete:(new-password);',
            'Required' => $Required,
          ]
        );

        return $res;
    }


    /**
     *
     * Type: Default
     *
     */
    $input = "<input
        class='$class'
        type='password'
        $attributes
        placeholder='$Placeholder'
        name='$name'
        id='$input_id'
        value=\"{$Value}\"
        $Required
        $disabled
        $readonly>";


    /**
     * Add floating label.
     */
    $label = input_label($Attr);
    if ($style == 'floating')
    {
        $input = "
        <div class='form-floating'>
            $input
            $label
        </div>";
    } else {
        $res.= $label;
    }

    $generate_btn = !$can_genarate ? '' : "
    <button type='button' generate-password title='Gerar senha forte' class='btn btn-st'>
        ".icon('fas fa-key')."
    </button>";

    /**
     * Build the input group
     */
    $res.= "
    <div class='input-group'>
        {$input}
        {$generate_btn}
        <button type='button' title='Mostrar senha' show-hide-password class='btn btn-st'>
            ".icon('fas fa-eye')."
        </button>
    </div>";

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}
