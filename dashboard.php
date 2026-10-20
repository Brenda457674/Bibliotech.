<?php
// ============================================================
// dashboard.php - Panel principal
// El menú y las estadísticas cambian según el rol.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requiereLogin();

$usuario = usuarioActual($conexion);

// Contadores simples para las tarjetas del panel
$stats = [];

if ($usuario['rol'] === 'estudiante') {
    // El estudiante solo ve sus propios datos
    $sql = "SELECT COUNT(*) FROM reservas
            WHERE id_usuario = :id AND estado = 'pendiente'";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id' => $usuario['id']]);
    $stats[] = ['valor' => $stmt->fetchColumn(), 'texto' => 'Reservas pendientes', 'enlace' => 'reservas.php'];

    $sql = "SELECT COUNT(*) FROM prestamos
            WHERE id_usuario = :id AND estado = 'activo'";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id' => $usuario['id']]);
    $stats[] = ['valor' => $stmt->fetchColumn(), 'texto' => 'Préstamos activos', 'enlace' => 'prestamos.php'];

    $sql = "SELECT COUNT(*) FROM multas m
            INNER JOIN prestamos p ON p.id = m.id_prestamo
            WHERE p.id_usuario = :id AND m.estado = 'pendiente'";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id' => $usuario['id']]);
    $stats[] = ['valor' => $stmt->fetchColumn(), 'texto' => 'Multas pendientes', 'enlace' => 'prestamos.php'];

} else {
    // Bibliotecario y administrador ven los totales de la biblioteca
    $stats[] = ['valor' => $conexion->query("SELECT COUNT(*) FROM libros")->fetchColumn(),
                'texto' => 'Libros registrados', 'enlace' => 'libros.php'];

    $stats[] = ['valor' => $conexion->query("SELECT COUNT(*) FROM reservas WHERE estado = 'pendiente'")->fetchColumn(),
                'texto' => 'Reservas por confirmar', 'enlace' => 'reservas.php'];

    $stats[] = ['valor' => $conexion->query("SELECT COUNT(*) FROM prestamos WHERE estado = 'activo'")->fetchColumn(),
                'texto' => 'Préstamos activos', 'enlace' => 'prestamos.php'];

    $stats[] = ['valor' => $conexion->query("SELECT COUNT(*) FROM multas WHERE estado = 'pendiente'")->fetchColumn(),
                'texto' => 'Multas pendientes', 'enlace' => 'multas.php'];

    if ($usuario['rol'] === 'administrador') {
        $stats[] = ['valor' => $conexion->query("SELECT COUNT(*) FROM usuarios")->fetchColumn(),
                    'texto' => 'Usuarios registrados', 'enlace' => 'usuarios.php'];
    }
}

$tituloPagina = 'Panel principal';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina">Hola, <?= e($usuario['nombre']) ?> 👋</h1>
<p class="subtitulo">
    Estás ingresando como
    <strong><?= e(ucfirst($usuario['rol'])) ?></strong>.
    Elige una opción del menú o usa los accesos rápidos.
</h1>

<div class="grid-tarjetas">
    <?php foreach ($stats as $stat): ?>
        <a href="<?= e($stat['enlace']) ?>" class="tarjeta tarjeta-stat">
            <span class="stat-valor"><?= (int) $stat['valor'] ?></span>
            <span class="stat-texto"><?= e($stat['texto']) ?></span>
        </a>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
