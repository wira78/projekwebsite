<?php
session_start();
require '../../koneksi/koneksi.php';

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    try {
        $id = $_POST['id'];
        
        $sql = "UPDATE user_pelanggan SET status_pelanggan = 'tidak aktif' WHERE iduser = ?";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "User berhasil dinonaktifkan";
    } catch(PDOException $e) {
        $_SESSION['error'] = "Gagal menonaktifkan user: " . $e->getMessage();
    }
    
    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>