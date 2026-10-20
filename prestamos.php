<?php
// ============================================================
// prestamos.php - Préstamos y devoluciones
// - Bibliotecario/Administrador: registra préstamos y devoluciones.
//   Al prestar baja el stock_disponible; al devolver lo sube.
//   Si la devolución es tardía, se genera automáticamente la multa.
// - Estudiante: solo consulta sus propios préstamos.
// ============================================================
require_once __DIR__ . '/includes/auth.php';
requiereLogin();

$usuario = usuarioActual($conexion);
$esStaff = in_array($usuario['rol'], ['bibliotecario', 'administrador'], true);

// Tarifa de multa por día de retraso (misma que los datos de ejemplo)
const MULTA_POR_DIA = 1.50;
// Días de plazo por defecto para devolver un libro
const DIAS_PLAZO = 7;

$errores = [];

// ============================================================
// 1) PROCESAMIENTO (POST)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if (!$esStaff) {
        setFlash('error', 'No tienes permiso para realizar esa acción.');
        header('Location: prestamos.php');
        exit;
    }

    // --- Registrar un préstamo nuevo ---
    if ($accion === 'prestar') {
        $idUsuario     = (int) ($_POST['id_usuario'] ?? 0);
        $idLibro       = (int) ($_POST['id_libro'] ?? 0);
        $idReserva     = (int) ($_POST['id_reserva'] ?? 0) ?: null;
        $fechaPrestamo = $_POST['fecha_prestamo'] ?? date('Y-m-d');
        $diasPlazo     = (int) ($_POST['dias_plazo'] ?? DIAS_PLAZO);

        // Validamos usuario y libro
        $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE id = :id AND estado = 'activo'");
        $stmt->execute([':id' => $idUsuario]);
        $prestatario = $stmt->fetch();

        $stmt = $conexion->prepare("SELECT * FROM libros WHERE id = :id");
        $stmt->execute([':id' => $idLibro]);
        $libro = $stmt->fetch();

        if (!$prestatario) {
            $errores[] = 'Debes elegir un usuario activo.';
        }
        if (!$libro) {
            $errores[] = 'Debes elegir un libro.';
        } elseif ((int) $libro['stock_disponible'] <= 0) {
            $errores[] = 'Este libro no tiene ejemplares disponibles.';
        }
        if (!DateTime::createFromFormat('Y-m-d', $fechaPrestamo)) {
            $errores[] = 'La fecha de préstamo no es válida.';
        }
        if ($diasPlazo < 1 || $diasPlazo > 60) {
            $errores[] = 'El plazo debe estar entre 1 y 60 días.';
        }

        // Guardamos si no hubo errores
        if (!$errores) {
            // Calculamos la fecha en que debe devolverse
            $fechaDevolucion = (new DateTime($fechaPrestamo))
                ->modify('+' . $diasPlazo . ' days')->format('Y-m-d');

            $sql = "INSERT INTO prestamos
                    (id_usuario, id_libro, id_reserva, fecha_prestamo,
                     fecha_devolucion_esperada, estado)
                    VALUES
                    (:usuario, :libro, :reserva, :fecha_prestamo,
                     :fecha_devolucion, 'activo')";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':usuario'          => $idUsuario,
                ':libro'            => $idLibro,
                ':reserva'          => $idReserva,
                ':fecha_prestamo'   => $fechaPrestamo,
                ':fecha_devolucion' => $fechaDevolucion,
            ]);

            // Bajamos el stock disponible del libro
            $stmt = $conexion->prepare(
                "UPDATE libros SET stock_disponible = stock_disponible - 1 WHERE id = :id"
            );
            $stmt->execute([':id' => $idLibro]);

            // La reserva vinculada queda "confirmada" (uso ya entregado)
            if ($idReserva) {
                $stmt = $conexion->prepare(
                    "UPDATE reservas SET estado = 'confirmada' WHERE id = :id"
                );
                $stmt->execute([':id' => $idReserva]);
            }

            setFlash('exito', "Préstamo registrado. Debe devolverse antes del "
                            . date('d/m/Y', strtotime($fechaDevolucion)) . '.');
            header('Location: prestamos.php');
            exit;
        }
    }

    // --- Registrar una devolución ---
    if ($accion === 'devolver' && !$errores) {
        $id = (int) ($_POST['id'] ?? 0);

        $stmt = $conexion->prepare("SELECT * FROM prestamos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $prestamo = $stmt->fetch();

        if (!$prestamo) {
            setFlash('error', 'El préstamo no existe.');
        } elseif (!in_array($prestamo['estado'], ['activo', 'vencido'], true)) {
            setFlash('error', 'Este préstamo ya fue cerrado.');
        } else {
            $hoy = date('Y-m-d');

            // Cerramos el préstamo
            $sql = "UPDATE prestamos
                    SET fecha_devolucion_real = :fecha, estado = 'devuelto'
                    WHERE id = :id";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([':fecha' => $hoy, ':id' => $id]);

            // Subimos el stock disponible del libro
            $stmt = $conexion->prepare(
                "UPDATE libros SET stock_disponible = stock_disponible + 1 WHERE id = :id"
            );
            $stmt->execute([':id' => $prestamo['id_libro']]);

            // ¿Llegó tarde? Generamos la multa automáticamente
            $diasTarde = 0;
            if ($hoy > $prestamo['fecha_devolucion_esperada']) {
                $diasTarde = (int) (
                    (strtotime($hoy) - strtotime($prestamo['fecha_devolucion_esperada'])) / 86400
                );
            }

            if ($diasTarde > 0) {
                // Evitamos duplicar la multa si ya existía
                $stmt = $conexion->prepare("SELECT id FROM multas WHERE id_prestamo = :id");
                $stmt->execute([':id' => $id]);

                if (!$stmt->fetch()) {
                    $monto = $diasTarde * MULTA_POR_DIA;
                    $sql = "INSERT INTO multas (id_prestamo, monto, estado)
                            VALUES (:prestamo, :monto, 'pendiente')";
                    $stmt = $conexion->prepare($sql);
                    $stmt->execute([':prestamo' => $id, ':monto' => $monto]);

                    setFlash('exito', "Devolución registrada con retraso de {$diasTarde} día(s). "
                                    . "Se generó una multa de S/ " . number_format($monto, 2) . '.');
                } else {
                    setFlash('exito', 'Devolución registrada.');
                }
            } else {
                setFlash('exito', 'Devolución registrada a tiempo. ¡Gracias!');
            }
            header('Location: prestamos.php');
            exit;
        }
    }
} // fin del procesamiento POST

// ============================================================
// 2) DATOS PARA LA VISTA
// ============================================================
// Listas para los desplegables del formulario
$usuariosLista = $conexion->query(
    "SELECT id, nombre, email FROM usuarios WHERE estado = 'activo' ORDER BY nombre"
)->fetchAll();

$librosDisponibles = $conexion->query(
    "SELECT id, titulo, stock_disponible FROM libros
     WHERE stock_disponible > 0 ORDER BY titulo"
)->fetchAll();

// Reservas confirmadas que aún no se han convertido en préstamo
$reservasConfirmadas = $conexion->query(
    "SELECT r.id, l.titulo, u.nombre
     FROM reservas r
     INNER JOIN libros l   ON l.id = r.id_libro
     INNER JOIN usuarios u ON u.id = r.id_usuario
     WHERE r.estado = 'confirmada'
       AND r.id NOT IN (SELECT id_reserva FROM prestamos WHERE id_reserva IS NOT NULL)
     ORDER BY r.fecha_reserva"
)->fetchAll();

// Préstamos: el estudiante ve los suyos, el personal todos
$sql = "SELECT p.*, u.nombre AS usuario, u.email, l.titulo AS libro
        FROM prestamos p
        INNER JOIN usuarios u ON u.id = p.id_usuario
        INNER JOIN libros l   ON l.id = p.id_libro";
if (!$esStaff) {
    $sql .= " WHERE p.id_usuario = :id";
}
$sql .= " ORDER BY p.created_at DESC";
$stmt = $conexion->prepare($sql);
if ($esStaff) {
    $stmt->execute();
} else {
    $stmt->execute([':id' => $usuario['id']]);
}
$prestamos = $stmt->fetchAll();

// Etiqueta de color para cada estado de préstamo
function etiquetaPrestamo(string $estado): string
{
    $mapa = [
        'activo'   => ['etiqueta-azul', 'Activo'],
        'devuelto' => ['etiqueta-verde', 'Devuelto'],
        'vencido'  => ['etiqueta-rojo', 'Vencido'],
        'cancelado' => ['etiqueta-gris', 'Cancelado'],
    ];
    [$clase, $texto] = $mapa[$estado] ?? ['etiqueta-gris', $estado];
    return "<span class=\"etiqueta {$clase}\">{$texto}</span>";
}

$tituloPagina = 'Préstamos';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="titulo-pagina"><?= $esStaff ? 'Préstamos' : 'Mis préstamos' ?></h1>

<?php if ($errores): ?>
    <div class="alerta alerta-error">
        <ul class="lista-errores">
            <?php foreach ($errores as $error): ?>
                <li><?= e($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($esStaff): ?>
    <!-- ========== FORMULARIO DE NUEVO PRÉSTAMO ========== -->
    <div class="tarjeta" style="max-width: 640px; margin-bottom: 1.5rem;">
        <h2>Registrar préstamo</h2>

        <form method="POST" action="prestamos.php" class="formulario">
            <input type="hidden" name="accion" value="prestar">

            <div class="dos-columnas">
                <div>
                    <label for="id_usuario">Estudiante *</label>
                    <select id="id_usuario" name="id_usuario" required>
                        <option value="">— Elegir —</option>
                        <?php foreach ($usuariosLista as $u): ?>
                            <option value="<?= (int) $u['id'] ?>">
                                <?= e($u['nombre']) ?> (<?= e($u['email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="id_libro">Libro (con stock) *</label>
                    <select id="id_libro" name="id_libro" required>
                        <option value="">— Elegir —</option>
                        <?php foreach ($librosDisponibles as $lib): ?>
                            <option value="<?= (int) $lib['id'] ?>">
                                <?= e($lib['titulo']) ?> (disponible: <?= (int) $lib['stock_disponible'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <label for="id_reserva">Reserva confirmada (opcional)</label>
            <select id="id_reserva" name="id_reserva">
                <option value="0">— Ninguna —</option>
                <?php foreach ($reservasConfirmadas as $res): ?>
                    <option value="<?= (int) $res['id'] ?>">
                        <?= e($res['titulo']) ?> → <?= e($res['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <div class="dos-columnas">
                <div>
                    <label for="fecha_prestamo">Fecha de préstamo</label>
                    <input type="date" id="fecha_prestamo" name="fecha_prestamo"
                           value="<?= date('Y-m-d') ?>">
                </div>
                <div>
                    <label for="dias_plazo">Plazo (días)</label>
                    <input type="number" id="dias_plazo" name="dias_plazo"
                           min="1" max="60" value="<?= DIAS_PLAZO ?>">
                </div>
            </div>

            <button type="submit" class="boton boton-primario" style="margin-top: 0.8rem;">
                Registrar préstamo
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- ========== LISTA DE PRÉSTAMOS ========== -->
<div class="tabla-contenedor">
    <table class="tabla">
        <thead>
            <tr>
                <th>ID</th>
                <?php if ($esStaff): ?>
                    <th>Estudiante</th>
                <?php endif; ?>
                <th>Libro</th>
                <th>Prestado</th>
                <th>Debe devolver</th>
                <th>Devuelto</th>
                <th>Estado</th>
                <?php if ($esStaff): ?>
                    <th>Acciones</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!$prestamos): ?>
                <tr>
                    <td colspan="8" class="fila-vacia">No hay préstamos registrados.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($prestamos as $pre): ?>
                <?php
                    // ¿Está vencido hoy? (aunque aún diga "activo" en la BD)
                    $estaVencido = $pre['estado'] === 'activo'
                        && $pre['fecha_devolucion_esperada'] < date('Y-m-d');
                ?>
                <tr>
                    <td><?= (int) $pre['id'] ?></td>
                    <?php if ($esStaff): ?>
                        <td>
                            <strong><?= e($pre['usuario']) ?></strong><br>
                            <small><?= e($pre['email']) ?></small>
                        </td>
                    <?php endif; ?>
                    <td><?= e($pre['libro']) ?></td>
                    <td><?= e(date('d/m/Y', strtotime($pre['fecha_prestamo']))) ?></td>
                    <td>
                        <?= e(date('d/m/Y', strtotime($pre['fecha_devolucion_esperada']))) ?>
                        <?php if ($estaVencido): ?>
                            <br><span class="etiqueta etiqueta-rojo">Retrasado</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= $pre['fecha_devolucion_real']
                            ? e(date('d/m/Y', strtotime($pre['fecha_devolucion_real'])))
                            : '—' ?>
                    </td>
                    <td><?= etiquetaPrestamo($pre['estado']) ?></td>
                    <?php if ($esStaff): ?>
                        <td>
                            <div class="acciones">
                                <?php if (in_array($pre['estado'], ['activo', 'vencido'], true)): ?>
                                    <form method="POST" action="prestamos.php"
                                          class="confirmar-eliminar"
                                          data-mensaje="¿Registrar la devolución de '<?= e($pre['libro']) ?>'?">
                                        <input type="hidden" name="accion" value="devolver">
                                        <input type="hidden" name="id" value="<?= (int) $pre['id'] ?>">
                                        <button type="submit" class="boton boton-exito boton-pequeno">
                                            Devolver
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="libro-datos">—</span>
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

