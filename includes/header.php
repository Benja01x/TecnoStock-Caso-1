<?php
$pageTitle = $pageTitle ?? 'TecnoStock';
$flash = $_SESSION['flash'] ?? null;
$flashClass = ($flash['type'] ?? '') === 'success' ? 'success' : 'danger';
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | TecnoStock</title>
    <link rel="stylesheet" href="assets/vendor/bootstrap/bootstrap.min.css">
</head>
<body class="bg-body-tertiary">
<header class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="dashboard.php">TecnoStock</a>
        <?php if (!empty($_SESSION['usuario'])): ?>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavigation"
                    aria-controls="mainNavigation" aria-expanded="false" aria-label="Abrir navegación">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNavigation">
                <nav class="navbar-nav me-auto" aria-label="Navegación principal">
                    <a class="nav-link" href="dashboard.php">Resumen</a>
                    <a class="nav-link" href="productos.php">Productos</a>
                    <a class="nav-link" href="categorias.php">Categorías</a>
                    <a class="nav-link" href="movimientos.php">Movimientos</a>
                </nav>
                <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-2 gap-sm-3 mt-3 mt-lg-0">
                    <span class="text-white-50"><?= e($_SESSION['usuario']['nombre']) ?></span>
                    <form method="post" action="logout.php" class="mb-0">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <button class="btn btn-link text-white text-decoration-none p-0" type="submit">Cerrar sesión</button>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</header>
<main class="container py-4">
    <?php if ($flash): ?>
        <div class="alert alert-<?= e($flashClass) ?>" role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>
