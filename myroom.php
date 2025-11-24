<?php
session_start();

// Schelet: doar verificăm dacă user-ul este logat
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_name = $_SESSION['user_name'] ?? 'Utilizator';
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camerele mele - HotelM</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<header class="bg-primary text-white text-center py-5">
    <h1>Bun venit, <?= htmlspecialchars($user_name) ?></h1>
    <p class="lead">Vezi rezervările și camerele tale</p>
</header>

<div class="container mt-5">
    <section class="mb-5">
        <h2 class="text-center mb-3">Rezervările mele</h2>
        <p class="text-center">Aici vor apărea rezervările tale (momentan schelet).</p>
    </section>
</div>

<footer class="footer">
    &copy; 2025 HotelManager.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
