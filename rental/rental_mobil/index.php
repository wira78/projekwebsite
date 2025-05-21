<?php

require 'koneksi/koneksi.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['USER'])) {
    header("Location: login.php");
    exit();
}

$carDetails = null;
if (isset($_GET['id'])) {
    $carId = $_GET['id'];
    $stmt = $koneksi->prepare('SELECT * FROM mobil WHERE id_mobil = ?');
    $stmt->execute([$carId]);
    $carDetails = $stmt->fetch();
}

include 'header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-user-circle fa-4x text-primary"></i>
                    </div>
                    <h5 class="card-title"><?php echo htmlspecialchars($_SESSION['USER']['nama']); ?></h5>
                    <p class="text-muted">Welcome back!</p>
                    <a href="logout.php" class="btn btn-outline-danger btn-block mt-3">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">Available Cars</h6>
                </div>
                <div class="card-body">
                    <?php 
                    $totalCars = $koneksi->query('SELECT COUNT(*) FROM mobil')->fetchColumn();
                    ?>
                    <h3 class="text-center"><?php echo $totalCars; ?></h3>
                </div>
            </div>
        </div>

        <div class="col-md-9">
            <?php if ($carDetails): ?>
                <div class="card shadow-sm mb-4">
                    <div class="row no-gutters">
                        <div class="col-md-5">
                            <img src="assets/image/<?php echo htmlspecialchars($carDetails['gambar'] ?? 'default-car.jpg'); ?>" 
                                 class="card-img h-100" 
                                 style="object-fit: cover; max-height: 400px;">
                        </div>
                        <div class="col-md-7">
                            <div class="card-body">
                                <h3 class="card-title"><?php echo htmlspecialchars($carDetails['merk'] ?? 'Car Model'); ?></h3>
                                <p class="text-muted">License: <?php echo htmlspecialchars($carDetails['platnomor']); ?></p>
                                
                                <div class="row mt-4">
                                    <div class="col-6">
                                        <p><strong>Year:</strong> <?php echo htmlspecialchars($carDetails['tahun']); ?></p>
                                        <p><strong>Color:</strong> <?php echo htmlspecialchars($carDetails['warna']); ?></p>
                                    </div>
                                    <div class="col-6">
                                        <p><strong>Seats:</strong> <?php echo htmlspecialchars($carDetails['jumlah_kursi'] ?? '4'); ?></p>
                                        <p><strong>Transmission:</strong> <?php echo htmlspecialchars($carDetails['transmisi'] ?? 'Automatic'); ?></p>
                                    </div>
                                </div>
                                
                                <hr>
                                
                                <h4 class="text-primary">
                                    Rp. <?php echo number_format($carDetails['harga_rental'], 0, ',', '.'); ?> / day
                                </h4>
                                
                                <div class="mt-4">
                                    <a href="booking.php?id=<?php echo htmlspecialchars($mobil['id_mobil']); ?>" 
   class="btn btn-sm btn-primary">
    <i class="fas fa-calendar-alt"></i> Book Now
</a>
                                    <a href="index.php" class="btn btn-outline-secondary ml-2">
                                        <i class="fas fa-arrow-left"></i> Back to List
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Available Rental Cars</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $query = $koneksi->query('SELECT * FROM mobil ORDER BY tahun DESC')->fetchAll();
                            foreach($query as $mobil): 
                            ?>
                                <div class="col-lg-4 col-md-6 mb-4">
                                    <div class="card h-100 shadow-sm">
                                        <img src="assets/image/<?php echo htmlspecialchars($mobil['gambar'] ?? 'default-car.jpg'); ?>" 
                                             class="card-img-top" 
                                             style="height: 180px; object-fit: cover;">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($mobil['merk'] ?? 'Car Model'); ?></h5>
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="badge badge-info"><?php echo htmlspecialchars($mobil['tahun']); ?></span>
                                                <span class="badge badge-secondary"><?php echo htmlspecialchars($mobil['warna']); ?></span>
                                            </div>
                                            <p class="text-primary font-weight-bold mb-3">
                                                Rp. <?php echo number_format($mobil['harga_rental'], 0, ',', '.'); ?> / day
                                            </p>
                                        </div>
                                        <div class="card-footer bg-white">
                                            <div class="d-flex justify-content-between">
                                                <a href="index.php?id=<?php echo htmlspecialchars($mobil['id_mobil']); ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-info-circle"></i> Details
                                                </a>
                                               <a href="booking.php?id=<?php echo htmlspecialchars($mobil['id_mobil']); ?>" 
   class="btn btn-sm btn-primary">
    <i class="fas fa-calendar-alt"></i> Book
</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>