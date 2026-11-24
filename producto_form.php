<?php
require_once __DIR__ . '/includes/app.php';
require_login();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$product = [
    'id_producto' => 0, 'codigo' => '', 'nombre' => '', 'descripcion' => '',
    'precio' => '', 'stock_actual' => 0, 'stock_minimo' => 0, 'id_categoria' => '',
];
try {
    if ($id) {
        $stmt = db()->prepare('SELECT * FROM productos WHERE id_producto = :id AND estado = 1');
        $stmt->execute(['id' => $id]);
        $found = $stmt->fetch();
        if (!$found) {
            set_flash('error', 'Producto no encontrado o desactivado.');
            redirect('productos.php');
        }
        $product = $found;
    }
    $categories = db()->query('SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();
} catch (PDOException $exception) {
    report_error($exception);
    $categories = [];
}

$pageTitle = $id ? 'Editar producto' : 'Nuevo producto';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4"><h1 class="h2 mb-1"><?= e($pageTitle) ?></h1>
    <p class="text-body-secondary mb-0">Los cambios de stock se registran también en el historial de movimientos.</p></div>
<section class="card shadow-sm">
    <div class="card-body">
    <?php if (!$categories): ?><div class="alert alert-danger">Primero debe existir al menos una categoría activa.</div><?php endif; ?>
    <form method="post" action="producto_guardar.php" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id_producto" value="<?= (int) $product['id_producto'] ?>">
        <div class="row g-3">
            <div class="col-12 col-md-6"><label class="form-label" for="codigo">Código</label><input class="form-control" id="codigo" name="codigo" required maxlength="30" value="<?= e($product['codigo']) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" required maxlength="150" value="<?= e($product['nombre']) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label" for="categoria">Categoría</label>
                <select class="form-select" id="categoria" name="id_categoria" required>
                    <option value="">Selecciona una categoría</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int) $category['id_categoria'] ?>" <?= (int) $product['id_categoria'] === (int) $category['id_categoria'] ? 'selected' : '' ?>>
                            <?= e($category['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-6"><label class="form-label" for="precio">Precio</label><input class="form-control" id="precio" name="precio" type="number" min="0" step="0.01" required value="<?= e((string) $product['precio']) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label" for="stock_actual">Stock actual</label><input class="form-control" id="stock_actual" name="stock_actual" type="number" min="0" step="1" required value="<?= (int) $product['stock_actual'] ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label" for="stock_minimo">Stock mínimo</label><input class="form-control" id="stock_minimo" name="stock_minimo" type="number" min="0" step="1" required value="<?= (int) $product['stock_minimo'] ?>"></div>
            <div class="col-12"><label class="form-label" for="descripcion">Descripción</label><textarea class="form-control" id="descripcion" name="descripcion" rows="4"><?= e($product['descripcion']) ?></textarea></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-primary" type="submit" <?= !$categories ? 'disabled' : '' ?>>Guardar producto</button>
            <a class="btn btn-outline-secondary" href="productos.php">Cancelar</a></div>
    </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
