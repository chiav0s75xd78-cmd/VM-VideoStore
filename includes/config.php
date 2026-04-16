<?php
session_start();

$host = getenv('DB_HOST') ?: 'dpg-d7g4pbtckfvc73b9nif0-a';
$dbname = getenv('DB_NAME') ?: 'videostore_u48q';
$username = getenv('DB_USER') ?: 'videouser';
$password = getenv('DB_PASSWORD') ?: 'rSv9HlVzRaKWvWypOQw6NilPsSTJ8M08';

try {
    $dsn = "pgsql:host=$host;dbname=$dbname;sslmode=require";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

define('NODE_API_URL', getenv('NODE_API_URL') ?: 'http://localhost:3000/api');
define('BASE_URL', getenv('BASE_URL') ?: '/');
?>