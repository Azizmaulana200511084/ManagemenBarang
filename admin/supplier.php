<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

include_once "../db/db.php";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $supplier_id = isset($_POST['supplier_id']) ? intval($_POST['supplier_id']) : 0;
    $aktif = isset($_POST['aktif']) ? $_POST['aktif'] : 'tidak';
    if ($supplier_id > 0) {
        $stmt = $connection->prepare("UPDATE supplier SET aktif = ? WHERE supplier_id = ?");
        $stmt->bind_param("si", $aktif, $supplier_id);
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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_supplier'])) {
    $nama_supplier = mysqli_real_escape_string($connection, $_POST['nama_supplier']);
    $kontak = mysqli_real_escape_string($connection, $_POST['kontak']);
    $alamat = mysqli_real_escape_string($connection, $_POST['alamat']);

    $check_query = "SELECT COUNT(*) AS count FROM supplier WHERE nama_supplier = '$nama_supplier'";
    $check_result = mysqli_query($connection, $check_query);
    $count = mysqli_fetch_assoc($check_result)['count'];

    if ($count > 0) {
        echo "<script>alert('Supplier dengan nama ini sudah ada.');</script>";
    } else {
        $insert_query = "INSERT INTO supplier (nama_supplier, kontak, alamat) VALUES ('$nama_supplier', '$kontak', '$alamat')";
        if (mysqli_query($connection, $insert_query)) {
            echo "<script>alert('Berhasil Menambahkan Supplier');</script>";
        } else {
            echo "<script>alert('Gagal Menambahkan Supplier');</script>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_supplier'])) {
    $supplier_id = mysqli_real_escape_string($connection, $_POST['supplier_id']);
    $nama_supplier = mysqli_real_escape_string($connection, $_POST['nama_supplier']);
    $kontak = mysqli_real_escape_string($connection, $_POST['kontak']);
    $alamat = mysqli_real_escape_string($connection, $_POST['alamat']);

    $check_query = "SELECT COUNT(*) AS count FROM supplier WHERE nama_supplier = ? AND supplier_id != ?";
    $stmt = $connection->prepare($check_query);
    $stmt->bind_param("si", $nama_supplier, $supplier_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    $stmt->close();

    if ($count > 0) {
        echo "<script>alert('Supplier dengan nama ini sudah ada.');</script>";
    } else {
        $update_query = "UPDATE supplier SET nama_supplier = ?, kontak = ?, alamat = ? WHERE supplier_id = ?";
        $stmt = $connection->prepare($update_query);
        $stmt->bind_param("sssi", $nama_supplier, $kontak, $alamat, $supplier_id);
        if ($stmt->execute()) {
            echo "<script>alert('Berhasil Memperbaharui');</script>";
        } else {
            echo "<script>alert('Gagal Memperbaharui');</script>";
        }
        $stmt->close();
    }
}

$search_keyword = isset($_GET['search_keyword']) ? mysqli_real_escape_string($connection, $_GET['search_keyword']) : '';
$supplier_query = "SELECT supplier_id, nama_supplier, kontak, alamat, aktif FROM supplier";
$where_clause = '';
if (!empty($search_keyword)) {
    $where_clause .= " WHERE nama_supplier LIKE '%$search_keyword%'";
}
$supplier_query .= $where_clause . " ORDER BY nama_supplier ASC";
$supplier_result = mysqli_query($connection, $supplier_query);
$suppliers = [];
if ($supplier_result && mysqli_num_rows($supplier_result) > 0) {
    while ($row = mysqli_fetch_assoc($supplier_result)) {
        $suppliers[] = $row;
    }
}

mysqli_close($connection);

$total_records = count($suppliers);
$records_per_page = 5;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max($current_page, 1);
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $records_per_page;
$current_page_data = array_slice($suppliers, $offset, $records_per_page);
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
    <link href="../css/supplier.css" rel="stylesheet">
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
            <li class="nav-item active">
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

                <div class="container-fluid">
                    <div class="d-sm-flex align-items-center justify-content-between mb-4">
                        <h1 class="h3 mb-0 text-gray-800">Data Supplier</h1>
                        &nbsp<br>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addSupplierModal">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <hr>
                        <form method="get" action="">
                            <div class="input-group mb-3" style="border: 1px solid blue; border-radius: 6px;">
                                <input type="text" class="form-control" id="search_keyword" name="search_keyword" placeholder="Cari Nama Supplier" aria-label="Cari Nama Supplier" aria-describedby="button-addon2">
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
                                        <th>Nama Supplier</th>
                                        <th>Kontak</th>
                                        <th>Alamat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($suppliers)) : ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php foreach ($current_page_data as $supplier) : ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="text-align: right; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo $no++; ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($supplier['nama_supplier'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black;"><?php echo htmlspecialchars($supplier['kontak'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;"><?php echo htmlspecialchars($supplier['alamat'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <div class="status-container">
                                                        <span id="status-<?php echo $supplier['supplier_id']; ?>" class="status"><?php echo $supplier['aktif'] == 'aktif' ? 'Aktif' : 'Tidak Aktif'; ?></span>
                                                        <form action="" method="POST">
                                                            <input type="hidden" name="supplier_id" value="<?php echo htmlspecialchars($supplier['supplier_id']); ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <label class="switch">
                                                                <input type="checkbox" name="aktif" value="aktif" <?php echo $supplier['aktif'] == 'aktif' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                                <span class="slider round"></span>
                                                            </label>
                                                        </form>
                                                    </div>
                                                </td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <button class="btn btn-sm btn-info edit-btn" data-toggle="modal" data-target="#editSupplierModal" data-id="<?php echo $supplier['supplier_id']; ?>" data-nama="<?php echo htmlspecialchars($supplier['nama_supplier'], ENT_QUOTES, 'UTF-8'); ?>" data-kontak="<?php echo htmlspecialchars($supplier['kontak'], ENT_QUOTES, 'UTF-8'); ?>" data-alamat="<?php echo htmlspecialchars($supplier['alamat'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5">No suppliers found.</td>
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

    <div class="modal fade" id="addSupplierModal" tabindex="-1" role="dialog" aria-labelledby="addSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addSupplierModalLabel">Tambah Supplier</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nama_supplier">Nama Supplier</label>
                            <input type="text" class="form-control" id="nama_supplier" name="nama_supplier" required>
                        </div>
                        <div class="form-group">
                            <label for="kontak">Kontak <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                            <input type="number" class="form-control" id="kontak" name="kontak" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div class="form-group">
                            <label for="alamat">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="add_supplier">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editSupplierModal" tabindex="-1" role="dialog" aria-labelledby="editSupplierModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="editSupplierModalLabel">Edit Supplier</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="supplier_id" id="edit_supplier_id">
                        <div class="form-group">
                            <label for="edit_nama_supplier">Nama Supplier</label>
                            <input type="text" class="form-control" id="edit_nama_supplier" name="nama_supplier" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_kontak">Kontak <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                            <input type="text" class="form-control" id="edit_kontak" name="kontak" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div class="form-group">
                            <label for="edit_alamat">Alamat</label>
                            <textarea class="form-control" id="edit_alamat" name="alamat" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="edit_supplier">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-easing/1.4.1/jquery.easing.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/dist/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/charts/chart-area-demo.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/charts/chart-pie-demo.min.js"></script>
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
            
            $('#editSupplierModal').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget);
                var supplier_id = button.data('id');
                var nama_supplier = button.data('nama');
                var kontak = button.data('kontak');
                var alamat = button.data('alamat');
                var modal = $(this);
                modal.find('.modal-body #edit_supplier_id').val(supplier_id);
                modal.find('.modal-body #edit_nama_supplier').val(nama_supplier);
                modal.find('.modal-body #edit_kontak').val(kontak);
                modal.find('.modal-body #edit_alamat').val(alamat);
            });

            $('#addSupplierModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });

            $('#editSupplierModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });

            $('.status-switch').change(function() {
                var isChecked = $(this).is(':checked');
                var supplierId = $(this).data('id');
                var status = isChecked ? 'aktif' : 'tidak';
                $.ajax({
                    url: 'supplier.php',
                    type: 'POST',
                    data: {
                        update_status: true,
                        supplier_id: supplierId,
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
</body>
</html>