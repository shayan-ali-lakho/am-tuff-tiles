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
    public static function send(string $to, string $subject, string $body, ?string $replyTo = null): bool
    {
        try {
            $to      = self::clean($to);
            $subject = self::clean($subject);

            if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
                return false;
            }

            $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
            $host = preg_replace('/^www\./', '', $host ?: 'localhost');
            $from = 'orders@' . $host;
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

    /** Remove line breaks so a value can never add extra email headers. */
    private static function clean(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], ' ', $value));
    }
}
