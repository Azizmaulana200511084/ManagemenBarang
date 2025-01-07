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
    private $isFirstPage = true;
    public function Header() {
        if ($this->isFirstPage) {
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
            $this->isFirstPage = false;
        }
    }
    public function Footer() {
        $this->SetY(-15);
        $this->SetFont('times', 'I', 8);
        $this->SetLineWidth(0.1);
        $this->Line(10, $this->GetY(), $this->getPageWidth() - 10, $this->GetY());
        $this->Cell(0, 10, 'Page ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
    }
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
if (empty($start_date) || empty($end_date)) {
    die('Invalid date range provided.');
}

$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, 'A4', true, 'UTF-8', false);
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Toko Mainan AyuToys');
$pdf->SetTitle('Laporan Barang Keluar');
$pdf->SetSubject('Laporan Barang Keluar');
$pdf->SetKeywords('TCPDF, PDF, laporan, barang keluar');
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
$pdf->SetMargins(10, 30, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);
$pdf->SetAutoPageBreak(TRUE, 13);
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
$pdf->AddPage();
$pdf->SetFont('times', '', 12);

$barang_query = "SELECT bk.barang_keluar_id, bk.kode_prd, bk.barang_id, bk.kode_bk, bk.user_id, b.nama_barang, b.satuan, bk.jumlah, bk.harga_jual, bk.tanggal_keluar, bk.dibuat, byr.total
                 FROM barang_keluar bk 
                 JOIN barang b ON bk.barang_id = b.barang_id 
                 JOIN bk_t byr ON bk.kode_bk = byr.kode_bk
                 WHERE bk.tanggal_keluar BETWEEN ? AND ?
                 ORDER BY bk.barang_keluar_id ASC";
$stmt = $connection->prepare($barang_query);
if (!$stmt) {
    die('Prepare statement failed: ' . $connection->error);
}

$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$barangs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$aggregated_data = [];
foreach ($barangs as $barang) {
    $kode_bk = $barang['kode_bk'];
    if (!isset($aggregated_data[$kode_bk])) {
        $aggregated_data[$kode_bk] = [
            'kode_bk' => $kode_bk,
            'tanggal_keluar' => $barang['tanggal_keluar'],
            'total' => $barang['total'],
            'details' => []
        ];
    }
    $aggregated_data[$kode_bk]['details'][] = [
        'kode_prd' => $barang['kode_prd'],
        'nama_barang' => $barang['nama_barang'],
        'satuan' => $barang['satuan'],
        'jumlah' => $barang['jumlah'],
        'harga_jual' => $barang['harga_jual']
    ];
}

$html = '<div style="text-align: center;">
            <h4 style="display: inline; text-decoration: underline;">Laporan Barang Keluar</h4>
         </div>';
$html .= '<p align="left" style="font-size: 12px;">Periode: ' . formatDateIndo(date('d-M-Y', strtotime($start_date))) . ' s/d ' . formatDateIndo(date('d-M-Y', strtotime($end_date))) . '</p>';
$html .= '<table cellspacing="1" cellpadding="4" width="100%" style="font-size: 12px;">';
$html .= '<tr style="font-weight: bold;">
            <th style="width: 4%;" align="right">No</th>
            <th style="width: 16%;">Kode BK</th>
            <th>Kode Produk</th>
            <th style="width: 16%;">Barang</th>
            <th>Jumblah</th>
            <th align="center">Tanggal</th>
            <th style="width: 18%;">Total</th>
          </tr>';

$index = 1;
$total_amount = 0;
foreach ($aggregated_data as $kode_bk => $data) {
    $rowspan = count($data['details']);
    $first_row = true;
    foreach ($data['details'] as $detail) {
        $total = $detail['jumlah'] * $detail['harga_jual'];
        $total_amount += $total;
        $html .= '<tr>';
        if ($first_row) {
            $html .= '<td align="right" rowspan="' . $rowspan . '">' . $index . '</td>
                      <td align="left" rowspan="' . $rowspan . '">' . htmlspecialchars($data['kode_bk'], ENT_QUOTES, 'UTF-8') . '</td>';
        }
        $html .= '<td align="left">' . htmlspecialchars($detail['kode_prd'], ENT_QUOTES, 'UTF-8') . '</td>';
        $html .= '<td align="left">' . htmlspecialchars($detail['nama_barang'], ENT_QUOTES, 'UTF-8') . ' Rp.' . htmlspecialchars(number_format($detail['harga_jual'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars($detail['satuan'], ENT_QUOTES, 'UTF-8') . ' x ' . htmlspecialchars($detail['jumlah'], ENT_QUOTES, 'UTF-8') . '</td>
                  <td align="left">Rp. ' . htmlspecialchars(number_format($total, 2, ',', '.'), ENT_QUOTES, 'UTF-8') . '</td>';
        if ($first_row) {
            $html .= '<td align="center" rowspan="' . $rowspan . '">' . formatDateIndo(date('d-M-Y', strtotime($data['tanggal_keluar']))) . '</td>
                      <td align="left" rowspan="' . $rowspan . '"><b>Rp. ' . htmlspecialchars(number_format($data['total'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') . '</b></td>';
            $first_row = false;
        }
        $html .= '</tr>';
    }
    $index++;
}

$html .= '</table>';
$html .= '<hr style="border-top: 1px dotted #000000;">';
$html .= '<p align="left" style="font-size: 12px;"><b>Total Keseluruhan: Rp. ' . number_format($total_amount, 2, ',', '.') . '</b></p>';
$html .= '<hr style="border-top: 1px dotted #000000;"> . <br> .';

$current_date = formatDateIndo(date('Y-m-d'));
$html .= '<p align="right" style="font-size: 12px;">Cirebon, ' . $current_date . '<br>Pemilik Toko,<br><br><br><br></p>';
$html .= '<p align="right" style="font-size: 12px;"><b>Sunandar</b></p>';


$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('laporan_barang_keluar.pdf', 'I');
ob_end_flush();
?>