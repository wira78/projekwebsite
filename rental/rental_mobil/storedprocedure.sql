DELIMITER //
CREATE PROCEDURE ProcessRental(
    IN p_action VARCHAR(20),
    IN p_id_mobil INT,
    IN p_id_user INT,
    IN p_durasi INT,
    IN p_id_rental INT
)
BEGIN
    DECLARE v_tgl_kembali DATE;
    DECLARE v_denda DECIMAL(10,2);
    DECLARE v_hari_terlambat INT;
    
    IF p_action = 'rent' THEN
        -- Process new rental
        SET v_tgl_kembali = DATE_ADD(CURDATE(), INTERVAL p_durasi DAY);
        
        INSERT INTO rental (tgl_rental, tgl_kembali, tgl_pengembalian, id_mobil, iduser, STATUS) 
        VALUES (CURDATE(), v_tgl_kembali, NULL, p_id_mobil, p_id_user, 'pending');
        
        UPDATE mobil SET STATUS = 'Tidak Tersedia' WHERE id_mobil = p_id_mobil;
        
    ELSEIF p_action = 'return' THEN
        -- Process return with penalty calculation
        SELECT tgl_kembali INTO v_tgl_kembali FROM rental WHERE id_rental = p_id_rental;
        
        SET v_hari_terlambat = DATEDIFF(CURDATE(), v_tgl_kembali);
        SET v_denda = IF(v_hari_terlambat > 0, v_hari_terlambat * 50000, 0);
        
        UPDATE rental 
        SET tgl_pengembalian = CURDATE(), 
            STATUS = 'completed'
        WHERE id_rental = p_id_rental;
        
        UPDATE mobil SET STATUS = 'Tersedia' 
        WHERE id_mobil = (SELECT id_mobil FROM rental WHERE id_rental = p_id_rental);
        
        IF v_denda > 0 THEN
            INSERT INTO denda (id_rental, tgl_denda, jumlah_denda, keterangan)
            VALUES (p_id_rental, CURDATE(), v_denda, 
                   CONCAT('Terlambat ', v_hari_terlambat, ' hari'));
        END IF;
        
        SELECT v_denda AS denda;
    END IF;
END //
DELIMITER ;


DELIMITER //
CREATE PROCEDURE sp_update_website_info(
    IN p_nama_rental VARCHAR(100),
    IN p_telp VARCHAR(15),
    IN p_alamat TEXT,
    IN p_email VARCHAR(50),
    IN p_no_rek VARCHAR(50),
    OUT p_result VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 @sqlstate = RETURNED_SQLSTATE, 
        @errno = MYSQL_ERRNO, @text = MESSAGE_TEXT;
        SET p_result = CONCAT('Error: ', @errno, ' (', @sqlstate, '): ', @text);
    END;
    
    UPDATE informasi_rental 
    SET 
        nama_rental = p_nama_rental,
        no_telp = p_telp,
        alamat_rental = p_alamat,
        email = p_email,
        no_rek = p_no_rek
    WHERE id_info = 1;
    
    SET p_result = 'Update successful';
END //
DELIMITER ;

DELIMITER //
CREATE PROCEDURE sp_get_dashboard_stats(
    OUT p_available_cars INT,
    OUT p_total_customers INT,
    OUT p_today_rentals INT
)
BEGIN
    SELECT available_count INTO p_available_cars FROM vw_available_cars;
    SELECT customer_count INTO p_total_customers FROM vw_total_customers;
    SELECT today_count INTO p_today_rentals FROM vw_today_rentals;
END //
DELIMITER ;

DELIMITER //

CREATE PROCEDURE `sp_get_rental_details`(
    IN p_rental_id INT,
    IN p_user_id INT
)
BEGIN
    SELECT 
        r.*, 
        m.merk, 
        m.platnomor, 
        m.gambar
    FROM 
        rental r
    JOIN 
        mobil m ON r.id_mobil = m.id_mobil
    WHERE 
        r.id_rental = p_rental_id 
        AND r.iduser = p_user_id 
        AND r.status = 'pending';
END //

DELIMITER ;

DELIMITER //

CREATE PROCEDURE `sp_update_user_details`(
    IN p_user_id INT,
    IN p_nama VARCHAR(100),
    IN p_no_hp VARCHAR(15),
    IN p_alamat TEXT,
    IN p_no_ktp VARCHAR(20),
    OUT p_result INT,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET p_result = 0;
        SET p_message = 'Error updating user details';
    END;
    
    UPDATE user_pelanggan SET 
        nama = p_nama,
        no_hp = p_no_hp,
        alamat = p_alamat,
        no_ktp = p_no_ktp
    WHERE iduser = p_user_id;
    
    SET p_result = 1;
    SET p_message = 'User details updated successfully';
END //

DELIMITER ;

DELIMITER //

CREATE PROCEDURE `sp_get_user_details`(
    IN p_user_id INT
)
BEGIN
    SELECT * FROM user_pelanggan WHERE iduser = p_user_id;
END //

DELIMITER ;

DELIMITER $$

CREATE PROCEDURE sp_tampil_pelanggan_aktif()
BEGIN
    SELECT 
        iduser,
        nama,
        alamat,
        jenis_kelamin,
        no_hp,
        no_ktp,
        email,
        status_pelanggan
    FROM 
        user_pelanggan
    WHERE 
        status_pelanggan = 'aktif';
END $$

DELIMITER ;


DELIMITER //
CREATE PROCEDURE `sp_get_users`(
    IN p_search VARCHAR(100),
    IN p_status VARCHAR(20),
    IN p_per_page INT,
    IN p_offset INT,
    OUT p_total_rows INT,
    OUT p_result_code INT,
    OUT p_result_message VARCHAR(255)
)
BEGIN
    DECLARE v_search_pattern VARCHAR(102);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        GET DIAGNOSTICS CONDITION 1 
        @sqlstate = RETURNED_SQLSTATE, 
        @errno = MYSQL_ERRNO, 
        @text = MESSAGE_TEXT;
        SET p_result_code = @errno;
        SET p_result_message = CONCAT('Error: ', @errno, ' (', @sqlstate, '): ', @text);
    END;
    
    -- Prepare search pattern
    IF p_search IS NULL OR p_search = '' THEN
        SET v_search_pattern = '%';
    ELSE
        SET v_search_pattern = CONCAT('%', p_search, '%');
    END IF;
    
    -- Set default status filter
    IF p_status IS NULL OR p_status = '' THEN
        SET p_status = '%';
    END IF;
    
    -- Get total rows count
    SELECT COUNT(*) INTO p_total_rows 
    FROM user_pelanggan 
    WHERE (nama LIKE v_search_pattern OR email LIKE v_search_pattern OR no_hp LIKE v_search_pattern)
    AND status_pelanggan LIKE p_status;
    
    -- Get paginated results
    SELECT 
        up.iduser,
        up.nama,
        up.email,
        up.no_hp,
        up.no_ktp,
        up.jenis_kelamin,
        up.status_pelanggan,
        (SELECT COUNT(*) FROM rental r WHERE r.iduser = up.iduser) AS total_rental
    FROM user_pelanggan up
    WHERE (up.nama LIKE v_search_pattern OR up.email LIKE v_search_pattern OR up.no_hp LIKE v_search_pattern)
    AND up.status_pelanggan LIKE p_status
    ORDER BY up.iduser DESC 
    LIMIT p_per_page OFFSET p_offset;
    
    SET p_result_code = 0;
    SET p_result_message = 'Success';
END //
DELIMITER ;

	DELIMITER //

	CREATE PROCEDURE sp_update_status_rental_otomatis()
	BEGIN
	    DECLARE done INT DEFAULT FALSE;
	    DECLARE rental_id INT;
	    DECLARE tgl_kembali DATE;
	    DECLARE cur_status VARCHAR(20);
	    
	    -- Deklarasi cursor untuk mengambil data rental yang perlu diperbarui
	    DECLARE rental_cursor CURSOR FOR 
		SELECT id_rental, tgl_kembali, STATUS 
		FROM rental 
		WHERE STATUS IN ('Diproses', 'Disewa') 
		AND tgl_kembali < CURDATE();
	    
	    -- Handler untuk ketika tidak ada data lagi
	    DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;
	    
	    -- Buka cursor
	    OPEN rental_cursor;
	    
	    -- Mulai loop
	    rental_loop: LOOP
		-- Ambil data dari cursor
		FETCH rental_cursor INTO rental_id, tgl_kembali, cur_status;
		
		-- Keluar dari loop jika tidak ada data lagi
		IF done THEN
		    LEAVE rental_loop;
		END IF;
		
		-- Update status menjadi 'Selesai' jika sudah lewat tanggal kembali
		IF tgl_kembali < CURDATE() THEN
		    UPDATE rental SET STATUS = 'Selesai' WHERE id_rental = rental_id;
		    
		    -- Update status mobil menjadi 'Tersedia'
		    UPDATE mobil m
		    JOIN rental r ON m.id_mobil = r.id_mobil
		    SET m.status = 'Tersedia'
		    WHERE r.id_rental = rental_id;
		END IF;
	    END LOOP;
	    
	    -- Tutup cursor
	    CLOSE rental_cursor;
	    
	    SELECT CONCAT('Proses update selesai. ', ROW_COUNT(), ' data diperbarui.') AS hasil;
	END //

	DELIMITER ;