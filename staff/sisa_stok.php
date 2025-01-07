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

if (isset($_POST['add_stok'])) {
    $barang_masuk_id = mysqli_real_escape_string($connection, $_POST['barang_masuk_id']);
    $kode_bm = mysqli_real_escape_string($connection, $_POST['kode_bm']);
    $kode_prd = mysqli_real_escape_string($connection, $_POST['kode_prd']);
    $barang_id = mysqli_real_escape_string($connection, $_POST['barang_id']);
    $supplier_id = mysqli_real_escape_string($connection, $_POST['supplier_id']);
    $harga_beli = mysqli_real_escape_string($connection, $_POST['harga_beli']);
    $harga_jual = mysqli_real_escape_string($connection, $_POST['harga_jual']);
    $stok = mysqli_real_escape_string($connection, $_POST['stok']);
    $stok_masuk = isset($_POST['stok_masuk']) ? mysqli_real_escape_string($connection, $_POST['stok_masuk']) : 0;
    $stok_akhir = isset($_POST['stok_akhir']) ? mysqli_real_escape_string($connection, $_POST['stok_akhir']) : 0;
    $lokasi_penyimpanan = mysqli_real_escape_string($connection, $_POST['lokasi_penyimpanan']);
    $tanggal_masuk = mysqli_real_escape_string($connection, $_POST['tanggal_masuk']);
    if (empty($kode_prd)) {
        echo "Error: Kode PRD tidak boleh kosong";
        exit;
    }
    mysqli_autocommit($connection, false);

    $query2 = "INSERT INTO stok (barang_masuk_id, kode_bm, kode_prd, barang_id, supplier_id, user_id, harga_beli, harga_jual, stok, stok_masuk, stok_akhir, lokasi_penyimpanan, tanggal_masuk)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt2 = $connection->prepare($query2);
    if ($stmt2 === false) {
        die("Prepare failed: " . $connection->error);
    }
    $stmt2->bind_param("issiiidddiiss", $barang_masuk_id, $kode_bm, $kode_prd, $barang_id, $supplier_id, $user_id, $harga_beli, $harga_jual, $stok, $stok_masuk, $stok_akhir, $lokasi_penyimpanan, $tanggal_masuk);
    if (!$stmt2->execute()) {
        mysqli_rollback($connection);
        echo "Gagal menambahkan ke stok: " . $stmt2->error;
    } else {
        mysqli_commit($connection);
        echo "<script>alert('Berhasil Ditambahkan');</script>";
    }
    mysqli_autocommit($connection, true);
}

$sort_column = isset($_GET['sort_column']) ? $_GET['sort_column'] : 'b.nama_barang';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'ASC';
$search_keyword = isset($_GET['search_keyword']) ? $_GET['search_keyword'] : '';
$show_zero_stok = isset($_GET['show_zero_stok']) ? $_GET['show_zero_stok'] === 'true' : false;
$barang_query = "SELECT s.id_stok, bm.barang_masuk_id, bm.kode_bm, s.kode_prd, b.barang_id, s.supplier_id, r.nama_supplier, b.nama_barang, bm.harga_beli, s.harga_jual, s.stok AS stok_awal, s.stok_masuk, s.stok_akhir, s.lokasi_penyimpanan, s.tanggal_masuk
                 FROM stok s 
                 JOIN barang b ON s.barang_id = b.barang_id
                 JOIN supplier r ON s.supplier_id = r.supplier_id
                 JOIN barang_masuk bm ON s.barang_masuk_id = bm.barang_masuk_id
                 JOIN users u ON bm.user_id = u.user_id";

$where_clause = '';
if (!empty($search_keyword)) {
    $where_clause .= " AND (b.nama_barang LIKE ? OR s.kode_prd LIKE ? OR bm.kode_bm LIKE ? OR s.lokasi_penyimpanan LIKE ?)";
}
if (!$show_zero_stok) {
    $where_clause .= " AND s.stok_masuk > 0";
}
$barang_query .= $where_clause . " ORDER BY $sort_column $sort_order";
$stmt = $connection->prepare($barang_query);
if (!empty($search_keyword)) {
    $search_keyword = "%{$search_keyword}%";
    $stmt->bind_param("ssss", $search_keyword, $search_keyword, $search_keyword, $search_keyword);
}
$stmt->execute();
$result = $stmt->get_result();
$barangs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$query_barang = "SELECT bm.barang_masuk_id, bm.user_id, bm.kode_bm, b.barang_id, b.nama_barang, bm.supplier_id, s.nama_supplier, bm.stok, bm.jumlah, bm.harga_beli, bm.tanggal_masuk, bm.dibuat 
                FROM barang_masuk bm
                JOIN barang b ON bm.barang_id = b.barang_id
                JOIN supplier s ON bm.supplier_id = s.supplier_id
                WHERE bm.barang_masuk_id NOT IN (SELECT DISTINCT barang_masuk_id FROM stok)";
$result_barang = mysqli_query($connection, $query_barang);
$barang_options = "";
if (mysqli_num_rows($result_barang) > 0) {
    while ($row_barang = mysqli_fetch_assoc($result_barang)) {
        $barang_masuk_id = $row_barang['barang_masuk_id'];
        $user_id = $row_barang['user_id'];
        $kode_bm = htmlspecialchars($row_barang['kode_bm'], ENT_QUOTES, 'UTF-8');
        $barang_id = $row_barang['barang_id'];
        $nama_barang = htmlspecialchars($row_barang['nama_barang'], ENT_QUOTES, 'UTF-8');
        $nama_supplier = htmlspecialchars($row_barang['nama_supplier'], ENT_QUOTES, 'UTF-8');
        $supplier_id = $row_barang['supplier_id'];
        $stok = htmlspecialchars($row_barang['stok'], ENT_QUOTES, 'UTF-8');
        $jumlah = htmlspecialchars($row_barang['jumlah'], ENT_QUOTES, 'UTF-8');
        $harga_beli = htmlspecialchars($row_barang['harga_beli'], ENT_QUOTES, 'UTF-8');
        $tanggal_masuk = htmlspecialchars($row_barang['tanggal_masuk'], ENT_QUOTES, 'UTF-8');
        $dibuat = htmlspecialchars($row_barang['dibuat'], ENT_QUOTES, 'UTF-8');
        $date = new DateTime($tanggal_masuk);
        $formatted_date = $date->format('d-m-y');
        $option_label = "$nama_barang, ($nama_supplier-$formatted_date)";
        $barang_options .= "<option value=\"$barang_masuk_id\" data-brg=\"$barang_id\" data-bm=\"$kode_bm\" data-sp=\"$supplier_id\" data-price=\"$harga_beli\" data-stok=\"$stok\" data-tm=\"$tanggal_masuk\" data-stokmsk=\"$jumlah\">$option_label</option>";
    }
}

mysqli_close($connection);
$total_records = count($barangs);
$records_per_page = 5;
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
    <link href="../css/ss.css" rel="stylesheet">
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
            <li class="nav-item active">
                <a class="nav-link" href="sisa_stok.php">
                    <i class="fas fa-fw fa-box"></i>
                    <span>Stok</span>
                </a>
            </li>
            <hr class="sidebar-divider">
            <div class="sidebar-heading">Barang Masuk & Keluar</div>
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

                <div class="container">
                    <ul class="nav justify-content-center">
                        <li class="nav-item1">
                            <a class="nav-link1 active" aria-current="page" href="sisa_stok.php">Stok</a>
                        </li>
                        <li class="nav-item1">
                            <a class="nav-link1" href="stok_opname.php">StokOpName</a>
                        </li>
                    </ul>
                </div>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Data Stok</h1>
                        &nbsp<br>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addStokModal">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <hr>
                        <form method="get" action="">
                            <div class="input-group mb-3">
                                <input type="text" class="form-control" id="search_keyword" name="search_keyword" placeholder="Cari Barang,PRD,BM,Penyimpanan" aria-label="Cari Barang atau PRD atau BM atau Penyimpanan" aria-describedby="button-addon2">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-primary" id="button-addon2"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="filter-stock">
                                    <label class="custom-control-label" for="filter-stock">
                                        <span class="switch-label">Tampilkan data stok habis</span>
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="container-fluid">
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="bg-gradient-primary text-white">
                                    <tr style="text-align: center;">
                                        <th>No</th>
                                        <th>Kode Produk</th>
                                        <th>Kode Barang Masuk</th>
                                        <th data-sort-column="b.nama_barang" class="sortable">Nama Barang <i class="fas fa-sort"></i></th>
                                        <th>Nama Supplier</th>
                                        <th>Stok</th>
                                        <th>Harga Jual</th>
                                        <th>Penyimpanan</th>
                                        <th data-sort-column="s.tanggal_masuk" class="sortable">Tanggal <i class="fas fa-sort"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($barangs)) : ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php foreach ($current_page_data as $barang) : ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="text-align: right; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo $no++; ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['kode_prd'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['kode_bm'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['nama_supplier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; font-weight: bold;"><?php echo htmlspecialchars($barang['stok_masuk'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo 'Rp.' . htmlspecialchars($barang['harga_jual'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['lokasi_penyimpanan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-right: 1px solid black;"><?php echo htmlspecialchars($barang['tanggal_masuk'], ENT_QUOTES, 'UTF-8'); ?></td>
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

    <div class="modal fade" id="addStokModal" tabindex="-1" role="dialog" aria-labelledby="addStokModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addStokModalLabel">Tambah Stok</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                        <div class="form-group">
                            <label for="kode_prd">Kode PRD</label>
                            <input type="text" class="form-control" id="kode_prd" name="kode_prd" required readonly>
                        </div>
                        <input type="hidden" id="barang_id" name="barang_id" required>
                        <div class="form-group">
                            <label for="barang_masuk_id">Barang</label>
                            <select class="form-control" id="barang_masuk_id" name="barang_masuk_id" required>
                                <option value="" disabled selected>Pilih Barang</option>
                                <?php echo $barang_options; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="kode_bm">Kode BM</label>
                            <input type="text" class="form-control" id="kode_bm" name="kode_bm" required readonly>
                        </div>
                        <div class="form-group" style="display:none;">
                            <label for="supplier_id">Supplier ID</label>
                            <input type="hidden" class="form-control" id="supplier_id" name="supplier_id" required readonly>
                        </div>
                        <div class="form-row">
                            <div class="form-group col">
                                <label for="harga_beli">Harga Beli</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control" id="harga_beli" name="harga_beli" required readonly>
                                </div>
                            </div>
                            <div class="form-group col">
                                <label for="harga_jual">Harga Jual</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" step="0.01" class="form-control" id="harga_jual" name="harga_jual" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                </div>
                            </div>
                        </div>
                        <div class="form-row" style="display:none;">
                            <div class="form-group col">
                                <label for="stok">Stok</label>
                                <input type="number" class="form-control" id="stok" name="stok" required readonly>
                            </div>
                            <div class="form-group col">
                                <label for="stok_masuk">Stok Masuk</label>
                                <input type="number" class="form-control" id="stok_masuk" name="stok_masuk" readonly>
                            </div>
                            <div class="form-group col">
                                <label for="stok_akhir">Stok Akhir</label>
                                <input type="number" class="form-control" id="stok_akhir" name="stok_akhir" readonly>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="lokasi_penyimpanan">Lokasi Penyimpanan</label>
                            <input type="text" class="form-control" id="lokasi_penyimpanan" name="lokasi_penyimpanan" required>
                        </div>
                        <div class="form-group" style="display:none;">
                            <label for="tanggal_masuk">Tanggal</label>
                            <input type="date" class="form-control" id="tanggal_masuk" name="tanggal_masuk" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="add_stok">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
            $('#addBarangModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            function getRandomInt(min, max) {
                return Math.floor(Math.random() * (max - min + 1)) + min;
            }
            var userId = document.getElementById('user_id').value.trim();
            if (!userId) {
                console.error('User ID is missing or invalid');
                return;
            }
            var randomNum = getRandomInt(1000, 9999);
            var now = new Date();
            var year = now.getFullYear();
            var month = ('0' + (now.getMonth() + 1)).slice(-2);
            var day = ('0' + now.getDate()).slice(-2);
            var formattedDate = day + month + year;
            var kodeSpn = 'PRD' + userId + randomNum + formattedDate;
            document.getElementById('kode_prd').value = kodeSpn;
        });


        function updateBM() {
            var selectedBarang = document.getElementById("barang_masuk_id");
            var selectedOption = selectedBarang.options[selectedBarang.selectedIndex];
            var kodeBmInput = document.getElementById("kode_bm");
            var supplierInput = document.getElementById("supplier_id");
            var hargaBeliInput = document.getElementById("harga_beli");
            var barangIdInput = document.getElementById("barang_id");
            var stokInput = document.getElementById("stok");
            var stokMasukInput = document.getElementById("stok_masuk");
            var tanggalMasukInput = document.getElementById("tanggal_masuk");
            var kode_bm = selectedOption.getAttribute("data-bm");
            var supplier_id = selectedOption.getAttribute("data-sp");
            var tanggal_masuk = selectedOption.getAttribute("data-tm");
            var hrg = selectedOption.getAttribute("data-price");
            var brgid = selectedOption.getAttribute("data-brg");
            var stk = selectedOption.getAttribute("data-stok");
            var stkmsk = selectedOption.getAttribute("data-stokmsk");
            kodeBmInput.value = kode_bm;
            supplierInput.value = supplier_id;
            hargaBeliInput.value = hrg;
            barangIdInput.value = brgid;
            stokInput.value = stk;
            stokMasukInput.value = stkmsk;
            tanggalMasukInput.value = tanggal_masuk;
            updateStokAkhir();
        }

        function updateStokAkhir() {
            var stok = parseFloat(document.getElementById('stok').value) || 0;
            var stokMasuk = parseFloat(document.getElementById('stok_masuk').value) || 0;
            var stokAkhir = stok + stokMasuk;
            document.getElementById('stok_akhir').value = stokAkhir;
        }

        document.getElementById('barang_masuk_id').addEventListener('change', updateBM);
        document.getElementById('stok').addEventListener('input', updateStokAkhir);
        document.getElementById('stok_masuk').addEventListener('input', updateStokAkhir);

        const headers = document.querySelectorAll('.sortable');
            headers.forEach(header => {
                header.addEventListener('click', function () {
                    const sortColumn = this.getAttribute('data-sort-column');
                    const currentSortOrder = this.classList.contains('asc') ? 'desc' : 'asc';
                    const urlParams = new URLSearchParams(window.location.search);
                    urlParams.set('sort_column', sortColumn);
                    urlParams.set('sort_order', currentSortOrder);

                    window.location.search = urlParams.toString();
                });
            });
            headers.forEach(header => {
                const sortColumn = header.getAttribute('data-sort-column');
                const urlParams = new URLSearchParams(window.location.search);
                if (sortColumn === urlParams.get('sort_column')) {
                    header.classList.add(urlParams.get('sort_order') === 'asc' ? 'asc' : 'desc');
                    const icon = header.querySelector('i');
                    icon.classList.remove('fa-sort');
                    if (header.classList.contains('asc')) {
                        icon.classList.add('fa-sort-up');
                    } else {
                        icon.classList.add('fa-sort-down');
                    }
                }
            });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const showZeroStock = urlParams.get('show_zero_stok') === 'true';
            document.getElementById('filter-stock').checked = showZeroStock;
        });
        document.getElementById('filter-stock').addEventListener('change', function() {
            const showZeroStock = this.checked;
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('show_zero_stok', showZeroStock);
            window.location.search = urlParams.toString();
        });
    </script>
</body>
</html>