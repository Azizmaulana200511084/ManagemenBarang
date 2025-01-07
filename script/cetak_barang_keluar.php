<?php
ob_start();
require_once('tcpdf/tcpdf.php');
include_once "../db/db.php";

function formatDateIndo($date) {
    $months = [
        'January' => 'Januari', 'February' => 'Februari', 'March' => 'Maret',
        'April' => 'April', 'May' => 'Mei', 'June' => 'Juni',
        'July' => 'Juli', 'August' => 'Agustus', 'September' => 'September',
        'October' => 'Oktober', 'November' => 'November', 'December' => 'Desember'
    ];
    $date = date_create($date);
    $day = date_format($date, 'd');
    $month = $months[date_format($date, 'F')];
    $year = date_format($date, 'Y');
    $time = date_format($date, 'H:i:s');
    return "$day $month $year $time";
}

class MYPDF extends TCPDF {
}

if (isset($_GET['kode_bk'])) {
    $kode_bk = $_GET['kode_bk'];
    $stmt = $connection->prepare("SELECT bk.barang_keluar_id, bk.barang_id, bk.kode_bk, bk.user_id, b.nama_barang, b.satuan, u.nama_lengkap, bk.plg, bk.jumlah, bk.harga_jual, bk.tanggal_keluar, bk.dibuat, byr.bayar, byr.kembalian 
                                  FROM barang_keluar bk 
                                  JOIN barang b ON bk.barang_id = b.barang_id 
                                  JOIN users u ON bk.user_id = u.user_id 
                                  JOIN bk_t byr ON bk.kode_bk = byr.kode_bk 
                                  WHERE bk.kode_bk = ?");
    $stmt->bind_param("s", $kode_bk);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pdf = new MYPDF('P', 'mm', array(58 * 0.8, 297 * 0.8), false, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('Detail Barang Keluar');
        $pdf->SetSubject('Detail Barang Keluar');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(0, 0, 0, 0);
        $pdf->SetAutoPageBreak(FALSE, 0);
        $current_date = formatDateIndo(date('d-M-Y H:i:s'));
        $filename = 'BarangKeluar_' . $kode_bk . '_' . $current_date . '.pdf';
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 12 * 0.8); 
        $pdf->Cell(0, 3 * 0.8, 'Toko Mainan AyuToys', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'I', 9 * 0.8);
        $pdf->Cell(0, 3 * 0.8, 'Jl. Arya Salingsingan, Ds.Warukawung', 0, 1, 'C');
        $pdf->Cell(0, 3 * 0.8, 'Kec.Depok, Kab.Cirebon - 45155', 0, 1, 'C');
        $pdf->Line(2 * 0.8, $pdf->GetY(), $pdf->getPageWidth() - 2 * 0.8, $pdf->GetY());

        $result->data_seek(0);
        if ($barang = $result->fetch_assoc()) {
            $pdf->SetFont('helvetica', '', 8 * 0.8);
            $pdf->Cell(0, 5 * 0.8, formatDateIndo(date('d-M-Y H:i:s', strtotime($barang['dibuat']))), 0, 1, 'R');
            $pdf->Line(2 * 0.8, $pdf->GetY(), $pdf->getPageWidth() - 2 * 0.8, $pdf->GetY(), ['dash' => '3,2']);
            $pdf->Cell(0, 5 * 0.8, 'Kode BK   : ' . $barang['kode_bk'], 0, 1, 'L');
            $pdf->Cell(0, 3 * 0.8, 'Pelanggan: ' . ucwords($barang['plg']), 0, 1, 'L');
            $pdf->Cell(0, 5 * 0.8, 'Kasir         : ' . ucwords($barang['nama_lengkap']), 0, 1, 'L');
        }

        $pdf->Line(2 * 0.8, $pdf->GetY(), $pdf->getPageWidth() - 2 * 0.8, $pdf->GetY(), ['dash' => '3,2']);
        $pdf->Cell(0, 3 * 0.8, '', 0, 1, 'L');

        $total_price = 0;
        $result->data_seek(0);
        while ($barang = $result->fetch_assoc()) {
            $total_item_price = $barang['jumlah'] * $barang['harga_jual'];
            $pdf->SetFont('helvetica', '', 8 * 0.8);
            $pdf->MultiCell(0, 3 * 0.8, $barang['nama_barang'], 0, 'L', 0, 1);
            $pdf->Cell(0, 3 * 0.8, $barang['jumlah'] . ' x ' . number_format($barang['harga_jual'], 2, ',', '.'), 0, 0, 'L');
            $pdf->Cell(0, 5 * 0.8, number_format($total_item_price, 2, ',', '.'), 0, 1, 'R');
            $total_price += $total_item_price;
        }

        $pdf->SetFont('helvetica', 'I', 9 * 0.8);
        $pdf->Cell(0, 3 * 0.8, '', 0, 1, 'L');
        $pdf->Line(2 * 0.8, $pdf->GetY(), $pdf->getPageWidth() - 2 * 0.8, $pdf->GetY(), ['dash' => '3,2']);

        $result->data_seek(0);
        if ($barang = $result->fetch_assoc()) {
            $pdf->Cell(0, 5 * 0.8, 'Jumblah Total', 0, 0, 'L');
            $pdf->Cell(0, 5 * 0.8, 'Rp. ' . number_format($total_price, 2, ',', '.'), 0, 1, 'R');
            $pdf->Cell(0, 5 * 0.8, 'Bayar', 0, 0, 'L');
            $pdf->Cell(0, 5 * 0.8, 'Rp. ' . number_format($barang['bayar'], 2, ',', '.'), 0, 1, 'R');
            $pdf->Cell(0, 5 * 0.8, 'Kembalian', 0, 0, 'L');
            $pdf->Cell(0, 5 * 0.8, 'Rp. ' . number_format($barang['kembalian'], 2, ',', '.'), 0, 1, 'R');
        }

        $pdf->Line(2 * 0.8, $pdf->GetY(), $pdf->getPageWidth() - 2 * 0.8, $pdf->GetY(), ['dash' => '3,2']);
        $pdf->SetFont('helvetica', 'I', 8 * 0.8);
        $pdf->Cell(0, 4 * 0.9, 'Terimakasih Sudah Berbelanja !', 0, 1, 'C');

        ob_end_clean();
        
        $pdf->Output($filename, 'I');
    } else {
        echo "Data barang tidak ditemukan.";
    }
    $stmt->close();
} else {
    echo "Kode barang tidak valid.";
}
mysqli_close($connection);
?>