<?php
// ============================================================
// includes/header.php
// Cabecera común de todas las páginas: menú según el rol
// y mensajes de éxito / error.
// Requiere que auth.php ya haya sido incluido.
// ============================================================

// Datos del usuario logueado (o null si es visitante)
$usuario = usuarioActual($conexion);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($tituloPagina ?? 'BiblioTech') ?> | BiblioTech</title>
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>

<header class="navbar">
    <div class="contenedor navbar-contenido">
        <a href="dashboard.php" class="marca">📚 BiblioTech</a>

        <button class="boton-menu" id="boton-menu" aria-label="Abrir menú">☰</button>

        <nav class="menu" id="menu">
            <?php if ($usuario): ?>
                <a href="dashboard.php">Inicio</a>
                <a href="catalogo.php">Catálogo</a>

                <?php if ($usuario['rol'] === 'estudiante'): ?>
                    <!-- Menú del estudiante -->
                    <a href="reservas.php">Mis reservas</a>
                    <a href="prestamos.php">Mis préstamos</a>
                <?php else: ?>
                    <!-- Menú del bibliotecario y del administrador -->
                    <a href="categorias.php">Categorías</a>
                    <a href="libros.php">Libros</a>
                    <a href="reservas.php">Reservas</a>
                    <a href="prestamos.php">Préstamos</a>
                    <a href="multas.php">Multas</a>
                    <?php if ($usuario['rol'] === 'administrador'): ?>
                        <a href="usuarios.php">Usuarios</a>
                    <?php endif; ?>
                <?php endif; ?>

                <span class="menu-usuario"><?= e($usuario['nombre']) ?></span>
                <a href="logout.php" class="menu-salir">Salir</a>
            <?php else: ?>
                <a href="catalogo.php">Catálogo</a>
                <a href="login.php">Iniciar sesión</a>
                <a href="registro.php">Registrarse</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="contenedor pagina <?= e($claseMain ?? '') ?>">

    <!-- Mensajes de éxito / error (flash) -->
    <?php foreach (obtenerMensajes() as $mensaje): ?>
        <div class="alerta alerta-<?= e($mensaje['tipo']) ?>">
            <?= e($mensaje['texto']) ?>
        </div>
    <?php endforeach; ?>
