<?php
// Conexión a la base de datos con PDO
$host = "localhost";
$dbname = "bd_bibliotech"; // el nombre exacto que ves en phpMyAdmin
$usuario = "root";
$password = "";         // en XAMPP normalmente va vacío

try {
    $conexion = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $usuario,
        $password
    );
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}