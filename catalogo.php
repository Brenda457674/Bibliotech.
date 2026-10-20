<?php
// ============================================================
// catalogo.php - Catálogo público de libros
// Búsqueda por título, autor o categoría.
// Visible para todos; el botón "Reservar" solo aparece
// si el usuario es estudiante y hay stock disponible.
// ============================================================
require_once __DIR__ . '/includes/auth.php';

// Texto de búsqueda y categoría elegida (salen del formulario GET)
$busqueda   = trim($_GET['buscar'] ?? '');
$idCategoriaFiltro = (int) ($_GET['categoria'] ?? 0);

// --- Consulta dinámica con consultas preparadas ---
$sql = "SELECT l.*, c.nombre AS categoria
        FROM libros l
        LEFT JOIN categorias c ON c.id = l.id_categoria
        WHERE 1 = 1";
$parametros = [];

// Buscar por título O autor (LIKE)
if ($busqueda !== '') {
    $sql .= " AND (l.titulo LIKE :busqueda OR l.autor LIKE :busqueda)";
    $parametros[':busqueda'] = '%' . $busqueda . '%';
}

// Filtrar por categoría
if ($idCategoriaFiltro > 0) {
    $sql .= " AND l.id_categoria = :categoria";
    $parametros[':categoria'] = $idCategoriaFiltro;
}

$sql .= " ORDER BY l.titulo";
$stmt = $conexion->prepare($sql);
$stmt->execute($parametros);
$libros = $stmt->fetchAll();

// Lista de categorías para el desplegable de filtros
$categorias = $conexion->query("SELECT id, nombre FROM categorias ORDER BY nombre")->fetchAll();

$usuario = usuarioActual($conexion);

$tituloPagina = 'Catálogo';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina">Catálogo de libros</h1>
<p class="subtitulo">Busca por título, autor o categoría.</p>

<!-- ========== BUSCADOR ========== -->
<form method="GET" action="catalogo.php" class="barra-busqueda">
    <div class="campo">
        <label for="buscar">Título o autor</label>
        <input type="text" id="buscar" name="buscar"
               value="<?= e($busqueda) ?>" placeholder="Ej: Clean Code, Borges...">
    </div>
    <div class="campo">
        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <option value="0">— Todas —</option>
            <?php foreach ($categorias as $cat): ?>
                <option value="<?= (int) $cat['id'] ?>"
                    <?= $idCategoriaFiltro === (int) $cat['id'] ? 'selected' : '' ?>>
                    <?= e($cat['nombre']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" class="boton boton-primario">Buscar</button>
    <a href="catalogo.php" class="boton boton-suave">Limpiar</a>
</form>

<p class="subtitulo"><?= count($libros) ?> resultado(s)</p>

<!-- ========== RESULTADOS ========== -->
<div class="grid-libros">
    <?php if (!$libros): ?>
        <div class="alerta alerta-aviso">
            No se encontraron libros con esos criterios.
        </div>
    <?php endif; ?>

    <?php foreach ($libros as $lib): ?>
        <div class="libro-tarjeta">
            <?php if ($lib['portada_url']): ?>
                <img class="libro-portada" src="<?= e($lib['portada_url']) ?>"
                     alt="Portada de <?= e($lib['titulo']) ?>"
                     onerror="this.outerHTML='<div class=\'libro-portada-fallback\'><span class=\'libro-portada-icono\'>📖</span><span class=\'libro-portada-texto\'>Sin portada</span></div>';">
            <?php else: ?>
                <div class="libro-portada-fallback">
                    <span class="libro-portada-icono">📖</span>
                    <span class="libro-portada-texto">Sin portada</span>
                </div>
            <?php endif; ?>

            <div class="libro-cuerpo">
                <span class="libro-titulo"><?= e($lib['titulo']) ?></span>
                <span class="libro-autor"><?= e($lib['autor']) ?></span>
                <span class="libro-datos">
                    <?= e($lib['categoria'] ?: 'Sin categoría') ?>
                    <?= $lib['anio'] ? ' · ' . (int) $lib['anio'] : '' ?>
                </span>

                <?php if ((int) $lib['stock_disponible'] > 0): ?>
                    <span class="etiqueta etiqueta-verde">
                        Disponible (<?= (int) $lib['stock_disponible'] ?>)
                    </span>
                <?php else: ?>
                    <span class="etiqueta etiqueta-rojo">No disponible</span>
                <?php endif; ?>

                <div class="libro-acciones">
                    <?php if (!$usuario): ?>
                        <a href="login.php" class="boton boton-suave boton-pequeno">
                            Inicia sesión para reservar
                        </a>
                    <?php elseif ($usuario['rol'] === 'estudiante'
                                  && (int) $lib['stock_disponible'] > 0): ?>
                        <!-- Reservar es un POST pequeño directo desde el catálogo -->
                        <form method="POST" action="reservas.php">
                            <input type="hidden" name="accion" value="reservar">
                            <input type="hidden" name="id_libro" value="<?= (int) $lib['id'] ?>">
                            <button type="submit" class="boton boton-primario boton-pequeno">
                                Reservar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
