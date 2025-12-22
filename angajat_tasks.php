<?php
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => isset($_SERVER['HTTPS']), 
    'use_strict_mode' => true,
    'cookie_samesite' => 'Strict'
]);

require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'employee') {
    header('HTTP/1.1 403 Forbidden');
    die("⛔ Acces interzis");
}

$employeeId = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];


if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || !hash_equals($csrf_token, $_POST['csrf_token'])) {
        header('HTTP/1.1 403 Forbidden');
        die("⛔ Token CSRF invalid!");
    }


    if (isset($_POST['accept_id'])) {
        $acceptId = filter_var($_POST['accept_id'], FILTER_VALIDATE_INT);
        if (!$acceptId || $acceptId <= 0) {
            die("⛔ ID invalid");
        }

        $checkStmt = $pdo->prepare("SELECT id FROM cleaning_requests WHERE id = :id AND employee_id = :eid AND status = 'pending'");
        $checkStmt->execute(['id' => $acceptId, 'eid' => $employeeId]);
        
        if ($checkStmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE cleaning_requests SET status = 'accepted' WHERE id = :id AND employee_id = :eid");
            $stmt->execute(['id' => $acceptId, 'eid' => $employeeId]);
            

            error_log(date('[Y-m-d H:i:s]') . " Employee $employeeId accepted request $acceptId", 3, 'logs/security.log');
            
            header("Location: angajat_tasks.php", true, 303);
            exit();
        } else {
            die("⛔ Cererea nu există sau nu îți aparține");
        }
    }


    if (isset($_POST['done_id'])) {
        $doneId = filter_var($_POST['done_id'], FILTER_VALIDATE_INT);
        if (!$doneId || $doneId <= 0) {
            die("⛔ ID invalid");
        }


        $checkStmt = $pdo->prepare("SELECT id FROM cleaning_requests WHERE id = :id AND employee_id = :eid AND status = 'accepted'");
        $checkStmt->execute(['id' => $doneId, 'eid' => $employeeId]);
        
        if ($checkStmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE cleaning_requests SET status = 'done', completed_at = NOW() WHERE id = :id AND employee_id = :eid");
            $stmt->execute(['id' => $doneId, 'eid' => $employeeId]);
            
            error_log(date('[Y-m-d H:i:s]') . " Employee $employeeId completed request $doneId", 3, 'logs/security.log');
            
            header("Location: angajat_tasks.php", true, 303);
            exit();
        } else {
            die("⛔ Cererea nu există sau nu este în starea corectă");
        }
    }
}


$stmt = $pdo->prepare("
    SELECT er.id AS emp_room_id, r.id AS room_id, r.room_number
    FROM employee_rooms er
    JOIN rooms r ON r.id = er.room_id
    WHERE er.employee_id = :eid
");
$stmt->execute(['eid' => $employeeId]);
$employeeRooms = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT cr.*, r.room_number, u.name AS client_name
    FROM cleaning_requests cr
    JOIN rooms r ON r.id = cr.room_id
    JOIN reservations res ON res.id = cr.reservation_id
    JOIN users u ON u.id = res.user_id
    WHERE cr.employee_id = :eid
    ORDER BY cr.created_at DESC
");
$stmt->execute(['eid' => $employeeId]);
$requests = $stmt->fetchAll();


header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("X-XSS-Protection: 1; mode=block");
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self' https://cdn.jsdelivr.net; style-src 'self' https://cdn.jsdelivr.net; img-src 'self' data:;">
<title>Task-uri Angajat</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
<link rel="stylesheet" href="index.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<header class="bg-primary text-white text-center py-5">
    <div class="container">
        <h1>Camerele mele</h1>
        <p class="lead">Lista camerelor și cererile de curățenie</p>
    </div>
</header>

<div class="container mt-5">

<h2>Camere alocate:</h2>
<?php if(empty($employeeRooms)): ?>
    <div class="alert alert-info">
        Nu ai camere alocate momentan.
    </div>
<?php else: ?>
    <?php foreach($employeeRooms as $room): ?>
        <div class="card shadow p-4 mb-4">
            <h4>Camera <?= htmlspecialchars($room['room_number'], ENT_QUOTES, 'UTF-8') ?></h4>

            <?php

            $roomRequests = array_filter($requests, function($r) use ($room) {
                return $r['room_id'] == $room['room_id'];
            });
            ?>

            <?php if(empty($roomRequests)): ?>
                <p class="pending-text">Nu există cereri de curățenie sau refill pentru această cameră.</p>
            <?php else: ?>
                <?php foreach($roomRequests as $r): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>Client:</strong> <?= htmlspecialchars($r['client_name'], ENT_QUOTES, 'UTF-8') ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Cerere:</strong> 
                                    <?= $r['request_type'] === 'cleaning' ? 'Curățenie' : 'Refill minibar' ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>Status:</strong> 
                                    <span class="badge bg-<?= 
                                        $r['status'] === 'pending' ? 'warning' : 
                                        ($r['status'] === 'accepted' ? 'info' : 'success') 
                                    ?>">
                                        <?= htmlspecialchars($r['status'], ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </li>
                                <li class="list-group-item">
                                    <strong>Data:</strong> <?= htmlspecialchars($r['created_at'], ENT_QUOTES, 'UTF-8') ?>
                                </li>
                            </ul>

                            <div class="mt-3">
                                <?php if($r['status'] == 'pending'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="accept_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="bi bi-check-circle"></i> Acceptă cererea
                                        </button>
                                    </form>
                                <?php elseif($r['status'] == 'accepted'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                        <input type="hidden" name="done_id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-success">
                                            <i class="bi bi-check-all"></i> Marchează ca finalizat
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-square"></i> Finalizat
                                        <?php if(isset($r['completed_at'])): ?>
                                            (<?= htmlspecialchars($r['completed_at'], ENT_QUOTES, 'UTF-8') ?>)
                                        <?php endif; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</div>




<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>


<script>
document.addEventListener('DOMContentLoaded', function() {
    if (window.history.replaceState) {
        window.history.replaceState(null, null, window.location.href);
    }

    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Procesare...';
            }
        });
    });
});
</script>

</body>

</html>