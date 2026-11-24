<?php
require_once __DIR__ . '/includes/app.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_input(INPUT_POST, 'id_categoria', FILTER_VALIDATE_INT);
    try {
        if ($action === 'guardar') {
            $name = trim((string) ($_POST['nombre'] ?? ''));
            $description = trim((string) ($_POST['descripcion'] ?? ''));
            if ($name === '' || mb_strlen($name) > 100 || mb_strlen($description) > 255) {
                throw new DomainException('El nombre es obligatorio y no debe superar 100 caracteres.');
            }
            if ($id) {
                $stmt = db()->prepare('UPDATE categorias SET nombre = :nombre, descripcion = :descripcion WHERE id_categoria = :id AND estado = 1');
                $stmt->execute(['nombre' => $name, 'descripcion' => $description ?: null, 'id' => $id]);
            } else {
                $stmt = db()->prepare('INSERT INTO categorias (nombre, descripcion) VALUES (:nombre, :descripcion)');
                $stmt->execute(['nombre' => $name, 'descripcion' => $description ?: null]);
            }
            set_flash('success', 'Categoría guardada correctamente.');
        } elseif ($action === 'desactivar' && $id) {
            $stmt = db()->prepare('UPDATE categorias SET estado = 0 WHERE id_categoria = :id AND estado = 1');
            $stmt->execute(['id' => $id]);
            set_flash('success', 'Categoría desactivada. No se eliminó de los productos existentes.');
        } else {
            throw new DomainException('Acción no válida.');
        }
    } catch (DomainException $exception) {
        set_flash('error', $exception->getMessage());
    } catch (PDOException $exception) {
        report_error($exception);
        if ((string) $exception->getCode() === '23000') {
            set_flash('error', 'Ya existe una categoría con ese nombre.');
        }
    }
    redirect('categorias.php');
}

$editId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
$editing = ['id_categoria' => 0, 'nombre' => '', 'descripcion' => ''];
try {
    if ($editId) {
        $stmt = db()->prepare('SELECT id_categoria, nombre, descripcion FROM categorias WHERE id_categoria = :id AND estado = 1');
        $stmt->execute(['id' => $editId]);
        $editing = $stmt->fetch() ?: $editing;
    }
    $categories = db()->query('SELECT id_categoria, nombre, descripcion FROM categorias WHERE estado = 1 ORDER BY nombre')->fetchAll();
} catch (PDOException $exception) {
    report_error($exception);
    $categories = [];
}

$pageTitle = 'Categorías';
require __DIR__ . '/includes/header.php';
?>
<div class="mb-4"><h1 class="h2 mb-1">Categorías</h1><p class="text-body-secondary mb-0">Organiza el catálogo por tipo de producto.</p></div>
<section class="card shadow-sm mb-4">
    <div class="card-body">
    <h2 class="h5 mb-3"><?= $editId ? 'Editar categoría' : 'Nueva categoría' ?></h2>
    <form method="post" action="categorias.php" data-validate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="guardar">
        <input type="hidden" name="id_categoria" value="<?= (int) $editing['id_categoria'] ?>">
        <div class="row g-3">
            <div class="col-12 col-md-6"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" required maxlength="100" value="<?= e($editing['nombre']) ?>"></div>
            <div class="col-12 col-md-6"><label class="form-label" for="descripcion">Descripción</label><input class="form-control" id="descripcion" name="descripcion" maxlength="255" value="<?= e($editing['descripcion']) ?>"></div>
        </div>
        <div class="d-flex flex-wrap gap-2 mt-4"><button class="btn btn-primary" type="submit">Guardar</button>
            <?php if ($editId): ?><a class="btn btn-outline-secondary" href="categorias.php">Cancelar</a><?php endif; ?>
        </div>
    </form>
    </div>
</section>
<section class="card shadow-sm">
    <div class="card-body">
    <div class="table-responsive"><table class="table table-hover align-middle mb-0">
        <thead><tr><th scope="col">Nombre</th><th scope="col">Descripción</th><th scope="col">Acciones</th></tr></thead>
        <tbody>
        <?php foreach ($categories as $category): ?>
            <tr><td><?= e($category['nombre']) ?></td><td><?= e($category['descripcion']) ?></td>
                <td class="text-nowrap"><a class="btn btn-sm btn-outline-primary" href="categorias.php?editar=<?= (int) $category['id_categoria'] ?>">Editar</a>
                    <form class="d-inline" method="post" action="categorias.php" data-confirm="¿Desactivar esta categoría?">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="desactivar">
                        <input type="hidden" name="id_categoria" value="<?= (int) $category['id_categoria'] ?>">
                        <button class="btn btn-sm btn-outline-danger" type="submit">Desactivar</button>
                    </form>
                </td></tr>
        <?php endforeach; ?>
        <?php if (!$categories): ?><tr><td colspan="3" class="text-center text-body-secondary py-4">No hay categorías activas.</td></tr><?php endif; ?>
        </tbody>
    </table></div>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
