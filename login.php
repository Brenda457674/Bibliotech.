<?php
// ============================================================
// login.php - Inicio de sesión
// ============================================================
require_once __DIR__ . '/includes/auth.php';

// Si ya inició sesión, va directo al panel
if (estaLogueado()) {
    header('Location: dashboard.php');
    exit;
}

$errores = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // Validaciones básicas
    if ($email === '' || $password === '') {
        $errores[] = 'Escribe tu email y tu contraseña.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El email no tiene un formato válido.';
    } else {
        // Buscamos el usuario por email (consulta preparada)
        $sql = "SELECT id, nombre, password_hash, rol, estado
                FROM usuarios
                WHERE email = :email";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        if (!$usuario) {
            $errores[] = 'No existe una cuenta con ese email.';
        } elseif ($usuario['estado'] === 'bloqueado') {
            // Usuario bloqueado: no entra aunque tenga buena contraseña
            $errores[] = 'Tu cuenta está bloqueada. Contacta con el administrador.';
        } elseif (!password_verify($password, $usuario['password_hash'])) {
            $errores[] = 'La contraseña es incorrecta.';
        } else {
            // Todo correcto: guardamos los datos en la sesión
            $_SESSION['usuario_id']    = $usuario['id'];
            $_SESSION['usuario_nombre'] = $usuario['nombre'];
            $_SESSION['usuario_rol']   = $usuario['rol'];

            header('Location: dashboard.php');
            exit;
        }
    }
}

$tituloPagina = 'Iniciar sesión';
$claseMain    = 'pagina-centrada';
require_once __DIR__ . '/includes/header.php';
?>

<div class="tarjeta tarjeta-auth">
    <h1 class="titulo-pagina">Iniciar sesión</h1>

    <?php if ($errores): ?>
        <div class="alerta alerta-error">
            <ul class="lista-errores">
                <?php foreach ($errores as $error): ?>
                    <li><?= e($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" class="formulario">
        <label for="email">Email</label>
        <input type="email" id="email" name="email"
               value="<?= e($email) ?>" placeholder="tuemail@ejemplo.com" required>

        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required>

        <button type="submit" class="boton boton-primario ancho">Entrar</button>
    </form>

    <p class="texto-centrado">
        ¿No tienes cuenta? <a href="registro.php">Regístrate aquí</a>
    </p>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
