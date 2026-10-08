<?php

declare(strict_types=1);

namespace App\Core;

use Throwable;

/**
 * Plain-text email. Sending is best effort: a mail problem must never stop an order, so failures are only logged.
 * On a local computer (APP_ENV=local) nothing is sent; the message is written to storage/logs/mail.log instead.
 */
final class Mailer
{
    /**
     * Send an email AFTER the visitor already has their page. The visitor never waits for the mail server, and the
     * session file is released first so their next click is not held up either.
     */
    public static function sendLater(callable $job): void
    {
        register_shutdown_function(static function () use ($job): void {
            try {
                if (session_status() === PHP_SESSION_ACTIVE) {
                    session_write_close();
                }

                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                } elseif (function_exists('litespeed_finish_request')) {
                    litespeed_finish_request();
                }

                @set_time_limit(25);
                $job();
            } catch (Throwable $e) {
                error_log('Deferred mail job failed: ' . $e->getMessage());
            }
        });
    }

    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        try {
            $to      = self::clean($to);
            $subject = self::clean($subject);

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            $from = 'orders@' . self::mailDomain();
            $name = self::clean((string) config('app.name'));

            if (config('app.env') === 'local') {
                $line = sprintf("[%s] To: %s | Subject: %s\n%s\n---\n", date('c'), $to, $subject, $body);
                @file_put_contents(BASE_PATH . '/storage/logs/mail.log', $line, FILE_APPEND);

                return true;
            }

            $headers = [
                'From: ' . $name . ' <' . $from . '>',
                'MIME-Version: 1.0',
                'Content-Type: text/plain; charset=UTF-8',
            ];

            if ($replyTo !== null && filter_var(self::clean($replyTo), FILTER_VALIDATE_EMAIL)) {
                $headers[] = 'Reply-To: ' . self::clean($replyTo);
            }

            $ok = mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));

            if (!$ok) {
                error_log('Mail to ' . $to . ' was not accepted by the server.');
            }

            return $ok;
        } catch (Throwable $e) {
            error_log('Mail failed: ' . $e->getMessage());

            return false;
        }
    }

    /**
     * Domain for the From address (orders@your-domain). Taken from APP_URL, even if it was typed slightly wrong
     * (for example "https//example.com"), otherwise from the address the visitor used; it must look like a real domain.
     */
    /** Web address of the site for links inside emails, e.g. https://example.com */
    public static function baseUrl(): string
    {
        $local = str_starts_with(strtolower((string) config('app.url')), 'http://');
        $host  = self::mailDomain();

        return ($local && $host === 'localhost.localdomain' ? 'http://' : 'https://') . ($host === 'localhost.localdomain' ? '127.0.0.1' : $host);
    }

    private static function mailDomain(): string
    {
        $candidates = [(string) config('app.url'), (string) ($_SERVER['HTTP_HOST'] ?? '')];

        foreach ($candidates as $value) {
            $value = strtolower(trim($value));
            $value = preg_replace('#^[a-z][a-z0-9+.-]*(?::/+|//+)#', '', $value) ?? $value; // drop "https://", "https//" and the like
            $value = preg_replace('#[/:?].*$#', '', $value) ?? $value;    // drop path and port
            $value = preg_replace('/^www\./', '', $value) ?? $value;

            if (preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $value) === 1) {
                return $value;
            }
        }

        return 'localhost.localdomain';
    }

    /** Remove line breaks so a value can never add extra email headers. */
    private static function clean(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
    }
}
