<?php
session_start();
require 'koneksi/koneksi.php';

if (isset($_SESSION['USER'])) {
    if ($_SESSION['USER']['role'] === 'admin') {
        header("Location: admin/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

$email_value = $_SESSION['registered_email'] ?? '';
$password_value = $_SESSION['registered_password'] ?? '';

unset($_SESSION['registered_email']);
unset($_SESSION['registered_password']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $koneksi->prepare("SELECT * FROM user_pelanggan WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            $_SESSION['USER'] = [
                'iduser' => $user['iduser'],
                'nama' => $user['nama'],
                'email' => $user['email'],
                'role' => $user['role'] 
            ];
            
            if ($user['role'] === 'admin') {
                header("Location: admin/index.php");
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            $error = "Email atau password salah!";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Rental Mobil</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .login-container { max-width: 400px; margin: 100px auto; }
    </style>
</head>
<script>
<?php if (isset($_SESSION['USER']) && $_SESSION['USER']['role'] === 'admin'): ?>
    window.location.href = 'admin/index.php';
<?php endif; ?>
</script>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="text-center">Login Rental Mobil</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required 
                                   value="<?= htmlspecialchars($email_value) ?>">
                        </div>
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" name="password" class="form-control" required
                                   value="<?= htmlspecialchars($password_value) ?>">
                        </div>
                        <button type="submit" class="btn btn-primary btn-block mt-3">Login</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Belum punya akun? <a href="register.php">Daftar disini</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>