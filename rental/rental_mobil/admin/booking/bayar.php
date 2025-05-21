<?php

session_start();
require '../../koneksi/koneksi.php';

if(empty($_SESSION['USER'])) {
    header("Location: ../../login.php");
    exit();
}

$title_web = 'Konfirmasi Pembayaran';
include '../header.php';

$id_rental = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if(!$id_rental) {
    $_SESSION['error'] = "ID Rental tidak valid";
    header("Location: peminjaman.php");
    exit();
}

try {
    $sql = "SELECT r.*, u.nama, u.no_ktp, u.no_hp, u.email, 
                   m.merk, m.harga_rental, m.gambar, m.status as mobil_status,
                   t.idtransaksi, t.tgl_bayar, t.jumlah_bayar, 
                   t.status_pembayaran
            FROM rental r
            JOIN user_pelanggan u ON r.iduser = u.iduser
            JOIN mobil m ON r.id_mobil = m.id_mobil
            LEFT JOIN transaksi t ON r.id_rental = t.id_rental
            WHERE r.id_rental = ?";
    
    $stmt = $koneksi->prepare($sql);
    $stmt->execute([$id_rental]);
    $rental = $stmt->fetch();
    
    if(!$rental) {
        $_SESSION['error'] = "Data rental tidak ditemukan";
        header("Location: peminjaman.php");
        exit();
    }

} catch(PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: peminjaman.php");
    exit();
}

$tgl_rental = new DateTime($rental['tgl_rental']);
$tgl_kembali = new DateTime($rental['tgl_kembali']);
$durasi = $tgl_rental->diff($tgl_kembali)->days;
?>

<div class="container mt-4">
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-credit-card"></i> Detail Pembayaran
                    </h5>
                </div>
                <div class="card-body">
                    <?php if(!empty($rental['idtransaksi'])): ?>
                    <table class="table table-bordered">
                        <tr>
                            <th width="40%">ID Transaksi</th>
                            <td><?= htmlspecialchars($rental['idtransaksi']) ?></td>
                        </tr>
                        
                        <tr>
                            <th>Jumlah Bayar</th>
                            <td>Rp <?= number_format($rental['jumlah_bayar'], 0, ',', '.') ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <span class="badge bg-<?= 
                                    $rental['status_pembayaran'] == 'lunas' ? 'success' : 
                                    ($rental['status_pembayaran'] == 'sebagian' ? 'warning' : 'danger') 
                                ?>">
                                    <?= ucfirst($rental['status_pembayaran']) ?>
                                </span>
                            </td>
                        </tr>
                        
                    </table>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-circle"></i> Belum ada pembayaran
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mt-3">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-car"></i> <?= htmlspecialchars($rental['merk']) ?>
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item <?= $rental['mobil_status'] == 'Tersedia' ? 'bg-success' : 'bg-danger' ?> text-white">
                        <i class="fas <?= $rental['mobil_status'] == 'Tersedia' ? 'fa-check' : 'fa-times' ?>"></i> 
                        <?= $rental['mobil_status'] == 'Tersedia' ? 'Tersedia' : 'Tidak Tersedia' ?>
                    </div>
                    <div class="list-group-item bg-secondary text-white">
                        <i class="fas fa-tag"></i> Rp <?= number_format($rental['harga_rental'], 0, ',', '.') ?>/hari
                    </div>
                    <div class="list-group-item bg-dark text-white">
                        <i class="fas fa-credit-card"></i> Free E-toll Rp 50.000
                    </div>
                </div>
                <div class="card-footer">
                    <a href="peminjaman.php?id=<?= $rental['id_rental'] ?>" 
                       class="btn btn-success btn-block">
                       <i class="fas fa-edit"></i> Ubah Status Peminjaman
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-check"></i> Detail Booking
                    </h5>
                </div>
                <div class="card-body">
                    <form method="post" action="proses.php" enctype="multipart/form-data">
                        <input type="hidden" name="id_rental" value="<?= $rental['id_rental'] ?>">
                        <input type="hidden" name="action" value="konfirmasi_pembayaran">
                        
                        <table class="table table-bordered">
                            <tr>
                                <th width="30%">Kode Booking</th>
                                <td><?= htmlspecialchars($rental['id_rental']) ?></td>
                            </tr>
                            <tr>
                                <th>No. KTP</th>
                                <td><?= htmlspecialchars($rental['no_ktp']) ?></td>
                            </tr>
                            <tr>
                                <th>Nama Pelanggan</th>
                                <td><?= htmlspecialchars($rental['nama']) ?></td>
                            </tr>
                            <tr>
                                <th>Telepon</th>
                                <td><?= htmlspecialchars($rental['no_hp']) ?></td>
                            </tr>
                            <tr>
                                <th>Email</th>
                                <td><?= htmlspecialchars($rental['email']) ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Sewa</th>
                                <td><?= date('d/m/Y', strtotime($rental['tgl_rental'])) ?></td>
                            </tr>
                            <tr>
                                <th>Tanggal Kembali</th>
                                <td><?= date('d/m/Y', strtotime($rental['tgl_kembali'])) ?></td>
                            </tr>
                            <tr>
                                <th>Lama Sewa</th>
                                <td><?= $durasi ?> hari</td>
                            </tr>
                            <tr>
                                <th>Total Harga</th>
                                <td>Rp <?= number_format($rental['total_harga'], 0, ',', '.') ?></td>
                            </tr>
                            <tr>
                                <th>Status Pembayaran</th>
                                <td>
                                    <select class="form-select" name="status_pembayaran" required>
                                        <option value="pending" <?= ($rental['status_pembayaran'] ?? '') == 'pending' ? 'selected' : '' ?>>Pending</option>
                                        <option value="sebagian" <?= ($rental['status_pembayaran'] ?? '') == 'sebagian' ? 'selected' : '' ?>>Pembayaran Sebagian</option>
                                        <option value="lunas" <?= ($rental['status_pembayaran'] ?? '') == 'lunas' ? 'selected' : '' ?>>Lunas</option>
                                    </select>
                                </td>
                            </tr>
                            
                            
                        </table>
                        
                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Simpan Perubahan
                            </button>
                            <a href="booking.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>