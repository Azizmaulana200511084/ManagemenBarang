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
    $kode_prd = mysqli_real_escape_string($connection, $_POST['kode_prd']);
    $stok = mysqli_real_escape_string($connection, $_POST['stok']);
    $jumlah = mysqli_real_escape_string($connection, $_POST['jumlah']);
    $harga_jual = mysqli_real_escape_string($connection, $_POST['harga_jual']);
    $insert_query = "INSERT INTO bk_sementara (barang_id, kode_prd, stok, jumlah, harga_jual) VALUES ('$barang_id', '$kode_prd', '$stok', '$jumlah', '$harga_jual')";
    if (mysqli_query($connection, $insert_query)) {
        echo "<script>alert('Barang Berhasil Ditambahkan');</script>";
    } else {
        echo "<script>alert('Barang Gagal Ditambahkan');</script>";
    }
}

$query_bk_sementara = "SELECT * FROM bk_sementara";
$result_bk_sementara = mysqli_query($connection, $query_bk_sementara);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_barang_keluar'])) {
    if (isset($_POST['kode_bk']) && isset($_POST['plg']) && isset($_POST['kode_prd']) && isset($_POST['tanggal_keluar']) && isset($_POST['dibuat']) && isset($_POST['bayar'])) {
        $kode_bk = mysqli_real_escape_string($connection, $_POST['kode_bk']);
        $plg = mysqli_real_escape_string($connection, $_POST['plg']);
        $kode_prd = mysqli_real_escape_string($connection, $_POST['kode_prd']);
        $tanggal_keluar = mysqli_real_escape_string($connection, $_POST['tanggal_keluar']);
        $dibuat = mysqli_real_escape_string($connection, $_POST['dibuat']);
        $bayar = mysqli_real_escape_string($connection, $_POST['bayar']);
        $total_bayar = 0;
        while ($row = mysqli_fetch_assoc($result_bk_sementara)) {
            $total_bayar += $row['jumlah'] * $row['harga_jual'];
        }
        mysqli_data_seek($result_bk_sementara, 0);
        $kembalian = $bayar - $total_bayar;
        $insert_bk_bayar_query = "INSERT INTO bk_t (kode_bk, bayar, kembalian, total) VALUES ('$kode_bk', '$bayar', '$kembalian', '$total_bayar')";
        if (!mysqli_query($connection, $insert_bk_bayar_query)) {
            echo "<script>alert('Gagal Menambahkan Ke Bkt');</script>";
        } else {
            $messages = array();
            while ($row = mysqli_fetch_assoc($result_bk_sementara)) {
                $barang_id = $row['barang_id'];
                $kode_prd = $row['kode_prd'];
                $jumlah = $row['jumlah'];
                $harga_jual = $row['harga_jual'];
                $insert_query = "INSERT INTO barang_keluar (plg, barang_id, kode_prd, kode_bk, user_id, jumlah, harga_jual, tanggal_keluar, dibuat) VALUES ('$plg', '$barang_id', '$kode_prd', '$kode_bk', '$user_id', '$jumlah', '$harga_jual', '$tanggal_keluar', '$dibuat')";
                if (mysqli_query($connection, $insert_query)) {
                    $_SESSION['bk'] = true;
                    $_SESSION['message'] = "Barang dengan kode $kode_bk berhasil Ditambahkan";
                    $stmt = $connection->prepare("SELECT stok FROM barang WHERE barang_id = ?");
                    $stmt->bind_param("i", $barang_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    $stok = $row['stok'];
                    $stok_akhir = $stok - $jumlah;
                    $update_barang_query = "UPDATE barang SET stok = ? WHERE barang_id = ?";
                    $stmt = $connection->prepare($update_barang_query);
                    $stmt->bind_param("ii", $stok_akhir, $barang_id);
                    $stmt->execute();
                } else {
                    echo "<script>alert('Gagal Memanbahkan Barang');</script>";
                }
            }
            $delete_query = "DELETE FROM bk_sementara";
            if (mysqli_query($connection, $delete_query)) {
                echo "<script>alert('Berhasil Dihapus');</script>";
                header("Location: barang_keluar.php");
                exit();
            } else {
                echo "<script>alert('Gagal Menghapus');</script>";
            }
        }
    } else {
        echo "<script>alert('Tidak Tersedia');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_barang_keluar'])) {
    $bk_id = mysqli_real_escape_string($connection, $_POST['bk_id']);
    $jumlah = mysqli_real_escape_string($connection, $_POST['jumlah']);
    $update_query = "UPDATE bk_sementara SET jumlah='$jumlah' WHERE bk_id='$bk_id'";
    if (mysqli_query($connection, $update_query)) {
        echo "<script>alert('Barang Berhasil Diperbaharui');</script>";
    } else {
        $error_message = mysqli_error($connection);
        echo "<script>alert('Gagal memperbaharui: $error_message');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_barang'])) {
    $delete_barang_id = mysqli_real_escape_string($connection, $_POST['delete_barang_id']);
    $delete_query = "DELETE FROM bk_sementara WHERE bk_id = '$delete_barang_id'";
    if (mysqli_query($connection, $delete_query)) {
        echo "<script>alert('Barang Berhasil Dihapus');</script>";
    } else {
        echo "<script>alert('Gagal Menghapus Barang');</script>";
    }
}

$search_keyword = isset($_GET['search_keyword']) ? mysqli_real_escape_string($connection, $_GET['search_keyword']) : '';
$barang_query = "SELECT bk.bk_id, bk.barang_id, bk.kode_prd, bk.stok, b.nama_barang, b.satuan, bk.jumlah, bk.harga_jual 
                 FROM bk_sementara bk
                 JOIN barang b ON bk.barang_id = b.barang_id";
$where_clause = '';
if (!empty($search_keyword)) {
    $where_clause .= " WHERE b.nama_barang LIKE ?";
}
$barang_query .= $where_clause . " ORDER BY b.nama_barang";
$stmt = $connection->prepare($barang_query);
if (!empty($search_keyword)) {
    $search_keyword = "%{$search_keyword}%";
    $stmt->bind_param("s", $search_keyword);
}
$stmt->execute();
$result = $stmt->get_result();
$barangs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$query_barang = "SELECT s.id_stok, s.kode_prd, s.stok_masuk, s.barang_id, s.harga_jual, s.lokasi_penyimpanan, b.nama_barang, s.tanggal_masuk
                    FROM stok s
                    JOIN barang b ON s.barang_id = b.barang_id
                    LEFT JOIN bk_sementara bk ON s.kode_prd = bk.kode_prd
                    WHERE s.stok_masuk > 0 AND bk.kode_prd IS NULL
                    ORDER BY b.nama_barang ASC, s.tanggal_masuk ASC";
$result_barang = mysqli_query($connection, $query_barang);
$barang_options = "";
if (mysqli_num_rows($result_barang) > 0) {
    while ($row_barang = mysqli_fetch_assoc($result_barang)) {
        $barang_id = $row_barang['barang_id'];
        $nama_barang = htmlspecialchars($row_barang['nama_barang'], ENT_QUOTES, 'UTF-8');
        $kode_prd = htmlspecialchars($row_barang['kode_prd'], ENT_QUOTES, 'UTF-8');
        $stok = $row_barang['stok_masuk'];
        $harga_jual = $row_barang['harga_jual'];
        $lokasi_penyimpanan = htmlspecialchars($row_barang['lokasi_penyimpanan'], ENT_QUOTES, 'UTF-8');
        $tanggal_masuk = htmlspecialchars($row_barang['tanggal_masuk'], ENT_QUOTES, 'UTF-8');
        $date = new DateTime($tanggal_masuk);
        $formatted_date = $date->format('d-m-y');
        $option_label = "$nama_barang, ($kode_prd), $formatted_date";
        $barang_options .= "<option value=\"$barang_id\" data-price=\"$harga_jual\" data-kode=\"$kode_prd\" data-stok=\"$stok\" data-penyimpanan=\"$lokasi_penyimpanan\">$option_label</option>";
    }
}
$sql = "SELECT SUM(jumlah * harga_jual) AS total_bayar FROM bk_sementara";
$result = $connection->query($sql);
$total_bayar = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $total_bayar = $row['total_bayar'];
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
    <link href="../css/tk.css" rel="stylesheet">
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
                <a class="nav-link collapsed active" href="#" data-toggle="collapse" data-target="#collapseOne"
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
                <a style="font-weight: bold;" class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo"
                    aria-expanded="true" aria-controls="collapseTwo">
                    <i class="fas fa-fw fa-shopping-cart"></i>
                    <span>Barang Keluar</span>
                </a>
                <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
                    <div class="bg-primary py-2 collapse-inner rounded">
                        <h6 class="collapse-header">Barang Keluar:</h6>
                        <a class="collapse-item text-white" href="barang_keluar.php">Barang Keluar</a>
                        <a class="collapse-item text-white font-weight-bold" href="tambah_barangkeluar.php">Tambah Barang Keluar</a>
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

                <div class="modal fade" id="addBarangModal" tabindex="-1" role="dialog" aria-labelledby="addBarangModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header bg-gradient-primary text-white">
                                <h5 class="modal-title" id="addBarangModalLabel">Tambah Barang Keluar</h5>
                                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="post" action="">
                                <div class="modal-body">
                                    <div class="form-group">
                                        <label for="barang_id">Nama Barang</label>
                                        <select class="form-control" id="barang_id" name="barang_id" required>
                                            <option value="" disabled selected>Pilih Barang</option>
                                            <?php echo $barang_options; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="kode_prd">Kode Produk</label>
                                        <input type="text" class="form-control" id="kode_prd" name="kode_prd" readonly>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col">
                                            <label for="lokasi_penyimpanan">Lokasi Penyimpanan</label>
                                            <input type="text" class="form-control" id="lokasi_penyimpanan" name="lokasi_penyimpanan" readonly>
                                        </div>
                                        <div class="form-group col">
                                            <label for="harga_jual">Harga Jual</label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input type="text" class="form-control" id="harga_jual" name="harga_jual" readonly required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col">
                                            <label for="stok">Sisa</label>
                                            <input type="text" class="form-control" id="stok" name="stok" readonly>
                                        </div>
                                        <div class="form-group col">
                                            <label for="jumlah">Jumblah <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                                            <input type="text" class="form-control" id="jumlah" name="jumlah" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
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

                <div class="container-fluid">
                    <h1 class="h3 mb-2 text-gray-800">Tambah Barang Keluar</h1>
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
                                            <th>Kode Produk</th>
                                            <th>Nama Barang</th>
                                            <th style="display:none;">Stok</th>
                                            <th>Jumlah</th>
                                            <th>Harga Jual</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($barangs as $barang) { ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="text-align: center; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['kode_prd'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['nama_barang'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="display:none; text-align: center; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['stok'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black;"><?php echo $barang['jumlah']; ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;"><?php echo 'Rp. ' . htmlspecialchars($barang['harga_jual'], ENT_QUOTES, 'UTF-8') . ' / ' . htmlspecialchars($barang['satuan'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <button class="btn btn-sm btn-info edit-btn" data-toggle="modal" data-target="#editBarangKeluarModal<?php echo $barang['bk_id']; ?>"><i class="fas fa-edit"></i> Edit</button>
                                                    <button class="btn btn-sm btn-danger delete-btn" data-toggle="modal" data-target="#deleteBarangModal<?php echo $barang['bk_id']; ?>"><i class="fas fa-trash"></i> Hapus</button>
                                                </td>
                                            </tr>
                                            <div class="modal fade" id="editBarangKeluarModal<?php echo $barang['bk_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="editBarangMasukModalLabel<?php echo $barang['bk_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-gradient-primary text-white">
                                                            <h5 class="modal-title" id="editBarangMasukModalLabel<?php echo $barang['bk_id']; ?>">Edit Barang Keluar</h5>
                                                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="post" action="" onsubmit="return validateJumlahedit(this)">
                                                                <input type="hidden" name="bk_id" value="<?php echo $barang['bk_id']; ?>">
                                                                <input type="hidden" id="stok_<?php echo $barang['bk_id']; ?>" value="<?php echo $barang['stok']; ?>">
                                                                <center><h3><b>(sisa <?php echo $barang['stok']; ?>)</b></h3></center>
                                                                <div class="form-group">
                                                                    <label for="jumlah">Jumlah <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                                                                    <input type="number" class="form-control" name="jumlah" value="<?php echo $barang['jumlah']; ?>" required>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button class="btn btn-danger" type="button" data-dismiss="modal">Batal</button>
                                                                    <button type="submit" name="edit_barang_keluar" class="btn btn-primary">Simpan</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="modal fade" id="deleteBarangModal<?php echo $barang['bk_id']; ?>" tabindex="-1" role="dialog" aria-labelledby="deleteBarangModalLabel<?php echo $barang['bk_id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog" role="document">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-gradient-primary text-white">
                                                            <h5 class="modal-title" id="deleteBarangModalLabel<?php echo $barang['bk_id']; ?>">Hapus Barang</h5>
                                                            <button class="close text-white" type="button" data-dismiss="modal" aria-label="Close">
                                                                <span aria-hidden="true">×</span>
                                                            </button>
                                                        </div>
                                                        <div class="modal-body">
                                                            Apakah anda yakin ingin menghapus barang ini?
                                                            <form method="post" action="">
                                                                <input type="hidden" name="delete_barang_id" value="<?php echo $barang['bk_id']; ?>">
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
                                    <input type="hidden" name="kode_prd" id="kode_prd" value="">
                                        <div class="form-group col-md-3">
                                            <label for="kode_bk">Kode Barang Keluar</label>
                                            <input style="border: 1px solid blue; border-radius: 6px;" type="text" name="kode_bk" id="kode_bk" class="form-control" required readonly>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="plg">Nama Pelanggan</label>
                                            <input type="text" style="border: 1px solid blue; border-radius: 6px;" name="plg" id="plg" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="tanggal_keluar">Tanggal Keluar</label>
                                            <input style="border: 1px solid blue; border-radius: 6px;" type="date" name="tanggal_keluar" id="tanggal_keluar" class="form-control" required>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="total">Total</label>
                                            <div class="input-group" style="border: 1px solid blue; border-radius: 6px;">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input type="text" name="total" id="total" class="form-control" value="<?php echo $total_bayar; ?>" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="bayar">Bayar</label>
                                            <div class="input-group" style="border: 1px solid blue; border-radius: 6px;">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input type="number" style="border: 1px solid blue; border-radius: 6px;" name="bayar" id="bayar" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-3">
                                            <label for="kembalian">Kembalian</label>
                                            <div class="input-group" style="border: 1px solid blue; border-radius: 6px;">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">Rp.</span>
                                                </div>
                                                <input type="text" name="kembalian" id="kembalian" class="form-control" readonly>
                                            </div>
                                        </div>
                                        <div class="form-group col-md-3" style="display: none;">
                                            <label for="dibuat">Di Buat</label>
                                            <input type="datetime-local" class="form-control" id="dibuat" name="dibuat" required>
                                        </div>
                                        <div class="form-group col-md-12 d-flex justify-content-center">
                                            <button type="submit" name="add_barang_keluar" id="saveButton" class="btn btn-primary mt-2">SIMPAN</button>
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
                $('#editBarangKeluarModal').on('show.bs.modal', function(event) {
                    var button = $(event.relatedTarget);
                    var bk_id = button.data('id');
                    var barang_id = button.data('barang_id');
                    var jumlah = button.data('jumlah');
                    var harga_jual = button.data('harga_jual');
                    var modal = $(this);
                    modal.find('.modal-body #edit_bm_id').val(bk_id);
                    modal.find('.modal-body #edit_barang_id').val(barang_id);
                    modal.find('.modal-body #edit_jumlah').val(jumlah);
                    modal.find('.modal-body #edit_harga_jual').val(harga_jual);
                });
                $('#addBarangModal').on('hidden.bs.modal', function() {
                    $(this).find('form').trigger('reset');
                });
                $('#editBarangKeluarModal').on('hidden.bs.modal', function() {
                    $(this).find('form').trigger('reset');
                });
                $('#deleteBarangModal').on('show.bs.modal', function(event) {
                    var button = $(event.relatedTarget);
                    var bk_id = button.data('id');
                    var modal = $(this);
                    modal.find('.modal-body #delete_barang_id').val(bk_id);
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
                document.getElementById('tanggal_keluar').value = today;
            });
        </script>
        <script>
            function updateHargaJual() {
                var selectedBarang = document.getElementById("barang_id");
                var selectedOption = selectedBarang.options[selectedBarang.selectedIndex];
                var hargaJualInput = document.getElementById("harga_jual");
                var kodeSpnInput = document.getElementById("kode_prd");
                var StokInput = document.getElementById("stok");
                var LokasiPenyimpananInput = document.getElementById("lokasi_penyimpanan");
                var hargaJual = selectedOption.getAttribute("data-price");
                var kodeSpn = selectedOption.getAttribute("data-kode");
                var Stokk = selectedOption.getAttribute("data-stok");
                var Penyimpanan = selectedOption.getAttribute("data-penyimpanan");
                hargaJualInput.value = hargaJual;
                kodeSpnInput.value = kodeSpn;
                StokInput.value = Stokk;
                LokasiPenyimpananInput.value = Penyimpanan;
            }
            document.getElementById("barang_id").addEventListener("change", updateHargaJual);

            function updateHargaJualEdit(select) {
                var selectedOption = select.options[select.selectedIndex];
                var hargaJualInput = document.getElementById("edit_harga_jual_" + select.getAttribute("data-bk-id"));
                var hargaJual = selectedOption.getAttribute("data-price");
                hargaJualInput.value = hargaJual;
            }

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
                return "BK" + userId + noAcak + tanggalBulanTahun;
            }
            function setBMCode() {
                var userId = <?php echo $_SESSION['user_id']; ?>;
                var kodeBMInput = document.getElementById('kode_bk');
                var bmCode = generateBMCode(userId);
                kodeBMInput.value = bmCode;
            }
            window.onload = setBMCode;

            function calculateChange() {
                var total = parseFloat(document.getElementById("total").value);
                var bayar = parseFloat(document.getElementById("bayar").value);
                var kembalian = bayar - total;
                document.getElementById("kembalian").value = kembalian.toFixed(2);
            }
            document.getElementById("bayar").addEventListener("input", calculateChange);

            function validateJumlah() {
                var stok = parseInt(document.getElementById("stok").value, 10);
                var jumlah = parseInt(document.getElementById("jumlah").value, 10);
                if (isNaN(stok)) stok = 0;
                if (isNaN(jumlah)) jumlah = 0;
                if (jumlah > stok) {
                    alert('Jumblah tidak boleh melebihi stok.');
                    document.getElementById("jumlah").value = stok;
                }
            }
            document.getElementById("jumlah").addEventListener("input", validateJumlah);

            function validateJumlahedit(form) {
                var jumlah = parseInt(form.jumlah.value, 10);
                var bk_id = form.bk_id.value;
                var stok = parseInt(document.getElementById('stok_' + bk_id).value, 10);
                if (jumlah > stok) {
                    alert('Jumblah tidak boleh melebihi stok yang tersedia.');
                    return false;
                }
                return true;
            }

            function validateBayar() {
                var total = parseFloat($('#total').val().replace('Rp.', '').replace(',', '')) || 0;
                var bayar = parseFloat($('#bayar').val()) || 0;
                var saveButton = document.getElementById('saveButton');
                if (bayar < total) {
                    $('#bayar').addClass('is-invalid');
                    $('#bayar').next('.invalid-feedback').remove();
                    $('#bayar').after('<div class="invalid-feedback">Jumblah bayar tidak boleh kurang dari total.</div>');
                    saveButton.disabled = true;
                } else {
                    $('#bayar').removeClass('is-invalid');
                    $('#bayar').next('.invalid-feedback').remove();
                    saveButton.disabled = false;
                }
            }
            $('#bayar').on('input', validateBayar);
            validateBayar();
        </script>
    </div>
</body>
</html>