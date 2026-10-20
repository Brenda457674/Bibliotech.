<?php
// ============================================================
// usuarios.php - CRUD de usuarios + bloquear/desbloquear
// Acceso EXCLUSIVO del administrador.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requiereRol(['administrador']);

$errores = [];

// ============================================================
// 1) PROCESAMIENTO (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    // --- Crear o editar ---
    if ($accion === 'crear' || $accion === 'editar') {
        $nombre = trim($_POST['nombre'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $rol    = $_POST['rol'] ?? 'estudiante';
        $password = $_POST['password'] ?? '';

        // Validaciones básicas
        if ($nombre === '' || $email === '') {
            $errores[] = 'El nombre y el email son obligatorios.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no tiene un formato válido.';
        }
        if (!in_array($rol, ['estudiante', 'bibliotecario', 'administrador'], true)) {
            $errores[] = 'El rol elegido no es válido.';
        }
        // Al crear es obligatorio; al editar solo si se quiere cambiar
        if ($accion === 'crear' && strlen($password) < 6) {
            $errores[] = 'La contraseña debe tener al menos 6 caracteres.';
        }
        if ($accion === 'editar' && $password !== '' && strlen($password) < 6) {
            $errores[] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        }

        // El email debe ser único
        if (!$errores) {
            $sql = "SELECT id FROM usuarios WHERE email = :email";
            $parametros = [':email' => $email];
            if ($accion === 'editar') {
                $sql .= " AND id <> :id";
                $parametros[':id'] = $id;
            }
            $stmt = $conexion->prepare($sql);
            $stmt->execute($parametros);
            if ($stmt->fetch()) {
                $errores[] = 'Ya existe un usuario con ese email.';
            }
        }

        if (!$errores) {
            if ($accion === 'crear') {
                $sql = "INSERT INTO usuarios (nombre, email, password_hash, rol, estado)
                        VALUES (:nombre, :email, :hash, :rol, 'activo')";
                $parametros = [
                    ':nombre' => $nombre,
                    ':email'  => $email,
                    ':hash'   => password_hash($password, PASSWORD_DEFAULT),
                    ':rol'    => $rol,
                ];
                $mensaje = 'Usuario creado correctamente.';
            } else {
                // Solo cambiamos la contraseña si escribieron una nueva
                if ($password !== '') {
                    $sql = "UPDATE usuarios
                            SET nombre = :nombre, email = :email, rol = :rol,
                                password_hash = :hash
                            WHERE id = :id";
                    $parametros = [':hash' => password_hash($password, PASSWORD_DEFAULT)];
                } else {
                    $sql = "UPDATE usuarios
                            SET nombre = :nombre, email = :email, rol = :rol
                            WHERE id = :id";
                    $parametros = [];
                }
                $parametros = array_merge($parametros, [
                    ':nombre' => $nombre,
                    ':email'  => $email,
                    ':rol'    => $rol,
                    ':id'     => $id,
                ]);
                $stmt = $conexion->prepare($sql);
                $stmt->execute($parametros);

                setFlash('exito', 'Usuario actualizado correctamente.');
                header('Location: usuarios.php');
                exit;
            }
            $stmt = $conexion->prepare($sql);
            $stmt->execute($parametros);

            setFlash('exito', $mensaje);
            header('Location: usuarios.php');
            exit;
        }
    }

    // --- Bloquear / Desbloquear ---
    if ($accion === 'bloquear' || $accion === 'desbloquear') {
        if ($id === (int) $_SESSION['usuario_id']) {
            setFlash('error', 'No puedes bloquear tu propia cuenta.');
        } else {
            $nuevoEstado = $accion === 'bloquear' ? 'bloqueado' : 'activo';
            $stmt = $conexion->prepare(
                "UPDATE usuarios SET estado = :estado WHERE id = :id"
            );
            $stmt->execute([':estado' => $nuevoEstado, ':id' => $id]);

            setFlash('exito', $accion === 'bloquear'
                ? 'Usuario bloqueado. No podrá iniciar sesión.'
                : 'Usuario desbloqueado. Ya puede iniciar sesión.');
        }
        header('Location: usuarios.php');
        exit;
    }

    // --- Eliminar ---
    if ($accion === 'eliminar') {
        if ($id === (int) $_SESSION['usuario_id']) {
            setFlash('error', 'No puedes eliminar tu propia cuenta.');
        } else {
            // No borramos usuarios con préstamos en su historial (hay FK)
            $stmt = $conexion->prepare("SELECT COUNT(*) FROM prestamos WHERE id_usuario = :id");
            $stmt->execute([':id' => $id]);
            $totalPrestamos = (int) $stmt->fetchColumn();

            if ($totalPrestamos > 0) {
                setFlash('error', "No se puede eliminar: el usuario tiene {$totalPrestamos} préstamo(s). "
                                . "Bloquéalo en su lugar.");
            } else {
                $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
                $stmt->execute([':id' => $id]);
                setFlash('exito', 'Usuario eliminado correctamente.');
            }
        }
        header('Location: usuarios.php');
        exit;
    }
}

// ============================================================
// 2) DATOS PARA LA VISTA
// ============================================================
$vista = $_GET['accion'] ?? 'listar';
if (!in_array($vista, ['crear', 'editar'], true)) {
    $vista = 'listar';
}

// Usuario del formulario crear/editar
$filaUsuario = [
    'id' => 0, 'nombre' => '', 'email' => '', 'rol' => 'estudiante', 'estado' => 'activo',
];

if ($vista === 'editar') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $usuarioEncontrado = $stmt->fetch();

    if (!$usuarioEncontrado) {
        setFlash('error', 'El usuario solicitado no existe.');
        header('Location: usuarios.php');
        exit;
    }
    $filaUsuario = $usuarioEncontrado;
}

// Si hubo errores, conservamos lo que escribió el usuario
if ($errores && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $filaUsuario = [
        'id'     => (int) ($_POST['id'] ?? 0),
        'nombre' => $_POST['nombre'] ?? '',
        'email'  => $_POST['email'] ?? '',
        'rol'    => $_POST['rol'] ?? 'estudiante',
        'estado' => 'activo',
    ];
    $vista = $_POST['accion'] ?? 'listar';
}

// Lista completa de usuarios
$usuarios = $conexion->query("SELECT * FROM usuarios ORDER BY nombre")->fetchAll();

$tituloPagina = 'Usuarios';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina">Usuarios</h1>

<?php if ($errores): ?>
    <div class="alerta alerta-error">
        <ul class="lista-errores">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($vista === 'crear' || $vista === 'editar'): ?>
    <!-- ========== FORMULARIO DE CREAR / EDITAR ========== -->
    <div class="tarjeta" style="max-width: 560px;">
        <h2><?= $vista === 'crear' ? 'Nuevo usuario' : 'Editar usuario' ?></h2>

        <form method="POST" action="usuarios.php" class="formulario">
            <input type="hidden" name="accion" value="<?= e($vista) ?>">
            <input type="hidden" name="id" value="<?= (int) $filaUsuario['id'] ?>">

            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" maxlength="120"
                   value="<?= e($filaUsuario['nombre']) ?>" required>

            <label for="email">Email *</label>
            <input type="email" id="email" name="email" maxlength="180"
                   value="<?= e($filaUsuario['email']) ?>" required>

            <label for="rol">Rol *</label>
            <select id="rol" name="rol" required>
                <option value="estudiante"
                    <?= $filaUsuario['rol'] === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                <option value="bibliotecario"
                    <?= $filaUsuario['rol'] === 'bibliotecario' ? 'selected' : '' ?>>Bibliotecario</option>
                <option value="administrador"
                    <?= $filaUsuario['rol'] === 'administrador' ? 'selected' : '' ?>>Administrador</option>
            </select>

            <label for="password">
                <?= $vista === 'crear'
                    ? 'Contraseña * (mínimo 6 caracteres)'
                    : 'Nueva contraseña (dejar vacío para no cambiarla)' ?>
            </label>
            <input type="password" id="password" name="password" minlength="6"
                   <?= $vista === 'crear' ? 'required' : '' ?>>

            <div style="margin-top: 0.8rem; display: flex; gap: 0.5rem;">
                <button type="submit" class="boton boton-primario">
                    <?= $vista === 'crear' ? 'Crear' : 'Guardar cambios' ?>
                </button>
                <a href="usuarios.php" class="boton boton-suave">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- ========== LISTA DE USUARIOS ========== -->
    <div class="barra-acciones">
        <span>Total: <?= count($usuarios) ?> usuario(s)</span>
        <a href="usuarios.php?accion=crear" class="boton boton-primario">+ Nuevo usuario</a>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Estado</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                    <?php $esYo = (int) $u['id'] === (int) $_SESSION['usuario_id']; ?>
                    <tr>
                        <td><?= (int) $u['id'] ?></td>
                        <td><strong><?= e($u['nombre']) ?></strong></td>
                        <td><?= e($u['email']) ?></td>
                        <td><span class="etiqueta etiqueta-azul"><?= e(ucfirst($u['rol'])) ?></span></td>
                        <td>
                            <?php if ($u['estado'] === 'activo'): ?>
                                <span class="etiqueta etiqueta-verde">Activo</span>
                            <?php else: ?>
                                <span class="etiqueta etiqueta-rojo">Bloqueado</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e(date('d/m/Y', strtotime($u['created_at']))) ?></td>
                        <td>
                            <div class="acciones">
                                <a href="usuarios.php?accion=editar&id=<?= (int) $u['id'] ?>"
                                   class="boton boton-suave boton-pequeno">Editar</a>

                                <?php if (!$esYo): ?>
                                    <?php if ($u['estado'] === 'activo'): ?>
                                        <form method="POST" action="usuarios.php"
                                              class="confirmar-eliminar"
                                              data-mensaje="¿Bloquear a '<?= e($u['nombre']) ?>'?">
                                            <input type="hidden" name="accion" value="bloquear">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="boton boton-peligro boton-pequeno">
                                                Bloquear
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" action="usuarios.php">
                                            <input type="hidden" name="accion" value="desbloquear">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button type="submit" class="boton boton-exito boton-pequeno">
                                                Desbloquear
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <form method="POST" action="usuarios.php"
                                          class="confirmar-eliminar"
                                          data-mensaje="¿ELIMINAR a '<?= e($u['nombre']) ?>'? Esta acción no se puede deshacer.">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button type="submit" class="boton boton-peligro boton-pequeno">
                                            Eliminar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="libro-datos">(tú)</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

