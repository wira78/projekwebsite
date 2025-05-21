<?php
session_start();
require '../../koneksi/koneksi.php';
$title_web = 'Peminjaman dan Denda';
include '../header.php';

if(empty($_SESSION['USER'])) {
    header("Location: ../../login.php");
    exit();
}

function generateKodeBooking() {
    return "BOOK-" . date("Ymd") . "-" . strtoupper(substr(uniqid(), -4));
}

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['tambah_peminjaman'])) {
    $id_mobil = $_POST['id_mobil'];
    $id_user = $_SESSION['USER']['iduser']; 
    $durasi = $_POST['durasi'];
    $tgl_rental = date('Y-m-d');
    $tgl_rencana_kembali = date('Y-m-d', strtotime($tgl_rental . " + $durasi days"));
    $kode_booking = generateKodeBooking();
    
    try {
        $stmt = $koneksi->prepare("INSERT INTO rental 
                                 (tgl_rental, tgl_kembali, tgl_pengembalian, id_mobil, iduser) 
                                 VALUES (?, ?, NULL, ?, ?)");
        $stmt->execute([$tgl_rental, $tgl_rencana_kembali, $id_mobil, $id_user]);
        
        $koneksi->prepare("UPDATE mobil SET status = 'Tidak Tersedia' WHERE id_mobil = ?")->execute([$id_mobil]);
        
        echo '<script>alert("Peminjaman berhasil!");</script>';
    } catch(PDOException $e) {
        echo '<script>alert("Gagal: '.$e->getMessage().'");</script>';
    }
}

if(isset($_GET['kembalikan'])) {
    $id_rental = $_GET['kembalikan'];
    $tgl_pengembalian = date('Y-m-d');
    
    try {
        $stmt = $koneksi->prepare("SELECT r.*, m.id_mobil, r.tgl_kembali 
                                 FROM rental r
                                 JOIN mobil m ON r.id_mobil = m.id_mobil
                                 WHERE r.id_rental = ?");
        $stmt->execute([$id_rental]);
        $rental = $stmt->fetch();
        
        $denda = 0;
        $keterangan = 'Tepat waktu';
        if($tgl_pengembalian > $rental['tgl_kembali']) {
            $hari_terlambat = date_diff(
                new DateTime($rental['tgl_kembali']), 
                new DateTime($tgl_pengembalian)
            )->days;
            $denda = $hari_terlambat * 50000;
            $keterangan = 'Terlambat '.$hari_terlambat.' hari';
            
            $stmt_denda = $koneksi->prepare("INSERT INTO denda 
                                           (id_rental, tgl_denda, jumlah_denda, keterangan) 
                                           VALUES (?, ?, ?, ?)");
            $stmt_denda->execute([
                $id_rental,
                $tgl_pengembalian,
                $denda,
                $keterangan
            ]);
        }
        
        $update = $koneksi->prepare("UPDATE rental SET 
                                   tgl_pengembalian = ?
                                   WHERE id_rental = ?");
        $update->execute([$tgl_pengembalian, $id_rental]);
        
        $koneksi->prepare("UPDATE mobil SET status = 'Tersedia' WHERE id_mobil = ?")
                ->execute([$rental['id_mobil']]);
        
        echo '<script>alert("Mobil telah dikembalikan. Denda: Rp '.number_format($denda).'");</script>';
    } catch(PDOException $e) {
        echo '<script>alert("Error: '.$e->getMessage().'");</script>';
    }
}

$mobil = $koneksi->query("SELECT * FROM mobil WHERE status = 'Tersedia'")->fetchAll();

$peminjaman = $koneksi->query("SELECT r.*, m.merk, u.nama 
                              FROM rental r
                              JOIN mobil m ON r.id_mobil = m.id_mobil
                              JOIN user_pelanggan u ON r.iduser = u.iduser
                              WHERE r.tgl_pengembalian IS NULL")->fetchAll();

$denda = $koneksi->query("SELECT d.*, r.id_rental, u.nama, m.merk
                         FROM denda d
                         JOIN rental r ON d.id_rental = r.id_rental
                         JOIN user_pelanggan u ON r.iduser = u.iduser
                         JOIN mobil m ON r.id_mobil = m.id_mobil
                         ORDER BY d.tgl_denda DESC")->fetchAll();
?>
<div class="container py-4">
    <h4 class="mb-4"><?= $title_web ?></h4>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Form Peminjaman Mobil</h5>
        </div>
        <div class="card-body">
            <form method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pilih Mobil</label>
                        <select name="id_mobil" class="form-select" required>
                            <option value="">-- Pilih Mobil --</option>
                            <?php foreach($mobil as $m): ?>
                                <option value="<?= $m['id_mobil'] ?>">
                                    <?= htmlspecialchars($m['merk']) ?> - <?= htmlspecialchars($m['platnomor']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Durasi (hari)</label>
                        <input type="number" name="durasi" class="form-control" min="1" value="1" required>
                    </div>
                </div>
                <button type="submit" name="tambah_peminjaman" class="btn btn-primary">
                    <i class="fas fa-car"></i> Pinjam Mobil
                </button>
            </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0">Peminjaman Aktif</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Penyewa</th>
                            <th>Mobil</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($peminjaman)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data peminjaman aktif</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($peminjaman as $i => $p): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($p['nama']) ?></td>
                                    <td><?= htmlspecialchars($p['merk']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['tgl_rental'])) ?></td>
                                    <td><?= date('d/m/Y', strtotime($p['tgl_kembali'])) ?></td>
                                    <td>
                                        <a href="?kembalikan=<?= $p['id_rental'] ?>" class="btn btn-sm btn-success"
                                           onclick="return confirm('Yakin mobil sudah dikembalikan?')">
                                            <i class="fas fa-check"></i> Kembalikan
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Riwayat Denda</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Mobil</th>
                            <th>Tgl Denda</th>
                            <th>Jumlah</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($denda)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data denda</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($denda as $i => $d): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($d['nama']) ?></td>
                                    <td><?= htmlspecialchars($d['merk']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($d['tgl_denda'])) ?></td>
                                    <td>Rp <?= number_format($d['jumlah_denda'], 0, ',', '.') ?></td>
                                    <td><?= htmlspecialchars($d['keterangan']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>