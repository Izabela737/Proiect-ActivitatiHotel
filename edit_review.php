<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit("invalid");

if (
    !isset($_SESSION['user_id'], $_POST['csrf'], $_SESSION['csrf_token']) ||
    $_POST['csrf'] !== $_SESSION['csrf_token']
) {
    exit("csrf");
}

$id = (int)($_POST['id'] ?? 0);
$rating = (int)($_POST['rating'] ?? 0);
$comment = trim($_POST['comment'] ?? '');

if ($id <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
    exit("invalid_data");
}

$stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE id = ?");
$stmt->execute([$id]);
$rev = $stmt->fetch();

if (!$rev || $rev['user_id'] != $_SESSION['user_id']) {
    exit("forbidden");
}

$stmt = $pdo->prepare("UPDATE reviews SET rating=?, comment=? WHERE id=?");
$stmt->execute([$rating, $comment, $id]);

echo "success";
