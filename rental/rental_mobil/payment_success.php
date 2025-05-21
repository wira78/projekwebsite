<?php
require 'koneksi/koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['payment_success'])) {
    header("Location: costomer_detail.php");
    exit();
}

unset($_SESSION['payment_success']);

$user = $_SESSION['USER'] ?? null;
if (!$user) {
    header("Location: login.php");
    exit();
}

try {
    $stmt = $koneksi->prepare("
        SELECT t.*, r.tgl_rental, r.tgl_kembali, m.merk, m.platnomor 
        FROM transaksi t
        JOIN rental r ON t.id_rental = r.id_rental
        JOIN mobil m ON r.id_mobil = m.id_mobil
        WHERE r.iduser = ?
        ORDER BY t.tgl_bayar DESC
        LIMIT 1
    ");
    
    $stmt->execute([$user['iduser']]);
    $transaksi = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$transaksi) {
        throw new Exception("Data transaksi tidak ditemukan");
    }
    
    $tgl_bayar = date('d F Y H:i', strtotime($transaksi['tgl_bayar']));
    $tgl_rental = date('d F Y', strtotime($transaksi['tgl_rental']));
    $tgl_kembali = date('d F Y', strtotime($transaksi['tgl_kembali']));
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Berhasil - Rental Mobil</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .success-icon {
            font-size: 5rem;
            color: #28a745;
        }
        .receipt {
            background-color: #f8f9fa;
            border-radius: 10px;
            border: 1px dashed #6c757d;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-body text-center py-5">
                    <div class="success-icon mb-4">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                    <h2 class="mb-3">Pembayaran Berhasil!</h2>
                    <p class="lead">Terima kasih telah melakukan pembayaran. Berikut detail transaksi Anda:</p>
                    
                    <div class="receipt p-4 my-4 text-start">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <h5>Detail Transaksi</h5>
                                <p class="mb-1"><strong>ID Transaksi:</strong> #<?= str_pad($transaksi['idtransaksi'], 6, '0', STR_PAD_LEFT) ?></p>
                                <p class="mb-1"><strong>Tanggal Pembayaran:</strong> <?= $tgl_bayar ?></p>
                                <p class="mb-1"><strong>Metode Pembayaran:</strong> <?= ucfirst($transaksi['metode_pembayaran']) ?></p>
                                <p class="mb-1"><strong>Status:</strong> <span class="badge bg-success"><?= ucfirst($transaksi['status_pembayaran']) ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <h5>Detail Rental</h5>
                                <p class="mb-1"><strong>Mobil:</strong> <?= htmlspecialchars($transaksi['merk']) ?> (<?= htmlspecialchars($transaksi['platnomor']) ?>)</p>
                                <p class="mb-1"><strong>Tanggal Rental:</strong> <?= $tgl_rental ?></p>
                                <p class="mb-1"><strong>Tanggal Kembali:</strong> <?= $tgl_kembali ?></p>
                            </div>
                        </div>
                        <div class="bg-white p-3 rounded text-end">
                            <h4 class="mb-0">Total Pembayaran: <span class="text-primary">Rp <?= number_format($transaksi['jumlah_bayar'], 0, ',', '.') ?></span></h4>
                        </div>
                    </div>
                    
                    <?php if ($transaksi['metode_pembayaran'] === 'transfer'): ?>
                        <div class="alert alert-info text-start">
                            <h5><i class="bi bi-info-circle"></i> Instruksi Tambahan</h5>
                            <p class="mb-2">Pembayaran Anda sedang diverifikasi oleh admin. Proses verifikasi biasanya memakan waktu 1x24 jam.</p>
                            <p class="mb-0">Anda akan menerima notifikasi via WhatsApp atau email setelah pembayaran dikonfirmasi.</p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-start">
                            <h5><i class="bi bi-info-circle"></i> Instruksi Tambahan</h5>
                            <p class="mb-0">Silakan datang ke kantor kami pada tanggal rental dengan membawa KTP dan bukti pembayaran.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-center mt-4">
                        <a href="history.php" class="btn btn-outline-primary"><i class="bi bi-list-ul"></i> Lihat Riwayat Rental</a>
                        <a href="index.php" class="btn btn-primary"><i class="bi bi-house"></i> Kembali ke Beranda</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>