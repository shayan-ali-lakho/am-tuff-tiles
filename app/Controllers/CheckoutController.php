<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\ImageUploader;
use App\Models\Cart;
use App\Models\Order;
use RuntimeException;

/**
 * Checkout, the order confirmation page and "My orders".
 *
 * Nobody has to log in to order. A guest sees the confirmation of the order they just placed because its number is
 * remembered in their session; logged-in customers also see their past orders under "My orders".
 * Payment is cash on delivery, or EasyPaisa (advance or full amount) with a screenshot of the transaction.
 */
final class CheckoutController
{
    private const REMEMBERED_ORDERS = 20;
    private const MAX_ORDERS_PER_HOUR = 5;

    public function show(): void
    {
        $user = Auth::user();
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
            'customer_name'    => (string) ($user['full_name'] ?? ''),
            'customer_phone'   => (string) ($user['phone'] ?? ''),
            'customer_email'   => (string) ($user['email'] ?? ''),
            'shipping_address' => (string) ($user['address'] ?? ''),
            'shipping_city'    => (string) ($user['city'] ?? ''),
            'notes'            => '',
            'payment_method'   => 'cod',
            'paid_amount'      => '',
        ];

        echo view('checkout/index', [
            'title'     => 'Checkout',
            'cart'      => $cart,
            'errors'    => flash_get('errors', []),
            'old'       => $old,
            'isGuest'   => $user === null,
            'easypaisa' => easypaisa(),
        ]);
    }

    public function place(): void
    {
        $user = Auth::user();

        // Hidden field that real visitors never see or fill in; simple bots do. Pretend all is well and do nothing.
        if (trim((string) ($_POST['hp_check'] ?? '')) !== '') {
            redirect('/shop');
        }

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

        // Guests can order, so slow down anyone placing many orders in a short time
        $recent = array_values(array_filter(
            is_array($_SESSION['order_times'] ?? null) ? $_SESSION['order_times'] : [],
            static fn ($t): bool => is_int($t) && $t > time() - 3600
        ));

        if (count($recent) >= self::MAX_ORDERS_PER_HOUR) {
            flash('error', 'You have placed several orders in a short time. Please call us to place more.');
            redirect('/cart');
        }

        $easypaisa = easypaisa();

        $in = [
            'customer_name'    => trim((string) ($_POST['customer_name'] ?? '')),
            'customer_phone'   => trim((string) ($_POST['customer_phone'] ?? '')),
            'customer_email'   => strtolower(trim((string) ($_POST['customer_email'] ?? ''))),
            'shipping_address' => trim((string) ($_POST['shipping_address'] ?? '')),
            'shipping_city'    => trim((string) ($_POST['shipping_city'] ?? '')),
            'notes'            => trim((string) ($_POST['notes'] ?? '')),
            'payment_method'   => trim((string) ($_POST['payment_method'] ?? 'cod')),
            'paid_amount'      => trim((string) ($_POST['paid_amount'] ?? '')),
        ];

        $errors = [];

        if (mb_strlen($in['customer_name']) < 2 || mb_strlen($in['customer_name']) > 120) {
            $errors['customer_name'] = 'Please enter your full name.';
        }
        if (preg_match('/^[0-9+()\-\s]{7,20}$/', $in['customer_phone']) !== 1) {
            $errors['customer_phone'] = 'Please enter a phone number we can call (7 to 20 digits).';
        }
        // The email is optional; when given it must be valid
        if ($in['customer_email'] !== '' && (!filter_var($in['customer_email'], FILTER_VALIDATE_EMAIL) || strlen($in['customer_email']) > 190)) {
            $errors['customer_email'] = 'That email address does not look right. You can also leave it empty.';
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

        // Payment
        $method = 'cod';
        $paid   = 0;
        $file   = null;

        if ($in['payment_method'] === 'easypaisa') {
            if (!$easypaisa['enabled']) {
                $errors['payment_method'] = 'EasyPaisa is not available right now. Please choose cash on delivery.';
            } else {
                $method = 'easypaisa';

                $amount = parse_price($in['paid_amount']);
                if ($amount === null || $amount < 100) {
                    $errors['paid_amount'] = 'Enter the amount you sent by EasyPaisa, for example 1500.';
                } elseif ($amount > $cart['total']) {
                    $errors['paid_amount'] = 'The amount cannot be more than the order total (' . money($cart['total']) . ').';
                } else {
                    $paid = $amount;
                }

                $candidate = $_FILES['payment_screenshot'] ?? null;
                if (
                    !is_array($candidate)
                    || is_array($candidate['tmp_name'] ?? null)
                    || (int) ($candidate['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE
                ) {
                    $errors['payment_screenshot'] = 'Please attach a screenshot of your EasyPaisa payment.';
                } else {
                    $file = $candidate;
                }
            }
        } elseif ($in['payment_method'] !== 'cod') {
            $errors['payment_method'] = 'Please choose how you want to pay.';
        }

        // Only look at the screenshot itself once everything else is fine, so a mistake elsewhere does not store a file
        $proof = null;

        if ($errors === [] && $method === 'easypaisa' && $file !== null) {
            try {
                $proof = ImageUploader::storeProof($file);
            } catch (RuntimeException $e) {
                $errors['payment_screenshot'] = $e->getMessage();
            }
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
            $number = Order::place($user !== null ? (int) $user['id'] : null, $raw, [
                'name'    => $in['customer_name'],
                'phone'   => $in['customer_phone'],
                'email'   => $in['customer_email'],
                'address' => $in['shipping_address'],
                'city'    => $in['shipping_city'],
                'notes'   => $in['notes'],
                'payment' => ['method' => $method, 'proof' => $proof, 'paid_paisa' => $paid],
            ]);
        } catch (RuntimeException $e) {
            if ($proof !== null) {
                ImageUploader::removeProof($proof);
            }

            flash('error', $e->getMessage());
            flash('old', $in);
            redirect('/cart');
        }

        Cart::clear();

        // Remember the order for this visitor so a guest can open the confirmation page
        $mine = is_array($_SESSION['my_orders'] ?? null) ? $_SESSION['my_orders'] : [];
        $mine[] = $number;
        $_SESSION['my_orders'] = array_slice($mine, -self::REMEMBERED_ORDERS);

        $recent[] = time();
        $_SESSION['order_times'] = $recent;

        \App\Core\Mailer::sendLater(static function () use ($number): void {
            Order::notify($number);
        });
        redirect('/order/' . rawurlencode($number));
    }

    public function confirmation(string $number): void
    {
        $user = Auth::user();

        // Order numbers look like AM-251008-1A2B3C; anything else is simply "not found"
        $order = preg_match('/^AM-\d{6}-[0-9A-F]{6}$/', $number) === 1 ? Order::findByNumber($number) : null;

        if ($order === null || !$this->mayView($order, $user)) {
            abort(404);
        }

        echo view('checkout/confirmation', [
            'title'   => 'Order ' . $order['order_number'],
            'order'   => $order,
            'isGuest' => $user === null,
        ]);
    }

    public function orders(): void
    {
        $user = Auth::user();

        if ($user === null) {
            flash('info', 'Please log in to see your orders. You do not need an account to place an order.');
            redirect('/login?next=' . rawurlencode('/orders'));
        }

        echo view('checkout/orders', ['title' => 'My orders', 'orders' => Order::forContact((int) $user['id'])]);
    }

    /** The customer who just placed the order (remembered in their session) or the logged-in owner may see it. */
    private function mayView(array $order, ?array $user): bool
    {
        $mine = $_SESSION['my_orders'] ?? [];

        if (is_array($mine) && in_array($order['order_number'], $mine, true)) {
            return true;
        }

        return $user !== null && $order['contact_id'] !== null && (int) $order['contact_id'] === (int) $user['id'];
    }
}
