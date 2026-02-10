<?php

function field_content_card(string $type_form, array $Attr = [])
{
    $id         = $Attr['id'] ?? 0;
    $counter    = $Attr['counter'] ?? 1;
    $label      = $Attr['label'] ?? '';
    $type       = $Attr['type'] ?? '';
    $questions  = $Attr['questions'] ?? '';

    $depth = !empty($Attr['depth'])
        ? "depth-{$Attr['depth']}"
        : 'depth-0';

    $click = (!empty($Attr['click']) AND $Attr['click'] == true)
        ? "<div class='btn click' id='$counter'>". icon('fas fa-mouse-pointer') ."</div>"
        : '';

    $move = (!empty($Attr['move']) AND $Attr['move'] == true)
        ? "<div class='btn move' id='$counter'>". icon('fas fa-arrows-up-down') ."</div>"
        : '';

    $draggable = (!empty($Attr['move']) AND $Attr['move'] == true)
        ? "draggable-item"
        : '';

    $delete = "<div class='btn-group remove'>
    <button type='button' class='btn remove-confirm dropdown-toggle' data-bs-toggle='dropdown'>". icon('fas fa-trash') ."</button>
    <ul class='dropdown-menu'>
        <p>Deseja excluir este fragmento?</p>
        <li><button class='dropdown-item remove-db' id='$counter' remove-item='{$id}' type='button'>Excluir</button></li>
        <li><button class='dropdown-item' type='button'>Cancelar</button></li>
    </ul>
    </div>";

    $delete = (!empty($Attr['delete']) AND $Attr['delete'] == true)
        ? $delete
        : '';

    $res = "
    <div class='field-content-card $depth fields-for-$type $draggable' id='form-item-$counter'>
    <div class='accordion-item'>

    <h2 class='accordion-header'>
    {$click}
    {$move}
    {$delete}
    <button class='accordion-button collapsed' type='button' data-bs-toggle='collapse' data-bs-target='#collapse$counter' aria-expanded='false' aria-controls='collapse$counter'>
        <span class='name'>{$label}</span>
        <span class='type'>{$type}</span>
    </button>
    </h2>

    <div id='collapse$counter' class='accordion-collapse collapse' data-bs-parent='#field-container-card'>
    <div class='accordion-body'>{$questions}</div>
    </div>

    </div>
    </div>";

    return $res;
}