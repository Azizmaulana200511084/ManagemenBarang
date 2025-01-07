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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_barang'])) {
    $barang_id = mysqli_real_escape_string($connection, $_POST['barang_id']);
    $stok = mysqli_real_escape_string($connection, $_POST['stok']);
    $jumlah = mysqli_real_escape_string($connection, $_POST['jumlah']);
    $harga_beli = mysqli_real_escape_string($connection, $_POST['harga_beli']);
    $insert_query = "INSERT INTO bm_sementara (barang_id, stok, jumlah, harga_beli) VALUES ('$barang_id', '$stok', '$jumlah', '$harga_beli')";
    if (mysqli_query($connection, $insert_query)) {
        echo "<script>alert('Barang Berhasil Ditambahkan');</script>";
    } else {
        echo "<script>alert('Barang Gagal Ditambahkan');</script>";
    }
}

$query_bm_sementara = "SELECT * FROM bm_sementara";
$result_bm_sementara = mysqli_query($connection, $query_bm_sementara);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_barang_masuk'])) {
    if (isset($_POST['kode_bm']) && isset($_POST['supplier_id'])  && isset($_POST['tanggal_masuk']) && isset($_POST['dibuat']) && isset($_POST['total'])) {
        $kode_bm = mysqli_real_escape_string($connection, $_POST['kode_bm']);
        $supplier_id = mysqli_real_escape_string($connection, $_POST['supplier_id']);
        $tanggal_masuk = mysqli_real_escape_string($connection, $_POST['tanggal_masuk']);
        $dibuat = mysqli_real_escape_string($connection, $_POST['dibuat']);
        $total = mysqli_real_escape_string($connection, $_POST['total']);
        $insert_bm_t_query = "INSERT INTO bm_t (kode_bm, total) VALUES ('$kode_bm', '$total')";
        if (!mysqli_query($connection, $insert_bm_t_query)) {
            echo "<script>alert('Error adding to bm_t');</script>";
        } else {
            $messages = array();
            while ($row = mysqli_fetch_assoc($result_bm_sementara)) {
                $barang_id = $row['barang_id'];
                $stok= $row['stok'];
                $jumlah = $row['jumlah'];
                $harga_beli = $row['harga_beli'];
                $insert_query = "INSERT INTO barang_masuk (user_id, kode_bm, barang_id, supplier_id, stok, jumlah, harga_beli, tanggal_masuk, dibuat) VALUES ('$user_id', '$kode_bm', '$barang_id', '$supplier_id', '$stok', '$jumlah', '$harga_beli', '$tanggal_masuk', '$dibuat')";
                if (mysqli_query($connection, $insert_query)) {
                    $_SESSION['bm'] = true;
                    $_SESSION['message'] = "Barang dengan $kode_bm berhasil Ditambahkan";
                    header("Location: barang_masuk.php");
                } else {
                    echo "<script>alert('Gagal menambahkan');</script>";
                }
                $update_barang_query = "UPDATE barang SET stok = stok + $jumlah WHERE barang_id = $barang_id";
                if (!mysqli_query($connection, $update_barang_query)) {
                echo "<script>alert('Error updating stock for barang ID $barang_id');</script>";
                exit();
                }
            }
            $delete_query = "DELETE FROM bm_sementara";
            if (mysqli_query($connection, $delete_query)) {
                echo "<script>alert('Berhasil dihapus');</script>";
                exit();
            } else {
                echo "<script>alert('Gagal Menghapus');</script>";
            }
        }
    } else {
        echo "<script>alert('Missing required fields');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_barang_masuk'])) {
    $bm_id = mysqli_real_escape_string($connection, $_POST['bm_id']);
    $barang_id = mysqli_real_escape_string($connection, $_POST['barang_id']);
    $stok = mysqli_real_escape_string($connection, $_POST['stok']);
    $jumlah = mysqli_real_escape_string($connection, $_POST['jumlah']);
    $harga_beli = mysqli_real_escape_string($connection, $_POST['harga_beli']);
    $update_query = "UPDATE bm_sementara SET barang_id='$barang_id', stok='$stok', jumlah='$jumlah', harga_beli='$harga_beli' WHERE bm_id='$bm_id'";
    if (mysqli_query($connection, $update_query)) {
        echo "<script>alert('Berhasil memperbaharui');</script>";
    } else {
        $error_message = mysqli_error($connection);
        echo "<script>alert('Gagal memperbaharui: $error_message');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_barang'])) {
    $delete_barang_id = mysqli_real_escape_string($connection, $_POST['delete_barang_id']);
    $delete_query = "DELETE FROM bm_sementara WHERE bm_id = '$delete_barang_id'";
    if (mysqli_query($connection, $delete_query)) {
        echo "<script>alert('Barang berhasil dihapus');</script>";
    } else {
        echo "<script>alert('Gagal menghapus barang');</script>";
    }
}

$search_keyword = isset($_GET['search_keyword']) ? mysqli_real_escape_string($connection, $_GET['search_keyword']) : '';
$barang_query = "SELECT bm.bm_id, bm.barang_id, b.nama_barang, b.satuan, b.stok, bm.jumlah, bm.harga_beli 
                 FROM bm_sementara bm 
                 JOIN barang b ON bm.barang_id = b.barang_id";
$where_clause = '';
if (!empty($search_keyword)) {
    $where_clause .= " WHERE b.nama_barang LIKE ? OR s.nama_supplier LIKE ?";
}
$barang_query .= $where_clause . " ORDER BY bm.bm_id";
$stmt = $connection->prepare($barang_query);
if (!empty($search_keyword)) {
    $search_keyword = "%{$search_keyword}%";
    $stmt->bind_param("ss", $search_keyword, $search_keyword);
}
$stmt->execute();
$result = $stmt->get_result();
$barangs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$query_barang = "SELECT b.barang_id, b.nama_barang, b.stok
                    FROM barang b
                    LEFT JOIN bm_sementara bm ON b.barang_id = bm.barang_id
                    WHERE b.aktif = 'aktif' AND bm.barang_id IS NULL";
$result_barang = mysqli_query($connection, $query_barang);
$barang_options = "";
if (mysqli_num_rows($result_barang) > 0) {
    while ($row_barang = mysqli_fetch_assoc($result_barang)) {
        $barang_id = $row_barang['barang_id'];
        $nama_barang = htmlspecialchars($row_barang['nama_barang'], ENT_QUOTES, 'UTF-8');
        $stok = htmlspecialchars($row_barang['stok'], ENT_QUOTES, 'UTF-8');
        $barang_options .= "<option value=\"$barang_id\" data-price=\"$stok\">$nama_barang</option>";
    }
}

$query_supplier = "SELECT supplier_id, nama_supplier FROM supplier WHERE aktif = 'aktif'";
$result_supplier = mysqli_query($connection, $query_supplier);
$supplier_options = "";
if (mysqli_num_rows($result_supplier) > 0) {
    while ($row_supplier = mysqli_fetch_assoc($result_supplier)) {
        $supplier_id = $row_supplier['supplier_id'];
        $nama_supplier = htmlspecialchars($row_supplier['nama_supplier'], ENT_QUOTES, 'UTF-8');
        $supplier_options .= "<option value=\"$supplier_id\">$nama_supplier</option>";
    }
}

$sql = "SELECT SUM(jumlah * harga_beli) AS total FROM bm_sementara";
$result = $connection->query($sql);
$total = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total = $row['total'];
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
    <link href="../css/tm.css" rel="stylesheet">
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
                <a class="nav-link" href="sisa_stok.php">
                    <i class="fas fa-fw fa-box"></i>
                    <span>Stok</span>
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
                        <a class="collapse-item text-white" href="barang_masuk.php">Barang Masuk</a>
                        <a class="collapse-item text-white font-weight-bold" href="tambah_barangmasuk.php">Tambah Barang Masuk</a>
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

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Tambah Barang Masuk</h1>
                    &nbsp<br>
                    
                    <div class="card shadow mb-4 border-primary" style="border: 2px solid black;">
                        <div class="card-header py-3">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addBarangModal">
                                <i class="fas fa-plus"></i> Tambah
                        </button>
                        </div>
                        <div class="card-body border-primary" style="border: 1px solid black;">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                    <thead class="bg-gradient-primary text-white">
                                        <tr style="text-align: center;">
                                            <th>Nama Barang</th>
                                            <th style="display:none;">Sisa Stok</th>
                                            <th>Jumblah</th>
                                            <th>Harga Beli</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($barangs as $barang) { ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="display: none; text-align: center; border-bottom: 1px solid black;"><?php echo $barang['stok']; ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black;"><?php echo $barang['jumlah']; ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;"><?php echo 'Rp. ' . htmlspecialchars($barang['harga_beli'], ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars($barang['satuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <button class="btn btn-sm btn-info edit-btn" data-toggle="modal" data-target="#editBarangMasukModal<?php echo $barang['bm_id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                                    <button class="btn btn-sm btn-danger delete-btn" data-toggle="modal" data-target="#deleteBarangModal<?php echo $barang['bm_id']; ?>"><i class="fas fa-trash"></i> Hapus</button>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="editBarangMasukModal<?php echo $barang['bm_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editBarangMasukModalLabel<?php echo $barang['bm_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-gradient-primary text-white">
                                                            <h5 class="modal-title" id="editBarangMasukModalLabel<?php echo $barang['bm_id']; ?>">Edit Barang Masuk</h5>
                                                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="post" action="">
                                                                <input type="hidden" name="bm_id" value="<?php echo $barang['bm_id']; ?>">
                                                                <!-- <div class="form-group" style="display: none;">
                                                                    <label for="barang_id">Nama Barang</label>
                                                                    <select class="form-control" name="barang_id" data-bm-id="<?php echo $barang['bm_id']; ?>" required>
                                                                        <option value="" disabled selected>Pilih Barang</option>
                                                                        <?php echo $barang_options; ?>
                                                                    </select>
                                                                </div> -->
                                                                <div class="form-group" style="display: none;">
                                                                    <label for="barang_id">bmid</label>
                                                                    <input type="text" class="form-control" id="edit_barang_<?php echo $barang['bm_id']; ?>" name="barang_id" value="<?php echo $barang['barang_id']; ?>" readonly required>
                                                                </div>
                                                                <div class="form-group" style="display:none;">
                                                                    <label for="stok">Stok</label>
                                                                    <input type="text" class="form-control" id="edit_stok_<?php echo $barang['bm_id']; ?>" name="stok" value="<?php echo $barang['stok']; ?>" readonly required>
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="jumlah">Jumblah <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                                                                    <input type="number" class="form-control" name="jumlah" value="<?php echo $barang['jumlah']; ?>" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                                                                </div>
                                                                <div class="form-group">
                                                                    <label for="harga_beli">Harga Beli <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                                                                    <div class="input-group">
                                                                        <div class="input-group-prepend">
                                                                            <span class="input-group-text">Rp.</span>
                                                                        </div>
                                                                        <input type="number" class="form-control" name="harga_beli" value="<?php echo $barang['harga_beli']; ?>" required pattern="[0-9]*" oninput="formatHarga(this);" title="Harap masukkan angka">
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button class="btn btn-danger" type="button" data-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="edit_barang_masuk" class="btn btn-primary">Simpan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal fade" id="deleteBarangModal<?php echo $barang['bm_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteBarangModalLabel<?php echo $barang['bm_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-gradient-primary text-white">
                                                            <h5 class="modal-title" id="deleteBarangModalLabel<?php echo $barang['bm_id']; ?>">Hapus Barang</h5>
                                                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Apakah anda yakin ingin menghapus barang ini?
                                                            <form method="post" action="">
                                                                <input type="hidden" name="delete_barang_id" value="<?php echo $barang['bm_id']; ?>">
                                                                <div class="modal-footer">
                                                                    <button type="submit" name="delete_barang" class="btn btn-danger">Hapus</button>
                                                                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Batal</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer py-3 border-primary" style="border-top: 1px solid black;">
                                <form method="POST">
                                    <div class="form-row col-md-12 d-flex justify-content-center">
                                    <input type="hidden" name="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                                        <div class="form-group col-md-3">
                                            <label for="kode_bm">Kode Barang Masuk</label>
                                            <input style="border: 1px solid blue; border-radius: 6px;" type="text" name="kode_bm" id="kode_bm" class="form-control" required readonly>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="supplier_id">Nama Supplier</label>
                                            <select style="border: 1px solid blue; border-radius: 6px;" name="supplier_id" id="supplier_id" class="form-control" required>
                                            <option value="" disabled selected>Pilih Supplier</option>
                                                <?php echo $supplier_options; ?>
                                            </select>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="tanggal_masuk">Tanggal</label>
                                            <input style="border: 1px solid blue; border-radius: 6px;" type="date" name="tanggal_masuk" id="tanggal_masuk" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="total">Total</label>
                                            <div class="input-group" style="border: 1px solid blue; border-radius: 6px;">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input type="text" name="total" id="total" class="form-control" value="<?php echo $total; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-3" style="display: none;">
                                            <label for="dibuat">Di Buat</label>
                                            <input type="datetime-local" class="form-control" id="dibuat" name="dibuat" required>
                                        </div>
                                        <div class="form-group col-md-12 d-flex justify-content-center">
                                            <button type="submit" name="add_barang_masuk" class="btn btn-primary mt-2">SIMPAN</button>
                                        </div>
                                    </div>
                                </form>
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
                        <h5 class="modal-title" id="addBarangModalLabel">Tambah Barang Masuk</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post" action="">
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="barang_id">Nama Barang </label>
                                <select class="form-control" id="barang_id" name="barang_id" required>
                                    <option value="" disabled selected>Pilih Barang</option>
                                    <?php echo $barang_options; ?>
                                </select>
                            </div>
                            <div class="form-group" style="display:none;">
                                <label for="stok">Stok</label>
                                <input type="text" class="form-control" id="stok" name="stok" readonly required>
                            </div>
                            <div class="form-group">
                                <label for="jumlah">Jumblah <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                                <input type="text" class="form-control" id="jumlah" name="jumlah" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                            </div>
                            <div class="form-group">
                                <label for="harga_beli">Harga Beli <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp.</span>
                                    </div>
                                    <input type="number" class="form-control" id="harga_beli" name="harga_beli" required pattern="[0-9]*" oninput="formatHarga(this);" title="Harap masukkan angka">
                                </div>
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
            $('#editBarangMasukModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var bm_id = button.data('id');
                var barang_id = button.data('barang_id');
                var jumlah = button.data('jumlah');
                var harga_beli = button.data('harga_beli');
                var modal = $(this);
                modal.find('.modal-body #edit_bm_id').val(bm_id);
                modal.find('.modal-body #edit_barang_id').val(barang_id);
                modal.find('.modal-body #edit_jumlah').val(jumlah);
                modal.find('.modal-body #edit_harga_beli').val(harga_beli);
            });
            $('#addBarangModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });
            $('#editBarangMasukModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });
            $('#deleteBarangModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var bm_id = button.data('id');
                var modal = $(this);
                modal.find('.modal-body #delete_barang_id').val(bm_id);
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

            var today = new Date().toISOString().split('T')[0];
            document.getElementById('tanggal_masuk').value = today;
        });
    </script>
    <script>
        function formatHarga(input) {
            input.value = parseFloat(input.value).toFixed(2).replace(/\.00$/, '');
        }
        function getRandomInt(min, max) {
            return Math.floor(Math.random() * (max - min + 1)) + min;
        }
        function formatDate(date) {
            var d = date.getDate().toString().padStart(2, '0');
            var m = (date.getMonth() + 1).toString().padStart(2, '0');
            var y = date.getFullYear().toString();
            return d + m + y;
        }
        function generateBMCode(userId) {
            var noAcak = getRandomInt(1000, 9999);
            var currentDate = new Date();
            var tanggalBulanTahun = formatDate(currentDate);
            return "BM" + userId + noAcak + tanggalBulanTahun;
        }
        function setBMCode() {
            var userId = <?php echo $_SESSION['user_id']; ?>;
            var kodeBMInput = document.getElementById('kode_bm');
            var bmCode = generateBMCode(userId);
            kodeBMInput.value = bmCode;
        }
        window.onload = setBMCode;
        function updateHargaJual() {
            var selectedBarang = document.getElementById("barang_id");
            var selectedOption = selectedBarang.options[selectedBarang.selectedIndex];
            var hargaJualInput = document.getElementById("stok");
            var hargaJual = selectedOption.getAttribute("data-price");
            hargaJualInput.value = hargaJual;
        }
        document.getElementById("barang_id").addEventListener("change", updateHargaJual);

        function updateHargaJualEdit(select) {
            var selectedOption = select.options[select.selectedIndex];
            var hargaJualInput = document.getElementById("edit_stok_" + select.getAttribute("data-bm-id"));
            var hargaJual = selectedOption.getAttribute("data-price");
            hargaJualInput.value = hargaJual;
        }
    </script>
</body>
</html>