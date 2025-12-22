<?php
session_start();

if(!isset($_SESSION['user_id'])) {
    header("Location: cont.php");
    exit;
}

require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'Utilizator';
$today = date("Y-m-d");


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_service'])) {
    $service_type = $_POST['service_type'] ?? '';
    $reservation_id = filter_var($_POST['reservation_id'], FILTER_VALIDATE_INT);

    if (!$reservation_id || $reservation_id <= 0) {
        die("ID rezervare invalid");
    }
    
    if (!in_array($service_type, ['cleaning', 'refill'])) {
        die("Tip serviciu invalid");
    }

    $checkStmt = $pdo->prepare("
        SELECT r.id, r.room_id 
        FROM reservations r 
        WHERE r.id = :rid 
        AND r.user_id = :uid
        AND r.status = 'approved'
    ");
    $checkStmt->execute([
        'rid' => $reservation_id,
        'uid' => $user_id
    ]);
    
    $reservation = $checkStmt->fetch();
    
    if (!$reservation) {
        die("Rezervarea nu există sau nu îți aparține");
    }

    $pendingCheck = $pdo->prepare("
        SELECT id FROM cleaning_requests 
        WHERE reservation_id = :res_id
        AND status = 'pending'
        LIMIT 1
    ");
    $pendingCheck->execute(['res_id' => $reservation_id]);
    
    if ($pendingCheck->fetch()) {
        $message = "Există deja o cerere în așteptare";
        $message_type = "warning";
    } else {

        $employeeStmt = $pdo->prepare("
            SELECT employee_id 
            FROM employee_rooms 
            WHERE room_id = :room_id 
            LIMIT 1
        ");
        $employeeStmt->execute(['room_id' => $reservation['room_id']]);
        $employee = $employeeStmt->fetch();
        $employee_id = $employee ? $employee['employee_id'] : NULL;

        $insertStmt = $pdo->prepare("
            INSERT INTO cleaning_requests 
            (reservation_id, room_id, employee_id, request_type, status, created_at) 
            VALUES (:res_id, :room_id, :emp_id, :req_type, 'pending', NOW())
        ");
        
        $success = $insertStmt->execute([
            'res_id' => $reservation_id,
            'room_id' => $reservation['room_id'],
            'emp_id' => $employee_id,
            'req_type' => $service_type
        ]);
        
        if ($success) {
            $message = "Cererea a fost trimisă cu succes!";
            $message_type = "success";
        } else {
            $message = "Eroare la trimiterea cererii";
            $message_type = "danger";
        }
    }
}


$stmt = $pdo->prepare("
    SELECT r.*, rooms.room_number, rooms.type, rooms.price
    FROM reservations r
    JOIN rooms ON rooms.id = r.room_id
    WHERE r.user_id = :uid
      AND r.status = 'approved'
      AND :today BETWEEN r.start_date AND r.end_date
    LIMIT 1
");

$stmt->execute([
    'uid'   => $user_id,
    'today' => $today
]);

$active = $stmt->fetch();

$existing_requests = [];
if ($active) {
    $requestsStmt = $pdo->prepare("
        SELECT cr.*, u.name as employee_name
        FROM cleaning_requests cr
        LEFT JOIN users u ON u.id = cr.employee_id
        WHERE cr.reservation_id = :res_id
        ORDER BY cr.created_at DESC
    ");
    $requestsStmt->execute(['res_id' => $active['id']]);
    $existing_requests = $requestsStmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Camera mea - HotelM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="index.css">
    <style>
        .service-option {
            border: 2px solid #dee2e6;
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            cursor: pointer;
        }
        .service-option:hover {
            background-color: #f8f9fa;
        }
        .service-option.selected {
            border-color: #0d6efd;
            background-color: #e7f1ff;
        }
        .service-option input[type="radio"] {
            margin-right: 8px;
        }
    </style>
</head>
<body>

<?php include 'navbar.php'; ?>

<header class="bg-primary text-white text-center py-4">
    <h1 class="h4">Bun venit, <?= htmlspecialchars($user_name) ?></h1>
    <p class="mb-0">Rezervarea ta activă și servicii disponibile</p>
</header>

<div class="container mt-4">

    <?php if(isset($message)): ?>
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <section class="mb-4">
        <h4 class="text-center mb-3">Rezervarea ta activă</h4>

        <?php if(!$active): ?>
            <p class="text-center">Momentan nu ai nicio rezervare activă.</p>
        <?php else: ?>
            <div class="card p-3 mb-3">
                <h5>Camera <?= htmlspecialchars($active['room_number']) ?></h5>
                <table class="table table-sm">
                    <tr>
                        <td><strong>Tip cameră:</strong></td>
                        <td><?= htmlspecialchars($active['type']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Check-in:</strong></td>
                        <td><?= $active['start_date'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Check-out:</strong></td>
                        <td><?= $active['end_date'] ?></td>
                    </tr>
                    <tr>
                        <td><strong>Meal plan:</strong></td>
                        <td><?= htmlspecialchars($active['meal_plan']) ?></td>
                    </tr>
                    <tr>
                        <td><strong>Preț total:</strong></td>
                        <td><?= $active['total_price'] ?> RON</td>
                    </tr>
                </table>
            </div>


            <?php if(!empty($existing_requests)): ?>
                <div class="mb-3">
                    <h5>Cererile tale anterioare:</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tr>
                                <th>Tip serviciu</th>
                                <th>Status</th>
                                <th>Data</th>
                            </tr>
                            <?php foreach($existing_requests as $request): ?>
                                <tr>
                                    <td>
                                        <?php if($request['request_type'] == 'cleaning'): ?>
                                            <span class="badge bg-info">Curățenie</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Refill minibar</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $request['status'] == 'pending' ? 'warning' : 
                                            ($request['status'] == 'accepted' ? 'primary' : 'success')
                                        ?>">
                                            <?= htmlspecialchars($request['status']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($request['created_at']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                </div>
            <?php endif; ?>


            <div class="card p-3">
                <h5 class="mb-3">Solicită serviciu pentru camera ta</h5>
                <form method="POST" id="serviceForm">
                    <input type="hidden" name="reservation_id" value="<?= (int)$active['id'] ?>">
                    

                    <div class="mb-3">
                        <label class="form-label"><strong>Alege tipul de serviciu:</strong></label>
                        
                        <div class="service-option" onclick="document.getElementById('cleaning').checked = true; highlightSelected(this);">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="service_type" id="cleaning" value="cleaning" required>
                                <label class="form-check-label" for="cleaning">
                                    <strong>Curățenie cameră</strong>
                                    <div class="text-muted small">Solicită curățenia completă a camerei</div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="service-option" onclick="document.getElementById('refill').checked = true; highlightSelected(this);">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="service_type" id="refill" value="refill" required>
                                <label class="form-check-label" for="refill">
                                    <strong>Refill minibar</strong>
                                    <div class="text-muted small">Completează minibar-ul cu produse</div>
                                </label>
                            </div>
                        </div>
                    </div>
                    

                    <button type="submit" name="request_service" class="btn btn-primary">
                        Trimite cererea
                    </button>
                </form>
            </div>
        <?php endif; ?>
    </section>

</div>

<footer class="footer">
    &copy; 2025 HotelManager.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function highlightSelected(element) {

        document.querySelectorAll('.service-option').forEach(opt => {
            opt.classList.remove('selected');
        });

        element.classList.add('selected');
    }
    

    document.addEventListener('DOMContentLoaded', function() {
        const selectedRadio = document.querySelector('input[name="service_type"]:checked');
        if (selectedRadio) {
            const parentOption = selectedRadio.closest('.service-option');
            if (parentOption) {
                parentOption.classList.add('selected');
            }
        }
        

        document.querySelectorAll('.service-option').forEach(option => {
            option.addEventListener('click', function() {
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    highlightSelected(this);
                }
            });
        });
    });
</script>

</body>
</html>