<?php
// File: core/functions.php

function compressAndUpload($source, $destination, $quality) {
    
    if (!function_exists('imagecreatefromjpeg')) {
        return move_uploaded_file($source, $destination);
    }

    $info = getimagesize($source);
    $mime = $info['mime'];

    // Create image resource based on type
    switch ($mime) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $image = imagecreatefrompng($source);
            break;
        default:
            return move_uploaded_file($source, $destination);
    }

    // --- FIX: AUTO ROTATE BASED ON EXIF (JPEG ONLY) ---
    if ($mime == 'image/jpeg' && function_exists('exif_read_data')) {
        // Suppress errors in case EXIF data is missing or corrupt
        $exif = @exif_read_data($source);
        if ($exif && isset($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $image = imagerotate($image, 180, 0);
                    break;
                case 6:
                    $image = imagerotate($image, -90, 0);
                    break;
                case 8:
                    $image = imagerotate($image, 90, 0);
                    break;
            }
        }
    }
    // --------------------------------------------------

    // --- RESIZE ---
    $max_width = 800; 
    $width = imagesx($image);
    $height = imagesy($image);

    if ($width > $max_width) {
        $new_width = $max_width;
        $new_height = ($height / $width) * $new_width;
        
        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve PNG transparency
        if($mime == 'image/png') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
            $transparent = imagecolorallocatealpha($new_image, 255, 255, 255, 127);
            imagefilledrectangle($new_image, 0, 0, $new_width, $new_height, $transparent);
        }

        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
        $image = $new_image; 
    }

    // --- SAVE ---
    if ($mime == 'image/png') {
         // Scale quality 0-100 to 0-9 for PNG
         $pngQuality = (int)($quality / 10);
         if($pngQuality > 9) $pngQuality = 9;
         // Invert because 0 is no compression in PNG
         $pngCompression = 9 - $pngQuality; 
         
         imagepng($image, $destination, 5); // Use mid-level compression for PNG
    } else {
         imagejpeg($image, $destination, $quality);
    }
    
    // Cleanup
    imagedestroy($image);
    if (isset($new_image)) imagedestroy($new_image);

    return true;
}
?>