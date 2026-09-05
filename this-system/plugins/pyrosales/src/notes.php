<?php
if (!isset($seg)) exit;

/**
 * Creates a new order note.
 *
 * @param array $data {
 *     @type int         $order_id     Required.
 *     @type string      $content      Required.
 *     @type string      $visibility   Optional. 'private' or 'customer'. Default 'private'.
 *     @type string      $note_type    Optional. manual|status_change|payment_event|gateway_payload|system
 *     @type string|null $title        Optional.
 *     @type string|null $body_type    Optional. Default 'default'.
 *     @type int|null    $created_by   Optional.
 * }
 *
 * @return array
 */
function add_order_note(array $data, bool $debug = false): array
{
    $required    = ['order_id', 'content'];
    $mode        = $data['mode'] ?? [];
    $note_id     = $data['note_id'] ?? 0;

    if (!in_array($mode, ['update', 'insert'], true)) {
        $mode = 'insert';
    }

    if ($note_id <= 0 && $mode == 'update')
    {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Invalid note_id']
        ];
    }

    foreach ($required as $field)
    {
        if (empty($data[$field]))
        {
            return [
                'code' => 'error',
                'msg'  => ['reason' => "Missing required field: {$field}"]
            ];
        }
    }

    $visibility = $data['visibility'] ?? 'private';
    $note_type  = $data['note_type']  ?? 'manual';

    if (!in_array($visibility, ['private', 'customer'], true))
    {
        return [
            'code' => 'error',
            'msg'  => ['reason' => 'Invalid visibility']
        ];
    }

    $insert = [
        'order_id'   => (int) $data['order_id'],
        'visibility' => $visibility,
        'note_type'  => $note_type,
        'title'      => $data['title'] ?? null,
        'content'    => $data['content'],
        'body_type'  => $data['body_type']  ?? 'default',
        'created_by' => $data['created_by'] ?? null,
    ];

    // Lights, camera & action.
    $mode('tb_order_notes', $insert, false, $debug);
    $verifier = verifier($mode);

    return [
        'code' => $verifier ? 'success' : 'error',
        'id' => inserted_id()
    ];
}

/**
 * Retrieves order notes.
 *
 * @param array $params {
 *     @type int   $order_id      Required.
 *     @type bool  $show_private  Optional. Default false.
 *     @type array $note_type     Optional. Empty means all note types.
 * }
 *
 * @return array
 */
function get_order_notes(array $params = [], bool $debug = false): array
{
    $order_id = (int) ($params['order_id'] ?? 0);

    if ($order_id <= 0) {
        return [];
    }

    $show_private = !empty($params['show_private']);
    $private_sql  = !$show_private ? "AND n.visibility = 'customer'" : '';

    $note_type = (array) ($params['note_type'] ?? []);
    $note_type = array_filter($note_type);

    $note_type_sql = '';

    if (!empty($note_type)) {
        $note_type = array_map('addslashes', $note_type);
        $note_type_sql = "AND n.note_type IN ('" . implode("','", $note_type) . "')";
    }

    return get_results("
        SELECT
            n.*,
            COALESCE(u.first_name, 'System') AS first_name
        FROM tb_order_notes AS n
        LEFT JOIN tb_users AS u ON u.id = n.created_by
        WHERE n.order_id = '{$order_id}'
            {$note_type_sql}
            {$private_sql}
        ORDER BY n.created_at ASC
    ", false, $debug);
}
