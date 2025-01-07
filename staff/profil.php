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
$update_message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $no_telepon = $_POST['no_telepon'];
    $photo = $_FILES['photo']['name'];
    $target_dir = "../aset/images/fotoprofil/";

    $new_photo_name = strtolower(str_replace(' ', '_', $nama_lengkap)) . '.jpg';
    $target_file = $target_dir . $new_photo_name;

    $check_username_query = "SELECT user_id FROM users WHERE username = ? AND user_id != ?";
    $check_username_stmt = $connection->prepare($check_username_query);
    $check_username_stmt->bind_param("si", $username, $user_id);
    $check_username_stmt->execute();
    $check_username_result = $check_username_stmt->get_result();
    if ($check_username_result && $check_username_result->num_rows > 0) {
        $update_message = "Nama Pengguna sudah terdaftar, silakan ganti yang lain.";
    } else {
        if ($photo) {
            if ($_SESSION['photo'] != '../aset/images/default.png') {
                $old_photo_path = $target_dir . basename($_SESSION['photo']);
                if (file_exists($old_photo_path)) {
                    unlink($old_photo_path);
                }
            }

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $target_file)) {
                $photo_path = $target_dir . $new_photo_name;
                $stmt = $connection->prepare("UPDATE users SET username = ?, email = ?, nama_lengkap = ?, no_telepon = ?, photo = ? WHERE user_id = ?");
                $stmt->bind_param("sssssi", $username, $email, $nama_lengkap, $no_telepon, $photo_path, $user_id);
            } else {
                $update_message = "Error uploading photo.";
            }
        } else {
            $photo_path = $_SESSION['photo'];
            $stmt = $connection->prepare("UPDATE users SET username = ?, email = ?, nama_lengkap = ?, no_telepon = ? WHERE user_id = ?");
            $stmt->bind_param("ssssi", $username, $email, $nama_lengkap, $no_telepon, $user_id);
        }

        if (!empty($_POST['new_password']) && !empty($_POST['confirm_new_password'])) {
            $new_password = $_POST['new_password'];
            $confirm_new_password = $_POST['confirm_new_password'];
        
            if ($new_password === $confirm_new_password) {
                $hashed_new_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt_password = $connection->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt_password->bind_param("si", $hashed_new_password, $user_id);
                if ($stmt_password->execute()) {
                    $update_message = "Password diperbaharui.";
                } else {
                    $update_message = "Error updating password.";
                }
                $stmt_password->close();
            } else {
                $update_message = "New passwords do not match.";
            }
        }
        if (isset($stmt)) {
            if ($stmt->execute()) {
                $update_message = "Profil berhasil diperbaharui.";
                $_SESSION['nama_lengkap'] = $nama_lengkap;
                $_SESSION['photo'] = $photo_path;
            } else {
                $update_message = "Error updating profile.";
            }
            $stmt->close();
        }
    }
    $check_username_stmt->close();
}

$stmt = $connection->prepare("SELECT username, email, nama_lengkap, no_telepon, photo FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $username = $row['username'];
    $email = $row['email'];
    $nama_lengkap = $row['nama_lengkap'];
    $no_telepon = $row['no_telepon'];
    $photo = $row['photo'];
} else {
    $username = $email = $nama_lengkap = $no_telepon = $photo = "";
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
    <link href="../css/profil.css" rel="stylesheet">
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
                    <?php if(isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {echo "<div class='success'>Login berhasil!</div>";unset($_SESSION['logged_in']);}?>
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
                                <a class="dropdown-item active" href="profil.php">
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
                    <div class="row mb-0">
                        <div class="col-12 d-flex align-items-center justify-content-between">
                            <h1 class="h3 mb-0 text-gray-800">Profil</h1>
                        </div>
                    </div>

                    <?php if ($update_message): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($update_message); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-body">
                            <form method="post" action="profil.php" enctype="multipart/form-data">
                                <div class="row">
                                    <div class="col-md-2 d-flex flex-column align-items-center text-center">
                                        <div class="form-group mb-2">
                                            <img id="photo-preview" src="<?php echo htmlspecialchars($photo); ?>" alt="Profile Photo" class="img-fluid rounded-circle mb-3" style="max-width: 150px;">
                                            <h4 class="mb-2"><?php echo htmlspecialchars($_SESSION['nama_lengkap']); ?></h4>
                                            <input type="file" class="form-control-file" id="photo" name="photo" accept="image/*">
                                        </div>
                                    </div>
                                    <div class="col-md-8">
                                        <div class="form-group mb-3">
                                            <label for="nama_lengkap">Nama Lengkap</label>
                                            <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" value="<?php echo htmlspecialchars($nama_lengkap); ?>" required>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group mb-3 col">
                                                <label for="no_telepon">No Telepon</label>
                                                <input type="text" class="form-control" id="no_telepon" name="no_telepon" value="<?php echo htmlspecialchars($no_telepon); ?>">
                                            </div>
                                            <div class="form-group mb-3 col">
                                                <label for="email">Email</label>
                                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                                            </div>
                                        </div>
                                        <div class="form-group mb-3">
                                            <label for="username">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                                        </div>
                                        <div class="form-row">
                                            <div class="form-group mb-3 col">
                                                <label for="new_password">PasswordBaru</label>
                                                <input type="password" class="form-control" id="new_password" name="new_password" minlength="8">
                                            </div>
                                            <div class="form-group mb-3 col">
                                                <label for="confirm_new_password">KonfirmasiPassword</label>
                                                <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" minlength="8">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <button type="submit" class="btn btn-primary btn-full-width">Simpan Profile</button>
                                        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/startbootstrap-sb-admin-2@4.1.4/dist/js/sb-admin-2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/charts/chart-area-demo.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/charts/chart-pie-demo.min.js"></script>
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

            document.getElementById('photo').addEventListener('change', function(event) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('photo-preview').src = e.target.result;
                };
                if (event.target.files[0]) {
                    reader.readAsDataURL(event.target.files[0]);
                } else {
                    document.getElementById('photo-preview').src = "<?php echo htmlspecialchars($photo); ?>";
                }
            });
        });
    </script>
</body>
</html>