<?php
require 'koneksi/koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['USER'])) {
    header("Location: login.php?login_required=1");
    exit();
}

$id_rental = $_GET['id_rental'] ?? ($_SESSION['ID_RENTAL'] ?? null);

if (!$id_rental) {
    $_SESSION['ERRORS'] = ["ID Rental tidak valid"];
    header("Location: costomer_detail.php");
    exit();
}


$_SESSION['ID_RENTAL'] = $id_rental;
$user = $_SESSION['USER'];


try {
    $stmt = $koneksi->prepare("
        SELECT r.*, m.merk, m.platnomor, m.gambar, u.nama, u.no_hp 
        FROM rental r
        JOIN mobil m ON r.id_mobil = m.id_mobil
        JOIN user_pelanggan u ON r.iduser = u.iduser
        WHERE r.id_rental = ? AND r.iduser = ?
    ");
    
    if (!$stmt->execute([$id_rental, $user['iduser']])) {
        throw new Exception("Gagal mengeksekusi query rental");
    }
    
    if ($stmt->rowCount() == 0) {
        throw new Exception("Data rental tidak ditemukan");
    }
    
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $tgl1 = new DateTime($rental['tgl_rental']);
    $tgl2 = new DateTime($rental['tgl_kembali']);
    $durasi = $tgl2->diff($tgl1)->days;
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $metode_pembayaran = $_POST['metode_bayar'] ?? null;
    $bukti_bayar = null;
 
    if (!in_array($metode_pembayaran, ['transfer', 'cash'])) {
        $error = "Metode pembayaran tidak valid";
    }
    
    if (!isset($error) && $metode_pembayaran === 'transfer') {
        if (empty($_FILES['bukti_bayar']['name'])) {
            $error = "Bukti transfer harus diupload";
        } else {
            $target_dir = "assets/bukti_bayar/";
            $file_ext = strtolower(pathinfo($_FILES['bukti_bayar']['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $error = "Format file tidak didukung";
            } elseif ($_FILES['bukti_bayar']['size'] > 2097152) {
                $error = "Ukuran file terlalu besar. Maksimal 2MB";
            } else {
                $new_filename = "bukti_" . $id_rental . "_" . time() . "." . $file_ext;
                $target_file = $target_dir . $new_filename;
                
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0755, true);
                }
                
                if (move_uploaded_file($_FILES['bukti_bayar']['tmp_name'], $target_file)) {
                    $bukti_bayar = $new_filename;
                } else {
                    $error = "Gagal mengupload bukti pembayaran";
                }
            }
        }
    }
    
    if (!isset($error)) {
        try {
            $koneksi->beginTransaction();
      
            $stmt_pegawai = $koneksi->prepare("SELECT idpegawai FROM pegawai LIMIT 1");
            $stmt_pegawai->execute();
            $id_pegawai = $stmt_pegawai->fetch(PDO::FETCH_COLUMN);
            
            if (!$id_pegawai) {
                throw new Exception("Tidak ada data pegawai untuk diassign ke transaksi");
            }
            
            
            $generate_id = $koneksi->prepare("
                SELECT COALESCE(MAX(idtransaksi), 0) + 1 AS next_id FROM transaksi
            ");
            $generate_id->execute();
            $next_id = $generate_id->fetch(PDO::FETCH_COLUMN);
            
            
            $insert_transaksi = $koneksi->prepare("
                INSERT INTO transaksi 
                (idtransaksi, tgl_bayar, jumlah_bayar, metode_pembayaran, status_pembayaran, idpegawai, id_rental) 
                VALUES (?, NOW(), ?, ?, 'pending', ?, ?)
            ");
            
            $insert_transaksi->execute([
                $next_id,
                $rental['total_harga'],
                $metode_pembayaran,
                $id_pegawai,
                $id_rental
            ]);
            
            if ($koneksi->commit()) {
                unset($_SESSION['ID_RENTAL']);
                $_SESSION['payment_success'] = true;
                header("Location: payment_success.php");
                exit();
            } else {
                throw new Exception("Gagal commit transaksi");
            }
            
        } catch (PDOException $e) {
            $koneksi->rollBack();
            $error = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $koneksi->rollBack();
            $error = $e->getMessage();
        }
    }
}

$errors = $_SESSION['ERRORS'] ?? [];
unset($_SESSION['ERRORS']);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Rental Mobil</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .payment-method {
            cursor: pointer;
            transition: all 0.3s;
        }
        .payment-method:hover {
            background-color: #f8f9fa;
        }
        .payment-method.selected {
            border: 2px solid #0d6efd;
            background-color: #e7f1ff;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="bi bi-credit-card"></i> Proses Pembayaran</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    
                    
                    <div class="mb-4">
                        <h5 class="border-bottom pb-2">Detail Booking</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>No. Booking:</strong> #<?= str_pad($id_rental, 6, '0', STR_PAD_LEFT) ?></p>
                                <p><strong>Mobil:</strong> <?= htmlspecialchars($rental['merk']) ?> (<?= htmlspecialchars($rental['platnomor']) ?>)</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Tanggal Rental:</strong> <?= date('d/m/Y', strtotime($rental['tgl_rental'])) ?></p>
                                <p><strong>Tanggal Kembali:</strong> <?= date('d/m/Y', strtotime($rental['tgl_kembali'])) ?></p>
                            </div>
                        </div>
                        <div class="bg-light p-3 mt-3 rounded">
                            <h6 class="mb-0">Total Pembayaran: <span class="float-end">Rp <?= number_format($rental['total_harga'], 0, ',', '.') ?></span></h6>
                        </div>
                    </div>
                    
                    <form method="POST" enctype="multipart/form-data">
                        <h5 class="border-bottom pb-2">Metode Pembayaran</h5>
                        
                        <div class="mb-4">
                            <div class="form-check payment-method p-3 mb-3 rounded selected" onclick="selectPaymentMethod('transfer')">
                                <input class="form-check-input" type="radio" name="metode_bayar" id="transfer" value="transfer" checked required>
                                <label class="form-check-label fw-bold" for="transfer">
                                    Transfer Bank
                                </label>
                                <div class="mt-2" id="transfer-details">
                                    <p class="mb-1">Silakan transfer ke salah satu rekening berikut:</p>
                                    <ul>
                                        <li>Bank BCA: 1234567890 (Rental Mobil)</li>
                                        <li>Bank Mandiri: 0987654321 (Rental Mobil)</li>
                                    </ul>
                                    <div class="mb-3">
                                        <label for="bukti_bayar" class="form-label">Upload Bukti Transfer</label>
                                        <input class="form-control" type="file" id="bukti_bayar" name="bukti_bayar" required>
                                        <div class="form-text">Format: JPG/PNG/PDF (maks. 2MB)</div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-check payment-method p-3 mb-3 rounded" onclick="selectPaymentMethod('cash')">
                                <input class="form-check-input" type="radio" name="metode_bayar" id="cash" value="cash">
                                <label class="form-check-label fw-bold" for="cash">
                                    Bayar di Tempat
                                </label>
                                <div class="mt-2" id="cash-details" style="display:none;">
                                    <p>Anda dapat melakukan pembayaran langsung saat pengambilan mobil di kantor kami.</p>
                                    <p><strong>Alamat:</strong> Jl. Contoh No. 123, Kota</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-between mt-4">
                            <a href="costumer_detail.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
                            <button type="submit" class="btn btn-primary">Konfirmasi Pembayaran <i class="bi bi-check-circle"></i></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
<script>
    function selectPaymentMethod(method) {
       
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
        
     
        document.getElementById(method).checked = true;
        
       
        if (method === 'transfer') {
            document.getElementById('transfer-details').style.display = 'block';
            document.getElementById('cash-details').style.display = 'none';
            document.getElementById('bukti_bayar').required = true;
        } else {
            document.getElementById('transfer-details').style.display = 'none';
            document.getElementById('cash-details').style.display = 'block';
            document.getElementById('bukti_bayar').required = false;
        }
    }
</script>
</body>
</html>