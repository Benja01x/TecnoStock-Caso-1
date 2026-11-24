<?php
require_once __DIR__ . '/includes/app.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}

verify_csrf();
$_SESSION = [];
session_regenerate_id(true);
set_flash('success', 'Sesión cerrada correctamente.');
redirect('login.php');
