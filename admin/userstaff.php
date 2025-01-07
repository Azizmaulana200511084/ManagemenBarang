<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit;
}

include_once "../db/db.php";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $aktif = isset($_POST['aktif']) ? $_POST['aktif'] : 'tidak';
    if ($user_id > 0) {
        $stmt = $connection->prepare("UPDATE users SET aktif = ? WHERE user_id = ?");
        $stmt->bind_param("si", $aktif, $user_id);
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

$edit_user_data = null; 
$edit_user_id = isset($_GET['edit_user_id']) ? intval($_GET['edit_user_id']) : 0;
if ($edit_user_id > 0) {
    $edit_stmt = $connection->prepare("SELECT * FROM users WHERE user_id = ?");
    $edit_stmt->bind_param("i", $edit_user_id);
    $edit_stmt->execute();
    $edit_result = $edit_stmt->get_result();
    if ($edit_result && $edit_result->num_rows > 0) {
        $edit_user_data = $edit_result->fetch_assoc();
    }
    $edit_stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_users'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];
        $email = $_POST['email'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $no_telepon = $_POST['no_telepon'];
        $role = $_POST['role'];
        $check_email_stmt = $connection->prepare("SELECT username FROM users WHERE username = ?");
        $check_email_stmt->bind_param("s", $username);
        $check_email_stmt->execute();
        $check_email_result = $check_email_stmt->get_result();
        if ($check_email_result && $check_email_result->num_rows > 0) {
            echo "<script>alert('Nama Pengguna Sudah Terdaftar');</script>";
        } else {
            $photo = NULL;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $photo_tmp = $_FILES['photo']['tmp_name'];
                $photo_name = $nama_lengkap . '.jpg';
                $photo_dir = '../aset/images/fotoprofil/';
                $photo_path = $photo_dir . $photo_name;
                if (move_uploaded_file($photo_tmp, $photo_path)) {
                    $photo = $photo_path;
                }
            }
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            $insert_stmt = $connection->prepare("INSERT INTO users (username, password, email, nama_lengkap, no_telepon, role, photo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $insert_stmt->bind_param("sssssss", $username, $password_hash, $email, $nama_lengkap, $no_telepon, $role, $photo);
            if ($insert_stmt->execute()) {
                echo "<script>alert('User Telah Ditambahkan');</script>";
            } else {
                echo "<script>alert('Gagal Menambahkan: " . $insert_stmt->error . "');</script>";
            }

            $insert_stmt->close();
        }
        $check_email_stmt->close();
    }

    if (isset($_POST['edit_users'])) {
        $user_id = $_POST['user_id'];
        $username = $_POST['username'];
        $email = $_POST['email'];
        $nama_lengkap = $_POST['nama_lengkap'];
        $no_telepon = $_POST['no_telepon'];
        $role = $_POST['role'];
        $dibuat = $_POST['dibuat'];

        $check_username_stmt = $connection->prepare("SELECT username FROM users WHERE username = ? AND user_id != ?");
        $check_username_stmt->bind_param("si", $username, $user_id);
        $check_username_stmt->execute();
        $check_username_result = $check_username_stmt->get_result();
        if ($check_username_result && $check_username_result->num_rows > 0) {
            echo "<script>alert('Username Sudah Terdaftar');</script>";
        } else {
            if (!empty($_POST['password'])) {
                $password = $_POST['password'];
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
            } else {
                $password_query = "SELECT password FROM users WHERE user_id=?";
                $password_stmt = $connection->prepare($password_query);
                $password_stmt->bind_param("i", $user_id);
                $password_stmt->execute();
                $password_result = $password_stmt->get_result();
                $password_row = $password_result->fetch_assoc();
                $password_hash = $password_row['password'];
                $password_stmt->close();
            }

            $photo = NULL;
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] == UPLOAD_ERR_OK) {
                $photo_query = "SELECT photo FROM users WHERE user_id=?";
                $photo_stmt = $connection->prepare($photo_query);
                $photo_stmt->bind_param("i", $user_id);
                $photo_stmt->execute();
                $photo_result = $photo_stmt->get_result();
                $photo_row = $photo_result->fetch_assoc();
                $old_photo = $photo_row['photo'];
                $photo_stmt->close();
                if ($old_photo && file_exists($old_photo) && $old_photo !== 'path/to/default/photo.jpg') {
                    unlink($old_photo);
                }
                $photo_tmp = $_FILES['photo']['tmp_name'];
                $photo_name = $nama_lengkap . '.jpg';
                $photo_dir = '../aset/images/fotoprofil/';
                $photo_path = $photo_dir . $photo_name;
                if (move_uploaded_file($photo_tmp, $photo_path)) {
                    $photo = $photo_path;
                }
            } else {
                $photo_query = "SELECT photo FROM users WHERE user_id=?";
                $photo_stmt = $connection->prepare($photo_query);
                $photo_stmt->bind_param("i", $user_id);
                $photo_stmt->execute();
                $photo_result = $photo_stmt->get_result();
                $photo_row = $photo_result->fetch_assoc();
                $photo = $photo_row['photo'];
                $photo_stmt->close();
            }

            $update_stmt = $connection->prepare("UPDATE users SET username=?, password=?, email=?, nama_lengkap=?, no_telepon=?, role=?, dibuat=?, photo=? WHERE user_id=?");
            $update_stmt->bind_param("ssssssssi", $username, $password_hash, $email, $nama_lengkap, $no_telepon, $role, $dibuat, $photo, $user_id);
            if ($update_stmt->execute()) {
                echo "<script>alert('User Berhasil Di Perbaharui');</script>";
            } else {
                echo "<script>alert('User Gagal Diperbaharui: " . $update_stmt->error . "');</script>";
            }
            $update_stmt->close();
        }
        $check_username_stmt->close();
    }
}

$search_keyword = isset($_GET['search_keyword']) ? mysqli_real_escape_string($connection, $_GET['search_keyword']) : '';
$users_query = "SELECT user_id, username, password, email, nama_lengkap, no_telepon, role, dibuat, photo, aktif FROM users WHERE role != 'admin'";
if (!empty($search_keyword)) {
    $users_query .= " AND (nama_lengkap LIKE '%$search_keyword%' OR username LIKE '%$search_keyword%')";
}
$users_query .= " ORDER BY nama_lengkap ASC";
$users_result = mysqli_query($connection, $users_query);
$users_result = mysqli_query($connection, $users_query);
$userss = [];
if ($users_result && mysqli_num_rows($users_result) > 0) {
    while ($row = mysqli_fetch_assoc($users_result)) {
        $userss[] = $row;
    }
}

mysqli_close($connection);

$total_records = count($userss);
$records_per_page = 5;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$current_page = max($current_page, 1);
$current_page = min($current_page, $total_pages);
$offset = ($current_page - 1) * $records_per_page;
$current_page_data = array_slice($userss, $offset, $records_per_page);
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
    <link href="../css/userstaff.css" rel="stylesheet">
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
            <li class="nav-item active">
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
                        <h1 class="h3 mb-0 text-gray-800">Data User Staff</h1>
                        &nbsp<br>
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addUsersModal">
                            <i class="fas fa-plus"></i> Tambah
                        </button>
                        <hr>
                        <form method="get" action="">
                            <div class="input-group mb-3" style="border: 1px solid blue; border-radius: 6px;">
                                <input type="text" class="form-control" id="search_keyword" name="search_keyword" placeholder="Cari Nama Lengkap" aria-label="Cari Nama Lengkap" aria-describedby="button-addon2">
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
                                        <th style="display:none;">Username</th>
                                        <th style="display:none;">Password</th>
                                        <th>Nama Lengkap</th>
                                        <th>Email</th>
                                        <th>Telepon</th>
                                        <th style="display:none;">Role</th>
                                        <th>Dibuat</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($userss)) : ?>
                                        <?php $no = $offset + 1; ?>
                                        <?php foreach ($current_page_data as $users) : ?>
                                            <tr class="text-primary" style="border: 1px solid black;">
                                                <td style="text-align: right; border-bottom: 1px solid black; border-left: 1px solid black;"><?php echo $no++; ?></td>
                                                <td style="border-bottom: 1px solid black; text-align: center;">
                                                    <?php if (!empty($users['photo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($users['photo']); ?>" alt="User Photo" width="100" height="100">
                                                    <?php else: ?>
                                                        Belum Di Upload
                                                    <?php endif; ?>
                                                </td>
                                                <td style="border-bottom: 1px solid black; display: none;"><?php echo htmlspecialchars($users['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; display: none;"><?php echo htmlspecialchars($users['password'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($users['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black;"><?php echo htmlspecialchars($users['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black;"><?php echo htmlspecialchars($users['no_telepon'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; display: none;"><?php echo htmlspecialchars($users['role'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="text-align: center; border-bottom: 1px solid black; border-right: 1px solid black;"><?php echo htmlspecialchars($users['dibuat'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <div class="status-container">
                                                        <span id="status-<?php echo $users['user_id']; ?>" class="status"><?php echo $users['aktif'] == 'aktif' ? 'Aktif' : 'Tidak Aktif'; ?></span>
                                                        <form action="" method="POST">
                                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($users['user_id']); ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <label class="switch">
                                                                <input type="checkbox" name="aktif" value="aktif" <?php echo $users['aktif'] == 'aktif' ? 'checked' : ''; ?> onchange="this.form.submit()">
                                                                <span class="slider round"></span>
                                                            </label>
                                                        </form>
                                                    </div>
                                                </td>
                                                <td style="border-bottom: 1px solid black; border-right: 1px solid black;">
                                                    <button class="btn btn-sm btn-info edit-btn" data-toggle="modal" data-target="#editUsersModal"
                                                        data-id="<?php echo $users['user_id']; ?>"
                                                        data-photo="<?php echo htmlspecialchars($users['photo'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-username="<?php echo htmlspecialchars($users['username'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-password="<?php echo htmlspecialchars($users['password'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-email="<?php echo htmlspecialchars($users['email'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-nama="<?php echo htmlspecialchars($users['nama_lengkap'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-telepon="<?php echo htmlspecialchars($users['no_telepon'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-role="<?php echo htmlspecialchars($users['role'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-dibuat="<?php echo htmlspecialchars($users['dibuat'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    >
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="5">No users found.</td>
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

    <div class="modal fade" id="addUsersModal" tabindex="-1" role="dialog" aria-labelledby="addUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="addUsersModalLabel">Tambah User Staff</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <?php if ($edit_user_data && !empty($edit_user_data['photo'])): ?>
                            <div class="form-group">
                                <label for="existing_photo">Existing Photo</label>
                                <img src="<?php echo htmlspecialchars($edit_user_data['photo']); ?>" alt="User Photo" width="100" height="100">
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
                            <label for="username">Nama User</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password" name="password" minlength="8" required>
                            <input type="checkbox" id="toggle-password" class="toggle-password">
                            <label for="toggle-password">Show Password</label>
                        </div>
                        <div class="form-group">
                            <label for="nama_lengkap">Nama Lengkap</label>
                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="form-group">
                            <label for="no_telepon">Nomor Telepon <small style="color: red;"><i>Harap masukkan angka</i></small></label>
                            <input type="number" class="form-control" id="no_telepon" name="no_telepon" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">
                                Silakan Inputkan Email Yang Benar.
                            </div>
                        </div>
                        <div style="display:none;" class="form-group">
                            <label for="role">Rolename</label>
                            <select class="form-control" id="role" name="role" required>
                                <option value="staff">Select role</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="add_users">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editUsersModal" tabindex="-1" role="dialog" aria-labelledby="editUsersModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-gradient-primary text-white">
                    <h5 class="modal-title" id="editUsersModalLabel">Edit User</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <form method="post" action="" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id" name="user_id">
                        <div class="form-group text-center">
                            <img id="existing_photo_preview" src="" alt="User Photo" width="100" height="100" style="display:none;">
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
                            <label for="edit_username">Nama User</label>
                            <input type="text" class="form-control" id="edit_username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">Password</label>
                            <input type="password" class="form-control" id="edit_password" name="password" minlength="8" disabled>
                            <input type="checkbox" id="toggle-edit-password" class="toggle-password">
                            <label for="toggle-edit-password">Klik Jika Edit Password</label>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_nama_lengkap">Nama Lengkap</label>
                            <input type="text" class="form-control" id="edit_nama_lengkap" name="nama_lengkap" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_no_telepon">Nomor Telepon</label>
                            <input type="number" class="form-control" id="edit_no_telepon" name="no_telepon" required oninput="this.value = this.value.replace(/[^0-9]/g, '');">
                        </div>
                        <div style="display:none;" class="form-group">
                            <label for="edit_role">Rolename</label>
                            <select class="form-control" id="edit_role" name="role" required>
                                <option value="staff">Select role</option>
                                <option value="admin">Admin</option>
                                <option value="staff">Staff</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_dibuat">Dibuat</label>
                            <input type="text" class="form-control" id="edit_dibuat" name="dibuat" readonly>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" name="edit_users">Simpan</button>
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
            $('#addUsersModal').on('hidden.bs.modal', function() {
                $(this).find('form').trigger('reset');
            });
            $('#editUsersModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget);
                var userId = button.data('id');
                var photo = button.data('photo');
                var username = button.data('username');
                var password = button.data('password');
                var email = button.data('email');
                var nama = button.data('nama');
                var telepon = button.data('telepon');
                var role = button.data('role');
                var dibuat = button.data('dibuat');
                var modal = $(this);
                modal.find('#edit_user_id').val(userId);
                modal.find('#existing_photo_preview').attr('src', photo).show();
                modal.find('#edit_username').val(username);
                modal.find('#edit_password').val(password);
                modal.find('#edit_email').val(email);
                modal.find('#edit_nama_lengkap').val(nama);
                modal.find('#edit_no_telepon').val(telepon);
                modal.find('#edit_role').val(role);
                modal.find('#edit_dibuat').val(dibuat);
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
            $('#toggle-edit-password').change(function() {
                var passwordField = $('#edit_password');
                if (this.checked) {
                    passwordField.attr('type', 'text');
                    passwordField.prop('disabled', false);
                } else {
                    passwordField.attr('type', 'password');
                    passwordField.prop('disabled', true);
                }
            });
            $('#edit_password').prop('disabled', true);
            if ($('#edit_password').val()) {
                $('#toggle-edit-password').prop('checked', true);
                $('#edit_password').prop('disabled', false).attr('type', 'text');
            }

            $('.status-switch').change(function() {
                var isChecked = $(this).is(':checked');
                var userstaffId = $(this).data('id');
                var status = isChecked ? 'aktif' : 'tidak';
                $.ajax({
                    url: 'userstaff.php',
                    type: 'POST',
                    data: {
                        update_status: true,
                        user_id: userstaffId,
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
        document.getElementById('toggle-password').addEventListener('change', function() {
            const passwordField = document.getElementById('password');
            if (this.checked) {
                passwordField.type = 'text';
            } else {
                passwordField.type = 'password';
            }
        });
    </script>
    <script>
        document.getElementById('email').addEventListener('input', function() {
            const emailField = document.getElementById('email');
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (emailPattern.test(emailField.value)) {
                emailField.classList.remove('is-invalid');
                emailField.classList.add('is-valid');
            } else {
                emailField.classList.remove('is-valid');
                emailField.classList.add('is-invalid');
            }
        });
        document.getElementById('submit').addEventListener('click', function(event) {
            const emailField = document.getElementById('email');
            if (!emailField.classList.contains('is-valid')) {
                event.preventDefault();
                alert('Please enter a valid email address.');
            }
        });
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