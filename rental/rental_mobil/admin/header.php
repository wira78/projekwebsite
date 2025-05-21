<?php

require 'C:/xampp/htdocs/rental/rental_mobil-master/koneksi/koneksi.php';

if(empty($_SESSION['USER'])) {
    echo '<script>alert("Anda harus login terlebih dahulu!");window.location="../login.php";</script>';
    exit();
}

if($_SESSION['USER']['role'] !== 'admin') {
    echo '<script>alert("Akses ditolak! Halaman khusus admin.");window.location="../index.php";</script>';
    exit();
}

$iduser = $_SESSION['USER']['iduser'];
$row = $koneksi->prepare("SELECT * FROM user_pelanggan WHERE iduser=?");
$row->execute(array($iduser));
$hasil_login = $row->fetch();
$url = 'http://' . $_SERVER['HTTP_HOST'] . '/rental/rental_mobil-master/';
$info_sql = $koneksi->query("SELECT * FROM informasi_rental WHERE id_info = 1");
$info_web = $info_sql->fetch(PDO::FETCH_OBJ);
?>

<!doctype html>
<html lang="en">
  <head>
    <title><?php echo $title_web;?> | Rental Mobil</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="<?php echo $url;?>assets/css/bootstrap.css" >
    <link rel="stylesheet" href="<?php echo $url;?>assets/css/font-awesome.css" >
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <style>
        .active {
            background-color: #555;
            border-radius: 5px;
        }
    </style>
  </head>
  <body>
    <div class="jumbotron pt-4 pb-4">
        <div class="row">
            <div class="col-sm-8">
                <h2><b style="text-transform:uppercase;"><?= htmlspecialchars($info_web->nama_rental); ?> </b></h2>
            </div>
        </div>
    </div>
    <div style="margin-top:-2pc"></div>
    <nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #333;">
        <a class="navbar-brand" href="<?php echo $url;?>admin/"><b>Admin Panel</b></a>
        <button class="navbar-toggler text-white d-lg-none" type="button" data-toggle="collapse" data-target="#collapsibleNavId" aria-controls="collapsibleNavId"
            aria-expanded="false" aria-label="Toggle navigation" style="color:#fff;">
            <i class="fa fa-bars"></i>
        </button>
        <div class="collapse navbar-collapse" id="collapsibleNavId">
            <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
                <li class="nav-item <?php if($title_web == 'Dashboard'){ echo 'active';}?>">
                    <a class="nav-link" href="<?php echo $url;?>admin/">Home <span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item <?php if($title_web == 'User'){ echo 'active';}?>">
                    <a class="nav-link" href="<?php echo $url;?>admin/user/index.php">User / Pelanggan</a>
                </li>
                <li class="nav-item <?php if($title_web == 'Daftar Mobil'){ echo 'active';}?>
                <?php if($title_web == 'Tambah Mobil'){ echo 'active';}?>
                <?php if($title_web == 'Edit Mobil'){ echo 'active';}?>">
                    <a class="nav-link" href="<?php echo $url;?>admin/mobil/mobil.php">Daftar Mobil</a>
                </li>
                <li class="nav-item <?php if($title_web == 'Daftar Booking'){ echo 'active';}?>
                <?php if($title_web == 'Konfirmasi'){ echo 'active';}?>">
                    <a class="nav-link" href="<?php echo $url;?>admin/booking/booking.php">Daftar Booking</a>
                </li>
                <li class="nav-item <?php if($title_web == 'Peminjaman'){ echo 'active';}?>">
                    <a class="nav-link" href="<?php echo $url;?>admin/peminjaman/peminjaman.php">Peminjaman / Pengembalian</a>
                </li>
            </ul>
            <ul class="navbar-nav my-2 my-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="#">
                        <i class="fa fa-user"> </i> Hallo, <?php echo htmlspecialchars($hasil_login['nama']); ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" onclick="return confirm('Apakah anda ingin logout ?');" href="<?php echo $url;?>admin/logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>
    </body>
</html>
    