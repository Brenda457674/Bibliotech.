<?php
// ============================================================
// registro.php - Registro de nuevos estudiantes
// ============================================================
require_once __DIR__ . '/includes/auth.php';

// Si ya inició sesión, va al panel
if (estaLogueado()) {
    header('Location: dashboard.php');
    exit;
}

$errores  = [];
$nombre   = '';
$email    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre   = trim($_POST['nombre'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmar = $_POST['confirmar_password'] ?? '';

    // --- Validaciones ---
    if ($nombre === '' || $email === '' || $password === '' || $confirmar === '') {
        $errores[] = 'Todos los campos son obligatorios.';
    }
    if ($nombre !== '' && strlen($nombre) < 3) {
        $errores[] = 'El nombre debe tener al menos 3 caracteres.';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no tiene un formato válido.';
    }
    if (strlen($password) < 6) {
        $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
    }
    if ($password !== $confirmar) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    // Si no hay errores, verificamos que el email no esté registrado
    if (!$errores) {
        $sql = "SELECT id FROM usuarios WHERE email = :email";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':email' => $email]);

        if ($stmt->fetch()) {
            $errores[] = 'Ya existe una cuenta con ese email.';
        }
    }

    // Si todo está bien, creamos el usuario (rol estudiante)
    if (!$errores) {
        $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado)
                VALUES (:nombre, :email, :hash, 'estudiante', 'activo')";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':nombre' => $nombre,
            ':email'  => $email,
            ':hash'   => password_hash($password, PASSWORD_DEFAULT),
        ]);

        setFlash('exito', 'Cuenta creada correctamente. Ya puedes iniciar sesión.');
        header('Location: login.php');
        exit;
    }
}

$tituloPagina = 'Crear cuenta';
$claseMain    = 'pagina-centrada';
require_once __DIR__ . '/includes/header.php';
?>

<div class="tarjeta tarjeta-auth">
    <h1 class="titulo-pagina">Crear cuenta</h1>

    <?php if ($errores): ?>
        <div class="alerta alerta-error">
            <ul class="lista-errores">
                <?php foreach ($errores as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="registro.php" class="formulario">
        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre"
               value="<?= e($nombre) ?>" placeholder="Ej: Carlos Ramírez" required>

        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= e($email) ?>" placeholder="tuemail@ejemplo.com" required>

        <label for="password">Contraseña (mínimo 6 caracteres)</label>
        <input type="password" id="password" name="password" required>

        <label for="confirmar_password">Repite la contraseña</label>
        <input type="password" id="confirmar_password" name="confirmar_password" required>

        <button type="submit" class="boton boton-primario ancho">Registrarme</button>
    </form>

    <p class="texto-centrado">
        ¿Ya tienes cuenta? <a href="login.php">Inicia sesión</a>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
