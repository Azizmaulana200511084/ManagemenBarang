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
$pdf->SetTitle('Laporan Barang Masuk');
$pdf->SetSubject('Laporan Barang Masuk');
$pdf->SetKeywords('TCPDF, PDF, laporan, barang masuk');
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

$barang_query = "SELECT bm.barang_masuk_id, bm.kode_bm, s.nama_supplier, bm.tanggal_masuk, b.nama_barang, b.satuan, bm.jumlah, bm.harga_beli 
                 FROM barang_masuk bm 
                 JOIN supplier s ON bm.supplier_id = s.supplier_id 
                 JOIN barang b ON bm.barang_id = b.barang_id 
                 WHERE bm.tanggal_masuk BETWEEN ? AND ? 
                 ORDER BY bm.barang_masuk_id ASC";
$stmt = $connection->prepare($barang_query);
if (!$stmt) {
    die('Prepare statement failed: ' . $connection->error);
}

$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();
$barangs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

function fetchTotalForKodeBM($connection, $kode_bm) {
    $query = "SELECT total FROM bm_t WHERE kode_bm = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("s", $kode_bm);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ? $row['total'] : 0;
}

$html = '<div style="text-align: center;">
            <h4 style="display: inline; text-decoration: underline;">Laporan Barang Masuk</h4>
         </div>';
$html .= '<p align="left" style="font-size: 12px;">Periode: ' . formatDateIndo(date('d-M-Y', strtotime($start_date))) . ' s/d ' . formatDateIndo(date('d-M-Y', strtotime($end_date))) . '</p>';
$html .= '<table cellspacing="1" cellpadding="4" width="100%" style="font-size: 12px;">';
$html .= '<tr style="font-weight: bold;">
            <th style="width: 4%;" align="right">No</th>
            <th style="width: 16%;">Kode BM</th>
            <th>Supplier</th>
            <th style="width: 16%;">Barang</th>
            <th>Jumblah</th>
            <th align="center">Tanggal</th>
            <th style="width: 18%;">Total</th>
          </tr>';

$prev_kode_bm = null;
$index = 1;
$overall_total = 0;
foreach ($barangs as $barang) {
    $kode_bm_cell = ($barang['kode_bm'] != $prev_kode_bm) ? htmlspecialchars($barang['kode_bm'], ENT_QUOTES, 'UTF-8') : '';
    $index_cell = ($barang['kode_bm'] != $prev_kode_bm) ? $index : '';
    $nama_supplier_cell = ($barang['kode_bm'] != $prev_kode_bm) ? htmlspecialchars($barang['nama_supplier'], ENT_QUOTES, 'UTF-8') : '';
    $tanggal_masuk_cell = ($barang['kode_bm'] != $prev_kode_bm) ? formatDateIndo(date('d-M-Y', strtotime($barang['tanggal_masuk']))) : '';
    $total = $barang['jumlah'] * $barang['harga_beli'];
    $overall_total += $total;
    $total_sum = ($barang['kode_bm'] != $prev_kode_bm) ? fetchTotalForKodeBM($connection, $barang['kode_bm']) : 0;
    $total_sum_cell = ($barang['kode_bm'] != $prev_kode_bm) ? '<td align="left"><b>Rp. ' . htmlspecialchars(number_format($total_sum, 2, ',', '.'), ENT_QUOTES, 'UTF-8') . '</b></td>' : '';
    $html .= '<tr>
                <td align="right">' . $index_cell . '</td>
                <td align="left">' . $kode_bm_cell . '</td>
                <td align="left">' . $nama_supplier_cell . '</td>
                <td align="left">' . htmlspecialchars($barang['nama_barang'], ENT_QUOTES, 'UTF-8') . ' Rp.' . htmlspecialchars(number_format($barang['harga_beli'], 2, ',', '.'), ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars($barang['satuan'], ENT_QUOTES, 'UTF-8') . ' x ' . htmlspecialchars($barang['jumlah'], ENT_QUOTES, 'UTF-8') . '</td>
                <td align="left">Rp. ' . htmlspecialchars(number_format($total, 2, ',', '.'), ENT_QUOTES, 'UTF-8') . '</td>
                <td align="center">' . $tanggal_masuk_cell . '</td>
                ' . $total_sum_cell . '
              </tr>';
    if ($barang['kode_bm'] != $prev_kode_bm) {
        $index++;
    }
    $prev_kode_bm = $barang['kode_bm'];
}
$html .= '</table>';
$html .= '<hr style="border-top: 1px dotted #000000;">';
$html .= '<p align="left" style="font-size: 12px;"><b>Total Keseluruhan: Rp. ' . number_format($overall_total, 2, ',', '.') . '</b></p>';
$html .= '<hr style="border-top: 1px dotted #000000;"> . <br> . ';

$current_date = formatDateIndo(date('Y-m-d'));
$html .= '<p align="right" style="font-size: 12px;">Cirebon, ' . $current_date . '<br>Pemilik Toko,<br><br><br><br></p>';
$html .= '<p align="right" style="font-size: 12px;"><b>Sunandar</b></p>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('laporan_barang_masuk.pdf', 'I');
ob_end_flush();
?>