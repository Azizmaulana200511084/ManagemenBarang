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
    return date_format($date, 'd') . ' ' . $months[date_format($date, 'F')] . ' ' . date_format($date, 'Y');
}

class MYPDF extends TCPDF {
    public function Header() {
        $this->Image('../aset/logo/shop.png', 10, 10, 18);
        $this->SetFont('times', 'B', 12);
        $this->SetY(15);
        // $this->Cell(0, 15, 'Detail Barang Masuk', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        $this->Cell(0, 15, '', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetFont('times', 'B', 14);
        $this->Cell(0, 15, 'Toko Mainan AyuToys', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetFont('times', 'I', 12);
        $this->Cell(0, 15, 'Alamat: Jl. Arya Salingsingan, Ds.Warukawung, Kec.Depok, Kab.Cirebon, Jawa Barat 45155', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->SetLineWidth(0.1);
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

if (isset($_GET['kode_bm'])) {
    $kode_bm = $_GET['kode_bm'];
    $stmt = $connection->prepare("SELECT bm.barang_masuk_id, bm.user_id, bm.kode_bm, bm.barang_id, bm.supplier_id, b.nama_barang, b.satuan, s.nama_supplier, u.nama_lengkap, bm.jumlah, bm.harga_beli, bm.tanggal_masuk, bm.dibuat 
                                  FROM barang_masuk bm 
                                  JOIN barang b ON bm.barang_id = b.barang_id 
                                  JOIN supplier s ON bm.supplier_id = s.supplier_id 
                                  JOIN users u ON bm.user_id = u.user_id 
                                  WHERE bm.kode_bm = ?");
    $stmt->bind_param("s", $kode_bm);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(PDF_CREATOR);
        $pdf->SetAuthor('Your Name');
        $pdf->SetTitle('Detail Barang Masuk');
        $pdf->SetSubject('Detail Barang Masuk');
        $pdf->setPrintHeader(true);
        $pdf->setPrintFooter(true);
        $pdf->SetMargins(10, 33, 10);
        $topMargin = 5;
        $bottomMargin = 5;
        $current_date = formatDateIndo(date('Y-m-d'));
        $filename = 'BarangMasuk_' . $kode_bm . '_' . $current_date . '.pdf';
        $pdf->AddPage();

        $pdf->SetLineWidth(0.1);
        $pdf->Line(10, $pdf->GetY(), $pdf->getPageWidth() - 10, $pdf->GetY());
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 15, 'Detail Barang Masuk', 0, 1, 'C');
        // $pdf->Cell(0, 10, 'Cirebon,' . $current_date, 0, 1, 'R');
        $pdf->SetFont('times', '', 12);

        $result->data_seek(0);
        if ($barang = $result->fetch_assoc()) {
            $pdf->Cell(50, 5, 'Kode Barang Masuk', 0, 0, 'L');
            $pdf->Cell(0, 5, ': ' . $barang['kode_bm'], 0, 1, 'L');
            $pdf->Cell(50, 5, 'Nama Supplier', 0, 0, 'L');
            $pdf->Cell(0, 5, ': ' . $barang['nama_supplier'], 0, 1, 'L');
            $pdf->Cell(50, 5, 'Tanggal Barang Masuk', 0, 0, 'L');
            $formatted_tanggal_masuk = formatDateIndo(date('d-M-Y', strtotime($barang['tanggal_masuk'])));
            $pdf->Cell(0, 5, ': ' . $formatted_tanggal_masuk, 0, 1, 'L');
            $pdf->Cell(50, 5, 'Nama Penerima', 0, 0, 'L');
            $pdf->Cell(0, 5, ': ' . $barang['nama_lengkap'], 0, 1, 'L');
        }

        $pdf->SetLineWidth(0.1);
        $pdf->Line(10, $pdf->GetY(), $pdf->getPageWidth() - 10, $pdf->GetY(), ['dash' => '2,2']);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(10, 10, 'No.', 0, 0, 'C');
        $pdf->Cell(60, 10, 'Nama Barang', 0, 0, 'L');
        $pdf->Cell(25, 10, 'Jumblah', 0, 0, 'C');
        $pdf->Cell(50, 10, 'Harga Beli', 0, 0, 'L');
        $pdf->Cell(40, 10, 'Total', 0, 1, 'L');
        $pdf->SetFont('times', 'I', 12);

        $total_price = 0;
        $count = 1;
        $result->data_seek(0);
        while ($barang = $result->fetch_assoc()) {
            $total_item_price = $barang['jumlah'] * $barang['harga_beli'];
            $pdf->Cell(10, 10, $count++ . '.', 0, 0, 'C');
            $pdf->Cell(60, 10, $barang['nama_barang'], 0, 0, 'L');
            $pdf->Cell(25, 10, $barang['jumlah'], 0, 0, 'C');
            $pdf->Cell(50, 10, 'Rp. ' . number_format($barang['harga_beli'], 2, ',', '.') . ' / ' . $barang['satuan'], 0, 0, 'L');
            $pdf->Cell(40, 10, 'Rp. ' . number_format($total_item_price, 2, ',', '.'), 0, 1, 'L');
            $total_price += $total_item_price;
        }

        $pdf->SetFont('times', 'B', 12);
        $pdf->Line(10, $pdf->GetY(), $pdf->getPageWidth() - 10, $pdf->GetY(), ['dash' => '2,2']);
        $pdf->Cell(145, 10, 'Jumblah Total', 0, 0, 'R');
        $pdf->Cell(40, 10, 'Rp. ' . number_format($total_price, 2, ',', '.'), 0, 1, 'L');
        $pdf->Line(10, $pdf->GetY(), $pdf->getPageWidth() - 10, $pdf->GetY(), ['dash' => '2,2']);

        $pdf->SetFont('times', '', 12);
        $pdf->Ln(10);
        $pdf->Cell(0, 1, 'Cirebon, ' . $current_date, 0, 1, 'R');
        $pdf->Cell(0, 1, 'Pemilik Toko,', 0, 1, 'R');
        $pdf->Ln(20);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(182, 10, 'Sunandar', 0, 1, 'R');

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
