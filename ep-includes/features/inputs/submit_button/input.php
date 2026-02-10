<?php

function input_submit_button(string $type_form, array $Attr = [])
{
    extract($Attr);
    $res = '';

    global $config;

    $Value = $Value ?? 'Enviar';
    $class = $class ?? 'btn-primary';

    $allow_schedule = (!empty($allow_schedule) && $allow_schedule)
     ? true
     : false;

    $block = (!empty($block) && $block)
     ? 'block'
     : '';

    $btn_block = (!$allow_schedule && $block == 'block') ? 'btn-block' : '';

    $button = "
    <button class='btn $class $btn_block' $attributes $disabled type='submit' name='$name' id='$input_id'>
    <span class='spinner-border spinner-border-sm' role='status' aria-hidden='true' style='display: none;'></span>
    <span>$Value</span>
    </button>";


    $schedule = '';
    if ($allow_schedule)
    {
        $schedule = "
        <div class='schedule-event' role='group' data-bs-auto-close='outside'>
        <button type='button' class='btn $class dropdown-toggle' data-bs-toggle='dropdown' aria-expanded='false' data-bs-auto-close='outside'>
          ". icon('fas fa-calendar-days') ."
        </button>
        <ul class='dropdown-menu'>

            <p>Programar para:</p>
            <small>Fuso horário: {$config['timezone']}</small>

            <div class='form-row'>";
            $schedule.= input(
                'basic',
                $type_form,
                [
                    'type' => 'date',
                    'size' => 'col-12',
                    'label' => 'Data',
                    'name' => 'schedule_event[date]',
                    'Alert' => "Cuidado ao programar eventos com mídia após ". TIME_TO_DELETE_TEMP_FILES ." dias.",
                ]
            ) . input(
                'basic',
                $type_form,
                [
                    'type' => 'time',
                    'size' => 'col-12',
                    'label' => 'Horário',
                    'name' => 'schedule_event[hour]',
                    'Value' => EVENT_DEFAULT_HOUR,
                ]
            );
            $schedule.= "
            </div>
        </ul>
        </div>";
    }

    if (!$allow_schedule) {
        $res.= $button;
    }
    elseif ($allow_schedule)
    {
        $res.= "
        <div class='send-form-group $block' role='group' aria-label='Enviar formulário'>
        {$button}
        {$schedule}
        </div>";
    }

    if (!$is_child) {
        $res = fieldset($type_form, $Attr, $res);
    }

    return $res;
}
