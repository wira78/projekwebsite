<?php
require 'koneksi/koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['USER'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['USER'];
$errors = $_SESSION['ERRORS'] ?? [];
unset($_SESSION['ERRORS']);

try {
    $stmt = $koneksi->prepare("
        SELECT * FROM vw_user_rental_history
        WHERE iduser = ?
        ORDER BY tgl_rental DESC
    ");
    $stmt->execute([$_SESSION['USER']['iduser']]);
    $rentals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($rentals as $rental) {
        
    }
} catch (PDOException $e) {
    die("Failed to fetch rental history: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Rental - Rental Mobil</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.0/font/bootstrap-icons.css">
    <style>
        .rental-card {
            transition: all 0.3s;
        }
        .rental-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h2 class="fw-bold"><i class="bi bi-clock-history"></i> Riwayat Rental</h2>
            <p class="text-muted">Berikut adalah daftar riwayat rental mobil Anda.</p>
        </div>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p class="mb-0"><?= htmlspecialchars($error) ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($rentals)): ?>
        <div class="alert alert-info">
            <h5><i class="bi bi-info-circle"></i> Belum ada riwayat rental</h5>
            <p class="mb-0">Anda belum pernah melakukan rental mobil. <a href="index.php" class="alert-link">Klik disini</a> untuk melihat daftar mobil yang tersedia.</p>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($rentals as $rental): 
                $status_class = '';
                $payment_status = $rental['status_pembayaran'] ?? 'unpaid';
                
                if ($payment_status === 'paid') {
                    $status_class = 'bg-success';
                } elseif ($payment_status === 'pending') {
                    $status_class = 'bg-warning text-dark';
                } else {
                    $status_class = 'bg-secondary';
                }
                
                $rental_status = '';
                $current_date = new DateTime();
                $tgl_rental = new DateTime($rental['tgl_rental']);
                $tgl_kembali = new DateTime($rental['tgl_kembali']);
                
                if ($current_date > $tgl_kembali) {
                    $rental_status = 'Selesai';
                } elseif ($current_date >= $tgl_rental && $current_date <= $tgl_kembali) {
                    $rental_status = 'Sedang Berjalan';
                } else {
                    $rental_status = 'Belum Dimulai';
                }
            ?>
            <div class="col-md-6 mb-4">
                <div class="card rental-card h-100">
                    <div class="row g-0">
                        <div class="col-md-4">
                            <img src="assets/img/mobil/<?= htmlspecialchars($rental['gambar']) ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="<?= htmlspecialchars($rental['merk']) ?>">
                        </div>
                        <div class="col-md-8">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h5 class="card-title mb-0"><?= htmlspecialchars($rental['merk']) ?></h5>
                                    <span class="badge <?= $status_class ?> status-badge">
                                        <?= ucfirst($payment_status) ?>
                                    </span>
                                </div>
                                <p class="card-text text-muted small mb-1"><i class="bi bi-car-front"></i> <?= htmlspecialchars($rental['platnomor']) ?></p>
                                <p class="card-text small mb-1"><i class="bi bi-calendar-event"></i> <?= date('d M Y', strtotime($rental['tgl_rental'])) ?> - <?= date('d M Y', strtotime($rental['tgl_kembali'])) ?></p>
                                <p class="card-text small mb-2"><i class="bi bi-info-circle"></i> Status: <?= $rental_status ?></p>
                                <h6 class="card-subtitle mb-3 text-primary">Rp <?= number_format($rental['total_harga'], 0, ',', '.') ?></h6>
                                
                                <div class="d-flex justify-content-between">
                                    <a href="detail_rental.php?id_rental=<?= $rental['id_rental'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i> Detail
                                    </a>
                                    
                                    <?php if ($payment_status === 'unpaid' || $payment_status === null): ?>
                                        <a href="payment.php?id_rental=<?= $rental['id_rental'] ?>" class="btn btn-sm btn-primary">
                                            <i class="bi bi-credit-card"></i> Bayar Sekarang
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

<script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>