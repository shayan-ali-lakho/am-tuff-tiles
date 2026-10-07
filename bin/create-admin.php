<?php

declare(strict_types=1);

/**
 * Create an admin account, or make an existing account an admin.
 * The password is typed here on the server, so it never goes through chat or Git.
 *
 * Usage (from the site folder):  php bin/create-admin.php
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('This script can only be run from the command line.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Auth;
use App\Models\Contact;

function ask(string $question, bool $hidden = false): string
{
    echo $question;

    $canHide = $hidden && function_exists('shell_exec') && DIRECTORY_SEPARATOR === '/';

    if ($canHide) {
        shell_exec('stty -echo 2>/dev/null');
    }

    $answer = trim((string) fgets(STDIN));

    if ($canHide) {
        shell_exec('stty echo 2>/dev/null');
        echo "\n";
    }

    return $answer;
}

$email = strtolower(ask('Admin email: '));

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
    fwrite(STDERR, "That is not a valid email address.\n");
    exit(1);
}

$existing = Contact::findForLogin($email);

if ($existing !== null) {
    if ($existing['portal_role'] === 'admin') {
        echo "{$email} is already an admin. Nothing to do.\n";
        exit(0);
    }

    $confirm = strtolower(ask("An account for {$email} already exists ({$existing['full_name']}). Make it an admin? [y/N] "));

    if ($confirm !== 'y' && $confirm !== 'yes') {
        echo "Cancelled.\n";
        exit(0);
    }

    Contact::setRole((int) $existing['id'], 'admin');
    echo "Done. {$email} is now an admin. Their password was not changed.\n";
    exit(0);
}

$name = ask('Full name: ');
$nameLength = mb_strlen($name);

if ($nameLength < 2 || $nameLength > 120) {
    fwrite(STDERR, "Name must be 2 to 120 characters.\n");
    exit(1);
}

$password = ask('Password (min 8 characters, input may be hidden): ', true);

if (strlen($password) < 8 || strlen($password) > 72) {
    fwrite(STDERR, "Password must be 8 to 72 characters.\n");
    exit(1);
}

if ($password !== ask('Repeat password: ', true)) {
    fwrite(STDERR, "The passwords do not match.\n");
    exit(1);
}

Contact::create($name, $email, '', Auth::hash($password), 'admin');
echo "Done. Admin account created for {$email}. You can log in at /login.\n";
