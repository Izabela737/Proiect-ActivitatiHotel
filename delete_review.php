<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

if($_SERVER['REQUEST_METHOD']!=='POST' || !isset($_POST['id'], $_POST['csrf'])) exit("invalid_request");

if($_POST['csrf'] !== ($_SESSION['csrf_token'] ?? '')) exit("forbidden");

$id = (int)$_POST['id'];

$stmt = $pdo->prepare("SELECT user_id FROM reviews WHERE id=:id");
$stmt->execute(['id'=>$id]);
$rev = $stmt->fetch();
if(!$rev) exit("not_found");

if($rev['user_id'] != $_SESSION['user_id'] && $_SESSION['user_role']!=='manager') exit("forbidden");

$stmt = $pdo->prepare("DELETE FROM reviews WHERE id=:id");
$stmt->execute(['id'=>$id]);
echo "success";
