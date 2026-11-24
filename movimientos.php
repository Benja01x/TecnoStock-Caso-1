<?php
require_once __DIR__ . '/includes/app.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $type = (string) ($_POST['tipo'] ?? '');
    $quantity = filter_var($_POST['cantidad'] ?? null, FILTER_VALIDATE_INT);
    $product = filter_var($_POST['id_producto'] ?? null, FILTER_VALIDATE_INT);
    $note = trim((string) ($_POST['observacion'] ?? ''));
    if (!in_array($type, ['ENTRADA', 'SALIDA'], true) || $quantity === false || $quantity < 1
        || !$product || $product < 1 || mb_strlen($note) > 255) {
        set_flash('error', 'Completa el movimiento con valores válidos. La cantidad debe ser mayor que cero.');
        redirect('movimientos.php');
    }
    try {
        $stmt = db()->prepare(
            'INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario)
             VALUES (:tipo, :cantidad, :observacion, :producto, :usuario)'
        );
        $stmt->execute([
            'tipo' => $type, 'cantidad' => $quantity, 'observacion' => $note ?: null,
            'producto' => $product, 'usuario' => $_SESSION['usuario']['id_usuario'],
        ]);
        set_flash('success', 'Movimiento registrado y stock actualizado.');
    } catch (PDOException $exception) {
        report_error($exception);
        if ($exception instanceof PDOException && str_contains($exception->getMessage(), 'SQLSTATE[45000]')) {
            set_flash('error', 'No se pudo registrar: verifica que el producto esté activo y que haya stock suficiente.');
        }
    }
    redirect('movimientos.php');
}

try {
    $products = db()->query('SELECT id_producto, codigo, nombre, stock_actual FROM productos WHERE estado = 1 ORDER BY nombre')->fetchAll();
    $movements = db()->query(
        'SELECT m.fecha_hora, m.tipo, m.cantidad, m.observacion, p.codigo, p.nombre AS producto, u.nombre AS responsable
         FROM movimientos m INNER JOIN productos p ON p.id_producto = m.id_producto
         INNER JOIN usuarios u ON u.id_usuario = m.id_usuario
         ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 100'
    )->fetchAll();
} catch (PDOException $exception) {
    report_error($exception);
    $products = $movements = [];
}

$pageTitle = 'Movimientos';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4"><h1 class="h2 mb-1">Movimientos</h1><p class="text-body-secondary mb-0">Registra entradas y salidas; el stock se actualiza automáticamente.</p></div>
<section class="card shadow-sm mb-4">
    <div class="card-body">
    <h2 class="h5 mb-3">Registrar movimiento</h2>
    <form method="post" action="movimientos.php" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <div class="row g-3">
            <div class="col-12 col-md-6"><label class="form-label" for="tipo">Tipo</label><select class="form-select" id="tipo" name="tipo" required>
                <option value="ENTRADA">Entrada</option><option value="SALIDA">Salida</option>
            </select></div>
            <div class="col-12 col-md-6"><label class="form-label" for="id_producto">Producto</label><select class="form-select" id="id_producto" name="id_producto" required>
                <option value="">Selecciona un producto activo</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int) $product['id_producto'] ?>"><?= e($product['codigo'] . ' - ' . $product['nombre'] . ' (stock: ' . $product['stock_actual'] . ')') ?></option>
                <?php endforeach; ?>
            </select></div>
            <div class="col-12 col-md-6"><label class="form-label" for="cantidad">Cantidad</label><input class="form-control" id="cantidad" name="cantidad" type="number" min="1" step="1" required></div>
            <div class="col-12 col-md-6"><label class="form-label" for="observacion">Observación</label><input class="form-control" id="observacion" name="observacion" maxlength="255"></div>
        </div>
        <div class="mt-4"><button class="btn btn-primary" type="submit" <?= !$products ? 'disabled' : '' ?>>Registrar movimiento</button></div>
    </form>
    </div>
</section>
<section class="card shadow-sm">
    <div class="card-body">
    <h2 class="h5 mb-3">Historial reciente</h2>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th scope="col">Fecha</th><th scope="col">Tipo</th><th scope="col">Código</th><th scope="col">Producto</th><th scope="col">Cantidad</th><th scope="col">Observación</th><th scope="col">Responsable</th></tr></thead>
        <tbody><?php foreach ($movements as $movement): ?>
            <tr><td><?= e($movement['fecha_hora']) ?></td><td><?= e($movement['tipo']) ?></td>
                <td><?= e($movement['codigo']) ?></td><td><?= e($movement['producto']) ?></td>
                <td><?= (int) $movement['cantidad'] ?></td><td><?= e($movement['observacion']) ?></td>
                <td><?= e($movement['responsable']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$movements): ?><tr><td colspan="7" class="text-center text-body-secondary py-4">No hay movimientos registrados.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
