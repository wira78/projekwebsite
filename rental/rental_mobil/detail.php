<?php

session_start();
require 'koneksi/koneksi.php';
include 'header.php';


if(!isset($_GET['id_mobil']) || empty($_GET['id_mobil'])) {
    echo '<script>
        alert("ID Mobil tidak valid");
        window.location.href = "blog.php";
    </script>';
    exit();
}


$id_mobil = strip_tags($_GET['id_mobil']);


try {
    $stmt = $koneksi->prepare("SELECT * FROM mobil WHERE id_mobil = ?");
    $stmt->execute([$id_mobil]);
    $hasil = $stmt->fetch(PDO::FETCH_ASSOC);
    
  
    if(!$hasil) {
        echo '<script>
            alert("Mobil tidak ditemukan");
            window.location.href = "blog.php";
        </script>';
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo '<script>
        alert("Terjadi kesalahan sistem");
        window.location.href = "blog.php";
    </script>';
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($hasil['merk'] ?? 'Detail Mobil') ?> - Rental Mobil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .car-detail-container {
            margin-top: 2rem;
            margin-bottom: 3rem;
        }
        .car-image {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .detail-card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .car-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2b2d42;
            margin-bottom: 0.5rem;
        }
        .car-subtitle {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }
        .spec-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
        }
        .spec-icon {
            width: 30px;
            color: #4361ee;
            margin-right: 10px;
        }
        .price-tag {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4361ee;
            margin: 1.5rem 0;
            text-align: center;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .btn-action {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s;
        }
        .btn-action:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
<div class="container car-detail-container">
    <div class="row">
        <div class="col-md-6 mb-4">
            <img class="car-image" 
                 src="assets/image/<?= htmlspecialchars($hasil['gambar'] ?? 'default.jpg') ?>" 
                 alt="<?= htmlspecialchars($hasil['merk'] ?? 'Mobil') ?>">
            
            <span class="status-badge <?= ($hasil['status'] ?? '') == 'Tersedia' ? 'bg-success text-white' : 'bg-danger text-white' ?>">
                <i class="fas <?= ($hasil['status'] ?? '') == 'Tersedia' ? 'fa-check' : 'fa-times' ?> me-1"></i>
                <?= ($hasil['status'] ?? '') == 'Tersedia' ? 'Tersedia' : 'Tidak Tersedia' ?>
            </span>
            
            <div class="price-tag">
                Rp <?= isset($hasil['harga']) ? number_format($hasil['harga'], 0, ',', '.') : '0' ?> /hari
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="detail-card">
                <h1 class="car-title"><?= htmlspecialchars($hasil['merk'] ?? 'Mobil') ?></h1>
                <h5 class="car-subtitle"><?= htmlspecialchars($hasil['tipe'] ?? 'Tipe Mobil') ?></h5>
                
                <div class="mb-4">
                    <h5><strong>Deskripsi:</strong></h5>
                    <p><?= htmlspecialchars($hasil['deskripsi'] ?? 'Tidak ada deskripsi tersedia') ?></p>
                </div>
                
                <h5><strong>Spesifikasi:</strong></h5>
                <div class="spec-item">
                    <div class="spec-icon"><i class="fas fa-id-card"></i></div>
                    <div>Plat: <?= htmlspecialchars($hasil['platnomor'] ?? '-') ?></div>
                </div>
                <div class="spec-item">
                    <div class="spec-icon"><i class="fas fa-palette"></i></div>
                    <div>Warna: <?= htmlspecialchars($hasil['warna'] ?? '-') ?></div>
                </div>
                <div class="spec-item">
                    <div class="spec-icon"><i class="fas fa-calendar-alt"></i></div>
                    <div>Tahun: <?= htmlspecialchars($hasil['tahun'] ?? '-') ?></div>
                </div>
                
                <div class="mt-4">
                    <a href="index.php" class="btn btn-outline-primary btn-action">
                        <i class="fas fa-arrow-left me-1"></i> Kembali
                    </a>
                    <a href="booking.php?id_mobil=<?= $id_mobil ?>" 
                       class="btn btn-primary btn-action <?= ($hasil['status'] ?? '') != 'Tersedia' ? 'disabled' : '' ?>">
                       <i class="fas fa-calendar-check me-1"></i> Booking Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>