<?php
if(!isset($seg)) exit;

/**
 * Generates a token string using a config array.
 *
 * Payload keys:
 * - mode   (string) 'md5' or 'numeric' (default: 'md5')
 * - length (int)    length for numeric tokens (default: 6)
 *
 * @param array $payload
 * @return string
 */
function token_generate(array $payload = []): string
{
    $mode   = isset($payload['mode']) ? (string) $payload['mode'] : 'md5';
    $length = isset($payload['length']) ? (int) $payload['length'] : 6;

    if ($mode === 'numeric') {
        $length = max(1, min($length, 20));
        $min = (int) str_pad('1', $length, '0'); // e.g. 100000
        $max = (int) str_pad('', $length, '9');  // e.g. 999999
        return (string) random_int($min, $max);
    }

    // Default: md5
    if (function_exists('random_bytes')) {
        return md5(bin2hex(random_bytes(16)) . microtime(true));
    }

    return md5(uniqid((string) mt_rand(), true));
}

/**
 * Creates and persists a token in the database.
 *
 * Payload keys:
 * - type        (string)  required. e.g.: 'nonce', 'password_recovery', 'validation'
 * - user_id     (int)     optional, related user id
 * - ttl_seconds (int)     optional, time-to-live in seconds (null = no expiration)
 * - mode        (string)  optional, token mode: 'md5' or 'numeric' (default: 'md5')
 * - length      (int)     optional, numeric token length (default: 6)
 *
 * @param array $payload
 * @return array|null Returns an array with token data on success or null on failure.
 */
function token_create(array $payload = [], bool $debug = false): ?array
{
    $type = isset($payload['type']) ? trim((string) $payload['type']) : '';

    if ($type === '') {
        return null;
    }

    $user_id        = array_key_exists('user_id', $payload) ? $payload['user_id'] : null;
    $ttl_seconds    = array_key_exists('ttl_seconds', $payload) ? (int) $payload['ttl_seconds'] : null;
    $mode           = isset($payload['mode']) ? (string) $payload['mode'] : 'md5';
    $resource_id    = isset($payload['resource_id']) ? (string) $payload['resource_id'] : null;
    $length         = isset($payload['length']) ? (int) $payload['length'] : 6;
    $meta           = isset($payload['meta']) ? (array) $payload['meta'] : [];
    $overwrite      = isset($payload['overwrite']) ? (bool) $payload['overwrite'] : false;

    // Se overwrite for TRUE, apaga tokens existentes do mesmo user e tipo
    if ($overwrite && $user_id !== null) {
        $typeEsc = addslashes($type);
        $userEsc = (int) $user_id;

        $sql = "DELETE FROM tb_tokens
                WHERE type = '{$typeEsc}'
                  AND user_id = {$userEsc}";
        query_it($sql);
    }

    // Gera novo token
    $token = token_generate([
        'mode'   => $mode,
        'length' => $length,
    ]);

    $now        = date('Y-m-d H:i:s');
    $expires_at = null;

    if (!empty($ttl_seconds) && $ttl_seconds > 0) {
        $expires_at = date('Y-m-d H:i:s', time() + $ttl_seconds);
    }

    $data = [
        'token'         => $token,
        'type'          => $type,
        'meta'          => $meta,
        'resource_id'   => $resource_id,
        'user_id'       => $user_id,
        'status'        => 'available',  // 👈 novo
        'expires_at'    => $expires_at,
        'consumed_at'   => null,         // 👈 novo (opcional, só pra clareza)
        'created_at'    => $now,
    ];

    $inserted = insert('tb_tokens', $data, false, $debug);

    if (!inserted_id()) {
        return null;
    }

    return $data;
}



/**
 * Internal helper: fetches a single token row by conditions.
 *
 * Payload keys:
 * - token   (string) required
 * - type    (string) required
 * - user_id (int)    optional
 *
 * @param array $payload
 * @return array|null
 */
function token_get_row(array $payload = []): ?array
{
    $token  = isset($payload['token']) ? (string) $payload['token'] : '';
    $type   = isset($payload['type']) ? (string) $payload['type'] : '';
    $user_id = array_key_exists('user_id', $payload) ? $payload['user_id'] : null;

    if ($token === '' || $type === '') {
        return null;
    }

    $token_esc = addslashes($token);
    $type_esc  = addslashes($type);

    $where = "token = '{$token_esc}' AND type = '{$type_esc}'";

    if ($user_id !== null) {
        $where .= ' AND user_id = ' . (int) $user_id;
    }

    $sql = "SELECT * FROM tb_tokens WHERE {$where} LIMIT 1";
    $row = get_result($sql);

    return !empty($row) ? $row : null;
}


/**
 * Validates a token.
 *
 * Checks:
 *  - token exists
 *  - type matches
 *  - user_id matches (if provided)
 *  - not expired
 *
 * If 'consume' is true, the token is revoked (deleted) on success.
 *
 * Payload keys:
 * - token    (string) required
 * - type     (string) required
 * - user_id  (int)    optional
 * - consume  (bool)   optional, default true
 *
 * @param array $payload
 * @return array|null Returns the token row on success, or null if invalid/expired.
 */
function token_validate(array $payload = []): ?array
{
    $token   = isset($payload['token']) ? (string) $payload['token'] : '';
    $type    = isset($payload['type']) ? (string) $payload['type'] : '';
    $user_id = array_key_exists('user_id', $payload) ? $payload['user_id'] : null;
    $consume = array_key_exists('consume', $payload) ? (bool) $payload['consume'] : true;

    if ($token === '' || $type === '') {
        return null;
    }

    $row = token_get_row([
        'token'   => $token,
        'type'    => $type,
        'user_id' => $user_id,
    ]);

    if (empty($row)) {
        return null;
    }

    // Se não estiver disponível, já era
    if (isset($row['status']) && $row['status'] !== 'available') {
        return null;
    }

    // Check de expiração
    if (!empty($row['expires_at']) && $row['expires_at'] !== '0000-00-00 00:00:00') {
        $now = date('Y-m-d H:i:s');
        if ($row['expires_at'] < $now) {
            // marca como revogado (expirado)
            token_revoke([
                'token'   => $token,
                'type'    => $type,
                'user_id' => $user_id,
            ]);
            return null;
        }
    }

    // Se for pra consumir, marca como VALIDADO + consumed_at = NOW()
    if ($consume) {
        $id  = isset($row['id']) ? (int) $row['id'] : 0;
        $now = date('Y-m-d H:i:s');

        if ($id > 0) {
            $sql = "
                UPDATE tb_tokens
                   SET status = 'validated',
                       consumed_at = '{$now}'
                 WHERE id = {$id}
                 LIMIT 1
            ";
        } else {
            // fallback por segurança
            $token_esc = addslashes($token);
            $type_esc  = addslashes($type);
            $where     = "token = '{$token_esc}' AND type = '{$type_esc}'";

            if ($user_id !== null) {
                $where .= ' AND user_id = ' . (int) $user_id;
            }

            $sql = "
                UPDATE tb_tokens
                   SET status = 'validated',
                       consumed_at = '{$now}'
                 WHERE {$where}
                 LIMIT 1
            ";
        }

        query_it($sql);
        $row['status']      = 'validated';
        $row['consumed_at'] = $now;
    }

    return $row;
}


/**
 * Revokes (deletes) a token.
 *
 * Payload keys:
 * - token   (string) required
 * - type    (string) required
 * - user_id (int)    optional
 *
 * @param array $payload
 * @return bool
 */
function token_revoke(array $payload = []): bool
{
    $token   = isset($payload['token']) ? (string) $payload['token'] : '';
    $type    = isset($payload['type']) ? (string) $payload['type'] : '';
    $user_id = array_key_exists('user_id', $payload) ? $payload['user_id'] : null;

    if ($token === '' || $type === '') {
        return false;
    }

    $token_esc = addslashes($token);
    $type_esc  = addslashes($type);

    $where = "token = '{$token_esc}' AND type = '{$type_esc}'";

    if ($user_id !== null) {
        $where .= ' AND user_id = ' . (int) $user_id;
    }

    $sql = "
        UPDATE tb_tokens
           SET status = 'revoked'
         WHERE {$where}
    ";

    $res = query_it($sql);

    return (bool) $res;
}


/**
 * Revokes all tokens of a given type for a specific user (or all users).
 *
 * Payload keys:
 * - type    (string) required
 * - user_id (int)    optional
 *
 * @param array $payload
 * @return bool
 */
function token_revoke_by_type_and_user(array $payload = []): bool
{
    $type    = isset($payload['type']) ? (string) $payload['type'] : '';
    $user_id = array_key_exists('user_id', $payload) ? $payload['user_id'] : null;

    if ($type === '') {
        return false;
    }

    $type_esc = addslashes($type);
    $where = "type = '{$type_esc}'";

    if ($user_id !== null) {
        $where .= ' AND user_id = ' . (int) $user_id;
    }

    $sql = "
        UPDATE tb_tokens
           SET status = 'revoked'
         WHERE {$where}
    ";

    $res = query_it($sql);

    return (bool) $res;
}


/**
 * Deletes all expired tokens from the table.
 *
 * Payload is optional and currently unused, but kept for signature consistency.
 *
 * Payload keys (optional, reserved for future use):
 * - now (string) custom datetime; if not provided, uses current time.
 *
 * @param array $payload
 * @return object mysqli result.
 */
function token_cleanup_expired(array $payload = []): int
{
    $now = isset($payload['now']) && trim((string) $payload['now']) !== ''
        ? trim((string) $payload['now'])
        : date('Y-m-d H:i:s');

    $sql = "DELETE FROM tb_tokens 
            WHERE expires_at IS NOT NULL 
              AND expires_at <> '0000-00-00 00:00:00'
              AND expires_at < '{$now}'";

    $result = query_it($sql);

    return $result->mysqli;
}

