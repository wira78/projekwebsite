<?php

session_start();
require 'koneksi/koneksi.php';
include 'header.php';

$info_rental = $koneksi->query("SELECT * FROM informasi_rental LIMIT 1")->fetch(PDO::FETCH_ASSOC);

if(!$info_rental) {
    $info_rental = [
        'nama_rental' => 'Nama Rental Default',
        'no_telp' => '000-0000-0000',
        'alamat_rental' => 'Alamat belum diisi',
        'email' => 'email@example.com',
        'no_rek' => 'Belum diset'
    ];
}
?>
<br>
<br>
<div class="container">
    <div class="row">
        <div class="col-sm-6 mx-auto">
            <div class="card">
                <div class="card-header">
                    Kontak Kami
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-4">Nama Rental</div>
                        <div class="col-sm-8"><?= htmlspecialchars($info_rental['nama_rental']); ?></div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-sm-4">Telp</div>
                        <div class="col-sm-8"><?= htmlspecialchars($info_rental['no_telp']); ?></div>
                    </div>
                
                    <div class="row mt-3">
                        <div class="col-sm-4">Alamat</div>
                        <div class="col-sm-8"><?= htmlspecialchars($info_rental['alamat_rental']); ?></div>
                    </div>
                
                    <div class="row mt-3">
                        <div class="col-sm-4">Email</div>
                        <div class="col-sm-8"><?= htmlspecialchars($info_rental['email']); ?></div>
                    </div>
                
                    <div class="row mt-3">
                        <div class="col-sm-4">No Rekening</div>
                        <div class="col-sm-8"><?= isset($info_rental['no_rek']) ? htmlspecialchars($info_rental['no_rek']) : 'Belum diset'; ?></div>
                    </div>
                </div>
            </div> 
        </div>
    </div>
</div>
<br>
<br>
<br>
<br>
<?php include 'footer.php';?>