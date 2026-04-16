<?php
session_start();

$host = getenv('DB_HOST') ?: 'localhost';
$dbname = getenv('DB_NAME') ?: 'videostore';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    // Forzar SSL para conexión a PostgreSQL en Render
    $dsn = "pgsql:host=$host;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

define('NODE_API_URL', getenv('NODE_API_URL') ?: 'http://localhost:3000/api');
define('BASE_URL', getenv('BASE_URL') ?: '/');
?>