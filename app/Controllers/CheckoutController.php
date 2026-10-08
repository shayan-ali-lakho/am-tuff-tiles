<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Cart;
use App\Models\Order;
use RuntimeException;

/** Checkout (cash on delivery), the order confirmation page and "My orders". Login required. */
final class CheckoutController
{
    public function show(): void
    {
        $user = $this->requireLogin('/checkout');
        $cart = Cart::summary();

        if ($cart['lines'] === []) {
            if ($cart['notices'] !== []) {
                flash('warning', implode(' ', $cart['notices']));
                redirect('/cart');
            }

            flash('warning', 'Your cart is empty.');
            redirect('/shop');
        }

        $old = flash_get('old', []);
        $old = is_array($old) && $old !== [] ? $old : [
            'customer_name'    => (string) $user['full_name'],
            'customer_phone'   => (string) ($user['phone'] ?? ''),
            'customer_email'   => (string) $user['email'],
            'shipping_address' => (string) ($user['address'] ?? ''),
            'shipping_city'    => (string) ($user['city'] ?? ''),
            'notes'            => '',
        ];

        echo view('checkout/index', [
            'title'  => 'Checkout',
            'cart'   => $cart,
            'errors' => flash_get('errors', []),
            'old'    => $old,
        ]);
    }

    public function place(): void
    {
        $user = $this->requireLogin('/checkout');
        $cart = Cart::summary();

        if ($cart['lines'] === []) {
            if ($cart['notices'] !== []) {
                flash('warning', implode(' ', $cart['notices']));
                redirect('/cart');
            }

            flash('warning', 'Your cart is empty.');
            redirect('/shop');
        }

        if ($cart['notices'] !== []) {
            flash('warning', implode(' ', $cart['notices']) . ' Please check your cart and confirm again.');
            redirect('/cart');
        }

        $in = [
            'customer_name'    => trim((string) ($_POST['customer_name'] ?? '')),
            'customer_phone'   => trim((string) ($_POST['customer_phone'] ?? '')),
            'customer_email'   => strtolower(trim((string) ($_POST['customer_email'] ?? ''))),
            'shipping_address' => trim((string) ($_POST['shipping_address'] ?? '')),
            'shipping_city'    => trim((string) ($_POST['shipping_city'] ?? '')),
            'notes'            => trim((string) ($_POST['notes'] ?? '')),
        ];

        $errors = [];

        if (mb_strlen($in['customer_name']) < 2 || mb_strlen($in['customer_name']) > 120) {
            $errors['customer_name'] = 'Please enter your full name.';
        }
        if (preg_match('/^[0-9+()\-\s]{7,20}$/', $in['customer_phone']) !== 1) {
            $errors['customer_phone'] = 'Please enter a phone number we can call (7 to 20 digits).';
        }
        if (!filter_var($in['customer_email'], FILTER_VALIDATE_EMAIL) || strlen($in['customer_email']) > 190) {
            $errors['customer_email'] = 'Please enter a valid email address.';
        }
        if (mb_strlen($in['shipping_address']) < 5 || mb_strlen($in['shipping_address']) > 255) {
            $errors['shipping_address'] = 'Please enter the full delivery address (5 to 255 characters).';
        }
        if (mb_strlen($in['shipping_city']) < 2 || mb_strlen($in['shipping_city']) > 100) {
            $errors['shipping_city'] = 'Please enter your city.';
        }
        if (mb_strlen($in['notes']) > 500) {
            $errors['notes'] = 'Notes can be at most 500 characters.';
        }

        if ($errors !== []) {
            flash('errors', $errors);
            flash('old', $in);
            redirect('/checkout');
        }

        $raw = [];
        foreach ($cart['lines'] as $line) {
            $raw[(int) $line['id']] = (int) $line['qty'];
        }

        try {
            $number = Order::place((int) $user['id'], $raw, [
                'name'    => $in['customer_name'],
                'phone'   => $in['customer_phone'],
                'email'   => $in['customer_email'],
                'address' => $in['shipping_address'],
                'city'    => $in['shipping_city'],
                'notes'   => $in['notes'],
            ]);
        } catch (RuntimeException $e) {
            flash('error', $e->getMessage());
            flash('old', $in);
            redirect('/cart');
        }

        Cart::clear();
        \App\Core\Mailer::sendLater(static function () use ($number): void {
            Order::notify($number);
        });
        redirect('/order/' . rawurlencode($number));
    }

    public function confirmation(string $number): void
    {
        $user = $this->requireLogin('/orders');

        // Order numbers look like AM-251008-1A2B3C; anything else is simply "not found"
        $order = preg_match('/^AM-\d{6}-[0-9A-F]{6}$/', $number) === 1
            ? Order::findForContact($number, (int) $user['id'])
            : null;

        if ($order === null) {
            abort(404);
        }

        echo view('checkout/confirmation', ['title' => 'Order ' . $order['order_number'], 'order' => $order]);
    }

    public function orders(): void
    {
        $user = $this->requireLogin('/orders');

        echo view('checkout/orders', ['title' => 'My orders', 'orders' => Order::forContact((int) $user['id'])]);
    }

    private function requireLogin(string $returnTo): array
    {
        $user = Auth::user();

        if ($user === null) {
            flash('info', 'Please log in or create an account to place your order.');
            redirect('/login?next=' . rawurlencode($returnTo));
        }

        return $user;
    }
}
