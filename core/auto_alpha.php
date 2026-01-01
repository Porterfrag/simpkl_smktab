<?php

// FUNGSI KIRIM WA (FONNTE)
function kirim_wa($target, $pesan) {
    // --- KONFIGURASI TOKEN ---
    // Pastikan token ini benar
    $token = "vm4YMHtKQTvRmsdXHZJy"; 

    $curl = curl_init();
    curl_setopt_array($curl, array(
      CURLOPT_URL => 'https://api.fonnte.com/send',
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_ENCODING => '',
      CURLOPT_MAXREDIRS => 10,
      CURLOPT_TIMEOUT => 0, // 30 detik timeout biar gak hang
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

// FUNGSI UTAMA AUTO ALPHA
function jalankan_auto_alpha($pdo) {
    
    // --- 1. AMBIL TANGGAL SETTING DARI DATABASE ---
    // [PERBAIKAN]: Tidak lagi hardcode, tapi ambil dari tabel settings
    $pkl_start = date('Y-m-d'); // Default hari ini (jaga-jaga error)

    try {
        // ASUMSI: Nama tabel kamu adalah 'settings' atau 'pengaturan'
        // Dan strukturnya: kolom 'nama_setting' dan 'isi_setting'
        // Sesuaikan query ini dengan nama tabel database kamu yang sebenarnya!
        
        // Contoh Query 1 (Jika tabelnya key-value):
        // $stmt_set = $pdo->query("SELECT isi_setting FROM settings WHERE nama_setting = 'pkl_start_date'");
        // $pkl_start = $stmt_set->fetchColumn();

        // CONTOH QUERY 2 (Paling Aman - Langsung cari yg mirip kode sebelumnya):
        // Saya pakai query umum, silakan sesuaikan nama tabelnya jika beda
        $stmt_set = $pdo->query("SELECT value_setting FROM pengaturan WHERE key_setting = 'pkl_start_date'");
        $result_set = $stmt_set->fetchColumn();

        if ($result_set) {
            $pkl_start = $result_set;
        } else {
            // Fallback jika tidak ditemukan di DB, set manual untuk safety
            $pkl_start = '2025-01-01'; 
        }

    } catch (Exception $e) {
        // Jika tabel settings belum dibuat/error query
        $pkl_start = '2025-01-01';
    }

    // --- 2. CEK TANGGAL ---
    $tanggal_cek = date('Y-m-d', strtotime("-1 days")); // Cek Kemarin

    // JIKA tanggal yang dicek (Kemarin) < Tanggal Mulai PKL
    // MAKA: Jangan jalankan apa-apa. Stop.
    if ($tanggal_cek < $pkl_start) {
        // echo "PKL Belum mulai. Skip.";
        return; 
    }

    $hari_minggu_cek = date('N', strtotime($tanggal_cek));
    $laporan_guru = [];

    // --- 3. AMBIL DATA SISWA ---
    // Menggunakan 'nama_guru' dan 'no_telp' sesuai struktur DB kamu yang benar
    $sql = "SELECT s.id_siswa, s.nama_lengkap, p.hari_kerja, g.id_pembimbing, g.nama_guru, g.no_telp 
            FROM siswa s
            JOIN perusahaan p ON s.id_perusahaan = p.id_perusahaan
            LEFT JOIN pembimbing g ON s.id_pembimbing = g.id_pembimbing
            WHERE s.id_perusahaan IS NOT NULL 
            AND s.id_pembimbing IS NOT NULL";
    
    $stmt = $pdo->query($sql);
    $siswa_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($siswa_list as $siswa) {
        $id_siswa = $siswa['id_siswa'];
        $id_pembimbing = $siswa['id_pembimbing'];
        $no_telp_guru = $siswa['no_telp'];
        
        $hari_kerja_arr = !empty($siswa['hari_kerja']) ? explode(',', $siswa['hari_kerja']) : [1,2,3,4,5];

        if (in_array($hari_minggu_cek, $hari_kerja_arr)) {
            
            // Cek apakah sudah absen?
            $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE id_siswa = :id AND tanggal = :tgl");
            $stmt_cek->execute([':id' => $id_siswa, ':tgl' => $tanggal_cek]);
            $sudah_absen = $stmt_cek->fetchColumn();

            if ($sudah_absen == 0) {
                try {
                    // INSERT ALPHA (Tanpa kolom dicatat_oleh)
                    $sql_insert = "INSERT INTO absensi (id_siswa, tanggal, jam_absen, status, keterangan, latitude, longitude) 
                                   VALUES (:id, :tgl, '23:59:00', 'Alpha', 'Tidak absen (Otomatis by System)', NULL, NULL)";
                    $stmt_insert = $pdo->prepare($sql_insert);
                    $stmt_insert->execute([':id' => $id_siswa, ':tgl' => $tanggal_cek]);
                    
                    // Kumpulkan data untuk laporan
                    if (!empty($no_telp_guru)) {
                        if (!isset($laporan_guru[$id_pembimbing])) {
                            $laporan_guru[$id_pembimbing] = [
                                'no_hp' => $no_telp_guru,
                                'nama_guru' => $siswa['nama_guru'], // Pakai nama_guru
                                'tanggal' => $tanggal_cek,
                                'siswa' => []
                            ];
                        }
                        if (!in_array($siswa['nama_lengkap'], $laporan_guru[$id_pembimbing]['siswa'])) {
                            $laporan_guru[$id_pembimbing]['siswa'][] = $siswa['nama_lengkap'];
                        }
                    }

                } catch (PDOException $e) {
                    // Silent error
                }
            }
        }
    }

    // --- 4. KIRIM NOTIFIKASI WA ---
    foreach ($laporan_guru as $id_pmb => $data) {
        $nama_siswa_str = "";
        $no = 1;
        foreach ($data['siswa'] as $nama) {
            $nama_siswa_str .= "$no. $nama\n";
            $no++;
        }

        $tgl_indo = date('d-m-Y', strtotime($data['tanggal']));

        $pesan = "*[SISTEM MONITORING PKL]*\n";
        $pesan .= "Halo Bapak/Ibu *" . $data['nama_guru'] . "*,\n\n";
        $pesan .= "Melaporkan siswa bimbingan Anda yang *TIDAK ABSEN (ALPHA)* pada tanggal *$tgl_indo*:\n\n";
        $pesan .= $nama_siswa_str;
        $pesan .= "\nSistem telah otomatis menandai status mereka menjadi Alpha. Mohon dikonfirmasi ke siswa ybs.\n";
        $pesan .= "_Terima Kasih._";

        kirim_wa($data['no_hp'], $pesan);
        sleep(2); // Jeda anti-spam
    }
}
?>