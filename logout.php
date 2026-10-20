<?php
// ============================================================
// logout.php - Cierre de sesión
// ============================================================
require_once __DIR__ . '/includes/auth.php';

// Vaciamos y destruimos la sesión
$_SESSION = [];
session_destroy();

header('Location: login.php');
exit;
