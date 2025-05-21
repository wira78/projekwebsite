<?php
session_start();
require '../../koneksi/koneksi.php';

if(!isset($_SESSION['USER'])) {
    $_SESSION['error'] = "Anda harus login terlebih dahulu";
    header("Location: ../login.php");
    exit();
}

if(!isset($_POST['aksi'])) {
    $_SESSION['error'] = "Aksi tidak valid";
    header("Location: mobil.php");
    exit();
}

$aksi = $_POST['aksi'];

if($aksi == 'edit') {
    if(!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $_SESSION['error'] = "ID mobil tidak valid";
        header("Location: mobil.php");
        exit();
    }

    $id = (int)$_POST['id'];
    $no_plat = htmlspecialchars($_POST['no_plat']);
    $merk = htmlspecialchars($_POST['merk']);
    $harga = (int)$_POST['harga'];
    $status = htmlspecialchars($_POST['status']);
    $gambar_lama = htmlspecialchars($_POST['gambar_lama']);

    $nama_gambar = $gambar_lama;
    
    if(isset($_FILES['gambar']) && $_FILES['gambar']['error'] == 0) {
        $file = $_FILES['gambar'];
        
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if(!in_array($file['type'], $allowed_types)) {
            $_SESSION['error'] = "Hanya file gambar (JPEG, PNG, GIF) yang diizinkan";
            header("Location: edit.php?id=".$id);
            exit();
        }
        
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $nama_gambar = uniqid().'.'.$ext;
        $target_file = "../../assets/image/".$nama_gambar;
        
        if(!move_uploaded_file($file['tmp_name'], $target_file)) {
            $_SESSION['error'] = "Gagal mengupload gambar";
            header("Location: edit.php?id=".$id);
            exit();
        }
        
        if($gambar_lama != 'default.jpg') {
            @unlink("../../assets/image/".$gambar_lama);
        }
    }

    try {
        $sql = "UPDATE mobil SET 
                platnomor = ?, 
                merk = ?, 
                harga_rental = ?, 
                status = ?, 
                gambar = ? 
                WHERE id_mobil = ?";
        
        $stmt = $koneksi->prepare($sql);
        $stmt->execute([$no_plat, $merk, $harga, $status, $nama_gambar, $id]);
        
        $_SESSION['success'] = "Data mobil berhasil diperbarui";
        header("Location: mobil.php");
        exit();
        
    } catch(PDOException $e) {
        $_SESSION['error'] = "Gagal memperbarui data: " . $e->getMessage();
        header("Location: edit.php?id=".$id);
        exit();
    }
}
?>