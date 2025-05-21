CREATE DATABASE IF NOT EXISTS rental_mobil1;
USE rental_mobil1;

DROP TRIGGER after_rental_insert;
DELIMITER //
CREATE TRIGGER after_rental_insert
AFTER INSERT ON rental
FOR EACH ROW
BEGIN
    INSERT INTO transaksi (
        id_rental,
        tgl_bayar,
        jumlah_bayar,
        status_pembayaran,
        metode_pembayaran
    ) VALUES (
        NEW.id_rental,
        NOW(),
        NEW.total_harga,
        'Pending',
        CASE 
            WHEN NEW.delivery_method = 'antar' THEN 'Transfer Bank + Delivery Fee'
            ELSE 'Transfer Bank'
        END
    );
    
    UPDATE mobil SET STATUS = 'Booked' WHERE id_mobil = NEW.id_mobil;
    
    IF NEW.delivery_method = 'antar' THEN
        UPDATE transaksi 
        SET jumlah_pembayaran = jumlah_pembayaran + 100000 
        WHERE id_rental = NEW.id_rental;
    END IF;
END//
DELIMITER ;

 
DELIMITER //
CREATE TRIGGER before_rental_update
BEFORE UPDATE ON rental
FOR EACH ROW
BEGIN
    DECLARE mobil_status VARCHAR(20);
    DECLARE rental_count INT;
    
    IF NEW.tgl_kembali <= NEW.tgl_rental THEN
        SIGNAL SQLSTATE '45000' 
        SET MESSAGE_TEXT = 'Tanggal kembali harus setelah tanggal rental';
    END IF;
    
    IF OLD.id_mobil != NEW.id_mobil THEN
        SELECT STATUS INTO mobil_status FROM mobil WHERE id_mobil = NEW.id_mobil;
        
        IF mobil_status != 'Tersedia' THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Mobil yang dipilih tidak tersedia';
        END IF;
        
        SELECT COUNT(*) INTO rental_count 
        FROM rental 
        WHERE id_mobil = NEW.id_mobil
          AND (
              (NEW.tgl_rental BETWEEN tgl_rental AND tgl_kembali) OR
              (NEW.tgl_kembali BETWEEN tgl_rental AND tgl_kembali) OR
              (tgl_rental BETWEEN NEW.tgl_rental AND NEW.tgl_kembali)
          )
          AND id_rental != NEW.id_rental;
          
        IF rental_count > 0 THEN
            SIGNAL SQLSTATE '45000' 
            SET MESSAGE_TEXT = 'Mobil sudah dipesan pada tanggal tersebut';
        END IF;
    END IF;
END//
DELIMITER ;


DELIMITER //
CREATE TRIGGER after_rental_update
AFTER UPDATE ON rental
FOR EACH ROW
BEGIN
    IF OLD.id_mobil != NEW.id_mobil THEN
        UPDATE mobil SET STATUS = 'Tersedia' WHERE id_mobil = OLD.id_mobil;
        
        UPDATE mobil SET STATUS = 'Booked' WHERE id_mobil = NEW.id_mobil;
    END IF;
    
    IF NEW.status = 'selesai' AND OLD.status != 'selesai' THEN
        UPDATE mobil SET STATUS = 'Tersedia' WHERE id_mobil = NEW.id_mobil;
        
        UPDATE transaksi 
        SET status_pembayaran = 'Lunas',
            tgl_bayar = NOW()
        WHERE id_rental = NEW.id_rental;
    END IF;
END//
DELIMITER ;


DELIMITER //
CREATE TRIGGER after_rental_delete
AFTER DELETE ON rental
FOR EACH ROW
BEGIN
    UPDATE mobil SET STATUS = 'Tersedia' WHERE id_mobil = OLD.id_mobil;
    
    UPDATE transaksi 
    SET status_pembayaran = 'Dibatalkan',
        tgl_bayar = NOW()
    WHERE id_rental = OLD.id_rental;
END//
DELIMITER ;