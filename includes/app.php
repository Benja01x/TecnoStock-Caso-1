<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function require_login(): void
{
    if (empty($_SESSION['usuario'])) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Solicitud no válida. Vuelve a cargar la página e inténtalo nuevamente.');
    }
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function report_error(Throwable $error): void
{
    error_log((string) $error);
    set_flash('error', 'No se pudo completar la operación. Revisa los datos e inténtalo nuevamente.');
}
