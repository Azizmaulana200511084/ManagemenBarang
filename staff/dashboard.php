<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'staff') {
    header('Location: ../index.php');
    exit;
}
include_once "../db/db.php";
$user_id = $_SESSION['user_id'];
$stmt = $connection->prepare("SELECT nama_lengkap, photo FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $nama_lengkap = ucwords($row['nama_lengkap']);
    $photo = $row['photo'] ? $row['photo'] : '../aset/images/default.png';
    $_SESSION['nama_lengkap'] = $nama_lengkap;
    $_SESSION['photo'] = $photo;
} else {
    $nama_lengkap = "Unknown";
    $photo = "../aset/images/default.png";
}
$stmt->close();

$totals = [
    'total_barang' => 0,
    'total_admin' => 0,
    'total_staff' => 0,
    'total_supplier' => 0
];

$result = $connection->query("SELECT COUNT(*) AS total_barang FROM barang");
if ($result) {
    $totals['total_barang'] = $result->fetch_assoc()['total_barang'];
}

$result = $connection->query("SELECT COUNT(*) AS total_admin FROM users WHERE role = 'admin'");
if ($result) {
    $totals['total_admin'] = $result->fetch_assoc()['total_admin'];
}

$result = $connection->query("SELECT COUNT(*) AS total_staff FROM users WHERE role = 'staff'");
if ($result) {
    $totals['total_staff'] = $result->fetch_assoc()['total_staff'];
}

$result = $connection->query("SELECT COUNT(*) AS total_supplier FROM supplier");
if ($result) {
    $totals['total_supplier'] = $result->fetch_assoc()['total_supplier'];
}

$popularItemsQuery = "
    SELECT b.nama_barang, SUM(bk.jumlah) AS total_terjual
    FROM barang_keluar bk
    JOIN barang b ON bk.barang_id = b.barang_id
    GROUP BY b.nama_barang
    ORDER BY total_terjual DESC
    LIMIT 10
";
$popularItemsResult = $connection->query($popularItemsQuery);
$popularItems = [];
if ($popularItemsResult) {
    while ($row = $popularItemsResult->fetch_assoc()) {
        $popularItems[] = $row;
    }
}

$salesDataQuery = "
    SELECT b.nama_barang, YEAR(bk.tanggal_keluar) AS tahun, MONTH(bk.tanggal_keluar) AS bulan, SUM(bk.jumlah) AS total_terjual
    FROM barang_keluar bk
    JOIN barang b ON bk.barang_id = b.barang_id
    GROUP BY b.nama_barang, tahun, bulan
    ORDER BY b.nama_barang, tahun DESC, bulan ASC
";
$salesDataResult = $connection->query($salesDataQuery);
$salesData = [];
$years = [];
while ($row = $salesDataResult->fetch_assoc()) {
    $nama_barang = $row['nama_barang'];
    $tahun = $row['tahun'];
    $bulan = $row['bulan'];
    $total = $row['total_terjual'];

    if (!isset($salesData[$nama_barang])) {
        $salesData[$nama_barang] = [];
    }
    if (!isset($salesData[$nama_barang][$tahun])) {
        $salesData[$nama_barang][$tahun] = [];
    }
    $salesData[$nama_barang][$tahun][$bulan] = $total;

    if (!in_array($tahun, $years)) {
        $years[] = $tahun;
    }
}


$sql = "
    SELECT b.nama_barang, s.kode_prd, s.harga_beli, s.harga_jual, SUM(bk.jumlah) AS jumlah_terjual, (s.harga_jual * SUM(bk.jumlah)) AS keuntungan_pendapatan, ((s.harga_jual - s.harga_beli) * SUM(bk.jumlah))keuntungan_bersih
    FROM barang_keluar bk
    JOIN stok s ON bk.kode_prd = s.kode_prd
    JOIN barang b ON s.barang_id = b.barang_id
    GROUP BY b.nama_barang, s.harga_jual
";
$result = $connection->query($sql);
$profits = [];
$totalProfit1 = 0;
$totalProfit2 = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $profits[] = $row;
        $totalProfit1 += $row['keuntungan_pendapatan'];
        $totalProfit2 += $row['keuntungan_bersih'];
    }
}
mysqli_close($connection);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Toko Mainan</title>
    <link href="../css/bootstrap/css/bt.css" rel="stylesheet">
    <link href="../css/aws/css/all.min.css" rel="stylesheet">
    <link href="../css/dashboard.css" rel="stylesheet">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>

<body id="page-top">
    <div id="wrapper">
        <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion" id="accordionSidebar">
            <a class="sidebar-brand d-flex align-items-center justify-content-center" href="dashboard.php">
                <div class="sidebar-brand-icon">
                    <img src="../aset/logo/shop-white.png" alt="Brand Logo" style="width: 50px; height: 50px;">
                </div>
                <div class="sidebar-brand-text mx-3">AyuToys</div>
            </a>

            <hr class="sidebar-divider my-0">
            <li class="nav-item active">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="sisa_stok.php">
                    <i class="fas fa-fw fa-box"></i>
                    <span>Stok</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">
                Barang Masuk & Keluar
            </div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseOne"
                    aria-expanded="true" aria-controls="collapseOne">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>Barang Masuk</span>
                </a>
                <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
                    <div class="bg-primary py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Barang Masuk:</h6>
                        <a class="collapse-item text-white" href="barang_masuk.php">Barang Masuk</a>
                        <a class="collapse-item text-white" href="tambah_barangmasuk.php">Tambah Barang Masuk</a>
                    </div>
                </div>

                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>Barang Keluar</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-primary py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Barang Keluar:</h6>
                        <a class="collapse-item text-white" href="barang_keluar.php">Barang Keluar</a>
                        <a class="collapse-item text-white" href="tambah_barangkeluar.php">Tambah Barang Keluar</a>
                    </div>
                </div>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">
                Laporan
            </div>
            <li class="nav-item">
                <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseThree"
                    aria-expanded="true" aria-controls="collapseThree">
                    <i class="fas fa-book fa-fw"></i>
                    <span>Laporan</span>
                </a>
                <div id="collapseThree" class="collapse" aria-labelledby="headingThree" data-parent="#accordionSidebar">
                    <div class="bg-primary py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Laporan Barang:</h6>
                        <a class="collapse-item text-white" href="Laporan_barang_masuk.php">Laporan Barang Masuk</a>
                        <a class="collapse-item text-white" href="laporan_barang_keluar.php">Laporan Barang Keluar</a>
                    </div>
                </div>
            </li>
            <hr class="sidebar-divider">
        </ul>

        <div id="content-wrapper" class="d-flex flex-column">
            <div id="content">
                <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">
                    <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
                            <i class="fa fa-bars"></i>
                    </button>
                    <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {echo "<div class='success'>Login Berhasil Sebagai Staff !</div>";unset($_SESSION['logged_in']);}?>
                    <ul class="navbar-nav ml-auto">

                        <div class="topbar-divider d-none d-sm-block"></div>
                        <li class="nav-item dropdown no-arrow">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button"
                                data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <span class="mr-2 d-none d-lg-inline text-gray-600 small"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></span>
                                <img class="img-profile rounded-circle" src="<?php echo htmlspecialchars($_SESSION['photo']); ?>" alt="Profile Photo">

                            </a>
                            <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in"
                                aria-labelledby="userDropdown">
                                <a class="dropdown-item" href="profil.php">
                                    <i class="fas fa-user fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Profil
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="../script/logout.php">
                                    <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                                    Keluar
                                </a>
                            </div>
                        </li>
                    </ul>
                </nav>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
                    </div>
                    
                    <div class="row">
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-primary shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                Total Barang
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo htmlspecialchars($totals['total_barang']); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-box fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-success shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                Total Admin
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo htmlspecialchars($totals['total_admin']); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-user-shield fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-info shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                Total Staff
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo htmlspecialchars($totals['total_staff']); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-users fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-xl-3 col-md-6 mb-4">
                            <div class="card border-left-warning shadow h-100 py-2">
                                <div class="card-body">
                                    <div class="row no-gutters align-items-center">
                                        <div class="col mr-2">
                                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                Total Supplier
                                            </div>
                                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                <?php echo htmlspecialchars($totals['total_supplier']); ?>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-truck fa-2x text-gray-300"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12 col-md-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Barang Paling Banyak Di Minati</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive table-scroll">
                                        <table class="table table-bordered table-striped" id="dataTable" width="100%" cellspacing="0">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Nama Barang</th>
                                                    <th>Total Barang Yang Dikeluarkan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php $no = 1; foreach ($popularItems as $item): ?>
                                                <tr>
                                                    <td><?php echo $no++; ?></td>
                                                    <td><?php echo htmlspecialchars($item['nama_barang']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['total_terjual']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="chart-wrapper">
                                        <div class="chart-container">
                                            <canvas id="popularItemsChart"></canvas>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-12">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Grafik Barang Keluar</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-group">
                                        <label for="yearSelect">Pilih Tahun:</label>
                                        <select class="form-control" id="yearSelect">
                                            <?php foreach ($years as $year): ?>
                                                <option value="<?php echo htmlspecialchars($year); ?>"><?php echo htmlspecialchars($year); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="row">
                                        <div class="col-lg-6 col-md-12 mb-4">
                                            <h3 class="text-center mb-4">Pie Chart</h3>
                                            <div class="canvas-container">
                                                <canvas id="pieChart"></canvas>
                                            </div>
                                        </div>
                                        <div class="col-lg-6 col-md-12 mb-4">
                                            <h3 class="text-center mb-4">Line Chart</h3>
                                            <div class="canvas-container">
                                                <canvas id="lineChart"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Keuntungan Setiap Barang</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <input type="text" id="searchInput" class="form-control" placeholder="Cari barang...">
                                    </div>
                                    <div class="table-responsive table-scroll">
                                        <table class="table table-bordered table-striped" id="profitTable" width="100%" cellspacing="0">
                                            <thead class="bg-gradient-primary text-white">
                                                <tr>
                                                    <th style="text-align: center;">Kode Produk</th>
                                                    <th style="text-align: center;">Nama Barang</th>
                                                    <th style="text-align: center;">Harga Beli</th>
                                                    <th style="text-align: center;">Harga Jual</th>
                                                    <th style="text-align: center;">Jumlah Terjual</th>
                                                    <th style="text-align: center;">Keuntungan Pendapatan</th>
                                                    <th style="text-align: center;">Keuntungan Bersih</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($profits as $profit): ?>
                                                <tr>
                                                    <td style="text-align: center;"><?php echo htmlspecialchars($profit['kode_prd']); ?></td>
                                                    <td><?php echo htmlspecialchars($profit['nama_barang']); ?></td>
                                                    <td><?php echo 'Rp ' . number_format(htmlspecialchars($profit['harga_beli']), 2, ',', '.'); ?></td>
                                                    <td><?php echo 'Rp ' . number_format(htmlspecialchars($profit['harga_jual']), 2, ',', '.'); ?></td>
                                                    <td style="text-align: center;"><?php echo htmlspecialchars($profit['jumlah_terjual']); ?></td>
                                                    <td><?php echo 'Rp ' . number_format(htmlspecialchars($profit['keuntungan_pendapatan']), 2, ',', '.'); ?></td>
                                                    <td><?php echo 'Rp ' . number_format(htmlspecialchars($profit['keuntungan_bersih']), 2, ',', '.'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-12 mb-4">
                            <div class="card shadow mb-4">
                                <div class="card-header py-3">
                                    <h6 class="m-0 font-weight-bold text-primary">Keuntungan Keseluruhan</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="font-weight-bold text-gray-800">
                                            <span>Pendapatan</span>
                                        </div>
                                        <div class="font-weight-bold text-gray-800">
                                            <span id="totalProfit1"><?php echo 'Rp ' . number_format(htmlspecialchars($totalProfit1), 2, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="font-weight-bold text-gray-800">
                                            <span>Bersih</span>
                                        </div>
                                        <div class="font-weight-bold text-gray-800">
                                            <span id="totalProfit2"><?php echo 'Rp ' . number_format(htmlspecialchars($totalProfit2), 2, ',', '.'); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="sticky-footer bg-white">
                <div class="container my-auto">
                    <div class="copyright text-center my-auto">
                        <span>AyuToys&copy;2024</span>
                    </div>
                </div>
            </footer>
        </div>
    </div>
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script>
        $(document).ready(function(){
            $('#sidebarToggleTop').click(function(){
                $('#accordionSidebar').toggleClass('toggled');
            });
            $('.dropdown-item.logout').click(function(e) {
                e.preventDefault();
                $.get($(this).attr('href'), function(data) {
                    window.location.href = '../index.php';
                });
            });
            $('.dropdown-item.profil').click(function(e) {
                e.preventDefault();
                $.get($(this).attr('href'), function(data) {
                    window.location.href = 'profil.php';
                });
            });
            if (localStorage.getItem("loggedIn")) {
                alert("Login Berhasil Sebagai Staff !");
                localStorage.removeItem("loggedIn");
            }
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const salesData = <?php echo json_encode($salesData); ?>;
            const years = <?php echo json_encode($years); ?>;
            const colorPalette = [
                '#FF6384', '#36A2EB', '#FFCE56', '#FF9F40', '#FFCD56', 
                '#4BC0C0', '#9966FF', '#FFB6C1', '#C2C2C2', '#E7E9ED', 
                '#F7464A', '#46BFBD', '#FDB45C', '#949FB1', '#4D5360',
                '#F39C12', '#E74C3C', '#1F77B4', '#2CA02C', '#D62728',
                '#BCBD22', '#17BECF', '#7F7F7F', '#FF7F0E', '#E5F5F9'
            ];

            function generateChartData(year) {
                const labels = [];
                const data = [];
                const lineData = [];
                const pieData = [];
                const itemColors = {};
                let colorIndex = 0;
                for (const itemName in salesData) {
                    const monthlySales = salesData[itemName][year] || {};
                    const itemData = [];
                    for (let i = 1; i <= 12; i++) {
                        if (!labels.includes(`Bulan ${i}`)) {
                            labels.push(`Bulan ${i}`);
                        }
                        itemData.push(monthlySales[i] || 0);
                    }
                    const color = colorPalette[colorIndex % colorPalette.length];
                    itemColors[itemName] = color;
                    lineData.push({
                        label: itemName,
                        data: itemData,
                        borderColor: color,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderWidth: 2
                    });
                    pieData.push({
                        label: itemName,
                        data: itemData.reduce((a, b) => a + b, 0),
                        backgroundColor: color,
                        hoverBackgroundColor: color.replace('hsl', 'rgba').replace(')', ', 0.8)').replace('hsl(', 'rgba(')
                    });
                    colorIndex++;
                }
                return { labels, lineData, pieData };
            }

            function updateCharts(year) {
                const { labels, lineData, pieData } = generateChartData(year);
                lineChart.data.labels = labels;
                lineChart.data.datasets = lineData;
                lineChart.update();
                pieChart.data.labels = pieData.map(item => item.label);
                pieChart.data.datasets = [{
                    data: pieData.map(item => item.data),
                    backgroundColor: pieData.map(item => item.backgroundColor),
                    hoverBackgroundColor: pieData.map(item => item.hoverBackgroundColor)
                }];
                pieChart.update();
            }

            const ctxLine = document.getElementById('lineChart').getContext('2d');
            const lineChart = new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: []
                },
                options: {
                    scales: {
                        x: {
                            beginAtZero: true
                        },
                        y: {
                            beginAtZero: true
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            const ctxPie = document.getElementById('pieChart').getContext('2d');
            const pieChart = new Chart(ctxPie, {
                type: 'pie',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [],
                        hoverBackgroundColor: []
                    }]
                },
                options: {
                    plugins: {
                        legend: {
                            position: 'bottom',
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (context.parsed !== null) {
                                        const correctedValue = context.parsed / 10000;
                                        label += ': ' + correctedValue.toFixed(0);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            const defaultYear = years[0];
            updateCharts(defaultYear);
            document.getElementById('yearSelect').addEventListener('change', function() {
                const selectedYear = this.value;
                updateCharts(selectedYear);
            });
            const originalProfit1 = parseFloat($('#totalProfit1').text().replace(/[^0-9,-]/g, '').replace(',', '.'));
            const originalProfit2 = parseFloat($('#totalProfit2').text().replace(/[^0-9,-]/g, '').replace(',', '.'));
            $('#searchInput').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                let totalProfit1 = 0;
                let totalProfit2 = 0;
                $('#profitTable tbody tr').each(function() {
                    const cells = $(this).find('td');
                    const itemName = cells.eq(1).text().toLowerCase();
                    if (itemName.includes(searchTerm)) {
                        $(this).show();
                        const profit1 = parseFloat(cells.eq(5).text().replace(/[^0-9,-]/g, '').replace(',', '.'));
                        const profit2 = parseFloat(cells.eq(6).text().replace(/[^0-9,-]/g, '').replace(',', '.'));
                        totalProfit1 += profit1;
                        totalProfit2 += profit2;
                    } else {
                        $(this).hide();
                    }
                });
                $('#totalProfit1').text('Rp ' + totalProfit1.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                $('#totalProfit2').text('Rp ' + totalProfit2.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                if (searchTerm.trim() === '') {
                    $('#totalProfit1').text('Rp ' + originalProfit1.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                    $('#totalProfit2').text('Rp ' + originalProfit2.toLocaleString('id-ID', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
                }
            });
        });
    </script>
    <script>
        var ctx = document.getElementById('popularItemsChart').getContext('2d');
        var popularItemsChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($popularItems, 'nama_barang')); ?>,
                datasets: [
                    {
                        label: 'Total Barang Yang Dikeluarkan (Bar)',
                        data: <?php echo json_encode(array_column($popularItems, 'total_terjual')); ?>,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1,
                        type: 'bar'
                    },
                    {
                        label: 'Total Barang Yang Dikeluarkan (Line)',
                        data: <?php echo json_encode(array_column($popularItems, 'total_terjual')); ?>,
                        borderColor: 'rgba(255, 99, 132, 1)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        type: 'line',
                        fill: false
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    },
                    x: {
                        ticks: {
                            autoSkip: false
                        }
                    }
                },
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                return tooltipItem.label + ': ' + tooltipItem.raw;
                            }
                        }
                    }
                },
                onClick: function(event, elements) {
                    if (elements.length > 0) {
                        var firstElement = elements[0];
                        var label = popularItemsChart.data.labels[firstElement.index];
                        var value = popularItemsChart.data.datasets[firstElement.datasetIndex].data[firstElement.index];
                        alert('Item: ' + label + '\nTotal: ' + value);
                    }
                }
            }
        });
    </script>
</body>
</html>