<?php require_once 'includes/config.php'; ?>
<?php
if (isset($_SESSION['user_id'])) header("Location: index.php");
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header("Location: index.php");
        exit;
    } else {
        $error = "Credenciales incorrectas";
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="form-box" style="max-width: 500px; margin: 40px auto;">
    <h2 style="color: #05d9e8;">🔐 Acceso a la bóveda</h2>
    <?php if($error): ?><p style="color:#ff2a6d;"><?php echo $error; ?></p><?php endif; ?>
    <form method="POST">
        <div class="form-group"><input type="text" name="username" placeholder="Usuario o Email" required></div>
        <div class="form-group"><input type="password" name="password" placeholder="Contraseña" required></div>
        <button type="submit" class="btn">Entrar</button>
    </form>
    <p style="margin-top: 20px;">¿Sin cuenta? <a href="register.php" style="color:#b026ff;">Regístrate aquí</a></p>
</div>
<?php include 'includes/footer.php'; ?>