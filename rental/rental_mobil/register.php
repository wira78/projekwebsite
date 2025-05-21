<?php
session_start();
require 'koneksi/koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $check = $koneksi->prepare("SELECT iduser FROM user_pelanggan WHERE username = ? OR email = ?");
        $check->execute([$username, $email]);
        
        if ($check->rowCount() > 0) {
            $error = "Username or email already exists!";
        } else {
                        
            $stmt = $koneksi->prepare("INSERT INTO user_pelanggan 
            (username, email, password, no_ktp, nama, no_hp, jenis_kelamin, status_pelanggan, role) 
            VALUES (?, ?, ?, CONCAT('TEMP_', UUID_SHORT()), ?, '0000000000', 'L', 'aktif', 'customer')");
            
        $stmt->execute([$username, $email, $password, $username]);
            $stmt = $koneksi->prepare("INSERT INTO user_pelanggan 
                (username, email, password) 
                VALUES (?, ?, ?)");
            
            if ($stmt->execute([$username, $email, $password])) {
                $_SESSION['success'] = "Registration successful! Please login.";
                header("Location: login.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
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
    <title>Register - Rental Mobil</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4>Register</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success'])): ?>
                            <div class="alert alert-success"><?= $_SESSION['success'] ?></div>
                            <?php unset($_SESSION['success']); ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control" required minlength="4">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required minlength="6">
                            </div>
                            <button type="submit" class="btn btn-primary">Register</button>
                            <a href="login.php" class="btn btn-link">Already have an account? Login</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>