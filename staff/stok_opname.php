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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kode_prd = mysqli_real_escape_string($connection, $_POST['kode_prd']);
    $barang_id = mysqli_real_escape_string($connection, $_POST['barang_id']);
    $stok_sistem = mysqli_real_escape_string($connection, $_POST['stok_sistem']);
    $stok_fisik = mysqli_real_escape_string($connection, $_POST['stok_fisik']);
    $selisih = $stok_fisik - $stok_sistem;
    $catatan = mysqli_real_escape_string($connection, $_POST['catatan']);
    $tanggal = mysqli_real_escape_string($connection, $_POST['tanggal']);

    $check_barang_id_query = "SELECT barang_id FROM barang WHERE barang_id = ?";
    $check_stmt = $connection->prepare($check_barang_id_query);
    $check_stmt->bind_param("i", $barang_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows == 0) {
        echo "<script>alert('Barang ID tidak ditemukan.');</script>";
    } else {
        $insert_query = "INSERT INTO stokopname (kode_prd, barang_id, stok_sistem, stok_fisik, selisih, catatan, tanggal) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $connection->prepare($insert_query);
        if ($stmt) {
            $stmt->bind_param("sisssss", $kode_prd, $barang_id, $stok_sistem, $stok_fisik, $selisih, $catatan, $tanggal);
            if ($stmt->execute()) {
                echo "<script>alert('Stokopname Berhasil Ditambahkan');</script>";
                $update_stok_query = "UPDATE stok SET stok_masuk = ?, stok_akhir = ? WHERE kode_prd = ?";
                $update_stmt = $connection->prepare($update_stok_query);
                if ($update_stmt) {
                    $stok_akhir = $stok_sistem + $stok_fisik - $selisih;
                    $update_stmt->bind_param("iis", $stok_fisik, $stok_akhir, $kode_prd);
                    if ($update_stmt->execute()) {
                        echo "<script>alert('Stok Fisik berhasil diperbarui di tabel stok.');</script>";
                    } else {
                        echo "<script>alert('Gagal memperbarui stok fisik di tabel stok.');</script>";
                    }
                    $update_stmt->close();
                } else {
                    echo "<script>alert('Gagal menyiapkan query pembaruan stok.');</script>";
                }
            } else {
                echo "<script>alert('Stokopname Gagal Ditambahkan');</script>";
            }
            $stmt->close();
        } else {
            echo "<script>alert('Gagal Menyiapkan');</script>";
        }
    }
    $check_stmt->close();
}

$search_keyword = isset($_GET['search_keyword']) ? $_GET['search_keyword'] : '';
$query = "SELECT so.id_stokopname, so.kode_prd, so.barang_id, b.nama_barang, so.stok_sistem, so.stok_fisik, so.selisih, so.catatan, so.tanggal
            FROM stokopname AS so
            JOIN barang AS b ON so.barang_id = b.barang_id
            WHERE b.nama_barang LIKE '%$search_keyword%'
            OR so.kode_prd LIKE '%$search_keyword%'
            OR DATE(so.tanggal) LIKE '%$search_keyword%'
            ORDER BY so.tanggal DESC";
$result = mysqli_query($connection, $query);
$barangs = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_free_result($result);


// $query_barang = "SELECT bm.id_stok, bm.user_id, bm.kode_bm, bm.kode_prd, b.barang_id, b.nama_barang, bm.stok_masuk, bm.tanggal_masuk
//                 FROM stok bm
//                 JOIN barang b ON bm.barang_id = b.barang_id
//                 WHERE bm.stok_masuk > 0
//                 ORDER BY b.nama_barang ASC, bm.tanggal_masuk ASC";
// $result_barang = mysqli_query($connection, $query_barang);
// $barang_options = "";
// if (mysqli_num_rows($result_barang) > 0) {
//     while ($row_barang = mysqli_fetch_assoc($result_barang)) {
//         $id_stok = $row_barang['id_stok'];
//         $user_id = $row_barang['user_id'];
//         $kode_bm = htmlspecialchars($row_barang['kode_bm'], ENT_QUOTES, 'UTF-8');
//         $kode_prd = htmlspecialchars($row_barang['kode_prd'], ENT_QUOTES, 'UTF-8');
//         $barang_id = $row_barang['barang_id'];
//         $nama_barang = htmlspecialchars($row_barang['nama_barang'], ENT_QUOTES, 'UTF-8');
//         $stok_masuk = htmlspecialchars($row_barang['stok_masuk'], ENT_QUOTES, 'UTF-8');
//         $tanggal_masuk = htmlspecialchars($row_barang['tanggal_masuk'], ENT_QUOTES, 'UTF-8');
//         $date = new DateTime($tanggal_masuk);
//         $formatted_date = $date->format('d-m-y');
//         $option_label = "<strong>$nama_barang, ($kode_prd), $formatted_date</strong>";
//         $barang_options .= "<option value=\"$id_stok\" data-brg=\"$barang_id\" data-prd=\"$kode_prd\" data-stok=\"$stok_masuk\">$option_label</option>";
//     }
// }

$query_barang = "SELECT bm.id_stok, bm.user_id, bm.kode_bm, bm.kode_prd, b.barang_id, b.nama_barang, bm.stok_masuk, bm.tanggal_masuk
                FROM stok bm
                JOIN  barang b ON bm.barang_id = b.barang_id
                WHERE NOT EXISTS (SELECT 1 FROM stokopname so WHERE so.kode_prd = bm.kode_prd AND so.stok_fisik = 0)
                ORDER BY b.nama_barang ASC, bm.tanggal_masuk ASC";
$result_barang = mysqli_query($connection, $query_barang);
$barang_options = "";
if (mysqli_num_rows($result_barang) > 0) {
    while ($row_barang = mysqli_fetch_assoc($result_barang)) {
        $id_stok = $row_barang['id_stok'];
        $user_id = $row_barang['user_id'];
        $kode_bm = htmlspecialchars($row_barang['kode_bm'], ENT_QUOTES, 'UTF-8');
        $kode_prd = htmlspecialchars($row_barang['kode_prd'], ENT_QUOTES, 'UTF-8');
        $barang_id = $row_barang['barang_id'];
        $nama_barang = htmlspecialchars($row_barang['nama_barang'], ENT_QUOTES, 'UTF-8');
        $stok_masuk = htmlspecialchars($row_barang['stok_masuk'], ENT_QUOTES, 'UTF-8');
        $tanggal_masuk = htmlspecialchars($row_barang['tanggal_masuk'], ENT_QUOTES, 'UTF-8');
        $date = new DateTime($tanggal_masuk);
        $formatted_date = $date->format('d-m-y');
        $option_label = "<strong>$nama_barang, ($kode_prd), $formatted_date</strong>";
        $barang_options .= "<option value=\"$id_stok\" data-brg=\"$barang_id\" data-prd=\"$kode_prd\" data-stok=\"$stok_masuk\">$option_label</option>";
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

$start_number = $total_records - $offset;
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
    <link href="../css/so.css" rel="stylesheet">
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
                            <a class="nav-link1" href="sisa_stok.php">Stok</a>
                        </li>
                        <li class="nav-item1">
                            <a class="nav-link1 active" aria-current="page" href="stok_opname.php">StokOpName</a>
                        </li>
                    </ul>
                </div>

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Data Stok OpName</h1>
                        &nbsp<br>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addStokopnameModal">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <hr>
                        <form method="get" action="">
                            <div class="input-group mb-3" style="border: 1px solid blue; border-radius: 6px;">
                                <input type="text" class="form-control" id="search_keyword" name="search_keyword" placeholder="Cari Barang,Kode PRD,Tanggal" aria-label="Cari Barang atau Kode Produk atau Tanggal" aria-describedby="button-addon2">
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
                                        <th>Kode Produk</th>
                                        <th>Nama Barang</th>
                                        <th>Stok Sistem</th>
                                        <th>Stok Fisik</th>
                                        <th>Selisih</th>
                                        <th>Keterangan</th>
                                        <th>Tanggal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($current_page_data)) : ?>
                                        <?php foreach ($current_page_data as $barang) : ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="text-align: right; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo $start_number--; ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['kode_prd']); ?></td>
                                                <td style="text-align: left; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['nama_barang']); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['stok_sistem']); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['stok_fisik']); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['selisih']); ?></td>
                                                <td style="text-align: left; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo htmlspecialchars($barang['catatan']); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-right: 1px solid black;"><?php echo htmlspecialchars($barang['tanggal']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="8" style="text-align: center;">No records found.</td>
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

    <div class="modal fade" id="addStokopnameModal" tabindex="-1" role="dialog" aria-labelledby="addStokopnameModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addStokopnameModalLabel">Tambah Stokopname</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" id="user_id" value="<?php echo $_SESSION['user_id']; ?>">
                        <div class="form-group" style="display:none;">
                            <label for="kode_prd">Kode PRD</label>
                            <input type="text" class="form-control" id="kode_prd" name="kode_prd" required readonly>
                        </div>
                        <input type="hidden" id="barang_id" name="barang_id" required>
                        <div class="form-group">
                            <label for="id_stok">Barang</label>
                            <select class="form-control" id="id_stok" name="id_stok" required>
                                <option value="" disabled selected>Pilih Barang</option>
                                <?php echo $barang_options; ?>
                            </select>
                        </div>
                        <div class="form-group" style="display:none;">
                            <label for="stok_sistem">Stok Sistem</label>
                            <input type="number" class="form-control" id="stok_sistem" name="stok_sistem" required readonly>
                        </div>
                        <div class="form-group">
                            <label for="stok_fisik">Stok Fisik</label>
                            <input type="number" class="form-control" id="stok_fisik" name="stok_fisik" oninput="calculateSelisih()" required>
                        </div>
                        <div class="form-group" style="display:none;">
                            <label for="selisih">Selisih</label>
                            <input type="text" class="form-control" id="selisih" name="selisih" readonly>
                        </div>
                        <div class="form-group">
                            <label for="catatan">Keterangan</label>
                            <textarea id="catatan" name="catatan" class="form-control" rows="4" placeholder="Masukkan Keterangan di sini..." required></textarea>
                        </div>
                        <div class="form-group" style="display:none;">
                            <label for="tanggal">Tanggal Opname</label>
                            <input type="datetime-local" class="form-control" id="tanggal" name="tanggal" required>
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
            $('#addStokopnameModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });

            function updateData() {
                var selectedBarang = document.getElementById("id_stok");
                var selectedOption = selectedBarang.options[selectedBarang.selectedIndex];
                var kodePrdInput = document.getElementById("kode_prd");
                var stokMasukInput = document.getElementById("stok_sistem");
                var barangIdInput = document.getElementById("barang_id");
                var kode_prd = selectedOption.getAttribute("data-prd");
                var stok_masuk = selectedOption.getAttribute("data-stok");
                var brgid = selectedOption.getAttribute("data-brg");
                kodePrdInput.value = kode_prd;
                stokMasukInput.value = stok_masuk;
                barangIdInput.value = brgid;
            }
            document.getElementById('id_stok').addEventListener('change', updateData);

            function calculateSelisih() {
                var stokSistem = document.getElementById('stok_sistem').value;
                var stokFisik = document.getElementById('stok_fisik').value;
                var selisih = stokFisik - stokSistem;
                document.getElementById('selisih').value = selisih;
            }
        });
    </script>
    <script>
        window.onload = function() {
            var now = new Date();
            var year = now.getFullYear();
            var month = ('0' + (now.getMonth() + 1)).slice(-2);
            var day = ('0' + now.getDate()).slice(-2);
            var hour = ('0' + now.getHours()).slice(-2);
            var minute = ('0' + now.getMinutes()).slice(-2);
            var formattedDateTime = year + '-' + month + '-' + day + 'T' + hour + ':' + minute;
            document.getElementById('tanggal').value = formattedDateTime;
        }
    </script>
</body>
</html>