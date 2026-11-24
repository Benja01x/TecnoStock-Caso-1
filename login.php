<?php
require_once __DIR__ . '/includes/app.php';

if (!empty($_SESSION['usuario'])) {
    redirect('dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $correo = trim((string) ($_POST['correo'] ?? ''));
    $clave = (string) ($_POST['clave'] ?? '');

    if (filter_var($correo, FILTER_VALIDATE_EMAIL) && $clave !== '') {
        try {
            $stmt = db()->prepare(
                'SELECT id_usuario, nombre, correo, clave FROM usuarios WHERE correo = :correo AND estado = 1'
            );
            $stmt->execute(['correo' => $correo]);
            $usuario = $stmt->fetch();
            if ($usuario && password_verify($clave, $usuario['clave'])) {
                session_regenerate_id(true);
                $_SESSION['usuario'] = [
                    'id_usuario' => (int) $usuario['id_usuario'],
                    'nombre' => $usuario['nombre'],
                    'correo' => $usuario['correo'],
                ];
                redirect('dashboard.php');
            }
        } catch (PDOException $exception) {
            report_error($exception);
            $error = 'No fue posible iniciar sesión. Comprueba la conexión con la base de datos.';
        }
    }

    if ($error === '') {
        $error = 'Correo o contraseña incorrectos.';
    }
}

$pageTitle = 'Iniciar sesión';
require __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center mt-4">
    <div class="col-12 col-sm-10 col-md-8 col-lg-6">
        <section class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h3 mb-2">Iniciar sesión</h1>
                <p class="text-body-secondary">Ingresa con un usuario activo registrado en TecnoStock.</p>
                <?php if ($error !== ''): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
                <form method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="mb-3">
                        <label class="form-label" for="correo">Correo electrónico</label>
                        <input class="form-control" id="correo" name="correo" type="email" autocomplete="username" required maxlength="150"
                               value="<?= e($_POST['correo'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="clave">Contraseña</label>
                        <input class="form-control" id="clave" name="clave" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Entrar</button>
                </form>
            </div>
        </section>
    </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
