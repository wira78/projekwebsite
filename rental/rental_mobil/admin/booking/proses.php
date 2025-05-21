<?php


session_start();
require '../../koneksi/koneksi.php';

if(empty($_SESSION['USER'])) {
    header("Location: ../../login.php");
    exit();
}

$action = $_POST['action'] ?? $_GET['aksi'] ?? '';
if(empty($action)) {
    $_SESSION['error'] = "Aksi tidak valid";
    header("Location: peminjaman.php");
    exit();
}


            $check_transaksi = $koneksi->prepare("SELECT idtransaksi FROM transaksi WHERE id_rental = ?");
            $check_transaksi->execute([$id_rental]);
            
            if($check_transaksi->rowCount() > 0) {
                $sql = "UPDATE transaksi SET 
                        status_pembayaran = ?,
                        metode_pembayaran = ?,
                        tgl_bayar = NOW()
                        WHERE id_rental = ?";
                
                $stmt = $koneksi->prepare($sql);
                $stmt->execute([
                    $status_pembayaran,
                    $metode_pembayaran,
                    $bukti_bayar,
                    $id_rental
                ]);
            } else {
                $sql = "INSERT INTO transaksi (
                        idtransaksi, 
                        id_rental, 
                        status_pembayaran, 
                        metode_pembayaran, 
                        bukti_bayar, 
                        jumlah_bayar,
                        tgl_bayar
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                
                $transaksi_id = "TRX".date('Ymd').str_pad($id_rental, 5, '0', STR_PAD_LEFT);
                
                $get_total = $koneksi->prepare("SELECT total_harga FROM rental WHERE id_rental = ?");
                $get_total->execute([$id_rental]);
                $total_harga = $get_total->fetchColumn();
                
                $stmt = $koneksi->prepare($sql);
                $stmt->execute([
                    $transaksi_id,
                    $id_rental,
                    $status_pembayaran,
                    $metode_pembayaran,
                    $bukti_bayar,
                    $total_harga
                ]);
            }
            
            $_SESSION['success'] = "Status pembayaran berhasil diperbarui";
            break;
            
        default:
            throw new Exception("Aksi tidak dikenali");
    
   
    $_SESSION['error'] = "Database error: ".$e->getMessage();
 catch(Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: bayar.php?id=".$id_rental);
exit();
?>