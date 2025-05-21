<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

require 'koneksi/koneksi.php';


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


if (!isset($_SESSION['USER'])) {
    $_SESSION['booking_redirect'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php?login_required=1");
    exit();
}


if (!isset($_SESSION['USER']['iduser']) || empty($_SESSION['USER']['iduser'])) {
    header("Location: login.php");
    exit();
}


$id_mobil = $_GET['id'] ?? null;
if (!$id_mobil || !is_numeric($id_mobil)) {
    header("Location: index.php");
    exit();
}

try {
   
    $stmt = $koneksi->prepare("SELECT * FROM mobil WHERE id_mobil = ? AND status = 'Tersedia'");
    $stmt->execute([$id_mobil]);
    $car = $stmt->fetch();

    if (!$car) {
        $_SESSION['error'] = "Mobil tidak tersedia atau sudah dipesan";
        header("Location: index.php");
        exit();
    }
} catch (PDOException $e) {
    die("Gagal mengambil data mobil: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $tgl_rental = $_POST['tgl_rental'] ?? null;
    $tgl_kembali = $_POST['tgl_kembali'] ?? null;
    $durasi_jam = $_POST['durasi_jam'] ?? 24;
    $delivery_method = $_POST['delivery_method'] ?? 'ambil';
    $total_harga = $_POST['total_harga'] ?? 0;
    $user_id = $_SESSION['USER']['iduser'];

  
    $errors = [];
    if (empty($tgl_rental)) $errors[] = "Tanggal rental harus diisi";
    if (empty($tgl_kembali)) $errors[] = "Tanggal kembali harus diisi";
    if ($tgl_rental && $tgl_kembali && strtotime($tgl_kembali) <= strtotime($tgl_rental)) {
        $errors[] = "Tanggal kembali harus setelah tanggal rental";
    }

    if (empty($errors)) {
        try {
            $koneksi->beginTransaction();

           
            $stmt = $koneksi->prepare("SELECT status FROM mobil WHERE id_mobil = ? FOR UPDATE");
            $stmt->execute([$id_mobil]);
            $status = $stmt->fetchColumn();
            
            if ($status != 'Tersedia') {
                throw new Exception("Mobil sudah tidak tersedia");
            }

          
            $stmt = $koneksi->prepare("
                SELECT COUNT(*) FROM rental 
                WHERE id_mobil = ? 
                AND (
                    (? BETWEEN tgl_rental AND tgl_kembali) OR
                    (? BETWEEN tgl_rental AND tgl_kembali) OR
                    (tgl_rental BETWEEN ? AND ?)
                )
                AND status != 'dibatalkan'
            ");
            $stmt->execute([$id_mobil, $tgl_rental, $tgl_kembali, $tgl_rental, $tgl_kembali]);
            $overlapping = $stmt->fetchColumn();
            
            if ($overlapping > 0) {
                throw new Exception("Mobil sudah dipesan pada tanggal tersebut");
            }
            $final_price = $total_harga;
            if ($delivery_method == 'antar') {
                $final_price += 100000; 
            }

           
            $stmt = $koneksi->prepare("
                INSERT INTO rental 
                (tgl_rental, tgl_kembali, durasi, delivery_method, total_harga, id_mobil, iduser, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            
            $stmt->execute([
                $tgl_rental,
                $tgl_kembali,
                $durasi_jam,
                $delivery_method,
                $final_price,
                $id_mobil,
                $user_id
            ]);
            
            $rental_id = $koneksi->lastInsertId();

     
            $_SESSION['temp_booking'] = [
                'rental_id' => $rental_id,
                'tgl_rental' => $tgl_rental,
                'tgl_kembali' => $tgl_kembali,
                'durasi_jam' => $durasi_jam,
                'delivery_method' => $delivery_method,
                'total_harga' => $final_price,
                'id_mobil' => $id_mobil,
                'car_info' => $car
            ];

            $koneksi->commit();
         
            header("Location: costumer_detail.php?rental_id=" . $rental_id);
            exit();

        } catch (Exception $e) {
            $koneksi->rollBack();
            $error = $e->getMessage();
        } catch (PDOException $e) {
            $koneksi->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Booking - <?= htmlspecialchars($car['merk'] ?? 'Car'); ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/font-awesome.min.css" rel="stylesheet">
    <style>
        .delivery-option { display: none; }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4>Car Booking</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger"><?= $error ?></div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <img src="assets/image/<?= htmlspecialchars($car['gambar'] ?? 'default.png'); ?>" class="img-fluid rounded">
                        </div>
                        <div class="col-md-6">
                            <h5 class="mb-2"><?= htmlspecialchars($car['merk'] ?? 'Car'); ?></h5>
                            <p>License: <?= htmlspecialchars($car['platnomor']); ?></p>
                            <p>Color: <?= htmlspecialchars($car['warna']); ?></p>
                            <p>Year: <?= htmlspecialchars($car['tahun']); ?></p>
                            <p>Price: <strong>Rp <?= number_format($car['harga_rental'], 0, ',', '.'); ?>/day</strong></p>
                        </div>
                    </div>

                    <form method="POST" id="bookingForm">
                        <input type="hidden" name="form_token" value="<?= $_SESSION['form_token'] ?>">
                        <input type="hidden" name="id_mobil" value="<?= htmlspecialchars($id_mobil) ?>">
                        <input type="hidden" name="lama_sewa" id="lamaSewaDays">
                        <input type="hidden" name="total_harga" id="totalHargaValue">
                        <input type="hidden" name="durasi_jam" id="durasiJam">

                        <div class="form-group mb-3">
                            <label>Rental Date</label>
                            <input type="date" name="tgl_rental" class="form-control" required min="<?= date('Y-m-d') ?>">
                        </div>

                        <div class="form-group mb-3">
                            <label>Return Date</label>
                            <input type="date" name="tgl_kembali" class="form-control" required>
                        </div>

                        <div class="form-group mb-3">
                            <label>Rental Duration</label>
                            <input type="text" class="form-control" id="lamaSewa" readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label>Total Price (estimate)</label>
                            <input type="text" id="totalHarga" class="form-control" readonly>
                        </div>

                        <div class="form-group mb-3">
                            <label>Delivery Method</label>
                            <select name="delivery_method" class="form-control" id="deliveryMethod">
                                <option value="ambil">Pick Up</option>
                                <option value="antar">Delivery</option>
                            </select>
                        </div>

                        <div class="form-group mb-3 delivery-option" id="jemputGroup" style="display: none;">
                            <label>Pickup Time</label>
                            <input type="datetime-local" name="waktu_jemput" class="form-control">
                        </div>

                        <div class="form-group mb-3 delivery-option" id="lokasiGroup" style="display: none;">
                            <label>Delivery Address</label>
                            <input type="text" name="lokasi_antar" class="form-control" placeholder="Full address">
                        </div>

                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fa fa-calendar-check"></i> Continue to Customer Details
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
<script>
    const hargaRental = <?= $car['harga_rental'] ?>;
    const deliveryFee = 100000;


    function hitungSewa() {
        const tglRental = document.querySelector('input[name="tgl_rental"]');
        const tglKembali = document.querySelector('input[name="tgl_kembali"]');
        const deliveryMethod = document.getElementById('deliveryMethod').value;
        
       
        if (tglRental.value && tglKembali.value) {
            const startDate = new Date(tglRental.value);
            const endDate = new Date(tglKembali.value);
            
            if (endDate > startDate) {
                const diffTime = endDate - startDate;
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const diffHours = Math.ceil(diffTime / (1000 * 60 * 60));
                
                document.getElementById('lamaSewa').value = diffDays + " hari";
                document.getElementById('lamaSewaDays').value = diffDays;
                document.getElementById('durasiJam').value = diffHours;
           
                let totalHarga = diffDays * hargaRental;
                if (deliveryMethod === 'antar') {
                    totalHarga += deliveryFee;
                }
                
                document.getElementById('totalHarga').value = "Rp " + totalHarga.toLocaleString("id-ID");
                document.getElementById('totalHargaValue').value = totalHarga;
            } else {
           
                resetPerhitungan();
            }
        } else {
        
            resetPerhitungan();
        }
    }

   
    function resetPerhitungan() {
        document.getElementById('lamaSewa').value = "";
        document.getElementById('lamaSewaDays').value = "";
        document.getElementById('durasiJam').value = "";
        document.getElementById('totalHarga').value = "";
        document.getElementById('totalHargaValue').value = "";
    }

   
    document.querySelector('input[name="tgl_rental"]').addEventListener('change', function() {
      
        if (this.value) {
            const minDate = new Date(this.value);
            minDate.setDate(minDate.getDate() + 1);
            document.querySelector('input[name="tgl_kembali"]').min = minDate.toISOString().split('T')[0];
            
           
            const tglKembali = document.querySelector('input[name="tgl_kembali"]');
            if (!tglKembali.value || new Date(tglKembali.value) < minDate) {
                tglKembali.value = minDate.toISOString().split('T')[0];
            }
        }
        hitungSewa();
    });

    document.querySelector('input[name="tgl_kembali"]').addEventListener('change', hitungSewa);

 
    document.getElementById('deliveryMethod').addEventListener('change', function() {
        hitungSewa();
        
        const showOptions = this.value === 'antar';
        document.getElementById('jemputGroup').style.display = showOptions ? 'block' : 'none';
        document.getElementById('lokasiGroup').style.display = showOptions ? 'block' : 'none';
        
        if (showOptions) {
            const tglRental = document.querySelector('input[name="tgl_rental"]').value;
            const waktuJemputInput = document.querySelector('input[name="waktu_jemput"]');
            
            if (tglRental && !waktuJemputInput.value) {
                waktuJemputInput.value = tglRental + 'T08:00';
            }
        }
    });

   
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelector('input[name="tgl_rental"]').value && 
            document.querySelector('input[name="tgl_kembali"]').value) {
            hitungSewa();
        }
    });
</script>
</body>
</html>