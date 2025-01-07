<?php
error_reporting(0); 

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
        @$this->Image('../aset/logo/shop.png', 18, 12, 15);
        $this->SetY(20);
        $this->SetFont('times', 'B', 14);
        $this->Cell(0, 10, 'Toko Mainan AyuToys', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetFont('times', 'I', 10);
        $this->Cell(0, 8, 'Jl. Arya Salingsingan, Desa Warukawung, Kecamatan Depok, Kabupaten Cirebon 45155', 0, 1, 'C', 0, '', 0, false, 'M', 'M');
        $this->SetLineWidth(0.5);
        $y = $this->GetY();
        $this->Line(10, $y, $this->getPageWidth() - 10, $y);
        $this->SetLineWidth(0.1);
        $this->Line(10, $y + 1, $this->getPageWidth() - 10, $y + 1);
    }

    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->SetLineWidth(0.1);
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());
        $this->Cell(0, 10, 'Page '.$this->getAliasNumPage().' / '.$this->getAliasNbPages(), 0, 0, 'C');
    }
}

$pdf = new MYPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('AyuToys');
$pdf->SetTitle('Laporan Data Persediaan Barang');
$pdf->SetSubject('Stock Report');
$pdf->SetMargins(10, 33, 10);

$pdf->AddPage();

$pdf->SetFont('helvetica', '', 10);

$sql = "SELECT nama_barang, kategori, stok FROM barang";
$result = $connection->query($sql);

if ($result->num_rows > 0) {
    $html = '<h4 style="text-align: center;">Laporan Data Persediaan Barang</h4>';
    $html .= '<table border="1" cellpadding="6" cellspacing="0" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="font-weight: bold; text-align: Center; width: 6%;">No.</th>
                        <th style="font-weight: bold; text-align: center; width: 40%;">Nama Barang</th>
                        <th style="font-weight: bold; text-align: center; width: 29%;">Kategori</th>
                        <th style="font-weight: bold; text-align: center; width: 10%;">Stok</th>
                        <th style="font-weight: bold; text-align: center; width: 15%;">Status</th>
                    </tr>
                </thead>
                <tbody>';

    $no = 1;
    while ($row = $result->fetch_assoc()) {
        $status = ($row['stok'] > 0) ? 'Masih ada' : 'Habis';

        $html .= '<tr>
                    <td style="text-align: right; width: 6%;">'.$no++.'</td>
                    <td style="text-align: left; width: 40%;">'.htmlspecialchars($row['nama_barang'], ENT_QUOTES, 'UTF-8').'</td>
                    <td style="text-align: left; width: 29%;">'.htmlspecialchars($row['kategori'], ENT_QUOTES, 'UTF-8').'</td>
                    <td style="text-align: center; width: 10%;">'.htmlspecialchars($row['stok'], ENT_QUOTES, 'UTF-8').'</td>
                    <td style="font-weight: bold; text-align: center; width: 15%;">'.htmlspecialchars($status, ENT_QUOTES, 'UTF-8').'</td>
                  </tr>';
    }

    $html .= '</tbody>
            </table>';

    $current_date = formatDateIndo(date('Y-m-d'));
    $html .= '<p align="right" style="font-size: 10px;">Cirebon, ' . $current_date . '<br>Pemilik Toko,<br><br><br><br></p>';
    $html .= '<p align="right" style="font-size: 10px;"><b>Sunandar</b></p>';
    $pdf->writeHTML($html, true, false, true, false, '');
}


$pdf->Output('laporan_data_persediaan_barang.pdf', 'I');
?>