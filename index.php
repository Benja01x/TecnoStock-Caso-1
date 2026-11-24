<?php
require_once __DIR__ . '/includes/app.php';

redirect(empty($_SESSION['usuario']) ? 'login.php' : 'dashboard.php');
