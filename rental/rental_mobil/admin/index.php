<?php

session_start();
if(empty($_SESSION['USER']) || $_SESSION['USER']['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("X-XSS-Protection: 1; mode=block");

date_default_timezone_set('Asia/Jakarta');

require '../koneksi/koneksi.php';

$title_web = 'Dashboard Admin';

include 'header.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $_SESSION['error'] = "Invalid CSRF token";
        header("Location: index.php");
        exit();
    }

    if(!empty($_POST['nama_rental'])) {
        try {
            $stmt = $koneksi->prepare("CALL sp_update_website_info(?, ?, ?, ?, ?, @result)");
            $stmt->execute([
                htmlspecialchars($_POST["nama_rental"]),
                htmlspecialchars($_POST["telp"]),
                htmlspecialchars($_POST["alamat"]),
                filter_var($_POST["email"], FILTER_SANITIZE_EMAIL),
                htmlspecialchars($_POST["no_rek"])
            ]);
            
            $result = $koneksi->query("SELECT @result AS result")->fetch(PDO::FETCH_OBJ);
            
            if(strpos($result->result, 'Error') === false) {
                $_SESSION['success'] = "Update Data Info Website Berhasil!";
            } else {
                $_SESSION['error'] = $result->result;
            }
            
            header("Location: index.php");
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal update info website: " . $e->getMessage();
        }
    }
    
    if(!empty($_POST['nama'])) {
        try {
            $password = !empty($_POST["password"]) ? 
                password_hash($_POST["password"], PASSWORD_DEFAULT) : 
                $_SESSION['USER']['password'];
            
            $stmt = $koneksi->prepare("CALL sp_update_admin_profile(?, ?, ?, ?, @result)");
            $stmt->execute([
                $_SESSION['USER']['iduser'],
                htmlspecialchars($_POST["nama"]),
                htmlspecialchars($_POST["username"]),
                $password
            ]);
            
            $result = $koneksi->query("SELECT @result AS result")->fetch(PDO::FETCH_OBJ);
            
            if(strpos($result->result, 'Error') === false) {
                $_SESSION['USER']['nama'] = htmlspecialchars($_POST["nama"]);
                $_SESSION['USER']['username'] = htmlspecialchars($_POST["username"]);
                $_SESSION['USER']['password'] = $password;
                
                $_SESSION['success'] = "Update Data Profil Berhasil!";
            } else {
                $_SESSION['error'] = $result->result;
            }
            
            header("Location: index.php");
            exit();
        } catch(PDOException $e) {
            $_SESSION['error'] = "Gagal update profil: " . $e->getMessage();
        }
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

try {
    $stmt = $koneksi->prepare("CALL sp_get_dashboard_stats(@available_cars, @total_customers, @today_rentals)");
    $stmt->execute();
    
    $result = $koneksi->query("SELECT @available_cars AS available_cars, 
                              @total_customers AS total_customers, 
                              @today_rentals AS today_rentals")->fetch(PDO::FETCH_OBJ);
    
    $availableCars = $result->available_cars;
    $totalCustomers = $result->total_customers;
    $todayRentals = $result->today_rentals;
} catch(PDOException $e) {
    $availableCars = 0;
    $totalCustomers = 0;
    $todayRentals = 0;
    $_SESSION['error'] = "Gagal memuat statistik: " . $e->getMessage();
}

try {
    $sql = "SELECT * FROM informasi_rental WHERE id_info = 1";
    $row = $koneksi->prepare($sql);
    $row->execute();
    $rentalInfo = $row->fetch(PDO::FETCH_OBJ);
} catch(PDOException $e) {
    $rentalInfo = null;
    $_SESSION['error'] = "Gagal memuat informasi rental: " . $e->getMessage();
}

try {
    $id = $_SESSION["USER"]["iduser"];
    $sql = "SELECT * FROM user_pelanggan WHERE iduser = ?";
    $row = $koneksi->prepare($sql);
    $row->execute(array($id));
    $adminProfile = $row->fetch(PDO::FETCH_OBJ);
} catch(PDOException $e) {
    $adminProfile = null;
    $_SESSION['error'] = "Gagal memuat profil admin: " . $e->getMessage();
}

try {
    $recentRentals = $koneksi->query("SELECT * FROM vw_recent_rentals")->fetchAll(PDO::FETCH_OBJ);
} catch(PDOException $e) {
    $recentRentals = [];
    $_SESSION['error'] = "Gagal memuat data rental terbaru: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?= $title_web ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #4e73df;
            --secondary: #858796;
            --success: #1cc88a;
            --info: #36b9cc;
            --warning: #f6c23e;
            --danger: #e74a3b;
            --light: #f8f9fc;
            --dark: #5a5c69;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fc;
            color: #333;
        }
        
        .navbar {
            background-color: white;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .card {
            border: none;
            border-radius: 0.5rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
            margin-bottom: 1.5rem;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }
        
        .stat-card {
            background: white;
            border-left: 4px solid var(--primary);
            padding: 1.5rem;
            border-radius: 0.5rem;
        }
        
        .stat-card.primary { border-left-color: var(--primary); }
        .stat-card.success { border-left-color: var(--success); }
        .stat-card.info { border-left-color: var(--info); }
        
        .card-header {
            background: none;
            padding: 1.5rem;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            font-weight: 600;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .table {
            color: #5a5c69;
        }
        
        .table thead th {
            background-color: #f8f9fc;
            border-bottom: 1px solid #e3e6f0;
            font-weight: 600;
        }
        
        .table-hover tbody tr:hover {
            background-color: #f8f9fc;
        }
        
        .btn {
            border-radius: 0.35rem;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-icon-split {
            display: inline-flex;
            align-items: center;
        }
        
        .btn-icon-split .icon {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-right: 1px solid rgba(255,255,255,0.15);
            margin-right: 0.75rem;
        }
        
        .form-control {
            border-radius: 0.35rem;
            border: 1px solid #d1d3e2;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
        }
        
        .form-control:focus {
            border-color: #bac8f3;
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }
        
        .input-group-text {
            border-radius: 0.35rem 0 0 0.35rem;
            background-color: #f8f9fc;
            border: 1px solid #d1d3e2;
        }
        
        .page-header {
            margin-bottom: 1.5rem;
            border-bottom: 1px solid #e3e6f0;
            padding-bottom: 1rem;
        }
        
        .alert {
            border-radius: 0.35rem;
            border: none;
        }
        
        .dashboard-container {
            padding: 1.5rem;
        }
        
        .stat-icon {
            font-size: 2rem;
            opacity: 0.3;
        }
        
        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
        }
        
        .stat-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 700;
        }
    </style>
</head>
<body>
    
<div class="dashboard-container">
    <div class="d-flex align-items-center justify-content-between page-header">
        <h1 class="h3 mb-0 text-gray-800"><?= $title_web ?></h1>
        <div>
            <span class="mr-2 d-none d-lg-inline text-gray-600">
                <i class="fas fa-user-circle mr-1"></i> <?= htmlspecialchars($_SESSION['USER']['nama']); ?>
            </span>
        </div>
    </div>

    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-2"></i><?= $_SESSION['success']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    
    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-2"></i><?= $_SESSION['error']; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-xl-4 col-md-4 mb-4">
            <div class="card stat-card primary h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-1 text-primary">Mobil Tersedia</p>
                        <p class="stat-value mb-0"><?= $availableCars ?></p>
                    </div>
                    <div class="stat-icon text-primary">
                        <i class="fas fa-car"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 mb-4">
            <div class="card stat-card success h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-1 text-success">Total Pelanggan</p>
                        <p class="stat-value mb-0"><?= $totalCustomers ?></p>
                    </div>
                    <div class="stat-icon text-success">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-4 mb-4">
            <div class="card stat-card info h-100">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="stat-label mb-1 text-info">Peminjaman Hari Ini</p>
                        <p class="stat-value mb-0"><?= $todayRentals ?></p>
                    </div>
                    <div class="stat-icon text-info">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-info-circle mr-2"></i>Informasi Rental
                    </h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="nama_rental" class="font-weight-bold small text-uppercase">Nama Rental</label>
                            <input type="text" class="form-control" 
                                   value="<?= htmlspecialchars($rentalInfo->nama_rental); ?>" 
                                   name="nama_rental" id="nama_rental" required>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email" class="font-weight-bold small text-uppercase">Email</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        </div>
                                        <input type="email" class="form-control" 
                                               value="<?= htmlspecialchars($rentalInfo->email); ?>" 
                                               name="email" id="email" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="telp" class="font-weight-bold small text-uppercase">Telepon</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        </div>
                                        <input type="text" class="form-control" 
                                               value="<?= htmlspecialchars($rentalInfo->no_telp); ?>" 
                                               name="telp" id="telp" required>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="alamat" class="font-weight-bold small text-uppercase">Alamat</label>
                            <textarea class="form-control" name="alamat" id="alamat" rows="3" required><?= htmlspecialchars($rentalInfo->alamat_rental); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="no_rek" class="font-weight-bold small text-uppercase">Nomor Rekening</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-credit-card"></i></span>
                                </div>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($rentalInfo->no_rek); ?>" 
                                       name="no_rek" id="no_rek" required>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-success">
                        <i class="fas fa-user-cog mr-2"></i>Profil Admin
                    </h6>
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="form-group">
                            <label for="nama_pengguna" class="font-weight-bold small text-uppercase">Nama Pengguna</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($adminProfile->nama); ?>" 
                                       name="nama_pengguna" id="nama" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="username" class="font-weight-bold small text-uppercase">Username</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-at"></i></span>
                                </div>
                                <input type="text" class="form-control" 
                                       value="<?= htmlspecialchars($adminProfile->username); ?>" 
                                       name="username" id="username" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="password" class="font-weight-bold small text-uppercase">Password Baru</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                </div>
                                <input type="password" class="form-control" name="password" id="password" placeholder="Biarkan kosong jika tidak ingin mengubah">
                            </div>
                            <small class="form-text text-muted">Minimal 8 karakter</small>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-edit mr-2"></i>Update Profil
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-info">
            <i class="fas fa-history mr-2"></i>Peminjaman Terbaru
        </h6>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Pelanggan</th>
                        <th>Mobil</th>
                        <th>Tanggal Rental</th>
                        <th>Tanggal Kembali</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recentRentals as $r): ?>
                    <tr>
                        <td><?= $r->id_rental; ?></td>
                        <td><?= htmlspecialchars($r->pelanggan_nama ?? 'N/A'); ?></td>
                        <td><?= htmlspecialchars($r->mobil_merk ?? 'N/A'); ?></td>
                        <td><?= date('d M Y', strtotime($r->tgl_rental)); ?></td>
                        <td><?= date('d M Y', strtotime($r->tgl_kembali)); ?></td>
                        <td>
                            <a href="booking/bayar.php?id=<?= $r->id_rental; ?>" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    
                    <?php if(count($recentRentals) === 0): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4">Tidak ada data peminjaman terbaru</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
<?php include 'footer.php';?>