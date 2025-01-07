<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
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

$search_keyword = isset($_GET['search_keyword']) ? mysqli_real_escape_string($connection, $_GET['search_keyword']) : '';
$barang_query = "SELECT bm.barang_masuk_id, bm.user_id, bm.kode_bm, bm.barang_id, bm.supplier_id, b.nama_barang, b.stok, b.satuan, s.nama_supplier, bm.stok, bm.jumlah, bm.harga_beli, bm.tanggal_masuk, bm.dibuat 
                 FROM barang_masuk bm 
                 JOIN barang b ON bm.barang_id = b.barang_id 
                 JOIN supplier s ON bm.supplier_id = s.supplier_id";
$where_clause = '';
if (!empty($search_keyword)) {
    $where_clause .= " WHERE b.nama_barang LIKE ? OR s.nama_supplier LIKE ? OR bm.kode_bm LIKE ?";
}
$barang_query .= $where_clause . " ORDER BY bm.barang_masuk_id DESC";
$stmt = $connection->prepare($barang_query);
if (!empty($search_keyword)) {
    $search_keyword = "%{$search_keyword}%";
    $stmt->bind_param("sss", $search_keyword, $search_keyword, $search_keyword);
}
$stmt->execute();
$result = $stmt->get_result();
$barangs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

mysqli_close($connection);

$grouped_data = [];
foreach ($barangs as $barang) {
    $kode_bm = $barang['kode_bm'];
    if (!isset($grouped_data[$kode_bm])) {
        $grouped_data[$kode_bm] = [
            'kode_bm' => $kode_bm,
            'tanggal_masuk' => $barang['tanggal_masuk'],
            'nama_barang' => $barang['nama_barang'],
            'nama_supplier' => $barang['nama_supplier'],
            'jumlah' => $barang['jumlah'],
            'harga_beli' => $barang['harga_beli'],
            'items' => []
        ];
    }
    $grouped_data[$kode_bm]['items'][] = $barang;
}

$total_kode_bm = count($grouped_data);
$records_per_page = 5;
$total_pages = ceil($total_kode_bm / $records_per_page);
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $records_per_page;
$current_page_data = array_slice($grouped_data, $offset, $records_per_page);

$serial_number_start = $total_kode_bm - $offset;
$serial_number = $serial_number_start;
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
    <link href="../css/bm.css" rel="stylesheet">
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
            <li class="nav-item">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-fw fa-tachometer-alt"></i>
                    <span>Dashboard</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="barang_stok.php">
                    <i class="fas fa-fw fa-box"></i>
                    <span>Data Barang</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="supplier.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Data Supplier</span></a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="userstaff.php">
                    <i class="fas fa-fw fa-users"></i>
                    <span>Data UserSaff</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Barang Masuk & Keluar</div>
            <li class="nav-item">
                <a style="font-weight: bold;" class="nav-link collapsed active" href="#" data-toggle="collapse" data-target="#collapseOne"
                    aria-expanded="true" aria-controls="collapseOne">
                    <i class="fas fa-fw fa-exchange-alt"></i>
                    <span>Barang Masuk</span>
                </a>
                <div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-parent="#accordionSidebar">
                    <div class="bg-primary py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Barang Masuk:</h6>
                        <a class="collapse-item text-white font-weight-bold" href="barang_masuk.php">Barang Masuk</a>
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
            <div class="sidebar-heading">Laporan</div>
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
                    <?php
                    if (isset($_SESSION['bm']) && $_SESSION['bm']) {
                        echo "<div class='success'>" . $_SESSION['message'] . "</div>";
                        unset($_SESSION['bm']);
                        unset($_SESSION['message']);
                    }
                    ?>
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
                        <h1 class="h3 mb-0 text-gray-800">Data Barang Masuk</h1>
                        &nbsp<br>
                        <form method="get" action="">
                            <div class="input-group mb-3" style="border: 1px solid blue; border-radius: 6px;">
                                <input type="text" class="form-control" id="search_keyword" name="search_keyword" placeholder="Cari Kode, Barang, Supplier" aria-label="Cari Kode, Barang, Supplier" aria-describedby="button-addon2">
                                <button type="submit" class="btn btn-primary" id="button-addon2"><i class="fas fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                    <div class="container-fluid">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-gradient-primary text-white">
                                    <tr style="text-align: center;">
                                        <th>No</th>
                                        <th>Kode BM</th>
                                        <th>Tanggal</th>
                                        <th>Nama Supplier</th>
                                        <th>Nama Barang</th>
                                        <th>Jumblah</th>
                                        <th>Harga Beli</th>
                                        <th class="print-ignore">Cetak</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    foreach ($current_page_data as $group) {
                                        $rowspan = count($group['items']);
                                        $is_first_row = true;

                                        foreach ($group['items'] as $item) {
                                            if ($is_first_row) {
                                                echo '<tr class="text-primary" style="border: 1px solid black;">';
                                                echo '<td style="text-align: right;" rowspan="' . $rowspan . '">' . $serial_number-- . '</td>';
                                                echo '<td style="text-align: center;" rowspan="' . $rowspan . '">' . htmlspecialchars($group['kode_bm'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td style="text-align: center;" rowspan="' . $rowspan . '">' . htmlspecialchars($group['tanggal_masuk'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td style="text-align: center;" rowspan="' . $rowspan . '">' . htmlspecialchars($group['nama_supplier'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td>' . htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td style="text-align: center;">' . htmlspecialchars($item['jumlah'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td style="text-align: left;">Rp. ' . htmlspecialchars(number_format($item['harga_beli'], 2), ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars($item['satuan'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td rowspan="' . $rowspan . '" class="print-ignore">';
                                                echo '<a href="../script/cetak_barang_masuk.php?kode_bm=' . urlencode($group['kode_bm']) . '" class="btn btn-sm btn-danger"><i class="fas fa-file-pdf"></i> PDF</a>';
                                                echo '</td>';
                                                echo '</tr>';
                                                $is_first_row = false;
                                            } else {
                                                echo '<tr class="text-primary">';
                                                echo '<td>' . htmlspecialchars($item['nama_barang'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td style="text-align: center;">' . htmlspecialchars($item['jumlah'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '<td style="text-align: left;">Rp. ' . htmlspecialchars(number_format($item['harga_beli'], 2), ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars($item['satuan'], ENT_QUOTES, 'UTF-8') . '</td>';
                                                echo '</tr>';
                                            }
                                        }
                                    }
                                    ?>
                                </tbody>
                            </table>
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-center">
                                    <?php if ($current_page > 1) : ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>">Previous</a>
                                        </li>
                                    <?php endif; ?>

                                    <?php for ($page = 1; $page <= $total_pages; $page++) : ?>
                                        <li class="page-item <?php echo ($page === $current_page) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $page; ?>"><?php echo $page; ?></a>
                                        </li>
                                    <?php endfor; ?>

                                    <?php if ($current_page < $total_pages) : ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>">Next</a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
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
    <script>
        $(document).ready(function() {
            $('#sidebarToggleTop').click(function() {
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
        });
    </script>
</body>
</html>