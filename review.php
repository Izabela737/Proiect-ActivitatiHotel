<?php
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

// Până aici doar schelet, fără interacțiune efectivă
$reviews = []; // Placeholder pentru review-uri
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review-uri HotelM</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<header class="bg-primary text-white text-center py-5">
    <h1>Review-uri HotelM</h1>
    <p class="lead">Ce spun clienții noștri</p>
</header>

<div class="container mt-5">
    <section class="mb-5">
        <h2 class="text-center mb-3">Ultimele review-uri</h2>

        <?php if (count($reviews) === 0): ?>
            <p class="text-center">Nu există review-uri momentan.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($reviews as $rev): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">⭐ <?= htmlspecialchars($rev['rating'] ?? '') ?>/5</h5>
                                <p class="card-text"><?= htmlspecialchars($rev['comment'] ?? '') ?></p>
                                <small class="text-muted">— <?= htmlspecialchars($rev['user_name'] ?? '') ?></small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<footer class="footer">
    &copy; 2025 HotelManager.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
