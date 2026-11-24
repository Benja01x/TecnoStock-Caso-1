<?php
require_once __DIR__ . '/includes/app.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método no permitido.');
}
verify_csrf();

$id = filter_input(INPUT_POST, 'id_producto', FILTER_VALIDATE_INT);
$codigo = trim((string) ($_POST['codigo'] ?? ''));
$nombre = trim((string) ($_POST['nombre'] ?? ''));
$descripcion = trim((string) ($_POST['descripcion'] ?? ''));
$precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);
$stock = filter_var($_POST['stock_actual'] ?? null, FILTER_VALIDATE_INT);
$stockMinimo = filter_var($_POST['stock_minimo'] ?? null, FILTER_VALIDATE_INT);
$category = filter_var($_POST['id_categoria'] ?? null, FILTER_VALIDATE_INT);

if ($id === false || $id < 0 || $codigo === '' || strlen($codigo) > 30 || $nombre === ''
    || mb_strlen($nombre) > 150 || $precio === false || $precio < 0
    || $stock === false || $stock < 0 || $stockMinimo === false || $stockMinimo < 0
    || !$category || $category < 1 || mb_strlen($descripcion) > 65535) {
    set_flash('error', 'Revisa los campos obligatorios y sus valores. Precio y cantidades no pueden ser negativos.');
    redirect($id > 0 ? 'producto_form.php?id=' . $id : 'producto_form.php');
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $categoryCheck = $pdo->prepare('SELECT id_categoria FROM categorias WHERE id_categoria = :id AND estado = 1');
    $categoryCheck->execute(['id' => $category]);
    if (!$categoryCheck->fetch()) {
        throw new DomainException('La categoría seleccionada no está activa.');
    }

    if ($id > 0) {
        $lock = $pdo->prepare('SELECT stock_actual FROM productos WHERE id_producto = :id AND estado = 1 FOR UPDATE');
        $lock->execute(['id' => $id]);
        $current = $lock->fetch();
        if (!$current) {
            throw new DomainException('El producto no existe o está desactivado.');
        }
        $update = $pdo->prepare(
            'UPDATE productos SET codigo = :codigo, nombre = :nombre, descripcion = :descripcion,
             precio = :precio, stock_minimo = :stock_minimo, id_categoria = :categoria
             WHERE id_producto = :id'
        );
        $update->execute([
            'codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion ?: null,
            'precio' => $precio, 'stock_minimo' => $stockMinimo, 'categoria' => $category, 'id' => $id,
        ]);
        $delta = $stock - (int) $current['stock_actual'];
        if ($delta !== 0) {
            $movement = $pdo->prepare(
                'INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario)
                 VALUES (:tipo, :cantidad, :observacion, :producto, :usuario)'
            );
            $movement->execute([
                'tipo' => $delta > 0 ? 'ENTRADA' : 'SALIDA',
                'cantidad' => abs($delta),
                'observacion' => 'Ajuste de stock desde edición de producto',
                'producto' => $id,
                'usuario' => $_SESSION['usuario']['id_usuario'],
            ]);
        }
        $message = 'Producto actualizado correctamente.';
    } else {
        $insert = $pdo->prepare(
            'INSERT INTO productos (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria)
             VALUES (:codigo, :nombre, :descripcion, :precio, 0, :stock_minimo, :categoria)'
        );
        $insert->execute([
            'codigo' => $codigo, 'nombre' => $nombre, 'descripcion' => $descripcion ?: null,
            'precio' => $precio, 'stock_minimo' => $stockMinimo, 'categoria' => $category,
        ]);
        $newId = (int) $pdo->lastInsertId();
        if ($stock > 0) {
            $movement = $pdo->prepare(
                "INSERT INTO movimientos (tipo, cantidad, observacion, id_producto, id_usuario)
                 VALUES ('ENTRADA', :cantidad, 'Stock inicial del producto', :producto, :usuario)"
            );
            $movement->execute([
                'cantidad' => $stock, 'producto' => $newId, 'usuario' => $_SESSION['usuario']['id_usuario'],
            ]);
        }
        $message = 'Producto registrado correctamente.';
    }
    $pdo->commit();
    set_flash('success', $message);
} catch (PDOException | DomainException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    report_error($exception);
    if ($exception instanceof DomainException) {
        set_flash('error', $exception->getMessage());
    } elseif ($exception instanceof PDOException
        && (string) $exception->getCode() === '23000'
        && isset($exception->errorInfo[1])
        && (int) $exception->errorInfo[1] === 1062) {
        set_flash('error', 'Ya existe un producto con ese código.');
    }
}

redirect($id > 0 ? 'producto_form.php?id=' . $id : 'productos.php');
