<?php
// ============================================================
// reservas.php - Reservas de libros
// - Estudiante: reserva (desde aquí o desde el catálogo) y cancela.
// - Bibliotecario/Administrador: confirma o cancela reservas.
// Una reserva "pendiente" expira a los 3 días si no se confirma.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requiereLogin();

$usuario = usuarioActual($conexion);
$esStaff = in_array($usuario['rol'], ['bibliotecario', 'administrador'], true);

// ============================================================
// 1) PROCESAMIENTO (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // --- Reservar (solo estudiantes) ---
    if ($accion === 'reservar') {
        $idLibro = (int) ($_POST['id_libro'] ?? 0);

        if ($usuario['rol'] !== 'estudiante') {
            setFlash('error', 'Solo los estudiantes pueden reservar libros.');
        } else {
            // El libro debe existir y tener stock disponible
            $stmt = $conexion->prepare("SELECT * FROM libros WHERE id = :id");
            $stmt->execute([':id' => $idLibro]);
            $libro = $stmt->fetch();

            if (!$libro) {
                setFlash('error', 'El libro solicitado no existe.');
            } elseif ((int) $libro['stock_disponible'] <= 0) {
                setFlash('error', 'Este libro no tiene ejemplares disponibles.');
            } else {
                // No permitir dos reservas activas del mismo libro
                $sql = "SELECT id FROM reservas
                        WHERE id_usuario = :usuario AND id_libro = :libro
                          AND estado IN ('pendiente', 'confirmada')";
                $stmt = $conexion->prepare($sql);
                $stmt->execute([':usuario' => $usuario['id'], ':libro' => $idLibro]);

                if ($stmt->fetch()) {
                    setFlash('error', 'Ya tienes una reserva activa para este libro.');
                } else {
                    // La reserva expira en 3 días si no se confirma
                    $sql = "INSERT INTO reservas (id_usuario, id_libro, fecha_expiracion, estado)
                            VALUES (:usuario, :libro, DATE_ADD(NOW(), INTERVAL 3 DAY), 'pendiente')";
                    $stmt = $conexion->prepare($sql);
                    $stmt->execute([':usuario' => $usuario['id'], ':libro' => $idLibro]);

                    setFlash('exito', 'Reserva registrada. El bibliotecario debe confirmarla '
                                    . 'antes de que expire (3 días).');
                }
            }
        }
        header('Location: reservas.php');
        exit;
    }

    // --- Cancelar (estudiante lo propio; staff cualquiera) ---
    if ($accion === 'cancelar') {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $conexion->prepare("SELECT * FROM reservas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $reserva = $stmt->fetch();

        if (!$reserva) {
            setFlash('error', 'La reserva no existe.');
        } elseif (!$esStaff && (int) $reserva['id_usuario'] !== (int) $usuario['id']) {
            setFlash('error', 'No puedes cancelar una reserva que no es tuya.');
        } elseif (in_array($reserva['estado'], ['cancelada', 'expirada'], true)) {
            setFlash('error', 'Esta reserva ya estaba cerrada.');
        } else {
            $stmt = $conexion->prepare(
                "UPDATE reservas SET estado = 'cancelada' WHERE id = :id"
            );
            $stmt->execute([':id' => $id]);
            setFlash('exito', 'Reserva cancelada correctamente.');
        }
        header('Location: reservas.php');
        exit;
    }

    // --- Confirmar (solo bibliotecario / administrador) ---
    if ($accion === 'confirmar') {
        if (!$esStaff) {
            setFlash('error', 'No tienes permiso para confirmar reservas.');
            header('Location: reservas.php');
            exit;
        }

        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $conexion->prepare("SELECT * FROM reservas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $reserva = $stmt->fetch();

        if (!$reserva) {
            setFlash('error', 'La reserva no existe.');
        } elseif ($reserva['estado'] !== 'pendiente') {
            setFlash('error', 'Solo se pueden confirmar reservas pendientes.');
        } elseif (strtotime($reserva['fecha_expiracion']) < time()) {
            // Pasó la fecha de expiración: ya no se puede confirmar
            $stmt = $conexion->prepare(
                "UPDATE reservas SET estado = 'expirada' WHERE id = :id"
            );
            $stmt->execute([':id' => $id]);
            setFlash('error', 'La reserva ya expiró el '
                    . date('d/m/Y H:i', strtotime($reserva['fecha_expiracion'])) . '.');
        } else {
            $stmt = $conexion->prepare(
                "UPDATE reservas SET estado = 'confirmada' WHERE id = :id"
            );
            $stmt->execute([':id' => $id]);
            setFlash('exito', 'Reserva confirmada. Ya puede registrarse el préstamo.');
        }
        header('Location: reservas.php');
        exit;
    }
} // fin del procesamiento POST

// ============================================================
// 2) DATOS PARA LA VISTA
// ============================================================
// El estudiante ve SUS reservas; el personal ve TODAS.
if ($esStaff) {
    $sql = "SELECT r.*, u.nombre AS usuario, u.email, l.titulo AS libro
            FROM reservas r
            INNER JOIN usuarios u ON u.id = r.id_usuario
            INNER JOIN libros l  ON l.id = r.id_libro
            ORDER BY r.created_at DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
} else {
    $sql = "SELECT r.*, u.nombre AS usuario, u.email, l.titulo AS libro
            FROM reservas r
            INNER JOIN usuarios u ON u.id = r.id_usuario
            INNER JOIN libros l  ON l.id = r.id_libro
            WHERE r.id_usuario = :id
            ORDER BY r.created_at DESC";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id' => $usuario['id']]);
}
$reservas = $stmt->fetchAll();

// Etiqueta de color para cada estado de reserva
function etiquetaReserva(string $estado): string
{
    $mapa = [
        'pendiente'  => ['etiqueta-amarillo', 'Pendiente'],
        'confirmada' => ['etiqueta-verde', 'Confirmada'],
        'expirada'   => ['etiqueta-gris', 'Expirada'],
        'cancelada'  => ['etiqueta-rojo', 'Cancelada'],
    ];
    [$clase, $texto] = $mapa[$estado] ?? ['etiqueta-gris', $estado];
    return "<span class=\"etiqueta {$clase}\">{$texto}</span>";
}

$tituloPagina = 'Reservas';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina"><?= $esStaff ? 'Reservas' : 'Mis reservas' ?></h1>

<?php if (!$esStaff): ?>
    <div class="barra-acciones">
        <span>Total: <?= count($reservas) ?> reserva(s)</span>
        <a href="catalogo.php" class="boton boton-primario">Reservar un libro</a>
    </div>
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
                <th>Reservada</th>
                <th>Expira</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$reservas): ?>
                <tr>
                    <td colspan="7" class="fila-vacia">No hay reservas registradas.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($reservas as $res): ?>
                <tr>
                    <td><?= (int) $res['id'] ?></td>
                    <?php if ($esStaff): ?>
                        <td>
                            <strong><?= e($res['usuario']) ?></strong><br>
                            <small><?= e($res['email']) ?></small>
                        </td>
                    <?php endif; ?>
                    <td><?= e($res['libro']) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($res['fecha_reserva']))) ?></td>
                    <td><?= e(date('d/m/Y H:i', strtotime($res['fecha_expiracion']))) ?></td>
                    <td><?= etiquetaReserva($res['estado']) ?></td>
                    <td>
                        <div class="acciones">
                            <?php if ($esStaff && $res['estado'] === 'pendiente'): ?>
                                <form method="POST" action="reservas.php">
                                    <input type="hidden" name="accion" value="confirmar">
                                    <input type="hidden" name="id" value="<?= (int) $res['id'] ?>">
                                    <button type="submit" class="boton boton-exito boton-pequeno">
                                        Confirmar
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($res['estado'], ['pendiente', 'confirmada'], true)): ?>
                                <form method="POST" action="reservas.php"
                                      class="confirmar-eliminar"
                                      data-mensaje="¿Cancelar esta reserva de '<?= e($res['libro']) ?>'?">
                                    <input type="hidden" name="accion" value="cancelar">
                                    <input type="hidden" name="id" value="<?= (int) $res['id'] ?>">
                                    <button type="submit" class="boton boton-peligro boton-pequeno">
                                        Cancelar
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if (in_array($res['estado'], ['expirada', 'cancelada'], true)): ?>
                                <span class="libro-datos">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

