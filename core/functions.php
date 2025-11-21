<?php
function compressAndUpload($source, $destination, $quality) {
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false; 
    }

    $max_width = 800; 
    $width = imagesx($image);
    $height = imagesy($image);

    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = ($height / $width) * $new_width;
        
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        if($info['mime'] == 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }

        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        $image = $new_image; 
    }

    imagejpeg($image, $destination, $quality);

    return true;
}

// --- FUNGSI KIRIM NOTIFIKASI ---
function kirim_notifikasi($pdo, $id_user, $judul, $pesan, $link = '#') {
    try {
        $sql = "INSERT INTO notifikasi (id_user, judul, pesan, link) VALUES (:id, :judul, :pesan, :link)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id_user,
            ':judul' => $judul,
            ':pesan' => $pesan,
            ':link' => $link
        ]);
    } catch (PDOException $e) {
        // Silent error (jangan sampai aplikasi crash cuma gara-gara notif gagal)
    }
}
?>