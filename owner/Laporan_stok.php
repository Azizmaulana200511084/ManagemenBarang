<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'owner') {
    header('Location: ../index.php');
    exit;
}

include_once "../db/db.php";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $barang_id = isset($_POST['barang_id']) ? intval($_POST['barang_id']) : 0;
    $aktif = isset($_POST['aktif']) ? $_POST['aktif'] : 'tidak';
    if ($barang_id > 0) {
        $stmt = $connection->prepare("UPDATE barang SET aktif = ? WHERE barang_id = ?");
        $stmt->bind_param("si", $aktif, $barang_id);
        if ($stmt->execute()) {
            echo "<script>alert('Status Berhasil Diganti');</script>";
        } else {
            echo "<script>alert('Status Gagal Di Diganti');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Invalid');</script>";
    }
}

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

$search_keyword = isset($_GET['search_keyword']) ? $_GET['search_keyword'] : '';
$barang_query = "SELECT barang_id, nama_barang, kategori, stok, satuan, dibuat, photo, aktif FROM barang";
$where_clause = '';
$params = [];
$types = '';
if (!empty($search_keyword)) {
    $where_clause .= " WHERE nama_barang LIKE ? OR kategori LIKE ?";
    $params[] = "%$search_keyword%";
    $params[] = "%$search_keyword%";
    $types = str_repeat('s', count($params));
}
$barang_query .= $where_clause . " ORDER BY nama_barang ASC";
$stmt = $connection->prepare($barang_query);
if ($where_clause) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$barang_result = $stmt->get_result();
$barangs = [];
if ($barang_result && $barang_result->num_rows > 0) {
    while ($row = $barang_result->fetch_assoc()) {
        $barangs[] = $row;
    }
}

mysqli_close($connection);

$total_records = count($barangs);
$records_per_page = 10;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max($current_page, 1);
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $records_per_page;
$current_page_data = array_slice($barangs, $offset, $records_per_page);
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
    <link href="../css/barang.css" rel="stylesheet">
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
                        <a class="collapse-item text-white font-weight-bold" href="Laporan_stok.php">Laporan Persediaan</a>
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
                        <h1 class="h3 mb-0 text-gray-800">Laporan Data Stok</h1>
                        &nbsp<br>
                    </div>
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <a href="../script/cetak_laporan_stok.php" class="btn btn-primary">
                            <i class="fas fa-download fa-sm text-white-50"></i> Cetak PDF
                        </a>
                    </div>
                    <div class="container-fluid">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-gradient-primary text-white">
                                    <tr style="text-align: center;">
                                        <th>No</th>
                                        <th>Nama Barang</th>
                                        <th>Kategori</th>
                                        <th>Stok</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($barangs)) : ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php foreach ($current_page_data as $barang) : ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="text-align: right; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo $no++; ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['kategori'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['stok'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <?php if ($barang['stok'] > 0) : ?>
                                                        <span style="color: green;"><strong>Masih Ada</strong></span>
                                                    <?php else : ?>
                                                        <span style="color: red;"><strong>Habis</strong></span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5">No barangs found.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-center">
                                    <?php if ($current_page > 1) : ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $current_page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                                <span class="sr-only">Previous</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                        <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($current_page < $total_pages) : ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $current_page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                                <span class="sr-only">Next</span>
                                            </a>
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