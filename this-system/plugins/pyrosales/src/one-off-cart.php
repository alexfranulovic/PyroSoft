<?php
if (!isset($seg)) exit;

/**
 * One-off cart ("no plan, no product" purchase)
 * ---------------------------------------------------------------------
 * One-off items don't have a fixed structure in the database (no tb_plans
 * / tb_products row). While the shopper is on the checkout page, the
 * single item being purchased is kept in the PHP session
 * ($_SESSION['one_off_item']), exactly like a 1-item cart. Everything
 * about that item (name, slug, quantity, unit_price, regular_unit_price,
 * activation_function, deactivation_function) travels inside there.
 *
 * Catalog:
 * The cart is never filled straight from client input. `set` only takes
 * an `item_slug` + `quantity` -- the rest (item_name, prices, activation/
 * deactivation functions) is looked up in $GLOBALS['one_off_catalog'][slug].
 * That global starts empty here; your own code (a functions file, another
 * plugin, wherever) is responsible for populating it, e.g.:
 *
 *   $GLOBALS['one_off_catalog']['turbos'] = [
 *     'item_name'             => 'Turbos',
 *     'unit_price'            => 15.20,
 *     'regular_unit_price'    => 20.00,
 *     'activation_function'   => "user_turbo_manager({order->user_id}, 'add', {items_lines->0->quantity})",
 *     'deactivation_function' => "user_turbo_manager({order->user_id}, 'remove', {items_lines->0->quantity})",
 *   ];
 *
 * This keeps `set` safe for anonymous callers (checkout page, API) since
 * price/behavior always come from server-side code, never from the request.
 *
 * Everything one-off related is meant to go through the single
 * manage_one_off_cart() function below (mirrors user_plan_function_manager()
 * for plans: one function, a $mode switch).
 */

$GLOBALS['one_off_catalog'] = $GLOBALS['one_off_catalog'] ?? [];


/**
 * Normalizes a raw item array into the shape the cart cookie / tb_order_items expects.
 *
 * @param array $item
 * @return array
 */
function normalize_one_off_cart_item(array $item): array
{
    $quantity = (int)($item['quantity'] ?? 1);
    if ($quantity < 1) $quantity = 1;

    $unit_price = (float)($item['unit_price'] ?? 0);
    if ($unit_price < 0) $unit_price = 0;

    $regular_unit_price = isset($item['regular_unit_price'])
        ? (float)$item['regular_unit_price']
        : $unit_price;
    if ($regular_unit_price < 0) $regular_unit_price = $unit_price;

    return [
        'item_type'             => 'one_off',
        'item_name'             => trim((string)($item['item_name'] ?? '')),
        'slug'                  => trim((string)($item['slug'] ?? '')),
        'quantity'              => $quantity,
        'unit_price'            => $unit_price,
        'regular_unit_price'    => $regular_unit_price,
        'activation_function'   => trim((string)($item['activation_function'] ?? '')),
        'deactivation_function' => trim((string)($item['deactivation_function'] ?? '')),
    ];
}


/**
 * Looks up a catalog entry by slug.
 *
 * @param string $slug
 * @return array|null
 */
function get_one_off_catalog_item(string $slug): ?array
{
    $item = $GLOBALS['one_off_catalog'][$slug] ?? null;
    return is_array($item) ? $item : null;
}


/**
 * Reads the current one-off cart item from the session, if any.
 *
 * @return array|null
 */
function get_one_off_cart_item(): ?array
{
    $item = $_SESSION['one_off_item'] ?? null;
    if (!is_array($item) || empty($item['item_name'])) return null;

    return normalize_one_off_cart_item($item);
}


/**
 * Writes the one-off cart item into the session.
 *
 * @param array $item Already-normalized item.
 * @return void
 */
function persist_one_off_cart_session(array $item): void
{
    $_SESSION['one_off_item'] = $item;
}


/**
 * Removes the one-off cart item from the session.
 *
 * Called once the order tied to it has been confirmed as paid (create_order())
 * or once a later payment notification confirms/settles it -- see task list.
 *
 * @return void
 */
function clear_one_off_cart_item(): void
{
    unset($_SESSION['one_off_item']);
}


/**
 * Single entry point to manage the one-off cart.
 *
 * Modes:
 * - 'set'            : Takes { item_slug, quantity }, looks item_slug up in
 *                       $GLOBALS['one_off_catalog'], and stores the resolved
 *                       item in the session, replacing whatever was there
 *                       before. Price/functions always come from the
 *                       catalog entry, never from $params -- that's what
 *                       keeps this mode safe to expose to anonymous callers.
 * - 'update_quantity' : Changes the quantity of whatever item is currently
 *                       in the cart, keeping its name/price/functions as-is.
 * - 'get'            : Returns the current cart item (or null).
 * - 'clear'          : Removes it from the session.
 *
 * @param string $mode
 * @param array  $params
 * @return array
 */
function manage_one_off_cart(string $mode, array $params = []): array
{
    switch ($mode)
    {
        case 'set':
        {
            $slug = trim((string)($params['item_slug'] ?? $params['slug'] ?? ''));
            if ($slug === '') {
                return ['code' => 'error', 'msg' => ['reason' => 'missing_item_slug']];
            }

            $catalog_item = get_one_off_catalog_item($slug);
            if (empty($catalog_item)) {
                return ['code' => 'error', 'msg' => ['reason' => 'item_not_found']];
            }

            $item = normalize_one_off_cart_item(array_merge($catalog_item, [
                'slug'     => $slug,
                'quantity' => $params['quantity'] ?? 1,
            ]));

            persist_one_off_cart_session($item);

            return ['code' => 'success', 'item' => $item];
        }

        case 'update_quantity':
        {
            $current = get_one_off_cart_item();
            if (empty($current)) {
                return ['code' => 'error', 'msg' => ['reason' => 'empty_cart']];
            }

            $quantity = (int)($params['quantity'] ?? 1);
            if ($quantity < 1) $quantity = 1;

            $current['quantity'] = $quantity;
            persist_one_off_cart_session($current);

            return ['code' => 'success', 'item' => $current];
        }

        case 'get':
        {
            $item = get_one_off_cart_item();
            return ['code' => 'success', 'item' => $item];
        }

        case 'clear':
        {
            clear_one_off_cart_item();
            return ['code' => 'success'];
        }
    }

    return ['code' => 'error', 'msg' => ['reason' => 'invalid_mode']];
}
