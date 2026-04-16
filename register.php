<?php require_once 'includes/config.php'; ?>
<?php
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        $stmt->execute([$username, $email, $password]);
        header("Location: login.php?registered=1");
        exit;
    } catch(PDOException $e) {
        $error = "Usuario o email ya existe";
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="form-box" style="max-width: 500px; margin: 40px auto;">
    <h2 style="color: #05d9e8;">📼 Registro Nuevo Agente</h2>
    <?php if($error): ?><p style="color:#ff2a6d;"><?php echo $error; ?></p><?php endif; ?>
    <form method="POST">
        <div class="form-group"><input type="text" name="username" placeholder="Nombre usuario" required></div>
        <div class="form-group"><input type="email" name="email" placeholder="Email" required></div>
        <div class="form-group"><input type="password" name="password" placeholder="Contraseña" required></div>
        <button type="submit" class="btn">Registrarse</button>
    </form>
    <p style="margin-top: 20px;">¿Ya tienes cuenta? <a href="login.php" style="color:#b026ff;">Inicia sesión</a></p>
</div>
<?php include 'includes/footer.php'; ?>