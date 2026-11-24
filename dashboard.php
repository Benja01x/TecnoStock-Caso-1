<?php
require_once __DIR__ . '/includes/app.php';
require_login();

try {
    $totals = db()->query(
        'SELECT COUNT(*) AS productos, COALESCE(SUM(stock_actual), 0) AS unidades
         FROM productos WHERE estado = 1'
    )->fetch();
    $lowStock = db()->query(
        "SELECT codigo, nombre, stock_actual, stock_minimo
         FROM vw_stock_bajo ORDER BY nombre"
    )->fetchAll();
    $recent = db()->query(
        'SELECT m.fecha_hora, m.tipo, m.cantidad, p.nombre AS producto, u.nombre AS responsable
         FROM movimientos m
         INNER JOIN productos p ON p.id_producto = m.id_producto
         INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
         ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 5'
    )->fetchAll();
} catch (PDOException $exception) {
    report_error($exception);
    $totals = ['productos' => 0, 'unidades' => 0];
    $lowStock = $recent = [];
}

$pageTitle = 'Resumen';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div><h1 class="h2 mb-1">Resumen de inventario</h1><p class="text-body-secondary mb-0">Estado actual del catálogo y sus existencias.</p></div>
    <a class="btn btn-primary" href="producto_form.php">Registrar producto</a>
</div>
<section class="row g-3 mb-4">
    <article class="col-12 col-md-4"><div class="card h-100 shadow-sm"><div class="card-body">
        <h2 class="h6 text-body-secondary">Productos activos</h2><p class="display-6 fw-semibold mb-0"><?= (int) $totals['productos'] ?></p>
    </div></div></article>
    <article class="col-12 col-md-4"><div class="card h-100 shadow-sm"><div class="card-body">
        <h2 class="h6 text-body-secondary">Unidades disponibles</h2><p class="display-6 fw-semibold mb-0"><?= (int) $totals['unidades'] ?></p>
    </div></div></article>
    <article class="col-12 col-md-4"><div class="card h-100 shadow-sm"><div class="card-body">
        <h2 class="h6 text-body-secondary">Alertas de stock bajo</h2><p class="display-6 fw-semibold mb-0"><?= count($lowStock) ?></p>
    </div></div></article>
</section>
<section class="card shadow-sm mb-4">
    <div class="card-body">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Stock bajo</h2><a href="productos.php?stock=bajo">Ver productos</a></div>
    <?php if (!$lowStock): ?><p class="text-body-secondary mb-0">No hay productos activos bajo el mínimo definido.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th scope="col">Código</th><th scope="col">Producto</th><th scope="col">Stock actual</th><th scope="col">Stock mínimo</th></tr></thead>
            <tbody><?php foreach ($lowStock as $product): ?>
                <tr class="table-warning"><td><?= e($product['codigo']) ?></td><td><?= e($product['nombre']) ?></td>
                    <td><?= (int) $product['stock_actual'] ?></td><td><?= (int) $product['stock_minimo'] ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
    </div>
</section>
<section class="card shadow-sm">
    <div class="card-body">
    <div class="d-flex justify-content-between align-items-center gap-3 mb-3"><h2 class="h5 mb-0">Movimientos recientes</h2><a href="movimientos.php">Ver historial</a></div>
    <?php if (!$recent): ?><p class="text-body-secondary mb-0">Aún no hay movimientos registrados.</p>
    <?php else: ?>
        <div class="table-responsive"><table class="table table-hover align-middle mb-0">
            <thead><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Producto</th><th scope="col">Cantidad</th><th scope="col">Responsable</th></tr></thead>
            <tbody><?php foreach ($recent as $movement): ?>
                <tr><td><?= e($movement['fecha_hora']) ?></td><td><?= e($movement['tipo']) ?></td>
                    <td><?= e($movement['producto']) ?></td><td><?= (int) $movement['cantidad'] ?></td>
                    <td><?= e($movement['responsable']) ?></td></tr>
            <?php endforeach; ?></tbody>
        </table></div>
    <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
