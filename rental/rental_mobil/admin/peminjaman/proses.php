<?php
session_start();
require '../../koneksi/koneksi.php';

if(empty($_SESSION['USER']) || $_SESSION['USER']['role'] !== 'admin') {
    header("location:../../login.php");
    exit();
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id_rental = $_POST['id_rental'] ?? '';
    $id_mobil = $_POST['id_mobil'] ?? '';
    
    try {
        if($action === 'update_status') {
            $status = $_POST['status'] ?? '';
            $status_mobil = $_POST['status_mobil'] ?? '';
            
            $koneksi->beginTransaction();
            
            $stmt = $koneksi->prepare("UPDATE rental SET status = ? WHERE id_rental = ?");
            $stmt->execute([$status, $id_rental]);
            
            $stmt2 = $koneksi->prepare("UPDATE mobil SET status = ? WHERE id_mobil = ?");
            $stmt2->execute([$status_mobil, $id_mobil]);
            
            $koneksi->commit();
            
            $_SESSION['success'] = "Status booking berhasil diperbarui!";
            header("location: peminjaman.php?id=".$id_rental);
            exit();
        }
    } catch(PDOException $e) {
        $koneksi->rollBack();
        $_SESSION['error'] = "Gagal memperbarui status: " . $e->getMessage();
        header("location: peminjaman.php?id=".$id_rental);
        exit();
    }
}

header("location: peminjaman.php");
exit();
?>