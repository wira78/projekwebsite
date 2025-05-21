<?php
session_start();
require '../../koneksi/koneksi.php';

if (empty($_SESSION['USER']) || $_SESSION['USER']['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_rental'])) {
    $id_rental = filter_input(INPUT_POST, 'id_rental', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
    $konfirmasi = filter_input(INPUT_POST, 'konfirmasi_pembayaran', FILTER_SANITIZE_STRING);

    try {
        $query = "UPDATE rental SET status = :status, konfirmasi_pembayaran = :konfirmasi WHERE id_rental = :id";
        $stmt = $koneksi->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':konfirmasi', $konfirmasi);
        $stmt->bindParam(':id', $id_rental, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['flash_success'] = "Status booking berhasil diperbarui.";
        header("Location: booking.php");
        exit();
    } catch (PDOException $e) {
        die("Gagal memperbarui status: " . $e->getMessage());
    }
}

$title_web = 'Daftar Booking';
include '../header.php';

$filter_id = null;
if (!empty($_GET['id'])) {
    $filter_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $sql = "SELECT * FROM view_booking_info WHERE iduser = :id ORDER BY id_rental DESC";
} else {
    $sql = "SELECT * FROM view_booking_info ORDER BY id_rental DESC";
}

try {
    $stmt = $koneksi->prepare($sql);
    if ($filter_id) {
        $stmt->bindParam(':id', $filter_id, PDO::PARAM_INT);
    }
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'selesai': return 'bg-success';
        case 'diproses': return 'bg-primary';
        case 'batal': return 'bg-danger';
        case 'pending': return 'bg-warning text-dark';
        default: return 'bg-secondary';
    }
}
?>

<div class="container mt-4">
    <?php if (!empty($_SESSION['flash_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $_SESSION['flash_success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-header text-white bg-primary">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Daftar Booking</h5>
                <?php if ($filter_id): ?>
                    <span class="badge bg-light text-dark">Filter: User ID <?= htmlspecialchars($filter_id) ?></span>
                <?php endif; ?>
                <a href="export_booking.php" class="btn btn-sm btn-success">
                    <i class="fas fa-file-export"></i> Export Data
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead class="table-white">
                        <tr>
                            <th>No</th>
                            <th>Kode Booking</th>
                            <th>Merk Mobil</th>
                            <th>Nama Pelanggan</th>
                            <th>Tanggal Sewa</th>
                            <th>Lama Sewa</th>
                            <th>Total Harga</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($bookings)): ?>
                            <tr>
                                <td colspan="9" class="text-center">Tidak ada data booking</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($bookings as $index => $booking): ?>
                                <tr>
                                    <td><?= $index + 1 ?></td>
                                    <td><?= htmlspecialchars($booking['kode_booking']) ?></td>
                                    <td><?= htmlspecialchars($booking['merk']) ?></td>
                                    <td><?= htmlspecialchars($booking['nama_pelanggan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($booking['tgl_rental'])) ?></td>
                                    <td><?= htmlspecialchars($booking['durasi']) ?> hari</td>
                                    <td>Rp <?= number_format($booking['total_harga'], 0, ',', '.') ?></td>
                                    <td>
                                        <span class="badge <?= getStatusBadgeClass($booking['status']) ?>">
                                            <?= htmlspecialchars(ucfirst($booking['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="bayar.php?id=<?= $booking['id_rental'] ?>" class="btn btn-sm btn-primary" title="Detail">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                        <button class="btn btn-sm btn-warning update-status"
                                                data-id="<?= $booking['id_rental'] ?>"
                                                data-status="<?= $booking['status'] ?>"
                                                data-konfirmasi="<?= $booking['konfirmasi_pembayaran'] ?>"
                                                title="Ubah Status">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Status Booking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="id_rental" id="modal_rental_id">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="status" id="modal_status" class="form-select" required>
                        <option value="pending">Pending</option>
                        <option value="diproses">Diproses</option>
                        <option value="selesai">Selesai</option>
                        <option value="batal">Batal</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Konfirmasi Pembayaran</label>
                    <select name="konfirmasi_pembayaran" id="modal_konfirmasi" class="form-select" required>
                        <option value="belum">Belum</option>
                        <option value="sebagian">Sebagian</option>
                        <option value="lunas">Lunas</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
document.querySelectorAll('.update-status').forEach(button => {
    button.addEventListener('click', () => {
        const id = button.getAttribute('data-id');
        const status = button.getAttribute('data-status');
        const konfirmasi = button.getAttribute('data-konfirmasi');

        document.getElementById('modal_rental_id').value = id;
        document.getElementById('modal_status').value = status;
        document.getElementById('modal_konfirmasi').value = konfirmasi;

        new bootstrap.Modal(document.getElementById('statusModal')).show();
    });
});
</script>

<?php include '../footer.php'; ?>
