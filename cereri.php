<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();


if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager') {
    die("⛔ Acces interzis");
}

if (isset($_POST['approve_id'])) {
    $stmt = $pdo->prepare("UPDATE reservations SET status='approved' WHERE id=:id AND status='pending'");
    $stmt->execute(['id'=>$_POST['approve_id']]);
    header("Location: cereri.php");
    exit();
}

if (isset($_POST['decline_id'])) {
    $stmt = $pdo->prepare("UPDATE reservations SET status='rejected' WHERE id=:id AND status='pending'");
    $stmt->execute(['id'=>$_POST['decline_id']]);
    header("Location: cereri.php");
    exit();
}

$stmt = $pdo->prepare("
    SELECT r.id, r.start_date, r.end_date, rm.room_number, u.name AS client_name
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    JOIN users u ON r.user_id = u.id
    WHERE r.status='pending'
    ORDER BY r.start_date ASC
");
$stmt->execute();
$pendingReservations = $stmt->fetchAll();

?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Cereri Rezervari - Manager</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="index.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container py-5">
<h2 class="mb-4">Cereri Rezervări Pending</h2>

<?php if(count($pendingReservations) === 0): ?>
   <p class="pending-text">Nu există cereri pending în acest moment.</p>

<?php else: ?>
    <table class="table table-dark table-striped">
        <thead>
            <tr>
                <th>Camera</th>
                <th>Client</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($pendingReservations as $res): ?>
            <tr>
                <td><?= htmlspecialchars($res['room_number']) ?></td>
                <td><?= htmlspecialchars($res['client_name']) ?></td>
                <td><?= htmlspecialchars($res['start_date']) ?></td>
                <td><?= htmlspecialchars($res['end_date']) ?></td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="approve_id" value="<?= $res['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-success">Approve</button>
                    </form>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="decline_id" value="<?= $res['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Decline</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
