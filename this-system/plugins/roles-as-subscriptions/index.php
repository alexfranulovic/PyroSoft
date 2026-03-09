<?php
if (!isset($seg)) exit;

global $tables;

function get_plans(string $mode = '', array $attr = [])
{
    $id = $attr['id'] ?? '';

    if ($mode == 'list') {
        return get_results("SELECT id as value, name as display FROM tb_user_roles WHERE type = 'plan' AND user_id IS NULL");
    }

    // if ($mode == 'crud')
    // {
    //     $trigger = $attr['trigger'] ?? '';

    //     return get_results("
    //         SELECT role_id as value FROM tb_user_role_permissions
    //         WHERE
    //             crud_id = '{$id}'
    //             AND allowed = 1
    //             AND action_trigger = '{$trigger}'
    //     ");
    // }

    // if ($mode == 'page')
    // {
    //     return get_results("
    //         SELECT role_id as value FROM tb_user_role_permissions
    //         WHERE page_id = '{$id}' AND allowed = 1");
    // }

    // if ($mode == 'custom')
    // {
    //     return get_results("
    //         SELECT role_id as value FROM tb_user_role_permissions
    //         WHERE permission_id = '{$id}' AND allowed = 1");
    // }

    return get_results("SELECT * FROM tb_user_roles WHERE type = 'plan' AND user_id IS NULL");
}

function get_plan(string $plan_id = '')
{
    return get_result("SELECT * FROM tb_user_roles WHERE id = '{$plan_id}' AND type = 'plan'");
}

// dump( get_plans() );
