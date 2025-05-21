<?php

session_start();

require '../../koneksi/koneksi.php';
$title_web = 'User Management';
include '../header.php';

if(empty($_SESSION['USER']) || $_SESSION['USER']['role'] !== 'admin') {
    header("Location: ../../login.php");
    exit();
}

$sql_create_view = "CREATE VIEW IF NOT EXISTS vw_user_pelanggan_with_rental AS
                    SELECT 
                        up.*,
                        (SELECT COUNT(*) FROM rental r WHERE r.iduser = up.iduser) AS total_rental
                    FROM user_pelanggan up";
$koneksi->exec($sql_create_view);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header text-white bg-primary">
                    <h5 class="card-title pt-2">
                        <i class="fas fa-users"></i> Daftar User / Pelanggan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <form method="get" class="form-inline">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Cari pelanggan..." 
                                           value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-secondary" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="btn-group">
                                <a href="?status=aktif" class="btn btn-sm <?= (!isset($_GET['status'])) || $_GET['status'] == 'aktif' ? 'btn-success' : 'btn-outline-success' ?>">
                                    Aktif
                                </a>
                                <a href="?status=tidak aktif" class="btn btn-sm <?= (isset($_GET['status'])) && $_GET['status'] == 'tidak aktif' ? 'btn-danger' : 'btn-outline-danger' ?>">
                                    Tidak Aktif
                                </a>
                                <a href="?" class="btn btn-sm btn-outline-primary">
                                    Tampilkan Semua
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover" id="dataTable">
                            <thead class="thead-white">
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pengguna</th>
                                    <th>Email</th>
                                    <th>No. HP</th>
                                    <th>No. KTP</th>
                                    <th>Jenis Kelamin</th>
                                    <th>Status</th>
                                    <th>Total Rental</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $search = isset($_GET['search']) ? "%".$_GET['search']."%" : "%";
                                $status_filter = isset($_GET['status']) ? $_GET['status'] : '%';
                                
                                $sql_count = "SELECT COUNT(*) FROM user_pelanggan 
                                             WHERE (nama LIKE ? OR email LIKE ? OR no_hp LIKE ?)
                                             AND status_pelanggan LIKE ?";
                                $stmt_count = $koneksi->prepare($sql_count);
                                $stmt_count->execute([$search, $search, $search, $status_filter]);
                                $total_rows = $stmt_count->fetchColumn();
                                
                                $per_page = 10;
                                $total_pages = ceil($total_rows / $per_page);
                                $current_page = isset($_GET['page']) ? max(1, min($total_pages, (int)$_GET['page'])) : 1;
                                $offset = ($current_page - 1) * $per_page;
                                
                                $sql = "SELECT 
                                            up.iduser,
                                            up.nama,
                                            up.email,
                                            up.no_hp,
                                            up.no_ktp,
                                            up.jenis_kelamin,
                                            up.status_pelanggan,
                                            (SELECT COUNT(*) FROM rental r WHERE r.iduser = up.iduser) as total_rental
                                        FROM user_pelanggan up
                                        WHERE (up.nama LIKE ? OR up.email LIKE ? OR up.no_hp LIKE ?)
                                        AND up.status_pelanggan LIKE ?
                                        ORDER BY up.iduser DESC 
                                        LIMIT ? OFFSET ?";
                                $stmt = $koneksi->prepare($sql);
                                $stmt->execute([$search, $search, $search, $status_filter, $per_page, $offset]);
                                $no = $offset + 1;
                                
                                while($r = $stmt->fetch(PDO::FETCH_OBJ)) {
                                ?>
                                <tr>
                                    <td><?= $no ?></td>
                                    <td><?= htmlspecialchars($r->nama) ?></td>
                                    <td><?= htmlspecialchars($r->email) ?></td>
                                    <td><?= htmlspecialchars($r->no_hp) ?></td>
                                    <td><?= isset($r->no_ktp) ? htmlspecialchars($r->no_ktp) : 'N/A' ?></td>
                                    <td><?= $r->jenis_kelamin == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
                                    <td>
                                        <span class="badge <?= $r->status_pelanggan == 'aktif' ? 'badge-success' : 'badge-danger' ?>">
                                            <?= ucfirst($r->status_pelanggan) ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-primary"><?= $r->total_rental ?></span>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="<?= $url ?>admin/booking/booking.php?id=<?= $r->iduser ?>" 
                                               class="btn btn-sm btn-primary" title="Detail Transaksi">
                                                <i class="fas fa-list"></i>
                                            </a>
                                            <a href="edit_user.php?id=<?= $r->iduser ?>" 
                                               class="btn btn-sm btn-warning" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if($r->status_pelanggan == 'aktif'): ?>
                                            <button class="btn btn-sm btn-danger btn-delete" 
                                                    data-id="<?= $r->iduser ?>" 
                                                    data-name="<?= htmlspecialchars($r->nama) ?>"
                                                    title="Nonaktifkan User">
                                                <i class="fas fa-user-times"></i>
                                            </button>
                                            <?php else: ?>
                                            <a href="aktifkan_user.php?id=<?= $r->iduser ?>" 
                                               class="btn btn-sm btn-success" title="Aktifkan User">
                                                <i class="fas fa-user-check"></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php $no++; } ?>
                                
                                <?php if($no == 1): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Tidak ada data pelanggan</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Konfirmasi Nonaktifkan User</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Apakah Anda yakin ingin menonaktifkan user <strong id="userName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <form id="deleteForm" method="post" action="nonaktifkan_user.php">
                    <input type="hidden" name="id" id="userId">
                    <button type="submit" class="btn btn-danger">Nonaktifkan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../footer.php'; ?>

<script>
$(document).ready(function() {
    $('.btn-delete').click(function() {
        var id = $(this).data('id');
        var name = $(this).data('name');
        $('#userId').val(id);
        $('#userName').text(name);
        $('#deleteModal').modal('show');
    });
    
    $('#dataTable').DataTable({
        "paging": false,
        "searching": false,
        "info": false,
        "ordering": true
    });
});
</script