<?php
session_start();
include_once './db/db.php';

$success_message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $connection->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if ($user) {
        if (strlen($user['password']) == 32 && md5($password) === $user['password']) {
            $hashed_new_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt_update = $connection->prepare("UPDATE users SET password = ? WHERE username = ?");
            $stmt_update->bind_param("ss", $hashed_new_password, $username);
            $stmt_update->execute();
            $stmt_update->close();
            $user['password'] = $hashed_new_password;
        }
        if (password_verify($password, $user['password'])) {
            if ($user['aktif'] == 'aktif') {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $success_message = $newmesage;
                if ($user['role'] == 'admin') {
                    $success_message = 'Login Berhasil Sebagai Admin !';
                    header('Location: ./admin/dashboard.php');
                } elseif ($user['role'] == 'staff') {
                    $success_message = 'Login Berhasil Sebagai Staff !';
                    header('Location: ./staff/dashboard.php');
                } elseif ($user['role'] == 'owner') {
                    $success_message = 'Login Berhasil Sebagai Pemilik !';
                    header('Location: ./owner/dashboard.php');
                }
                exit;
            } else {
                $error = 'Akun telah dinonaktifkan';
            }
        } else {
            $error = 'Username atau Password salah';
        }
    } else {
        $error = 'Username atau Password salah';
    }
}


if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header('Location: ./admin/dashboard.php');
    } elseif ($_SESSION['role'] == 'staff') {
        header('Location: ./staff/dashboard.php');
    } elseif ($_SESSION['role'] == 'owner') {
        header('Location: ./owner/dashboard.php');
    }
    exit;
}
$_SESSION['success_message'] = $success_message;
$_SESSION['error'] = $error;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Toko Mainan</title>
    <link href="./css/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/aws/css/all.min.css" rel="stylesheet">
    <link href="./css/login.css" rel="stylesheet">
</head>
<body>
    <section class="d-flex justify-content-center align-items-center vh-100">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-4 col-md-6 col-sm-8">
                    <div class="card login-card">
                        <div class="card-body">
                            <a href="index.php" style="text-decoration: none;">
                                <div class="text-center mb-4">
                                    <img src="./aset/logo/shop.png" alt="Brand Logo" style="width: 100px; height: 100px;">
                                </div>
                                <h4 class="fs-4 card-title fw-bold color-primary text-center">Aplikasi Manajemen Barang</h4>
                            </a>
                            <form method="POST" action="" class="needs-validation" novalidate="" autocomplete="off">
                                <div class="mb-3">
                                    <label class="form-label" for="username">Nama Pengguna</label>
                                    <input id="username" type="text" class="form-control" name="username" value="" required autofocus>
                                    <div class="invalid-feedback">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label" for="password">Password</label>
                                    <input id="password" type="password" class="form-control" name="password" minlength="8" required>
                                    <div class="invalid-feedback">
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Masuk</button>
                                
                                <?php if ($success_message): ?>
                                    <div class="alert alert-success mt-3" role="alert">
                                        <?php echo $success_message; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger mt-3" role="alert">
                                        <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                            </form>
                        </div>
                        <div class="text-center footer-text text-muted">
                            AyuToys&copy;2024 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(functinon (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })();
    </script>
</body>
</html>
