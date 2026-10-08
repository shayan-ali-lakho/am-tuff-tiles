<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Cart;
use App\Models\Product;

/** The shopping cart. POST requests are CSRF-checked by the router. */
final class CartController
{
    public function show(): void
    {
        $cart = Cart::summary();

        echo view('cart/index', ['title' => 'Your cart', 'cart' => $cart]);
    }

    public function add(): void
    {
        $id  = (int) ($_POST['product_id'] ?? 0);
        $qty = $this->quantity($_POST['qty'] ?? 1);
        $back = safe_next($_POST['back'] ?? null, '/shop');

        $product = Product::forCart([$id])[$id] ?? null;

        if ($product === null || (int) $product['stock_qty'] <= 0) {
            flash('error', 'Sorry, this product is not available right now.');
            redirect($back);
        }

        $stock    = (int) $product['stock_qty'];
        $inCart   = (int) ($_SESSION['cart'][$id] ?? 0);
        $canAdd   = $stock - $inCart;

        if ($canAdd <= 0) {
            flash('warning', 'You already have all ' . $stock . ' available in your cart.');
            redirect('/cart');
        }

        if ($qty > $canAdd) {
            $qty = $canAdd;
            flash('warning', 'Only ' . $stock . ' available, so we added ' . $canAdd . '.');
        } else {
            flash('success', $product['name'] . ' was added to your cart.');
        }

        if (!Cart::add($id, $qty)) {
            flash('error', 'Your cart is full. Please place your order or remove something first.');
        }

        redirect('/cart');
    }

    public function update(): void
    {
        $id  = (int) ($_POST['product_id'] ?? 0);
        $qty = $this->quantity($_POST['qty'] ?? 1);

        Cart::set($id, $qty);
        // quantity above the stock is lowered and explained by Cart::summary() when the page loads
        redirect('/cart');
    }

    public function remove(): void
    {
        Cart::remove((int) ($_POST['product_id'] ?? 0));
        flash('success', 'Item removed from your cart.');
        redirect('/cart');
    }

    /** A whole number from 1 to 999; anything else becomes 1. */
    private function quantity(mixed $value): int
    {
        $number = filter_var($value, FILTER_VALIDATE_INT);

        return is_int($number) ? max(1, min(Cart::MAX_QTY, $number)) : 1;
    }
}
