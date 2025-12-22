<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    die("⛔ Acces interzis");
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rapoarte - Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="index.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container py-5 text-center">
    <div class="card shadow p-5" style="background-color:#1e2b40; color:#e6e6e6; margin: 10px;">
        <h2 class="mb-3">📊 Rapoarte</h2>
        <p class="lead">Această secțiune este în lucru și va fi disponibilă în curând.</p>
        <img src="https://cdn-icons-png.flaticon.com/512/2910/2910763.png" alt="In lucru" style="width:100px; opacity:0.5;">
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
