<?php
if (!isset($seg)) exit;

global $tables;
$tables['tb_carts'] = 'Carrinho';


/**
 * Cart Helpers (DB for logged users, SESSION for guests)
 *
 * - cart_add():    Insert a new cart item (does NOT merge).
 * - cart_update(): Update quantity (inc/dec/set). Add & less are update.
 * - cart_delete(): Remove item by cart item id.
 *
 * Notes:
 * - For guests, cart items live in $_SESSION['cart']['items'].
 * - For logged users, cart items live in tb_carts.
 * - Product rules are enforced using tb_products (stock, limits, etc).
 */

/**
 * Ensures session is started (required for guest carts).
 */
function cart_ensure_session(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE)
    {
        session_start();
    }

    if (!isset($_SESSION['cart']))
    {
        $_SESSION['cart'] = ['items' => []];
    }

    if (!isset($_SESSION['cart']['items']) || !is_array($_SESSION['cart']['items']))
    {
        $_SESSION['cart']['items'] = [];
    }
}

/**
 * Returns the current user id if logged in, otherwise null.
 */
function cart_current_user_id(): ?int
{
    global $current_user;

    if (isset($current_user['id']) && (int)$current_user['id'] > 0)
    {
        return (int)$current_user['id'];
    }

    if (isset($_SESSION['current_user']['id']) && (int)$_SESSION['current_user']['id'] > 0)
    {
        return (int)$_SESSION['current_user']['id'];
    }

    return null;
}

/**
 * Fetch product row from tb_products.
 * This table contains both parent products and variations (children).
 */
function cart_get_product(int $product_id): array
{
    $p = get_result("SELECT * FROM tb_products WHERE id = '{$product_id}' LIMIT 1");
    if (!$p)
    {
        throw new Exception("Product not found: {$product_id}");
    }
    return $p;
}

/**
 * Apply product constraints and return the final allowed quantity.
 *
 * Rules (aligned with your tb_products schema):
 * - simple:
 *    - if manage_stock=1 and backorder=0 => cap by stock_qty
 * - variable:
 *    - you MUST add a variation row (child). If you pass the parent row, throw.
 *    - variation row (child) behaves like a simple item regarding stock.
 * - downloadable:
 *    - cap by download_limit if set, otherwise cap to 10 (safe default)
 */
function cart_apply_quantity_rules(array $product, int $desired_qty): int
{
    if ($desired_qty < 1) $desired_qty = 1;

    $type = (string)($product['product_type'] ?? 'simple');

    // Variable: must be a child variation
    if ($type === 'variable') {
        if (empty($product['product_id'])) {
            throw new Exception("You must select a variation (child product) for a variable product.");
        }
        $type = 'simple';
    }

    // Cart min/max per item
    $min_cart = isset($product['min_cart_qty']) ? (int)$product['min_cart_qty'] : 1;
    if ($min_cart < 1) $min_cart = 1;

    $max_cart = (isset($product['max_cart_qty']) && $product['max_cart_qty'] !== null)
        ? (int)$product['max_cart_qty']
        : null;

    if ($max_cart !== null && $max_cart < $min_cart) {
        $max_cart = $min_cart;
    }

    // Apply min/max first
    if ($desired_qty < $min_cart) $desired_qty = $min_cart;
    if ($max_cart !== null && $desired_qty > $max_cart) $desired_qty = $max_cart;

    // Downloadable limits
    if ($type === 'downloadable') {
        $limit = (isset($product['download_limit']) && $product['download_limit'] !== null)
            ? (int)$product['download_limit']
            : null;

        if ($limit !== null && $limit > 0 && $desired_qty > $limit) {
            $desired_qty = $limit;
        }

        // Re-apply cart max (in case download_limit < max)
        if ($max_cart !== null && $desired_qty > $max_cart) $desired_qty = $max_cart;

        // If download_limit forced below min, keep the forced value (can't invent quantity)
        return max(0, $desired_qty);
    }

    // Stock rules
    if ($type === 'simple') {
        $manage_stock = (int)($product['manage_stock'] ?? 0) === 1;
        $backorder    = (int)($product['backorder'] ?? 0) === 1;

        if ($manage_stock && !$backorder) {
            $stock_qty = isset($product['stock_qty']) ? (int)$product['stock_qty'] : 0;
            if ($stock_qty < 0) $stock_qty = 0;

            if ($desired_qty > $stock_qty) {
                $desired_qty = $stock_qty;
            }

            // IMPORTANT: do NOT force min_cart if stock is lower than min_cart
            // If stock is 0 => return 0 (caller can delete/block)
            return max(0, $desired_qty);
        }

        return $desired_qty;
    }

    return $desired_qty;
}



/**
 * Generate a cart item id for SESSION carts.
 */
function cart_generate_item_id(): string
{
    return bin2hex(random_bytes(8)); // 16 chars
}

/**
 * 1) cart_add() - Insert a new item into the cart
 *
 * This does NOT merge with existing items by design.
 *
 * @param int $product_id A product id from tb_products.
 * @param int $quantity   Desired quantity to insert.
 * @param array|null $meta Optional metadata to store (SESSION only unless your tb_carts has meta_json).
 * @return array{ok:bool, cart_item_id:string|int, quantity:int}
 */
function cart_add(int $product_id, int $quantity = 1, ?array $meta = null): array
{
    $user_id = cart_current_user_id();

    $product = cart_get_product($product_id);
    $final_qty = cart_apply_quantity_rules($product, $quantity);

    // Logged user => DB
    if ($user_id !== null)
    {
        // If item already exists, update quantity instead of inserting a new row
        $existing = get_result("
            SELECT id, quantity
            FROM tb_carts
            WHERE user_id = '{$user_id}' AND product_id = '{$product_id}'
            LIMIT 1
        ");

        if ($existing)
        {
            $new_qty = (int)$existing['quantity'] + $final_qty;
            $new_qty = cart_apply_quantity_rules($product, $new_qty);

            query_it("
                UPDATE tb_carts
                SET quantity = '{$new_qty}', modified_at = NOW()
                WHERE id = '{$existing['id']}' AND user_id = '{$user_id}'
            ");

            return [
                'code' => 'success',
                'cart_item_id' => (int)$existing['id'],
                'quantity' => (int)$new_qty,
            ];
        }

        // Otherwise insert
        query_it("
            INSERT INTO tb_carts (user_id, product_id, quantity, created_at, modified_at)
            VALUES ('{$user_id}', '{$product_id}', '{$final_qty}', NOW(), NOW())
        ");

        $row = get_result("
            SELECT id, quantity
            FROM tb_carts
            WHERE user_id = '{$user_id}' AND product_id = '{$product_id}'
            ORDER BY id DESC
            LIMIT 1
        ");

        return [
            'code' => 'success',
            'cart_item_id' => $row ? (int)$row['id'] : 0,
            'quantity' => $row ? (int)$row['quantity'] : $final_qty,
        ];
    }

    // Guest => SESSION
    cart_ensure_session();

    // If item already exists in session, update quantity
    foreach ($_SESSION['cart']['items'] as $cid => $item)
    {
        if ((int)($item['product_id'] ?? 0) === $product_id)
        {
            $new_qty = (int)($item['quantity'] ?? 0) + $final_qty;
            $new_qty = cart_apply_quantity_rules($product, $new_qty);

            $_SESSION['cart']['items'][$cid]['quantity'] = $new_qty;
            $_SESSION['cart']['items'][$cid]['modified_at'] = date('Y-m-d H:i:s');

            return [
                'code' => 'success',
                'cart_item_id' => $cid,
                'quantity' => (int)$new_qty,
            ];
        }
    }

    // Otherwise insert new session item
    $cart_item_id = cart_generate_item_id();
    $_SESSION['cart']['items'][$cart_item_id] = [
        'id'         => $cart_item_id,
        'product_id' => $product_id,
        'quantity'   => $final_qty,
        'created_at' => date('Y-m-d H:i:s'),
        'modified_at'=> date('Y-m-d H:i:s'),
    ];

    return [
        'code' => 'success',
        'cart_item_id' => $cart_item_id,
        'quantity' => $final_qty,
    ];
}


/**
 * 2) cart_update() - Update an existing cart item quantity
 *
 * This function supports:
 * - inc: quantity += $quantity
 * - dec: quantity -= $quantity
 * - set: quantity  = $quantity
 *
 * If final quantity <= 0, the item is deleted.
 *
 * @param string|int $cart_item_id DB id (int) or SESSION id (string)
 * @param int $quantity Change value (inc/dec) or final qty (set)
 * @param string $mode One of: inc|dec|set
 * @return array{ok:bool, cart_item_id:string|int, quantity:int, deleted:bool}
 */
function cart_update($cart_item_id, int $quantity, string $mode = 'inc'): array
{
    $user_id = cart_current_user_id();

    if (!in_array($mode, ['inc', 'dec', 'set'], true)) {
        throw new Exception("Invalid update mode: {$mode}");
    }

    if ($quantity < 1 && $mode !== 'set') {
        $quantity = 1;
    }

    // Logged user => DB
    if ($user_id !== null)
    {
        $id = (int)$cart_item_id;

        $cart = get_result("SELECT * FROM tb_carts WHERE id = '{$id}' AND user_id = '{$user_id}' LIMIT 1");
        if (!$cart) {
            throw new Exception("Cart item not found: {$id}");
        }

        $product_id = (int)$cart['product_id'];
        $product    = cart_get_product($product_id);

        $current_qty = (int)$cart['quantity'];
        $desired_qty = $current_qty;

        if ($mode === 'inc') $desired_qty = $current_qty + $quantity;
        if ($mode === 'dec') $desired_qty = $current_qty - $quantity;
        if ($mode === 'set') $desired_qty = $quantity;

        if ($desired_qty <= 0)
        {
            query_it("DELETE FROM tb_carts WHERE id = '{$id}' AND user_id = '{$user_id}'");
            return [
                'code' => 'success',
                'cart_item_id' => $id,
                'quantity' => 0,
                'deleted' => true,
            ];
        }

        $final_qty = cart_apply_quantity_rules($product, $desired_qty);

        query_it("UPDATE tb_carts SET quantity = '{$final_qty}', modified_at = NOW() WHERE id = '{$id}' AND user_id = '{$user_id}'");

        return [
            'code' => 'success',
            'cart_item_id' => $id,
            'quantity' => $final_qty,
            'deleted' => false,
        ];
    }

    // Guest => SESSION
    cart_ensure_session();

    $id = (string)$cart_item_id;

    if (!isset($_SESSION['cart']['items'][$id])) {
        throw new Exception("Cart item not found: {$id}");
    }

    $cart = $_SESSION['cart']['items'][$id];

    $product_id = (int)$cart['product_id'];
    $product    = cart_get_product($product_id);

    $current_qty = (int)$cart['quantity'];
    $desired_qty = $current_qty;

    if ($mode === 'inc') $desired_qty = $current_qty + $quantity;
    if ($mode === 'dec') $desired_qty = $current_qty - $quantity;
    if ($mode === 'set') $desired_qty = $quantity;

    if ($desired_qty <= 0) {
        unset($_SESSION['cart']['items'][$id]);
        return [
            'code' => 'success',
            'cart_item_id' => $id,
            'quantity' => 0,
            'deleted' => true,
        ];
    }

    $final_qty = cart_apply_quantity_rules($product, $desired_qty);

    $_SESSION['cart']['items'][$id]['quantity'] = $final_qty;
    $_SESSION['cart']['items'][$id]['modified_at'] = date('Y-m-d H:i:s');

    return [
        'code' => 'success',
        'cart_item_id' => $id,
        'quantity' => $final_qty,
        'deleted' => false,
    ];
}

/**
 * 3) cart_delete() - Delete a cart item
 *
 * @param string|int $cart_item_id DB id (int) or SESSION id (string)
 * @return array{ok:bool, cart_item_id:string|int}
 */
function cart_delete($cart_item_id): array
{
    $user_id = cart_current_user_id();

    // Logged user => DB
    if ($user_id !== null)
    {
        $id = (int)$cart_item_id;
        query_it("DELETE FROM tb_carts WHERE id = '{$id}' AND user_id = '{$user_id}'");
        return ['code' => 'success', 'cart_item_id' => $id];
    }

    // Guest => SESSION
    cart_ensure_session();

    $id = (string)$cart_item_id;
    unset($_SESSION['cart']['items'][$id]);

    return ['code' => 'success', 'cart_item_id' => $id];
}


function cart_clear(): array
{
    $user_id = cart_current_user_id();

    // Logged user => DB
    if ($user_id !== null)
    {
        query_it("DELETE FROM tb_carts WHERE user_id = '{$user_id}'");
        return ['code' => 'success', 'cleared' => true];
    }

    // Guest => SESSION
    cart_ensure_session();
    $_SESSION['cart']['items'] = [];

    return ['code' => 'success', 'cleared' => true];
}


function get_cart(): array
{
    $user_id = cart_current_user_id();
    $items = [];
    $subtotal = 0;

    // Logged user => DB
    if ($user_id !== null)
    {
        $rows = get_results("
            SELECT *
            FROM tb_carts
            WHERE user_id = '{$user_id}'
        ") ?: [];

        foreach ($rows as $row)
        {
            $product = cart_get_product((int)$row['product_id']);

            $price = $product['sale_price'] > 0
                ? (float)$product['sale_price']
                : (float)$product['regular_price'];

            $line_total = round($price * (int)$row['quantity'], 2);
            $subtotal += $line_total;

            $items[] = [
                'cart_item_id' => (int)$row['id'],
                'product_id'   => (int)$row['product_id'],
                'name'         => $product['name'],
                'quantity'     => (int)$row['quantity'],
                'unit_price'   => $price,
                'line_total'   => $line_total,
            ];
        }
    }
    else
    {
        cart_ensure_session();

        foreach ($_SESSION['cart']['items'] as $cid => $row)
        {
            $product = cart_get_product((int)$row['product_id']);

            $price = $product['sale_price'] > 0
                ? (float)$product['sale_price']
                : (float)$product['regular_price'];

            $line_total = round($price * (int)$row['quantity'], 2);
            $subtotal += $line_total;

            $items[] = [
                'cart_item_id' => $cid,
                'product_id'   => (int)$row['product_id'],
                'name'         => $product['name'],
                'quantity'     => (int)$row['quantity'],
                'unit_price'   => $price,
                'line_total'   => $line_total,
            ];
        }
    }

    return [
        'code' => 'success',
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'total_items' => count($items),
    ];
}
