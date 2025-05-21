<?php
session_start();
require '../../koneksi/koneksi.php';
$title_web = 'Edit Mobil';
include '../header.php';

if(!isset($_SESSION['USER']) || empty($_SESSION['USER'])) {
    header("Location: ../login.php");
    exit();
}

if(!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID mobil tidak valid";
    header("Location: mobil.php");
    exit();
}

$id = (int)$_GET['id'];

try {
    $sql = "SELECT * FROM mobil WHERE id_mobil = ?";
    $row = $koneksi->prepare($sql);
    $row->execute([$id]);
    
    if($row->rowCount() == 0) {
        $_SESSION['error'] = "Data mobil tidak ditemukan";
        header("Location: mobil.php");
        exit();
    }
    
    $hasil = $row->fetch();
} catch(PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan database: " . $e->getMessage();
    header("Location: mobil.php");
    exit();
}
?>

<?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?= $_SESSION['success']; unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="container">
    <div class="card">
        <div class="card-header text-white bg-primary">
            <h4 class="card-title">
                Edit Mobil - <?= htmlspecialchars($hasil['merk']); ?>
                <div class="float-right">
                    <a class="btn btn-warning" href="mobil.php" role="button">Kembali</a>
                </div>
            </h4>
        </div>
        <div class="card-body">
            <div class="container">
                <form method="post" action="proses.php" enctype="multipart/form-data">
                    <input type="hidden" name="aksi" value="edit">
                    <input type="hidden" name="id" value="<?= $id; ?>">
                    
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="form-group row">
                                <label class="col-sm-3">No Plat</label>
                                <input type="text" class="form-control col-sm-9" 
                                       value="<?= htmlspecialchars($hasil['platnomor']); ?>" 
                                       name="no_plat" placeholder="Isi No Plat" required>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3">Merk Mobil</label>
                                <input type="text" class="form-control col-sm-9" 
                                       value="<?= htmlspecialchars($hasil['merk']); ?>" 
                                       name="merk" placeholder="Isi Merk" required>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3">Harga Rental</label>
                                <input type="number" class="form-control col-sm-9" 
                                       value="<?= htmlspecialchars($hasil['harga_rental']); ?>" 
                                       name="harga" placeholder="Isi Harga" required min="0">
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group row">
                                <label class="col-sm-3">Status</label>
                                <select class="form-control col-sm-9" name="status" required>
                                    <option value="Tersedia" <?= $hasil['status'] == 'Tersedia' ? 'selected' : ''; ?>>Tersedia</option>
                                    <option value="Tidak Tersedia" <?= $hasil['status'] == 'Tidak Tersedia' ? 'selected' : ''; ?>>Tidak Tersedia</option>
                                </select>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3">Gambar Saat Ini</label>
                                <div class="col-sm-9">
                                    <img src="../../assets/image/<?= htmlspecialchars($hasil['gambar']); ?>" 
                                         class="img-thumbnail" style="max-width: 200px;">
                                </div>
                            </div>

                            <div class="form-group row">
                                <label class="col-sm-3">Ganti Gambar</label>
                                <div class="col-sm-9">
                                    <input type="file" class="form-control-file" name="gambar" accept="image/*">
                                    <small class="form-text text-muted">Biarkan kosong jika tidak ingin mengubah gambar</small>
                                </div>
                            </div>
                            
                            <input type="hidden" name="gambar_lama" value="<?= htmlspecialchars($hasil['gambar']); ?>">
                        </div>
                    </div>
                    <hr>
                    <div class="float-right">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </div>
                </form>       
            </div>
        </div>
    </div>
</div>
<?php include '../footer.php'; ?>