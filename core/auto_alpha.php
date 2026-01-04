<?php

// --- FUNGSI KIRIM WA (Tidak Berubah) ---
function kirim_wa($target, $pesan) {
    // KONFIGURASI TOKEN (Sesuaikan dengan token Fonnte Anda)
    $token = "arjnMHqWLkFBAaN9bBoY"; 

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

// --- FUNGSI UTAMA AUTO ALPHA (Telah Diperbaiki) ---
function jalankan_auto_alpha($pdo) {
    
    // 1. AMBIL PENGATURAN TANGGAL DARI DATABASE
    // Pastikan nama tabel di bawah ('pengaturan') sesuai dengan nama tabel di database Anda
    // Script ini mencari key: 'pkl_start_date' dan 'pkl_end_date'
    try {
        $stmt_setting = $pdo->prepare("SELECT key_setting, value_setting FROM pengaturan WHERE key_setting IN ('pkl_start_date', 'pkl_end_date')");
        $stmt_setting->execute();
        $settings = $stmt_setting->fetchAll(PDO::FETCH_KEY_PAIR); // Hasil array: ['key' => 'value']
    } catch (PDOException $e) {
        // Jika tabel pengaturan error/tidak ada, hentikan fungsi agar tidak kacau
        return; 
    }

    // Jika setting tanggal tidak ditemukan, hentikan fungsi
    if (empty($settings['pkl_start_date']) || empty($settings['pkl_end_date'])) {
        return;
    }

    $tgl_mulai_pkl = $settings['pkl_start_date'];
    $tgl_selesai_pkl = $settings['pkl_end_date'];
    
    $laporan_guru = [];

    // Loop mundur 3 hari ke belakang
    for ($i = 3; $i >= 1; $i--) {
        
        $tanggal_cek = date('Y-m-d', strtotime("-$i days"));

        // 2. VALIDASI PERIODE PKL
        // Jika tanggal yang dicek berada DI LUAR rentang PKL, skip/lanjutkan
        if ($tanggal_cek < $tgl_mulai_pkl || $tanggal_cek > $tgl_selesai_pkl) {
            continue; 
        }

        $hari_minggu_cek = date('N', strtotime($tanggal_cek)); 

        // Ambil siswa yang aktif magang
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
            
            // Cek hari kerja siswa (Default Senin-Jumat: 1,2,3,4,5)
            $hari_kerja_arr = !empty($siswa['hari_kerja']) ? explode(',', $siswa['hari_kerja']) : [1,2,3,4,5];

            if (in_array($hari_minggu_cek, $hari_kerja_arr)) {
                
                // Cek apakah sudah absen?
                $stmt_cek = $pdo->prepare("SELECT COUNT(*) FROM absensi WHERE id_siswa = :id AND tanggal = :tgl");
                $stmt_cek->execute([':id' => $id_siswa, ':tgl' => $tanggal_cek]);
                $sudah_absen = $stmt_cek->fetchColumn();

                // Jika belum absen (0), tandai Alpha
                if ($sudah_absen == 0) {
                    try {
                        $sql_insert = "INSERT INTO absensi (id_siswa, tanggal, status, dicatat_oleh, keterangan) 
                                       VALUES (:id, :tgl, 'Alpha', 'Sistem', 'Tidak absen (Otomatis)')";
                        $stmt_insert = $pdo->prepare($sql_insert);
                        $stmt_insert->execute([':id' => $id_siswa, ':tgl' => $tanggal_cek]);
                        
                        // Masukkan ke antrian laporan WA
                        if (!empty($no_telp_guru)) {
                            if (!isset($laporan_guru[$id_pembimbing])) {
                                $laporan_guru[$id_pembimbing] = [
                                    'no_hp' => $no_telp_guru,
                                    'siswa' => []
                                ];
                            }
                            
                            // 3. FORMAT LAPORAN (Nama + Tanggal)
                            // Format: "Ahmad (05-01-2026)" agar guru tahu kapan kejadiannya
                            $info_siswa = $siswa['nama_lengkap'] . " (" . date('d-m-Y', strtotime($tanggal_cek)) . ")";

                            // Cek duplikasi di array agar nama tidak muncul ganda
                            if (!in_array($info_siswa, $laporan_guru[$id_pembimbing]['siswa'])) {
                                $laporan_guru[$id_pembimbing]['siswa'][] = $info_siswa;
                            }
                        }

                    } catch (PDOException $e) {
                        // Error handling silent (bisa ditambahkan log jika perlu)
                    }
                }
            }
        }
    }

    // 4. KIRIM REKAP LAPORAN KE GURU
    foreach ($laporan_guru as $id_pmb => $data) {
        $daftar_siswa_str = "";
        $no = 1;
        foreach ($data['siswa'] as $info) {
            $daftar_siswa_str .= "$no. $info\n";
            $no++;
        }

        $pesan = "*[SISTEM PKL - LAPORAN ABSENSI]*\n\n";
        $pesan .= "Yth. Bapak/Ibu Pembimbing,\n";
        $pesan .= "Berikut adalah siswa yang terdeteksi *ALPHA (Tidak Absen)* berdasarkan pengecekan sistem (3 hari terakhir):\n\n";
        $pesan .= $daftar_siswa_str;
        $pesan .= "\nMohon untuk ditindaklanjuti/dikonfirmasi ke siswa ybs.\n";
        $pesan .= "_Pesan ini dikirim otomatis oleh sistem._";

        kirim_wa($data['no_hp'], $pesan);
    }
}
?>