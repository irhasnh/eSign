<?php
// d:\laragon\www\eSign\process.php

require_once __DIR__ . '/vendor/autoload.php'; // Tambahkan baris ini untuk autoloader Composer

require_once __DIR__ . '/ESignBsre.php';
require_once __DIR__ . '/ESignBsreResponse.php';

use DiskominfotikBandaAceh\ESignBsrePhp\ESignBsre;
use DiskominfotikBandaAceh\ESignBsrePhp\ESignBsreResponse;

// Konfigurasi API BSrE (Ganti dengan kredensial dan URL asli Anda)
$baseUrl = 'URL_API_BSRE_ANDA'; // Contoh: 'https://tte.bsre.go.id/api'
$username = 'USERNAME_ANDA';
$password = 'PASSWORD_ANDA';

// Inisialisasi objek ESignBsre
$esign = new ESignBsre($baseUrl, $username, $password);

$action = $_POST['action'] ?? null;
$response = null;
$output = '';

try {
    if ($action === 'Sign Document') {
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($_FILES['pdf_file']['tmp_name']);
            $fileName = basename($_FILES['pdf_file']['name']);
            $nik = $_POST['nik'] ?? '';
            $passphrase = $_POST['passphrase'] ?? '';

            if (empty($fileContent) || empty($fileName) || empty($nik) || empty($passphrase)) {
                 $output = "<p style='color: red;'>Error: Semua input diperlukan untuk Tanda Tangan.</p>";
                 // $response tetap null di sini
            } else {
                 // Panggil metode sign
                 $response = $esign->setFile($fileContent, $fileName)->sign($nik, $passphrase);
            }

        } else {
            $output = "<p style='color: red;'>Error: Gagal mengunggah file PDF.</p>";
             // $response tetap null di sini
        }

    } elseif ($action === 'Verify Document') {
         if (isset($_FILES['signed_pdf_file']) && $_FILES['signed_pdf_file']['error'] === UPLOAD_ERR_OK) {
            $fileContent = file_get_contents($_FILES['signed_pdf_file']['tmp_name']);
            $fileName = basename($_FILES['signed_pdf_file']['name']);

             if (empty($fileContent) || empty($fileName)) {
                 $output = "<p style='color: red;'>Error: File diperlukan untuk Verifikasi.</p>";
            } else {
                 // Panggil metode verification
                 $response = $esign->setFile($fileContent, $fileName)->verification();
            }
        } else {
            $output = "<p style='color: red;'>Error: Gagal mengunggah file PDF yang sudah ditandatangani.</p>";
        }

    } elseif ($action === 'Check User Status') {
        $nik = $_POST['status_nik'] ?? '';
        if (empty($nik)) {
            $output = "<p style='color: red;'>Error: NIK diperlukan untuk Cek Status Pengguna.</p>";
        } else {
            // Panggil metode statusUser
            $response = $esign->statusUser($nik);
        }
    }

    // Proses dan tampilkan respons dari API
    if ($response instanceof ESignBsreResponse) {
        $output .= "<h3>Hasil:</h3>";
        $output .= "<p>Status HTTP: " . $response->getStatus() . "</p>";

        if ($response->getStatus() === ESignBsreResponse::STATUS_OK) {
            $output .= "<p style='color: green;'>Status: Sukses</p>";
            $data = $response->getData();

            if ($action === 'Sign Document') {
                // Untuk tanda tangan, data yang berhasil biasanya adalah konten file PDF yang sudah ditandatangani
                // Anda bisa menyediakan link download atau langsung menampilkan (jika browser support)
                if (!empty($data)) {
                     $signedFileName = 'signed_' . ($_FILES['pdf_file']['name'] ?? 'document.pdf');
                     // Contoh: menyediakan link download (membutuhkan file PHP terpisah atau logic inline)
                     // Untuk contoh sederhana, kita bisa encoded base64 atau hanya menunjukkan bahwa data diterima
                     $output .= "<p>File berhasil ditandatangani. Data file diterima.</p>";
                     // Untuk menyediakan download langsung:
                     // header('Content-Type: application/pdf');
                     // header('Content-Disposition: attachment; filename="' . $signedFileName . '"');
                     // echo $data;
                     // exit; // Hentikan eksekusi setelah mengirim file

                     // Atau simpan file di server sementara:
                     // file_put_contents(__DIR__ . '/' . $signedFileName, $data);
                     // $output .= "<p><a href='./" . $signedFileName . "' download>Download File yang Ditandatangani</a></p>";
                     // Catatan: Penyimpanan sementara memerlukan penanganan pembersihan file.
                } else {
                     $output .= "<p style='color: orange;'>Sukses, tetapi tidak ada data file yang dikembalikan.</p>";
                }

            } elseif ($action === 'Verify Document') {
                 // Data untuk verifikasi biasanya berupa objek JSON dengan informasi verifikasi
                 $output .= "<p>Data Verifikasi: <pre>" . print_r($data, true) . "</pre></p>";

            } elseif ($action === 'Check User Status') {
                 // Data untuk status pengguna biasanya berupa objek JSON
                 $output .= "<p>Data Status Pengguna: <pre>" . print_r($data, true) . "</pre></p>";
            }

        } else {
            $output .= "<p style='color: red;'>Status: Gagal</p>";
            $errors = $response->getErrors();
            $output .= "<p>Pesan Error: " . (is_array($errors) ? implode(', ', $errors) : $errors) . "</p>";
        }
    }

} catch (\Exception $e) {
    // Tangani error lain yang mungkin terjadi di luar kelas ESignBsre
    $output = "<p style='color: red;'>Terjadi kesalahan dalam pemrosesan: " . $e->getMessage() . "</p>";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Hasil Proses BSrE</title>
</head>
<body>
    <h1>Hasil Pemrosesan</h1>
    <p><a href="index.html">Kembali</a></p>
    <div>
        <?php echo $output; ?>
    </div>
</body>
</html>
```