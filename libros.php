<?php
// ============================================================
// libros.php - CRUD de libros
// Acceso: bibliotecario y administrador.
// Maneja portada (portada_url) y stock total / disponible.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requiereRol(['bibliotecario', 'administrador']);

$errores = [];

// ============================================================
// 1) PROCESAMIENTO DEL FORMULARIO (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // --- Crear o editar ---
    if ($accion === 'crear' || $accion === 'editar') {
        $id               = (int) ($_POST['id'] ?? 0);
        $titulo           = trim($_POST['titulo'] ?? '');
        $autor            = trim($_POST['autor'] ?? '');
        $isbn             = trim($_POST['isbn'] ?? '');
        $id_categoria     = (int) ($_POST['id_categoria'] ?? 0) ?: null;
        $editorial        = trim($_POST['editorial'] ?? '');
        $anio             = trim($_POST['anio'] ?? '');
        $portada_url      = trim($_POST['portada_url'] ?? '');
        $sinopsis         = trim($_POST['sinopsis'] ?? '');
        $stock_total      = (int) ($_POST['stock_total'] ?? 0);
        $stock_disponible = (int) ($_POST['stock_disponible'] ?? 0);

        // --- Validaciones ---
        if ($titulo === '') {
            $errores[] = 'El título es obligatorio.';
        }
        if ($autor === '') {
            $errores[] = 'El autor es obligatorio.';
        }
        if ($stock_total < 0) {
            $errores[] = 'El stock total no puede ser negativo.';
        }
        if ($stock_disponible < 0 || $stock_disponible > $stock_total) {
            $errores[] = 'El stock disponible debe estar entre 0 y el stock total.';
        }
        if ($anio !== '' && (!ctype_digit($anio) || (int) $anio < 1000 || (int) $anio > 2100)) {
            $errores[] = 'El año debe ser un número entre 1000 y 2100 (o déjalo vacío).';
        }

        // ISBN único (la tabla tiene índice único): verificamos antes de guardar
        if ($isbn !== '') {
            $sql = "SELECT id FROM libros WHERE isbn = :isbn";
            $parametros = [':isbn' => $isbn];
            if ($accion === 'editar') {
                $sql .= " AND id <> :id";
                $parametros[':id'] = $id;
            }
            $stmt = $conexion->prepare($sql);
            $stmt->execute($parametros);
            if ($stmt->fetch()) {
                $errores[] = 'Ya existe un libro con ese ISBN.';
            }
        }

        // --- Guardar ---
        if (!$errores) {
            if ($accion === 'crear') {
                $sql = "INSERT INTO libros
                        (titulo, autor, isbn, id_categoria, editorial, anio,
                         portada_url, sinopsis, stock_total, stock_disponible)
                        VALUES
                        (:titulo, :autor, :isbn, :id_categoria, :editorial, :anio,
                         :portada_url, :sinopsis, :stock_total, :stock_disponible)";
                $mensaje = 'Libro creado correctamente.';
            } else {
                $sql = "UPDATE libros
                        SET titulo = :titulo, autor = :autor, isbn = :isbn,
                            id_categoria = :id_categoria, editorial = :editorial,
                            anio = :anio, portada_url = :portada_url,
                            sinopsis = :sinopsis, stock_total = :stock_total,
                            stock_disponible = :stock_disponible
                        WHERE id = :id";
                $mensaje = 'Libro actualizado correctamente.';
            }
            $stmt = $conexion->prepare($sql);
            $parametros = [
                ':titulo'           => $titulo,
                ':autor'            => $autor,
                ':isbn'             => $isbn ?: null,
                ':id_categoria'     => $id_categoria,
                ':editorial'        => $editorial ?: null,
                ':anio'             => $anio !== '' ? (int) $anio : null,
                ':portada_url'      => $portada_url ?: null,
                ':sinopsis'         => $sinopsis ?: null,
                ':stock_total'      => $stock_total,
                ':stock_disponible' => $stock_disponible,
            ];
            if ($accion === 'editar') {
                $parametros[':id'] = $id;
            }
            $stmt->execute($parametros);

            setFlash('exito', $mensaje);
            header('Location: libros.php');
            exit;
        }
    }

    // --- Eliminar ---
    if ($accion === 'eliminar') {
        $id = (int) ($_POST['id'] ?? 0);

        // No borramos si el libro tiene préstamos en su historial
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM prestamos WHERE id_libro = :id");
        $stmt->execute([':id' => $id]);
        $totalPrestamos = (int) $stmt->fetchColumn();

        if ($totalPrestamos > 0) {
            setFlash('error', "No se puede eliminar: el libro tiene {$totalPrestamos} préstamo(s) registrados. "
                            . "Pon su stock en 0 para retirarlo del catálogo.");
        } else {
            $stmt = $conexion->prepare("DELETE FROM libros WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlash('exito', 'Libro eliminado correctamente.');
        }
        header('Location: libros.php');
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

// Lista desplegable de categorías (para el formulario)
$categorias = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();

// Datos del libro que se está creando/editando
$libro = [
    'id' => 0, 'titulo' => '', 'autor' => '', 'isbn' => '', 'id_categoria' => null,
    'editorial' => '', 'anio' => '', 'portada_url' => '', 'sinopsis' => '',
    'stock_total' => 1, 'stock_disponible' => 1,
];

if ($vista === 'editar') {
    $id = (int) ($_GET['id'] ?? 0);
    $stmt = $conexion->prepare("SELECT * FROM libros WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $libroEncontrado = $stmt->fetch();

    if (!$libroEncontrado) {
        setFlash('error', 'El libro solicitado no existe.');
        header('Location: libros.php');
        exit;
    }
    $libro = $libroEncontrado;
}

// Si hubo errores, conservamos lo que escribió el usuario
if ($errores && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $libro = array_merge($libro, $_POST);
    $libro['id'] = (int) ($_POST['id'] ?? 0);
    $vista = $_POST['accion'] ?? 'listar';
}

// Lista completa de libros con su categoría
$sql = "SELECT l.*, c.nombre AS categoria
        FROM libros l
        LEFT JOIN categorias c ON c.id = l.id_categoria
        ORDER BY l.titulo";
$libros = $conexion->query($sql)->fetchAll();

$tituloPagina = 'Libros';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina">Libros</h1>

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
    <div class="tarjeta" style="max-width: 640px;">
        <h2><?= $vista === 'crear' ? 'Nuevo libro' : 'Editar libro' ?></h2>

        <form method="POST" action="libros.php" class="formulario">
            <input type="hidden" name="accion" value="<?= e($vista) ?>">
            <input type="hidden" name="id" value="<?= (int) $libro['id'] ?>">

            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" maxlength="255"
                   value="<?= e($libro['titulo']) ?>" required>

            <label for="autor">Autor *</label>
            <input type="text" id="autor" name="autor" maxlength="180"
                   value="<?= e($libro['autor']) ?>" required>

            <div class="dos-columnas">
                <div>
                    <label for="isbn">ISBN</label>
                    <input type="text" id="isbn" name="isbn" maxlength="20"
                           value="<?= e($libro['isbn']) ?>">
                </div>
                <div>
                    <label for="id_categoria">Categoría</label>
                    <select id="id_categoria" name="id_categoria">
                        <option value="0">— Sin categoría —</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= (int) $libro['id_categoria'] === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="dos-columnas">
                <div>
                    <label for="editorial">Editorial</label>
                    <input type="text" id="editorial" name="editorial" maxlength="150"
                           value="<?= e($libro['editorial']) ?>">
                </div>
                <div>
                    <label for="anio">Año</label>
                    <input type="number" id="anio" name="anio" min="1000" max="2100"
                           value="<?= e((string) $libro['anio']) ?>">
                </div>
            </div>

            <label for="portada_url">Portada (URL de la imagen)</label>
            <input type="url" id="portada_url" name="portada_url" maxlength="500"
                   placeholder="https://covers.openlibrary.org/b/isbn/..."
                   value="<?= e($libro['portada_url']) ?>">

            <label for="sinopsis">Sinopsis</label>
            <textarea id="sinopsis" name="sinopsis" rows="3"><?= e($libro['sinopsis']) ?></textarea>

            <div class="dos-columnas">
                <div>
                    <label for="stock_total">Stock total *</label>
                    <input type="number" id="stock_total" name="stock_total" min="0"
                           value="<?= (int) $libro['stock_total'] ?>" required>
                </div>
                <div>
                    <label for="stock_disponible">Stock disponible *</label>
                    <input type="number" id="stock_disponible" name="stock_disponible" min="0"
                           value="<?= (int) $libro['stock_disponible'] ?>" required>
                </div>
            </div>

            <div style="margin-top: 0.8rem; display: flex; gap: 0.5rem;">
                <button type="submit" class="boton boton-primario">
                    <?= $vista === 'crear' ? 'Crear' : 'Guardar cambios' ?>
                </button>
                <a href="libros.php" class="boton boton-suave">Cancelar</a>
            </div>
        </form>
    </div>
<?php else: ?>
    <!-- ========== LISTA DE LIBROS ========== -->
    <div class="barra-acciones">
        <span>Total: <?= count($libros) ?> libro(s)</span>
        <a href="libros.php?accion=crear" class="boton boton-primario">+ Nuevo libro</a>
    </div>

    <div class="tabla-contenedor">
        <table class="tabla">
            <thead>
                <tr>
                    <th>Portada</th>
                    <th>Título / Autor</th>
                    <th>Categoría</th>
                    <th>ISBN</th>
                    <th>Año</th>
                    <th>Stock</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$libros): ?>
                    <tr>
                        <td colspan="7" class="fila-vacia">No hay libros registrados.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($libros as $lib): ?>
                    <tr>
                        <td>
                            <?php if ($lib['portada_url']): ?>
                                <img src="<?= e($lib['portada_url']) ?>" alt="Portada"
                                     style="width: 42px; height: 60px; object-fit: cover; border-radius: 4px;"
                                     onerror="this.style.display='none';">
                            <?php else: ?>
                                📖
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= e($lib['titulo']) ?></strong><br>
                            <small><?= e($lib['autor']) ?></small>
                        </td>
                        <td><?= e($lib['categoria'] ?: '—') ?></td>
                        <td><?= e($lib['isbn'] ?: '—') ?></td>
                        <td><?= e($lib['anio'] ?: '—') ?></td>
                        <td>
                            <?php if ((int) $lib['stock_disponible'] === 0): ?>
                                <span class="etiqueta etiqueta-rojo">Agotado</span>
                            <?php else: ?>
                                <?= (int) $lib['stock_disponible'] ?> / <?= (int) $lib['stock_total'] ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="acciones">
                                <a href="libros.php?accion=editar&id=<?= (int) $lib['id'] ?>"
                                   class="boton boton-suave boton-pequeno">Editar</a>

                                <form method="POST" action="libros.php"
                                      class="confirmar-eliminar"
                                      data-mensaje="¿Eliminar el libro '<?= e($lib['titulo']) ?>'?">
                                    <input type="hidden" name="accion" value="eliminar">
                                    <input type="hidden" name="id" value="<?= (int) $lib['id'] ?>">
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


