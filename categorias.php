<?php
// ============================================================
// categorias.php - CRUD de categorías
// Acceso: bibliotecario y administrador.
// Patrón: un solo archivo con ?accion=crear | editar | eliminar
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requiereRol(['bibliotecario', 'administrador']);

$errores = [];

// ============================================================
// 1) PROCESAMIENTO DEL FORMULARIO (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // --- Crear o editar (comparten la misma validación) ---
    if ($accion === 'crear' || $accion === 'editar') {
        $id          = (int) ($_POST['id'] ?? 0);
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        // Validaciones
        if ($nombre === '') {
            $errores[] = 'El nombre de la categoría es obligatorio.';
        } elseif (mb_strlen($nombre) > 100) {
            $errores[] = 'El nombre no puede superar los 100 caracteres.';
        } else {
            // El nombre debe ser único (la tabla tiene índice único)
            $sql = "SELECT id FROM categorias WHERE nombre = :nombre";
            $parametros = [':nombre' => $nombre];
            if ($accion === 'editar') {
                $sql .= " AND id <> :id";
                $parametros[':id'] = $id;
            }
            $stmt = $conexion->prepare($sql);
            $stmt->execute($parametros);
            if ($stmt->fetch()) {
                $errores[] = 'Ya existe una categoría con ese nombre.';
            }
        }

        // Guardar si no hubo errores
        if (!$errores) {
            if ($accion === 'crear') {
                $sql = "INSERT INTO categorias (nombre, descripcion)
                        VALUES (:nombre, :descripcion)";
                $mensaje = 'Categoría creada correctamente.';
            } else {
                $sql = "UPDATE categorias
                        SET nombre = :nombre, descripcion = :descripcion
                        WHERE id = :id";
                $mensaje = 'Categoría actualizada correctamente.';
            }
            $stmt = $conexion->prepare($sql);
            $parametros = [':nombre' => $nombre, ':descripcion' => $descripcion];
            if ($accion === 'editar') {
                $parametros[':id'] = $id;
            }
            $stmt->execute($parametros);

            setFlash('exito', $mensaje);
            header('Location: categorias.php');
            exit;
        }
    }

    // --- Eliminar ---
    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        // No eliminamos si la categoría tiene libros asociados
        $stmt = $conexion->prepare(
            "SELECT COUNT(*) FROM libros WHERE id_categoria = :id"
        );
        $stmt->execute([':id' => $id]);
        $totalLibros = (int) $stmt->fetchColumn();

        if ($totalLibros > 0) {
            setFlash('error', "No se puede eliminar: hay {$totalLibros} libro(s) en esta categoría. "
                            . "Primero mueve esos libros a otra categoría.");
        } else {
            $stmt = $conexion->prepare("DELETE FROM categorias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlash('exito', 'Categoría eliminada correctamente.');
        }
        header('Location: categorias.php');
        exit;
    }
}

// ============================================================
// 2) DATOS PARA LA VISTA
// ============================================================
// ¿Qué vista mostramos? 'listar' (por defecto), 'crear' o 'editar'
$vista = $_GET['accion'] ?? 'listar';
if (!in_array($vista, ['crear', 'editar'], true)) {
    $vista = 'listar';
}

// Datos de la categoría que se está creando/editando
$categoria = ['id' => 0, 'nombre' => '', 'descripcion' => ''];

if ($vista === 'editar') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conexion->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $categoriaEncontrada = $stmt->fetch();

    if (!$categoriaEncontrada) {
        setFlash('error', 'La categoría solicitada no existe.');
        header('Location: categorias.php');
        exit;
    }
    $categoria = $categoriaEncontrada;
}

// Si el formulario tuvo errores, conservamos lo que escribió el usuario
if ($errores && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoria = [
        'id'          => (int) ($_POST['id'] ?? 0),
        'nombre'      => $_POST['nombre'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? '',
    ];
    $vista = $_POST['accion'] ?? 'listar';
}

// Lista de categorías con el total de libros de cada una
$sql = "SELECT c.*, (SELECT COUNT(*) FROM libros l WHERE l.id_categoria = c.id) AS total_libros
        FROM categorias c
        ORDER BY c.nombre";
$categorias = $conexion->query($sql)->fetchAll();

// Las categorías también se necesitan en el formulario de libros
// (se guardan aquí para reutilizarlas si hace falta)
$tituloPagina = 'Categorías';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina">Categorías</h1>

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
        <h2><?= $vista === 'crear' ? 'Nueva categoría' : 'Editar categoría' ?></h2>

        <form method="POST" action="categorias.php" class="formulario">
            <input type="hidden" name="accion" value="<?= e($vista) ?>">
            <input type="hidden" name="id" value="<?= (int) $categoria['id'] ?>">

            <label for="nombre">Nombre *</label>
            <input type="text" id="nombre" name="nombre" maxlength="100"
                   value="<?= e($categoria['nombre']) ?>" required>

            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= e($categoria['descripcion']) ?></textarea>

            <div style="margin-top: 0.8rem; display: flex; gap: 0.5rem;">
                <button type="submit" class="boton boton-primario">
                    <?= $vista === 'crear' ? 'Crear' : 'Guardar cambios' ?>
                </button>
                <a href="categorias.php" class="boton boton-suave">Cancelar</a>
            </div>
        </form>
    </div>

<?php else: ?>
    <!-- ========== LISTA DE CATEGORÍAS ========== -->
    <div class="barra-acciones">
        <span>Total: <?= count($categorias) ?> categoría(s)</span>
        <a href="categorias.php?accion=crear" class="boton boton-primario">+ Nueva categoría</a>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th>Libros</th>
                    <th>Creada</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$categorias): ?>
                    <tr>
                        <td colspan="6" class="fila-vacia">No hay categorías registradas.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?= (int) $cat['id'] ?></td>
                        <td><strong><?= e($cat['nombre']) ?></strong></td>
                        <td><?= e($cat['descripcion'] ?: '—') ?></td>
                        <td><?= (int) $cat['total_libros'] ?></td>
                        <td><?= e(date('d/m/Y', strtotime($cat['created_at']))) ?></td>
                        <td>
                            <div class="acciones">
                                <a href="categorias.php?accion=editar&id=<?= (int) $cat['id'] ?>"
                                   class="boton boton-suave boton-pequeno">Editar</a>

                                <!-- Eliminar siempre va por POST + confirmación de JS -->
                                <form method="POST" action="categorias.php"
                                      class="confirmar-eliminar"
                                      data-mensaje="¿Eliminar la categoría '<?= e($cat['nombre']) ?>'?">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $cat['id'] ?>">
                                    <button type="submit" class="boton boton-peligro boton-pequeno">Eliminar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
