<?php

function jalankan_auto_alpha($pdo) {
    
    for ($i = 3; $i >= 1; $i--) {
        
        $tanggal_cek = date('Y-m-d', strtotime("-$i days"));
        $hari_minggu_cek = date('N', strtotime($tanggal_cek)); 

        $sql = "SELECT s.id_siswa, p.hari_kerja 
                FROM siswa s
                JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                WHERE s.id_perusahaan IS NOT NULL";
        
        $stmt = $pdo->query($sql);
        $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($siswa_list as $siswa) {
            $id_siswa = $siswa['id_siswa'];
            
            $hari_kerja_arr = !empty($siswa['hari_kerja']) ? explode(',', $siswa['hari_kerja']) : [1,2,3,4,5];

            if (in_array($hari_minggu_cek, $hari_kerja_arr)) {
                
                $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE id_siswa = :id AND tanggal = :tgl");
                $stmt_cek->execute([':id' => $id_siswa, ':tgl' => $tanggal_cek]);
                $sudah_absen = $stmt_cek->fetchColumn();

                if ($sudah_absen == 0) {
                    try {
                        $sql_insert = "INSERT INTO absensi (id_siswa, tanggal, status, dicatat_oleh, keterangan) 
                                       VALUES (:id, :tgl, 'Alpha', 'Sistem', 'Tidak absen (Otomatis)')";
                        $stmt_insert = $pdo->prepare($sql_insert);
                        $stmt_insert->execute([':id' => $id_siswa, ':tgl' => $tanggal_cek]);
                    } catch (PDOException $e) {
                    }
                }
            }
        }
    }
}
?>