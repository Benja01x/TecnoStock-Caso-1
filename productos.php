<?php
require_once __DIR__ . '/includes/app.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
    if (!$id || $id < 1) {
        set_flash('error', 'Producto no válido.');
        redirect('productos.php');
    }
    try {
        $stmt = db()->prepare('UPDATE productos SET estado = 0 WHERE id_producto = :id AND estado = 1');
        $stmt->execute(['id' => $id]);
        set_flash($stmt->rowCount() ? 'success' : 'error', $stmt->rowCount()
            ? 'Producto desactivado; su historial se conservó.'
            : 'No se encontró un producto activo con ese identificador.');
    } catch (PDOException $exception) {
        report_error($exception);
    }
    redirect('productos.php');
}

$search = trim((string) ($_GET['q'] ?? ''));
$category = filter_input(INPUT_GET, 'categoria', FILTER_VALIDATE_INT);
$lowOnly = ($_GET['stock'] ?? '') === 'bajo';
$sql = 'SELECT * FROM vw_inventario WHERE estado = 1';
$params = [];
if ($search !== '') {
    $sql .= ' AND (nombre LIKE :nombre OR codigo LIKE :codigo OR categoria LIKE :categoria_texto)';
    $term = '%' . $search . '%';
    $params += ['nombre' => $term, 'codigo' => $term, 'categoria_texto' => $term];
}
if ($category) {
    $sql .= ' AND id_categoria = :categoria';
    $params['categoria'] = $category;
}
if ($lowOnly) {
    $sql .= ' AND stock_actual < stock_minimo';
}
$sql .= ' ORDER BY nombre';

try {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();
    $categories = db()->query('SELECT id_categoria, nombre FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();
} catch (PDOException $exception) {
    report_error($exception);
    $products = $categories = [];
}

$pageTitle = 'Productos';
require __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div><h1 class="h2 mb-1">Productos</h1><p class="text-body-secondary mb-0">Consulta, busca y administra el catálogo.</p></div>
    <a class="btn btn-primary" href="producto_form.php">Nuevo producto</a>
</div>
<section class="card shadow-sm">
    <div class="card-body">
    <form class="row g-2 align-items-center mb-3" method="get" action="productos.php">
        <div class="col-12 col-lg">
            <label class="visually-hidden" for="q">Buscar por nombre, código o categoría</label>
            <input class="form-control" id="q" name="q" type="search" placeholder="Nombre, código o categoría" value="<?= e($search) ?>">
        </div>
        <div class="col-12 col-md-5 col-lg-3">
            <label class="visually-hidden" for="categoria">Categoría</label>
            <select class="form-select" id="categoria" name="categoria"><option value="">Todas las categorías</option>
            <?php foreach ($categories as $item): ?>
                <option value="<?= (int) $item['id_categoria'] ?>" <?= $category == $item['id_categoria'] ? 'selected' : '' ?>>
                    <?= e($item['nombre']) ?>
                </option>
            <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12 col-md-auto">
            <div class="form-check">
                <input class="form-check-input" id="stock" type="checkbox" name="stock" value="bajo" <?= $lowOnly ? 'checked' : '' ?>>
                <label class="form-check-label" for="stock">Solo stock bajo</label>
            </div>
        </div>
        <div class="col-6 col-md-auto"><button class="btn btn-primary w-100" type="submit">Buscar</button></div>
        <div class="col-6 col-md-auto"><a class="btn btn-outline-secondary w-100" href="productos.php">Limpiar</a></div>
    </form>
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th scope="col">Código</th><th scope="col">Nombre</th><th scope="col">Categoría</th><th scope="col">Precio</th><th scope="col">Stock</th><th scope="col">Estado stock</th><th scope="col">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($products as $product): ?>
            <tr class="<?= $product['nivel_stock'] === 'BAJO' ? 'table-warning' : '' ?>">
                <td><?= e($product['codigo']) ?></td><td><?= e($product['nombre']) ?></td>
                <td><?= e($product['categoria']) ?></td><td>$<?= number_format((float) $product['precio'], 0, ',', '.') ?></td>
                <td><?= (int) $product['stock_actual'] ?> / <?= (int) $product['stock_minimo'] ?></td>
                <td><span class="badge rounded-pill <?= $product['nivel_stock'] === 'BAJO' ? 'text-bg-danger' : 'text-bg-success' ?>"><?= e($product['nivel_stock']) ?></span></td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-outline-primary" href="producto_form.php?id=<?= (int) $product['id_producto'] ?>">Editar</a>
                    <form class="d-inline" method="post" action="productos.php" data-confirm="¿Desactivar este producto? No se eliminará su historial.">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="id_producto" value="<?= (int) $product['id_producto'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Desactivar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$products): ?><tr><td colspan="7" class="text-center text-body-secondary py-4">No se encontraron productos activos.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
