<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
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

$edit_barang_id = isset($_GET['edit_barang_id']) ? intval($_GET['edit_barang_id']) : 0;
$edit_barang_data = null;
if ($edit_barang_id > 0) {
    $edit_stmt = $connection->prepare("SELECT * FROM barang WHERE barang_id = ?");
    $edit_stmt->bind_param("i", $edit_barang_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_barang_data = $edit_result->fetch_assoc();
    }
    $edit_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_barang'])) {
    $nama_barang = mysqli_real_escape_string($connection, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($connection, $_POST['kategori']);
    $stok = mysqli_real_escape_string($connection, $_POST['stok']);
    $satuan = mysqli_real_escape_string($connection, $_POST['satuan']);
    $dibuat = mysqli_real_escape_string($connection, $_POST['dibuat']);
    $photo_path = '';

    $check_query = "SELECT barang_id FROM barang WHERE nama_barang = ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("s", $nama_barang);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result && $check_result->num_rows > 0) {
        echo "<script>alert('Nama Barang sudah ada. Mohon nama barang lain.');</script>";
    } else {
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $photo_tmp_name = $_FILES['photo']['tmp_name'];
            $photo_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $nama_barang) . date('Y-m-d_H-i-s') . '.' . $photo_extension;
            $upload_dir = '../aset/images/fotobarang/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true); 
            }
            $upload_file = $upload_dir . $photo_name;
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($photo_tmp_name);
            if (in_array($file_type, $allowed_types) && move_uploaded_file($photo_tmp_name, $upload_file)) {
                $photo_path = $upload_file;
            } else {
                echo "<script>alert('Gagal Upload File');</script>";
                $photo_path = '';
            }
        }
        $insert_query = "INSERT INTO barang (nama_barang, kategori, stok, satuan, dibuat, photo) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("ssssss", $nama_barang, $kategori, $stok, $satuan, $dibuat, $photo_path);
            if ($stmt->execute()) {
                echo "<script>alert('Barang Berhasil Ditambahkan');</script>";
            } else {
                echo "<script>alert('Barang Gagal Ditambahkan');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Gagal Menyiapkan');</script>";
        }
    }
    $check_stmt->close();
}


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_barang'])) {
    $barang_id = mysqli_real_escape_string($connection, $_POST['barang_id']);
    $nama_barang = mysqli_real_escape_string($connection, $_POST['nama_barang']);
    $kategori = mysqli_real_escape_string($connection, $_POST['kategori']);
    $stok = mysqli_real_escape_string($connection, $_POST['stok']);
    $satuan = mysqli_real_escape_string($connection, $_POST['satuan']);
    $dibuat = mysqli_real_escape_string($connection, $_POST['dibuat']);
    $aktif = mysqli_real_escape_string($connection, $_POST['aktif']);

    $check_query = "SELECT barang_id FROM barang WHERE nama_barang = ? AND barang_id != ?";
    $check_stmt = $connection->prepare($check_query);
    $check_stmt->bind_param("si", $nama_barang, $barang_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    if ($check_result && $check_result->num_rows > 0) {
        echo "<script>alert('Nama Barang sudah ada. Mohon masukan nama barang lain.');</script>";
    } else {
        $current_photo_query = "SELECT photo FROM barang WHERE barang_id = ?";
        $stmt = $connection->prepare($current_photo_query);
        $stmt->bind_param("i", $barang_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current_photo = '';
        if ($result && $result->num_rows > 0) {
            $current_photo = $result->fetch_assoc()['photo'];
        }
        $stmt->close();
        $photo_path = $current_photo;
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
            $photo_tmp_name = $_FILES['photo']['tmp_name'];
            $photo_extension = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
            $photo_name = preg_replace('/[^a-zA-Z0-9_]/', '_', $nama_barang) . '_' . date('Y-m-d_H-i-s') . '.' . $photo_extension;
            $upload_dir = '../aset/images/fotobarang/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $upload_file = $upload_dir . $photo_name;
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($photo_tmp_name);
            if (in_array($file_type, $allowed_types) && move_uploaded_file($photo_tmp_name, $upload_file)) {
                if (!empty($current_photo) && file_exists($upload_dir . basename($current_photo))) {
                    unlink($upload_dir . basename($current_photo));
                }
                $photo_path = $upload_file;
            } else {
                echo "<script>alert('Gagal Upload File');</script>";
                $photo_path = $current_photo;
            }
        }
        $update_query = "UPDATE barang SET nama_barang=?, kategori=?, stok=?, satuan=?, dibuat=?, photo=?, aktif=? WHERE barang_id=?";
        $stmt = $connection->prepare($update_query);
        if ($stmt) {
            $stmt->bind_param("ssissssi", $nama_barang, $kategori, $stok, $satuan, $dibuat, $photo_path, $aktif, $barang_id);
            if ($stmt->execute()) {
                echo "<script>alert('Barang Berhasil Diperbaharui');</script>";
            } else {
                echo "<script>alert('Gagal Memperbaharui Barang: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Gagal menyiapkan pernyataan');</script>";
        }
    }
    $check_stmt->close();
}


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
            <li class="nav-item active">
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
                            <a class="nav-link1 active" aria-current="page" href="barang_stok.php">DataBarang</a>
                        </li>
                        <li class="nav-item1">
                            <a class="nav-link1" href="sisa_stok.php">Stok</a>
                        </li>
                        <li class="nav-item1">
                            <a class="nav-link1" href="stok_opname.php">StokOpName</a>
                        </li>
                    </ul>
                </div>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Data Barang</h1>
                        &nbsp<br>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addBarangModal">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <hr>
                        <form method="get" action="">
                            <div class="input-group mb-3" style="border: 1px solid blue; border-radius: 6px;">
                                <input type="text" class="form-control" id="search_keyword" name="search_keyword" placeholder="Cari NamaBarang,Kategori" aria-label="Cari Nama Barang atau Kategori" aria-describedby="button-addon2">
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
                                        <th>Photo</th>
                                        <th>Nama Barang</th>
                                        <th style="display:none;">Stok</th>
                                        <th>Kategori</th>
                                        <th>Di Buat</th>
                                        <th>Status</th>
                                        <th class="print-ignore">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($barangs)) : ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php foreach ($current_page_data as $barang) : ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="text-align: right; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo $no++; ?></td>
                                                <td style="border-bottom: 1px solid black; text-align: center;">
                                                    <?php if (!empty($barang['photo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($barang['photo']); ?>" alt="Barang Photo" width="100" height="100">
                                                    <?php else: ?>
                                                        Belum Di Upload
                                                    <?php endif; ?>
                                                </td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; display:none; border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['stok'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($barang['kategori'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-right: 1px solid black;"><?php echo htmlspecialchars($barang['dibuat'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <div class="status-container">
                                                        <span id="status-<?php echo $barang['barang_id']; ?>" class="status"><?php echo $barang['aktif'] == 'aktif' ? 'Aktif' : 'Tidak Aktif'; ?></span>
                                                        <form action="" method="POST">
                                                            <input type="hidden" name="barang_id" value="<?php echo htmlspecialchars($barang['barang_id']); ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <label class="switch">
                                                                <input type="checkbox" name="aktif" value="aktif" <?php echo $barang['aktif'] == 'aktif' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                                <span class="slider round"></span>
                                                            </label>
                                                        </form>
                                                    </div>
                                                </td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;" class="print-ignore">
                                                    <button class="btn btn-sm btn-info edit-btn" 
                                                            data-toggle="modal" 
                                                            data-target="#editBarangModal" 
                                                            data-id="<?php echo $barang['barang_id']; ?>"
                                                            data-photo="<?php echo htmlspecialchars($barang['photo'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-nama="<?php echo htmlspecialchars($barang['nama_barang'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                            data-kategori="<?php echo htmlspecialchars($barang['kategori'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-stok="<?php echo htmlspecialchars($barang['stok'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-satuan="<?php echo htmlspecialchars($barang['satuan'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-dibuat="<?php echo htmlspecialchars($barang['dibuat'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            data-aktif="<?php echo htmlspecialchars($barang['aktif'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
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

    <div class="modal fade" id="addBarangModal" tabindex="-1" role="dialog" aria-labelledby="addBarangModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addBarangModalLabel">Tambah Barang</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                    <?php if ($edit_barang_data && !empty($edit_barang_data['photo'])): ?>
                            <div class="form-group">
                                <label for="existing_photo">Existing Photo</label>
                                <img src="<?php echo htmlspecialchars($edit_barang_data['photo']); ?>" alt="Barang Photo" width="100" height="100">
                            </div>
                        <?php endif; ?>
                        <div class="form-group">
                            <div id="photo-preview" class="mb-3 text-center"></div>
                            <div class="row align-items-center border p-3">
                                <div class="col-auto">
                                    <label for="photo">Photo</label>
                                </div>
                                <div class="col">
                                    <input type="file" name="photo" id="photo" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="nama_barang">Nama Barang</label>
                            <input type="text" class="form-control" id="nama_barang" name="nama_barang" required>
                        </div>
                        <div class="form-group">
                            <label for="kategori">Kategori</label>
                            <select class="form-control" id="kategori" name="kategori" required>
                                <option value="">Pilih Kategori</option>
                                <option value="Mainan Edukatif">Mainan Edukatif</option>
                                <option value="Mainan Konstruksi">Mainan Konstruksi</option>
                                <option value="Mainan Boneka">Mainan Boneka</option>
                                <option value="Mainan Kendaraan">Mainan Kendaraan</option>
                                <option value="Mainan Elektronik">Mainan Elektronik</option>
                                <option value="Mainan Peran">Mainan Peran</option>
                                <option value="Mainan Seni dan Kreativitas">Mainan Seni dan Kreativitas</option>
                                <option value="Mainan Olahraga dan Aktivitas Fisik">Mainan Olahraga dan Aktivitas Fisik</option>
                                <option value="Mainan Interaktif">Mainan Interaktif</option>
                                <option value="Mainan Karakter">Mainan Karakter</option>
                                <option value="Mainan Bayi dan Balita">Mainan Bayi dan Balita</option>
                                <option value="Mainan Puzzle dan Teka-teki">Mainan Puzzle dan Teka-teki</option>
                                <option value="Mainan Remote Control">Mainan Remote Control</option>
                                <option value="Mainan Outdoor">Mainan Outdoor</option>
                                <option value="Mainan Koleksi">Mainan Koleksi</option>
                                <option value="Mainan Sains dan Eksperimen">Mainan Sains dan Eksperimen</option>
                                <option value="Mainan DIY (Do It Yourself)">Mainan DIY (Do It Yourself)</option>
                                <option value="Mainan Musik">Mainan Musik</option>
                                <option value="Mainan Mekanik">Mainan Mekanik</option>
                                <option value="Mainan Tradisional">Mainan Tradisional</option>
                                <option value="Mainan Board Game">Mainan Board Game</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: none;">
                            <label for="stok">Stok</label>
                            <input type="number" class="form-control" id="stok" name="stok" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div class="form-group">
                            <label for="satuan">Satuan</label>
                            <input type="text" class="form-control" id="satuan" name="satuan" readonly>
                        </div>
                        <div class="form-group" style="display: none;">
                            <label for="dibuat">Tanggal</label>
                            <input type="datetime-local" class="form-control" id="dibuat" name="dibuat" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="add_barang">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editBarangModal" tabindex="-1" role="dialog" aria-labelledby="editBarangModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="editBarangModalLabel">Edit Barang</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" name="barang_id" id="edit_barang_id">
                        <div class="form-group text-center">
                            <img id="existing_photo_preview" src="" alt="Barang Photo" width="100" height="100" style="display:none;">
                        </div>
                        <div class="form-group">
                            <div class="row align-items-center border p-3">
                                <div class="col-auto">
                                    <label for="edit-photo-photo">Photo</label>
                                </div>
                                <div class="col">
                                    <input type="file" name="photo" id="edit-photo-photo" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="edit_nama_barang">Nama Barang</label>
                            <input type="text" class="form-control" id="edit_nama_barang" name="nama_barang" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_kategori">Kategori</label>
                            <select class="form-control" id="edit_kategori" name="kategori">
                            <option value="">Pilih Kategori</option>
                                <option value="Mainan Edukatif">Mainan Edukatif</option>
                                <option value="Mainan Konstruksi">Mainan Konstruksi</option>
                                <option value="Mainan Boneka">Mainan Boneka</option>
                                <option value="Mainan Kendaraan">Mainan Kendaraan</option>
                                <option value="Mainan Elektronik">Mainan Elektronik</option>
                                <option value="Mainan Peran">Mainan Peran</option>
                                <option value="Mainan Seni dan Kreativitas">Mainan Seni dan Kreativitas</option>
                                <option value="Mainan Olahraga dan Aktivitas Fisik">Mainan Olahraga dan Aktivitas Fisik</option>
                                <option value="Mainan Interaktif">Mainan Interaktif</option>
                                <option value="Mainan Karakter">Mainan Karakter</option>
                                <option value="Mainan Bayi dan Balita">Mainan Bayi dan Balita</option>
                                <option value="Mainan Puzzle dan Teka-teki">Mainan Puzzle dan Teka-teki</option>
                                <option value="Mainan Remote Control">Mainan Remote Control</option>
                                <option value="Mainan Outdoor">Mainan Outdoor</option>
                                <option value="Mainan Koleksi">Mainan Koleksi</option>
                                <option value="Mainan Sains dan Eksperimen">Mainan Sains dan Eksperimen</option>
                                <option value="Mainan DIY (Do It Yourself)">Mainan DIY (Do It Yourself)</option>
                                <option value="Mainan Musik">Mainan Musik</option>
                                <option value="Mainan Mekanik">Mainan Mekanik</option>
                                <option value="Mainan Tradisional">Mainan Tradisional</option>
                                <option value="Mainan Board Game">Mainan Board Game</option>
                            </select>
                        </div>
                        <div class="form-group" style="display: none;">
                            <label for="edit_stok">Stok <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                            <input type="number" class="form-control" id="edit_stok" name="stok" oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div class="form-group">
                            <label for="edit_satuan">Satuan</label>
                            <input type="text" class="form-control" id="edit_satuan" name="satuan" readonly>
                        </div>
                        <div class="form-group" style="display: none;">
                            <label for="edit_dibuat">Dibuat</label>
                            <input type="datetime-local" class="form-control" id="edit_dibuat" name="dibuat">
                        </div>
                        <div class="form-group" style="display: none;">
                            <label for="edit_aktif">Aktif</label>
                            <input type="text" class="form-control" id="edit_aktif" name="aktif">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="edit_barang">Simpan</button>
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
            
            $('#editBarangModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var barang_id = button.data('id');
                var photo = button.data('photo');
                var nama_barang = button.data('nama');
                var kategori = button.data('kategori');
                var stok = button.data('stok');
                var satuan = button.data('satuan');
                var dibuat = button.data('dibuat');
                var aktif = button.data('aktif');
                var modal = $(this);
                modal.find('.modal-body #edit_barang_id').val(barang_id);
                modal.find('#existing_photo_preview').attr('src', photo).show();
                modal.find('.modal-body #edit_nama_barang').val(nama_barang);
                modal.find('.modal-body #edit_kategori').val(kategori);
                modal.find('.modal-body #edit_stok').val(stok);
                modal.find('.modal-body #edit_satuan').val(satuan || 'PCS');
                modal.find('.modal-body #edit_dibuat').val(dibuat);
                modal.find('.modal-body #edit_aktif').val(aktif);
            });

            $('#edit-photo-photo').on('change', function() {
                var file = this.files[0];
                var reader = new FileReader();
                reader.onload = function(e) {
                    $('#existing_photo_preview').attr('src', e.target.result).show();
                };
                if (file) {
                    reader.readAsDataURL(file);
                } else {
                    $('#existing_photo_preview').hide();
                }
            });

            $('#addBarangModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });

            $('#editBarangModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });

            $('.status-switch').change(function() {
                var isChecked = $(this).is(':checked');
                var barangId = $(this).data('id');
                var status = isChecked ? 'aktif' : 'tidak';
                $.ajax({
                    url: 'barang_stok.php',
                    type: 'POST',
                    data: {
                        update_status: true,
                        barang_id: barangId,
                        aktif: status
                    },
                    success: function(response) {
                        console.log(response);
                    },
                    error: function(xhr, status, error) {
                        console.error(error);
                    }
                });
            });
            $('input[type="checkbox"]').change(function() {
                var statusText = $(this).is(':checked') ? 'Aktif' : 'Tidak Aktif';
                $(this).closest('td').find('span').text(statusText);
            });

        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var now = new Date();
            var year = now.getFullYear();
            var month = ('0' + (now.getMonth() + 1)).slice(-2);
            var day = ('0' + now.getDate()).slice(-2);
            var hours = ('0' + now.getHours()).slice(-2);
            var minutes = ('0' + now.getMinutes()).slice(-2);
            var formattedDate = year + '-' + month + '-' + day + 'T' + hours + ':' + minutes;
            document.getElementById('dibuat').value = formattedDate;
        });
    </script>
    <script>
        window.onload = function() {
            document.getElementById('satuan').value = 'PCS';
        };
        window.onload = function() {
            var satuan = document.getElementById('satuan');
            if (satuan) {
                satuan.value = 'PCS';
            }
        };
    </script>
    <script>
        document.getElementById('photo').addEventListener('change', function(event) {
            var photoPreview = document.getElementById('photo-preview');
            photoPreview.innerHTML = '';
            var file = event.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    img.width = 100;
                    img.height = 100;
                    photoPreview.appendChild(img);
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>