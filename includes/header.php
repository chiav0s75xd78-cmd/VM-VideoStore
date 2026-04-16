<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id']) && basename($_SERVER['PHP_SELF']) != 'login.php' && basename($_SERVER['PHP_SELF']) != 'register.php') {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>VideoStore ZZZ</title>
    <link rel="stylesheet" href="/public/css/style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<header>
    <div class="container">
        <nav>
            <div class="logo">VIDEO<span>STORE</span> ZZZ</div>
            <div class="nav-links">
                <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'active':''; ?>">Inicio</a>
                <a href="search.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='search.php'?'active':''; ?>">Busqueda</a>
                <a href="products.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='products.php'?'active':''; ?>">Productos</a>
                <a href="about.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='about.php'?'active':''; ?>">Acerca de</a>
            </div>
            <div class="user-info">
                👾 <?php echo htmlspecialchars($_SESSION['username'] ?? 'Invitado'); ?>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="logout.php" style="margin-left: 10px; color:#ff2a6d;">[Salir]</a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
<div class="container">