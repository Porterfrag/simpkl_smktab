<?php

function kirim_wa($target, $pesan) {
    $token = "vm4YMHtKQTvRmsdXHZJy"; 

    $curl = curl_init();

    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.fonnte.com/send',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
      CURLOPT_CUSTOMREQUEST => 'POST',
      CURLOPT_POSTFIELDS => array(
        'target' => $target,
        'message' => $pesan,
      ),
      CURLOPT_HTTPHEADER => array(
        "Authorization: $token"
      ),
    ));

    $response = curl_exec($curl);
    curl_close($curl);
}

function jalankan_auto_alpha($pdo) {
    
    $laporan_guru = [];

    for ($i = 3; $i >= 1; $i--) {
        
        $tanggal_cek = date('Y-m-d', strtotime("-$i days"));
        $hari_minggu_cek = date('N', strtotime($tanggal_cek)); 

        $sql = "SELECT s.id_siswa, s.nama_lengkap, p.hari_kerja, g.id_pembimbing, g.no_telp 
                FROM siswa s
                JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
                LEFT JOIN pembimbing g ON s.id_pembimbing = g.id_pembimbing
                WHERE s.id_perusahaan IS NOT NULL AND s.id_pembimbing IS NOT NULL";
        
        $stmt = $pdo->query($sql);
        $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($siswa_list as $siswa) {
            $id_siswa = $siswa['id_siswa'];
            $id_pembimbing = $siswa['id_pembimbing'];
            $no_telp_guru = $siswa['no_telp'];
            
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
                        
                        if (!empty($no_telp_guru)) {
                            if (!isset($laporan_guru[$id_pembimbing])) {
                                $laporan_guru[$id_pembimbing] = [
                                    'no_hp' => $no_telp_guru,
                                    'tanggal' => $tanggal_cek,
                                    'siswa' => []
                                ];
                            }
                            if (!in_array($siswa['nama_lengkap'], $laporan_guru[$id_pembimbing]['siswa'])) {
                                $laporan_guru[$id_pembimbing]['siswa'][] = $siswa['nama_lengkap'];
                            }
                        }

                    } catch (PDOException $e) {
                    }
                }
            }
        }
    }

    foreach ($laporan_guru as $id_pmb => $data) {
        $nama_siswa_str = "";
        $no = 1;
        foreach ($data['siswa'] as $nama) {
            $nama_siswa_str .= "$no. $nama\n";
            $no++;
        }

        $pesan = "*[SISTEM PKL - LAPORAN ABSENSI]*\n\n";
        $pesan .= "Yth. Bapak/Ibu Pembimbing,\n";
        $pesan .= "Berikut adalah siswa yang terdeteksi *ALPHA (Tidak Absen)* pada tanggal " . date('d-m-Y', strtotime($data['tanggal'])) . ":\n\n";
        $pesan .= $nama_siswa_str;
        $pesan .= "\nMohon untuk ditindaklanjuti/dikonfirmasi ke siswa ybs.\n";
        $pesan .= "_Pesan ini dikirim otomatis oleh sistem._";

        kirim_wa($data['no_hp'], $pesan);
    }
}
?>