<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Toko Mainan</title>
    <link href="./css/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./css/aws/css/all.min.css" rel="stylesheet">
    <link href="./css/gp.css" rel="stylesheet">
</head>

<body>
    <div class="bg-primary header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <img src="./aset/logo/shop-white.png" alt="AyuToys Logo" style="width: 60px; height: auto; margin-right: 12px;">
            <span class="text-light" style="font-size: 18px; line-height: 1.2;">
                <b>Toko Mainan<br>AyuToys Warukawung</b>
            </span>
        </div>
        <a href="login.php" class="btn btn-light">Masuk</a>
    </div>

    <div class="container">
        <div class="search-form">
        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" class="form-control" name="search" placeholder="Cari Barang" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <div class="input-group-append">
                    <button type="submit" class="btn btn-primary" id="button-addon2">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </div>
        </form>
        </div>

        <div class="row">
            <?php
            session_start();
            include_once "./db/db.php";
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
            error_reporting(E_ALL);
            ini_set('display_errors', 1);

            $limit = 9;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $offset = ($page - 1) * $limit;
            $search = isset($_GET['search']) ? $_GET['search'] : '';
            $searchTerm = "%$search%";
            $totalSql = "
                SELECT COUNT(DISTINCT b.nama_barang) AS total
                FROM barang AS b
                JOIN (
                    SELECT barang_id
                    FROM stok
                    WHERE stok_masuk > 0
                ) AS s ON b.barang_id = s.barang_id
                WHERE b.nama_barang LIKE ?
            ";
            $totalStmt = $connection->prepare($totalSql);
            if (!$totalStmt) {
                die("Query preparation failed: " . $connection->error);
            }
            $totalStmt->bind_param('s', $searchTerm);
            $totalStmt->execute();
            $totalResult = $totalStmt->get_result();
            if (!$totalResult) {
                die("Query execution failed: " . $totalStmt->error);
            }
            $totalRow = $totalResult->fetch_assoc();
            $totalItems = $totalRow['total'];
            $totalPages = ceil($totalItems / $limit);

            $sql = "
                SELECT b.nama_barang, b.photo, s.harga_jual
                FROM barang AS b
                JOIN (
                    SELECT barang_id, harga_jual, stok_masuk, tanggal_masuk
                    FROM stok
                    WHERE stok_masuk > 0
                ) AS s ON b.barang_id = s.barang_id
                WHERE b.nama_barang LIKE ?
                GROUP BY b.nama_barang
                ORDER BY b.nama_barang ASC
                LIMIT ? OFFSET ?
            ";
            $stmt = $connection->prepare($sql);
            if (!$stmt) {
                die("Query preparation failed: " . $connection->error);
            }
            $stmt->bind_param('sii', $searchTerm, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            if (!$result) {
                die("Query execution failed: " . $stmt->error);
            }
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo '
                    <div class="col-md-4 d-flex align-items-stretch mb-4">
                        <div class="product-card w-100">
                            <img src="' . $row["photo"] . '" alt="' . $row["nama_barang"] . '">
                            <div class="product-name">' . $row["nama_barang"] . '</div>
                            <div class="product-price">Rp. ' . number_format($row["harga_jual"], 0, ',', '.') . '</div>
                        </div>
                    </div>';
                }
            } else {
                echo '<p>No products found.</p>';
            }
            $stmt->close();
            $totalStmt->close();
            $connection->close();
            ?>
        </div>

        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>

        <footer class="sticky-footer bg-white">
            <div class="container my-auto">
                <div class="copyright text-center my-auto">
                    <span>AyuToys&copy;2024</span>
                </div>
            </div>
        </footer>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>