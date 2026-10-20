<?php
// ============================================================
// multas.php - Multas por devoluciones tardías
// Las multas se generan SOLAS al registrar una devolución tarde
// (ver módulo 6: prestamos.php).
// Aquí el bibliotecario/administrador las marca como
// "pagada" o "condonada". El estudiante solo consulta las suyas.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requiereLogin();

$usuario = usuarioActual($conexion);
$esStaff = in_array($usuario['rol'], ['bibliotecario', 'administrador'], true);

// ============================================================
// 1) PROCESAMIENTO (POST) - solo personal
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if (!$esStaff) {
        setFlash('error', 'No tienes permiso para realizar esa acción.');
        header('Location: multas.php');
        exit;
    }

    if ($accion === 'pagar' || $accion === 'condonar') {
        // Solo se puede accionar sobre multas pendientes
        $stmt = $conexion->prepare("SELECT * FROM multas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $multa = $stmt->fetch();

        if (!$multa) {
            setFlash('error', 'La multa no existe.');
        } elseif ($multa['estado'] !== 'pendiente') {
            setFlash('error', 'Esta multa ya fue resuelta.');
        } else {
            if ($accion === 'pagar') {
                // "pagada": además registramos la fecha de pago
                $sql = "UPDATE multas
                        SET estado = 'pagada', fecha_pago = NOW()
                        WHERE id = :id";
                setFlash('exito', 'Multa marcada como PAGADA.');
            } else {
                // "condonada": la biblioteca perdona la multa
                $sql = "UPDATE multas
                        SET estado = 'condonada', fecha_pago = NOW()
                        WHERE id = :id";
                setFlash('exito', 'Multa CONDONADA.');
            }
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':id' => $id]);
        }
        header('Location: multas.php');
        exit;
    }
}

// ============================================================
// 2) DATOS PARA LA VISTA
// ============================================================
// El estudiante ve SUS multas; el personal ve todas.
$sql = "SELECT m.*, p.id_usuario, p.id_libro, p.fecha_devolucion_real,
               u.nombre AS usuario, u.email, l.titulo AS libro
        FROM multas m
        INNER JOIN prestamos p ON p.id = m.id_prestamo
        INNER JOIN usuarios u  ON u.id = p.id_usuario
        INNER JOIN libros l    ON l.id = p.id_libro";
if (!$esStaff) {
    $sql .= " WHERE p.id_usuario = :id";
}
$sql .= " ORDER BY m.fecha_generacion DESC";
$stmt = $conexion->prepare($sql);
if ($esStaff) {
    $stmt->execute();
} else {
    $stmt->execute([':id' => $usuario['id']]);
}
$multas = $stmt->fetchAll();

// Total pendiente (solo informativo)
$totalPendiente = 0;
foreach ($multas as $m) {
    if ($m['estado'] === 'pendiente') {
        $totalPendiente += (float) $m['monto'];
    }
}

// Etiqueta de color para cada estado de multa
function etiquetaMulta(string $estado): string
{
    $mapa = [
        'pendiente' => ['etiqueta-amarillo', 'Pendiente'],
        'pagada'    => ['etiqueta-verde', 'Pagada'],
        'condonada' => ['etiqueta-gris', 'Condonada'],
    ];
    [$clase, $texto] = $mapa[$estado] ?? ['etiqueta-gris', $estado];
    return "<span class=\"etiqueta {$clase}\">{$texto}</span>";
}

$tituloPagina = 'Multas';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina"><?= $esStaff ? 'Multas' : 'Mis multas' ?></h1>

<?php if ($esStaff): ?>
    <p class="subtitulo">
        Total pendiente por cobrar:
        <strong>S/ <?= number_format($totalPendiente, 2) ?></strong>
        · Las multas se generan automáticamente al devolver tarde
        (S/ 1.50 por día de retraso).
    </p>
<?php endif; ?>

<div class="tabla-contenedor">
    <table class="tabla">
        <thead>
            <tr>
                <th>ID</th>
                <?php if ($esStaff): ?>
                    <th>Estudiante</th>
                <?php endif; ?>
                <th>Libro</th>
                <th>Devuelto</th>
                <th>Monto</th>
                <th>Generada</th>
                <th>Estado</th>
                <?php if ($esStaff): ?>
                    <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!$multas): ?>
                <tr>
                    <td colspan="8" class="fila-vacia">
                        No hay multas. ¡Que siga así! 🎉
                    </td>
                </tr>
            <?php endif; ?>

            <?php foreach ($multas as $m): ?>
                <tr>
                    <td><?= (int) $m['id'] ?></td>
                    <?php if ($esStaff): ?>
                        <td>
                            <strong><?= e($m['usuario']) ?></strong><br>
                            <small><?= e($m['email']) ?></small>
                        </td>
                    <?php endif; ?>
                    <td><?= e($m['libro']) ?></td>
                    <td>
                        <?= $m['fecha_devolucion_real']
                            ? e(date('d/m/Y', strtotime($m['fecha_devolucion_real'])))
                            : '—' ?>
                    </td>
                    <td><strong>S/ <?= number_format((float) $m['monto'], 2) ?></strong></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($m['fecha_generacion']))) ?></td>
                    <td><?= etiquetaMulta($m['estado']) ?></td>
                    <?php if ($esStaff): ?>
                        <td>
                            <div class="acciones">
                                <?php if ($m['estado'] === 'pendiente'): ?>
                                    <form method="POST" action="multas.php"
                                          class="confirmar-eliminar"
                                          data-mensaje="¿Marcar esta multa como PAGADA?">
                                        <input type="hidden" name="accion" value="pagar">
                                        <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                        <button type="submit" class="boton boton-exito boton-pequeno">
                                            Pagada
                                        </button>
                                    </form>

                                    <form method="POST" action="multas.php"
                                          class="confirmar-eliminar"
                                          data-mensaje="¿CONDONAR esta multa? El estudiante no pagará.">
                                        <input type="hidden" name="accion" value="condonar">
                                        <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                                        <button type="submit" class="boton boton-suave boton-pequeno">
                                            Condonar
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="libro-datos">
                                        Resuelta el
                                        <?= $m['fecha_pago']
                                            ? e(date('d/m/Y', strtotime($m['fecha_pago'])))
                                            : '—' ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

