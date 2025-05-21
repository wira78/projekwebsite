<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'koneksi/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['USER'])) {
    header("Location: login.php");
    exit();
}


$rental_id = $_GET['rental_id'] ?? ($_SESSION['temp_booking']['rental_id'] ?? null);

if (!$rental_id) {
    $_SESSION['ERRORS'] = ["ID Rental tidak valid"];
    header("Location: index.php");
    exit();
}

$temp_booking = $_SESSION['temp_booking'] ?? null;
if (!$temp_booking) {
    try {
        $stmt = $koneksi->prepare("
            SELECT r.*, m.merk, m.platnomor, m.gambar 
            FROM rental r
            JOIN mobil m ON r.id_mobil = m.id_mobil
            WHERE r.id_rental = ? AND r.iduser = ? AND r.status = 'pending'
        ");
        $stmt->execute([$rental_id, $_SESSION['USER']['iduser']]);
        $temp_booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$temp_booking) {
            throw new Exception("Data booking tidak ditemukan");
        }
      
        $temp_booking['car_info'] = [
            'merk' => $temp_booking['merk'],
            'platnomor' => $temp_booking['platnomor'],
            'gambar' => $temp_booking['gambar']
        ];
        
        $_SESSION['temp_booking'] = $temp_booking;
    } catch (Exception $e) {
        $_SESSION['ERRORS'] = [$e->getMessage()];
        header("Location: index.php");
        exit();
    }
}


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'] ?? null;
    $no_hp = $_POST['no_hp'] ?? null;
    $alamat = $_POST['alamat'] ?? null;
    $no_ktp = $_POST['no_ktp'] ?? null;
    
    $errors = [];
    if (empty($nama)) $errors[] = "Nama harus diisi";
    if (empty($no_hp)) $errors[] = "Nomor HP harus diisi";
    if (empty($alamat)) $errors[] = "Alamat harus diisi";
    if (empty($no_ktp)) $errors[] = "Nomor KTP harus diisi";
   
    
    if (empty($errors)) {
        try {
            
            $update_user = $koneksi->prepare("
                UPDATE user_pelanggan SET 
                nama = ?, 
                no_hp = ?,
                alamat = ?,
                no_ktp = ?
                WHERE iduser = ?
            ");
            $update_user->execute([$nama, $no_hp, $alamat, $no_ktp, $_SESSION['USER']['iduser']]);
            
       
            $_SESSION['USER']['nama'] = $nama;
            $_SESSION['USER']['no_hp'] = $no_hp;
            
            
            $_SESSION['temp_booking']['customer_details'] = [
                'nama' => $nama,
                'no_hp' => $no_hp,
                'alamat' => $alamat,
                'no_ktp' => $no_ktp,
            ];
            
           
            header("Location: bayar.php?rental_id=" . $rental_id);
            exit();
            
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}


try {
    $stmt = $koneksi->prepare("SELECT * FROM user_pelanggan WHERE iduser = ?");
    $stmt->execute([$_SESSION['USER']['iduser']]);
    $user_details = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Gagal mengambil data user: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Pelanggan</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4>Detail Pelanggan</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>
                    
                   
                    <div class="mb-4">
                        <h5>Ringkasan Booking</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Mobil:</strong> <?= htmlspecialchars($temp_booking['car_info']['merk']) ?></p>
                                <p><strong>Plat:</strong> <?= htmlspecialchars($temp_booking['car_info']['platnomor']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tanggal Rental:</strong> <?= date('d/m/Y', strtotime($temp_booking['tgl_rental'])) ?></p>
                                <p><strong>Tanggal Kembali:</strong> <?= date('d/m/Y', strtotime($temp_booking['tgl_kembali'])) ?></p>
                            </div>
                        </div>
                        <div class="bg-light p-3 rounded">
                            <h5>Total Harga: Rp <?= number_format($temp_booking['total_harga'], 0, ',', '.') ?></h5>
                        </div>
                    </div>
                    
               
                    <form method="POST" enctype="multipart/form-data">
                        <h5 class="mb-3">Data Diri</h5>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="nama" class="form-control" 
                                   value="<?= htmlspecialchars($user_details['nama'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor HP</label>
                            <input type="tel" name="no_hp" class="form-control" 
                                   value="<?= htmlspecialchars($user_details['no_hp'] ?? '') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Alamat</label>
                            <textarea name="alamat" class="form-control" required><?= 
                                htmlspecialchars($user_details['alamat'] ?? '') ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nomor KTP</label>
                            <input type="text" name="no_ktp" class="form-control" 
                                   value="<?= htmlspecialchars($user_details['no_ktp'] ?? '') ?>" required>
                        </div>
                                        
                       
                        <div class="d-flex justify-content-between mt-4">
                            <a href="booking.php?id=<?= $temp_booking['id_mobil'] ?>" class="btn btn-secondary">Kembali</a>
                            <button type="submit" class="btn btn-primary">Lanjut ke Pembayaran</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
</body>
</html>