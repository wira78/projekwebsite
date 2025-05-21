<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require __DIR__ . '/koneksi/koneksi.php';


$info_web = $koneksi->query("SELECT nama_rental FROM informasi_rental LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Ambil data profil user jika sudah login
$userData = [];
if (!empty($_SESSION['USER'])) {
    try {
        
        $stmt = $koneksi->prepare("SELECT nama, username, foto FROM pelanggan WHERE id_pelanggan = ?");
        $stmt->execute([$_SESSION['USER']['id']]);
        $userData = $stmt->fetch(PDO::FETCH_ASSOC);
       
        if (!$userData) {
        
        }
    } catch (PDOException $e) {
       
        error_log("Error mengambil data user: " . $e->getMessage());
        
        $userData = [
            'nama' => $_SESSION['USER']['nama'] ?? $_SESSION['USER']['username'] ?? 'User',
            'username' => $_SESSION['USER']['username'] ?? 'User',
            'foto' => null
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <title>Rental Mobil</title>
   
    
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/css/font-awesome.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <style>
        .user-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
        }
        .dropdown-menu-right {
            right: 0;
            left: auto;
        }
    </style>
</head>
<body>
  
    <div class="jumbotron pt-4 pb-4">
        <div class="row">
            <div class="col-sm-8">
                <h2><b style="text-transform:uppercase;"><?= $info_web['nama_rental'] ?? 'Rental Mobil'; ?></b></h2>
            </div>
            <div class="col-sm-4">
                <form class="form-inline" method="get" action="index.php">
                    <input class="form-control mr-sm-2" type="search" name="cari" placeholder="Cari Nama Mobil" aria-label="Search">
                    <button class="btn btn-outline-success my-2 my-sm-0" type="submit">Search</button>
                </form>
            </div>
        </div>
    </div>
    
    <div style="margin-top:-2pc"></div>
    
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo01" aria-controls="navbarTogglerDemo01" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarTogglerDemo01">
            <a class="navbar-brand" href="#"></a>
            <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
                <li class="nav-item active">
                    <a class="nav-link" href="index.php">Home <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item active">
                    <a class="nav-link" href="kontak.php">Kontak Kami</a>
                </li>
                <?php if(!empty($_SESSION['USER'])) { ?>
                <li class="nav-item active">
                    <a class="nav-link" href="history.php">History</a>
                </li>
                <?php } ?>
            </ul>
            
            <?php if(!empty($_SESSION['USER'])) { ?>
            <ul class="navbar-nav my-2 my-lg-0">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php if (!empty($userData['foto_profil'])): ?>
                            <img src="uploads/profil/<?= htmlspecialchars($userData['foto_profil']) ?>" class="user-avatar" alt="Profil">
                        <?php else: ?>
                            <i class="fa fa-user-circle"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($userData['nama'] ?? $userData['username'] ?? 'User') ?>
                    </a>
                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                        <a class="dropdown-item" href="profil.php"><i class="fa fa-user"></i> Profil Saya</a>
                        <a class="dropdown-item" href="history.php"><i class="fa fa-history"></i> History Rental</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" onclick="return confirm('Apakah anda ingin logout?');" href="logout.php">
                            <i class="fa fa-sign-out"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
            <?php } ?>
        </div>
    </nav>