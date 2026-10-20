<?php
// ============================================================
// includes/auth.php
// Archivo general de sesión, mensajes y control de acceso.
// Siempre se incluye al inicio de cada página:
//   require_once __DIR__ . '/includes/auth.php';
// ============================================================

// 1) Iniciar la sesión (si aún no está iniciada)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 2) Cargar la conexión a la base de datos (variable $conexion)
require_once __DIR__ . '/../config/conexion.php';

/**
 * Guarda un mensaje temporal (flash) para mostrarlo en la siguiente página.
 * Tippos válidos: 'exito' (verde) y 'error' (rojo).
 */
function setFlash(string $tipo, string $texto): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'texto' => $texto];
}

/**
 * Devuelve los mensajes guardados y los limpia
 * (solo se muestran una vez).
 */
function obtenerMensajes(): array
{
    $mensajes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $mensajes;
}

/**
 * Indica si hay un usuario logueado en la sesión.
 */
function estaLogueado(): bool
{
    return isset($_SESSION['usuario_id']);
}

/**
 * Devuelve los datos del usuario actual leyéndolos de la base de datos,
 * o null si no hay nadie logueado.
 */
function usuarioActual(PDO $conexion): ?array
{
    if (!estaLogueado()) {
        return null;
    }

    $sql = "SELECT id, nombre, email, rol, estado
            FROM usuarios
            WHERE id = :id";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([':id' => $_SESSION['usuario_id']]);
    $usuario = $stmt->fetch();

    return $usuario ?: null;
}

/**
 * Exige estar logueado para ver la página.
 * Si el usuario quedó bloqueado, cierra su sesión automáticamente.
 */
function requiereLogin(): void
{
    if (!estaLogueado()) {
        header('Location: login.php');
        exit;
    }

    // Verificamos que la cuenta siga activa
    // (por si el administrador lo bloqueó mientras estaba dentro)
    global $conexion;
    $usuario = usuarioActual($conexion);

    if (!$usuario || $usuario['estado'] !== 'activo') {
        $_SESSION = [];
        session_destroy();
        setFlash('error', 'Tu cuenta está bloqueada. Contacta con el administrador.');
        header('Location: login.php');
        exit;
    }

    // Mantenemos el nombre y rol actualizados en la sesión
    $_SESSION['usuario_nombre'] = $usuario['nombre'];
    $_SESSION['usuario_rol'] = $usuario['rol'];
}

/**
 * Exige uno o varios roles para ver la página.
 * Ejemplo: requiereRol(['bibliotecario', 'administrador']);
 */
function requiereRol(array $roles): void
{
    requiereLogin();

    if (!in_array($_SESSION['usuario_rol'], $roles, true)) {
        setFlash('error', 'No tienes permiso para acceder a esa página.');
        header('Location: dashboard.php');
        exit;
    }
}

/**
 * Escapa un texto para mostrarlo seguro en HTML.
 */
function e(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}
