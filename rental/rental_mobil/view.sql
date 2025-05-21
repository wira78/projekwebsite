CREATE DATABASE rental_mobil1;
USE rental_mobil1;

CREATE TABLE user_pelanggan(
	iduser INT NOT NULL AUTO_INCREMENT,
	nama VARCHAR(45) NOT NULL,
	alamat TEXT NOT NULL,
	jenis_kelamin ENUM('P', 'L')NOT NULL,
	no_hp VARCHAR(45)NOT NULL,
	no_ktp VARCHAR(45)NOT NULL,
	email VARCHAR(45) NOT NULL,
	status_pelanggan ENUM('aktif', 'tidak aktif') NOT NULL,
	PRIMARY KEY(iduser)
);
CREATE TABLE mobil (
    id_mobil INT PRIMARY KEY AUTO_INCREMENT,
    platnomor VARCHAR(20) UNIQUE NOT NULL,
    merk VARCHAR(50) NOT NULL,
    warna VARCHAR(20),
    tahun INT,
    harga_rental DECIMAL(12,2) NOT NULL,
    gambar VARCHAR(255),
    STATUS ENUM('Tersedia', 'Tidak Tersedia') DEFAULT 'Tersedia'
);
CREATE TABLE informasi_rental(
    id_info INT NOT NULL AUTO_INCREMENT,
    nama_rental VARCHAR(100) NOT NULL,
    alamat_rental TEXT NOT NULL,
    no_telp VARCHAR(20) NOT NULL,
    email VARCHAR(50) NOT NULL,
    PRIMARY KEY(id_info)
);

INSERT INTO informasi_rental (nama_rental, alamat_rental, no_telp, email) VALUES
('Sewa Mobil Bandung', 'Jl. Soekarno Hatta No. 123, Bandung', '0221234567', 'info@sewamobilbandung.com');

CREATE TABLE rental(
	id_rental INT AUTO_INCREMENT NOT NULL,
	tgl_rental DATE NOT NULL,
	tgl_kembali DATE NOT NULL,
	tgl_pengembalian DATE NOT NULL,
	id_mobil INT NOT NULL,
	iduser INT NOT NULL,
	PRIMARY KEY(id_rental),
	FOREIGN KEY(id_mobil) REFERENCES mobil (id_mobil),
	FOREIGN KEY(iduser) REFERENCES user_pelanggan (iduser)
);

CREATE TABLE pegawai(
	idpegawai INT NOT NULL AUTO_INCREMENT,
	nama VARCHAR(45) NOT NULL,
	jabatan VARCHAR(45) NOT NULL,
	no_hp VARCHAR(45) NOT NULL,
	atasan_id INT NOT NULL,
	PRIMARY KEY(idpegawai)
);

CREATE TABLE transaksi(
	idtransaksi INT NOT NULL,
	tgl_bayar DATE NOT NULL, 
	jumlah_bayar FLOAT NOT NULL,
	metode_pembayaran VARCHAR(45) NOT NULL,
	status_pembayaran VARCHAR(45) NOT NULL,
	idpegawai INT NOT NULL,
	id_rental INT NOT NULL,
	PRIMARY KEY(idtransaksi),
	FOREIGN KEY(idpegawai) REFERENCES pegawai (idpegawai),
	FOREIGN KEY(id_rental) REFERENCES rental (id_rental)
);

CREATE TABLE denda (
    iddenda INT NOT NULL AUTO_INCREMENT,
    id_rental INT NOT NULL,
    tgl_denda DATE NOT NULL,
    jumlah_denda FLOAT NOT NULL,
    keterangan VARCHAR(100) NOT NULL,
    PRIMARY KEY (iddenda),
    FOREIGN KEY (id_rental) REFERENCES rental(id_rental)
);
CREATE TABLE login (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    PASSWORD VARCHAR(255) NOT NULL,
    ROLE ENUM('admin','customer') NOT NULL DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP DATABASE rental_mobil1;

INSERT INTO mobil (platnomor, merk, warna, tahun, harga_rental, gambar, STATUS) VALUES
('D 1234 ABC', 'Toyota Avanza', 'Putih', 2023, 350000.00, 'avanza.jpg', 'Tersedia'),
('D 2345 BCD', 'Honda Brio', 'Merah', 2022, 300000.00, 'brio.jpg', 'Tersedia'),
('D 3456 CDE', 'Daihatsu Xenia', 'Silver', 2021, 325000.00, 'xenia.jpg', 'Tersedia'),
('D 4567 DEF', 'Toyota Innova', 'Hitam', 2022, 500000.00, 'innova.jpg', 'Tersedia'),
('D 5678 EFG', 'Mitsubishi Xpander', 'Putih', 2023, 450000.00, 'xpander.jpg', 'Tersedia'),
('D 6789 FGH', 'Honda HR-V', 'Abu-abu', 2022, 475000.00, 'hrv.jpg', 'Tersedia'),
('D 7890 GHI', 'Suzuki Ertiga', 'Merah', 2021, 350000.00, 'ertiga.jpg', 'Tersedia'),
('D 8901 HIJ', 'Toyota Rush', 'Hitam', 2023, 400000.00, 'rush.jpg', 'Tersedia'),
('D 9012 IJK', 'Daihatsu Terios', 'Silver', 2022, 400000.00, 'terios.jpg', 'Tersedia'),
('D 0123 JKL', 'Honda Mobilio', 'Putih', 2021, 325000.00, 'mobilio.jpg', 'Tersedia'),
('D 1234 KLM', 'Toyota Calya', 'Biru', 2022, 300000.00, 'calya.jpg', 'Tersedia'),
('D 2345 LMN', 'Suzuki XL7', 'Putih', 2023, 425000.00, 'xl7.jpg', 'Tersedia'),
('D 3456 MNO', 'Mitsubishi Pajero Sport', 'Hitam', 2022, 850000.00, 'pajero.jpg', 'Tersedia'),
('D 4567 NOP', 'Toyota Fortuner', 'Putih', 2023, 900000.00, 'fortuner.jpg', 'Tersedia'),
('D 5678 OPQ', 'Honda CR-V', 'Silver', 2022, 750000.00, 'crv.jpg', 'Tersedia');

-- Add sample data for user_pelanggan
INSERT INTO user_pelanggan (nama, alamat, jenis_kelamin, no_hp, no_ktp, email, status_pelanggan) VALUES
('Budi Santoso', 'Jl. Merdeka No. 10, Bandung', 'L', '081234567890', '3273081209800001', 'budi@example.com', 'aktif'),
('Siti Rahayu', 'Jl. Asia Afrika No. 15, Bandung', 'P', '082345678901', '3273154304900002', 'siti@example.com', 'aktif'),
('Rudi Hermawan', 'Jl. Pasirkaliki No. 25, Bandung', 'L', '083456789012', '3273082505850003', 'rudi@example.com', 'aktif'),
('Dewi Safitri', 'Jl. Dipatiukur No. 30, Bandung', 'P', '084567890123', '3273157606920004', 'dewi@example.com', 'aktif'),
('Andi Wijaya', 'Jl. Setiabudi No. 45, Bandung', 'L', '085678901234', '3273081707880005', 'andi@example.com', 'aktif');

-- Add sample data for pegawai
INSERT INTO pegawai (nama, jabatan, no_hp, atasan_id) VALUES
('Ahmad Faisal', 'Manager', '081122334455', 0),
('Ratna Sari', 'Supervisor', '082233445566', 1),
('Deni Prasetyo', 'Admin', '083344556677', 2),
('Indah Permata', 'Customer Service', '084455667788', 2),
('Bayu Nugroho', 'Driver', '085566778899', 2);

-- Add sample data for login
INSERT INTO login (username, email, PASSWORD, ROLE) VALUES
('admin', 'admin@rentalmobil.com', '$2y$10$abcdefghijklmnopqrstuv', 'admin'),
('budi', 'budi@example.com', '$2y$10$vwxyzabcdefghijklmnopqr', 'customer'),
('siti', 'siti@example.com', '$2y$10$stuvwxyzabcdefghijklmno', 'customer'),
('rudi', 'rudi@example.com', '$2y$10$nopqrstuvwxyzabcdefghij', 'customer'),
('dewi', 'dewi@example.com', '$2y$10$klmnopqrstuvwxyzabcdefg', 'customer'),
('andi', 'andi@example.com', '$2y$10$ghijklmnopqrstuvwxyzabc', 'customer');

DROP TABLE login;
-- Untuk tabel rental
ALTER TABLE rental
MODIFY tgl_rental DATETIME NOT NULL,
MODIFY tgl_kembali DATETIME NOT NULL,
MODIFY tgl_pengembalian DATETIME,
ADD COLUMN durasi INT NOT NULL COMMENT 'Dalam jam' AFTER tgl_pengembalian,
ADD COLUMN delivery_method ENUM('ambil','antar') NOT NULL AFTER durasi,
ADD COLUMN waktu_jemput TIME AFTER delivery_method,
ADD COLUMN total_harga DECIMAL(12,2) NOT NULL AFTER waktu_jemput,
ADD COLUMN lokasi_antar VARCHAR(255) AFTER waktu_jemput;

-- Untuk tabel transaksi
ALTER TABLE transaksi
MODIFY tgl_bayar DATETIME NOT NULL,
MODIFY jumlah_bayar DECIMAL(12,2) NOT NULL,
MODIFY metode_pembayaran ENUM('Transfer','Tunai','Kredit') NOT NULL,
MODIFY status_pembayaran ENUM('Lunas','Pending','Gagal') NOT NULL,
ADD COLUMN bukti_bayar VARCHAR(255) AFTER status_pembayaran,
MODIFY idtransaksi INT AUTO_INCREMENT;

-- Untuk tabel denda
ALTER TABLE denda
MODIFY jumlah_denda DECIMAL(12,2) NOT NULL,
MODIFY keterangan TEXT,
ADD COLUMN STATUS ENUM('Belum Lunas','Lunas') DEFAULT 'Belum Lunas' AFTER keterangan;

-- Untuk tabel mobil
ALTER TABLE mobil
ADD COLUMN km_terakhir INT AFTER STATUS,
ADD COLUMN terakhir_servis DATE AFTER km_terakhir;

-- Untuk tabel pegawai
ALTER TABLE pegawai
ADD COLUMN email VARCHAR(45) NOT NULL UNIQUE AFTER NO _hp,
ADD COLUMN user_level ENUM('Admin','Supervisor','Kasir') NOT NULL AFTER email;

-- Untuk tabel login
ALTER TABLE login
ADD COLUMN terakhir_login DATETIME AFTER ROLE,
ADD COLUMN id_terkait INT COMMENT 'ID user atau pegawai terkait' AFTER terakhir_login;

DROP TABLE login;
DROP TABLE user_pelanggan;

-- 1. First disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- 2. Now perform your ALTER TABLE statement
ALTER TABLE user_pelanggan
ADD COLUMN username VARCHAR(50) NOT NULL UNIQUE AFTER iduser,
ADD COLUMN PASSWORD VARCHAR(255) NOT NULL AFTER username,
ADD COLUMN ROLE ENUM('admin', 'customer') NOT NULL DEFAULT 'customer' AFTER PASSWORD,
MODIFY COLUMN nama VARCHAR(100) NOT NULL,
MODIFY COLUMN no_hp VARCHAR(20) NOT NULL,
MODIFY COLUMN no_ktp VARCHAR(16) NOT NULL UNIQUE,
MODIFY COLUMN email VARCHAR(100) NOT NULL UNIQUE,
MODIFY COLUMN status_pelanggan ENUM('aktif', 'tidak aktif') NOT NULL DEFAULT 'aktif';
DROP TABLE user_pelanggan;
ALTER TABLE user_pelanggan MODIFY COLUMN nama VARCHAR(255) NULL;
ALTER TABLE user_pelanggan MODIFY COLUMN alamat VARCHAR(255) NULL;
ALTER TABLE user_pelanggan MODIFY COLUMN no_ktp VARCHAR(50);

-- Hapus tabel jika sudah ada (hati-hati dengan data yang ada)
-- DROP TABLE IF EXISTS user_pelanggan;

-- Buat tabel baru dengan struktur yang benar
CREATE TABLE IF NOT EXISTS user_pelanggan (
    iduser INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    PASSWORD VARCHAR(255) NOT NULL,
    ROLE ENUM('admin', 'customer') NOT NULL DEFAULT 'customer',
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    no_hp VARCHAR(20) NOT NULL,
    alamat VARCHAR(255),
    no_ktp VARCHAR(50) UNIQUE,
    jenis_kelamin ENUM('L', 'P'),
    status_pelanggan ENUM('aktif', 'tidak aktif') NOT NULL DEFAULT 'aktif'
);

ALTER TABLE transaksi MODIFY COLUMN tgl_bayar DATETIME NULL;

ALTER TABLE transaksi MODIFY COLUMN metode_pembayaran ENUM('Transfer','Tunai','Kredit','Debit','Digital Wallet');
-- 3. Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;
DROP DATABASE rental_mobil1;

DELIMITER //
CREATE TRIGGER before_user_pelanggan_insert
BEFORE INSERT ON user_pelanggan
FOR EACH ROW
BEGIN
    -- Set default values for required fields
    IF NEW.no_ktp IS NULL THEN
        SET NEW.no_ktp = CONCAT('TEMP_', UUID_SHORT());
    END IF;
    
    IF NEW.nama IS NULL THEN
        SET NEW.nama = NEW.username;
    END IF;
    
    IF NEW.no_hp IS NULL THEN
        SET NEW.no_hp = '0000000000';
    END IF;
    
    IF NEW.jenis_kelamin IS NULL THEN
        SET NEW.jenis_kelamin = 'L';
    END IF;
    
    IF NEW.status_pelanggan IS NULL THEN
        SET NEW.status_pelanggan = 'aktif';
    END IF;
    
    IF NEW.role IS NULL THEN
        SET NEW.role = 'customer';
    END IF;
END//
DELIMITER ;
DROP VIEW view_transaksi_pelanggan;
-- View for complete transaction information
CREATE OR REPLACE VIEW view_transaksi_pelanggan AS
SELECT 
    t.idtransaksi,
    t.tgl_bayar,
    t.jumlah_bayar,
    t.metode_pembayaran,
    t.status_pembayaran,
    m.id_mobil,
    m.merk,
    m.gambar,
    m.platnomor,
    r.tgl_rental,
    r.tgl_kembali,
    u.iduser,  -- Added this column
    u.nama AS nama_pelanggan,
    p.nama AS nama_pegawai,
    DATEDIFF(r.tgl_kembali, r.tgl_rental) AS lama_sewa,
    (SELECT COALESCE(SUM(d.jumlah_denda), 0) 
     FROM denda d 
     WHERE d.id_rental = r.id_rental) AS total_denda,
    CONCAT('TRX-', LPAD(t.idtransaksi, 6, '0')) AS kode_booking
FROM transaksi t
JOIN rental r ON t.id_rental = r.id_rental
JOIN mobil m ON r.id_mobil = m.id_mobil
JOIN user_pelanggan u ON r.iduser = u.iduser
JOIN pegawai p ON t.idpegawai = p.idpegawai;

-- View for active rentals
CREATE VIEW view_rental_aktif AS
SELECT 
    r.id_rental,
    r.tgl_rental,
    r.tgl_kembali,
    m.merk,
    m.platnomor,
    u.nama AS nama_pelanggan,
    u.no_hp,
    DATEDIFF(r.tgl_kembali, r.tgl_rental) AS lama_sewa,
    (m.harga_rental * DATEDIFF(r.tgl_kembali, r.tgl_rental)) AS total_harga
FROM rental r
JOIN mobil m ON r.id_mobil = m.id_mobil
JOIN user_pelanggan u ON r.iduser = u.iduser
WHERE r.tgl_kembali >= CURDATE();

DROP PROCEDURE sp_insert_rental;
DELIMITER //
CREATE OR REPLACE PROCEDURE sp_insert_rental(
    IN p_tgl_rental DATE,
    IN p_tgl_kembali DATE,
    IN p_durasi INT,
    IN p_delivery_method ENUM('ambil','antar'),
    IN p_waktu_jemput TIME,
    IN p_total_harga DECIMAL(12,2),
    IN p_id_mobil INT,
    IN p_iduser INT,
    OUT p_id_rental INT
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Insert data rental dengan semua kolom yang diperlukan
    INSERT INTO rental (
        tgl_rental, 
        tgl_kembali, 
        durasi, 
        delivery_method,
        waktu_jemput, 
        total_harga, 
        id_mobil,
        iduser,
        STATUS
    ) VALUES (
        p_tgl_rental, 
        p_tgl_kembali, 
        p_durasi, 
        p_delivery_method,
        p_waktu_jemput, 
        p_total_harga, 
        p_id_mobil,
        p_iduser,
        'pending'
    );
    
    SET p_id_rental = LAST_INSERT_ID();
    
    -- Update status mobil menjadi tidak tersedia
    UPDATE mobil SET STATUS = 'Tidak Tersedia' WHERE id_mobil = p_id_mobil;
    
    COMMIT;
END //
DELIMITER ;

INSERT INTO user_pelanggan 
(username, PASSWORD, ROLE, nama, no_hp, no_ktp, email, status_pelanggan)
VALUES 
('admin', 'admin123', 
 'admin', 'Administrator', '081234567890', '1234567890123456', 
 'admin@rental.com', 'aktif');
 
 UPDATE user_pelanggan SET ROLE = 'admin' WHERE email = 'admin@email.com';
 
 
 -- Add status column to rental table if not exists
ALTER TABLE rental 
ADD COLUMN STATUS ENUM('pending', 'diproses', 'selesai', 'batal') DEFAULT 'pending';

-- Add payment confirmation status
ALTER TABLE rental
ADD COLUMN konfirmasi_pembayaran ENUM('belum', 'lunas', 'sebagian') DEFAULT 'belum';

-- Create view for booking information
CREATE OR REPLACE VIEW view_booking_info AS
SELECT 
    r.id_rental,
    r.tgl_rental,
    r.tgl_kembali,
    r.durasi,
    r.total_harga,
    r.status,
    r.konfirmasi_pembayaran,
    m.merk,
    m.platnomor,
    m.gambar,
    u.iduser,
    u.nama AS nama_pelanggan,
    u.no_hp,
    u.email,
    CONCAT('BOOK-', LPAD(r.id_rental, 6, '0')) AS kode_booking
FROM rental r
JOIN mobil m ON r.id_mobil = m.id_mobil
JOIN user_pelanggan u ON r.iduser = u.iduser;


-- Pertama, hapus foreign key constraint jika ada
ALTER TABLE rental DROP FOREIGN KEY rental_ibfk_1;

-- Ubah tipe data kolom id_mobil menjadi VARCHAR untuk menyimpan plat nomor
ALTER TABLE rental MODIFY id_mobil VARCHAR(20) NOT NULL;

-- Tambahkan kembali foreign key yang merujuk ke platnomor di tabel mobil
ALTER TABLE rental 
ADD CONSTRAINT fk_rental_mobil 
FOREIGN KEY (id_mobil) REFERENCES mobil(platnomor);

-- Change waktu_jemput from TIME to DATETIME
ALTER TABLE rental 
MODIFY COLUMN waktu_jemput DATETIME NULL;