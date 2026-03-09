<?php
if (!isset($seg)) exit;

register_rest_route('cart-manager', [
  'methods'  => ['GET', 'POST'],
  'callback' => function ()
  {
    $data = ($_SERVER['REQUEST_METHOD'] === 'POST')
        ? ($_POST ?: (json_decode(file_get_contents('php://input'), true) ?: []))
        : $_GET;

    $action   = trim((string)($data['action'] ?? ''));
    $qty      = (int)($data['quantity'] ?? 1);
    if ($qty < 1) $qty = 1;

    $id       = $data['id'] ?? null;                 // cart_item_id (DB int or session string)
    $product  = (int)($data['product_id'] ?? 0);     // only for insert

    try
    {
      if ($action === 'insert')
      {
        if ($product <= 0) throw new Exception("product_id is required.");
        print_r($_SESSION['cart']);
        return cart_add($product, $qty);
      }

      if ($action === 'delete')
      {
        if (!$id) throw new Exception("id is required.");
        print_r($_SESSION['cart']);
        return cart_delete($id);
      }

      if (in_array($action, ['inc', 'dec', 'set'], true))
      {
        if (!$id) throw new Exception("id is required.");
        print_r($_SESSION['cart']);
        return cart_update($id, $qty, $action);
      }

      throw new Exception("Invalid action.");
    }
    catch (Exception $e)
    {
      return ['code' => 'error', 'error' => $e->getMessage()];
    }
  },
  'permission_callback' => '__return_true',
]);
