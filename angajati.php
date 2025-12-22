<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'manager'){
    exit("Acces interzis!");
}

if (!isset($_SESSION['bot_token'])) {
    $_SESSION['bot_token'] = bin2hex(random_bytes(16));
}
$bot_token = $_SESSION['bot_token'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bot_token']) && $_POST['bot_token'] === $_SESSION['bot_token']) {
    $employee_id = (int)($_POST['employee_id'] ?? 0);
    $room_id = (int)($_POST['room_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if($employee_id && $room_id && in_array($action,['add','remove'])){
        if($action === 'add'){
            $stmt = $pdo->prepare("INSERT IGNORE INTO employee_rooms (employee_id, room_id) VALUES (:employee_id,:room_id)");
            $stmt->execute(['employee_id'=>$employee_id,'room_id'=>$room_id]);
        } elseif($action === 'remove'){
            $stmt = $pdo->prepare("DELETE FROM employee_rooms WHERE employee_id=:employee_id AND room_id=:room_id");
            $stmt->execute(['employee_id'=>$employee_id,'room_id'=>$room_id]);
        }
    }
    header("Location: angajati.php");
    exit();
}

$stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role='employee' ORDER BY name");
$stmt->execute();
$employees = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id, room_number FROM rooms ORDER BY room_number");
$allRooms = $stmt->fetchAll();

$employeeRooms = [];
$stmt = $pdo->query("SELECT * FROM employee_rooms");
foreach($stmt->fetchAll() as $er){
    $employeeRooms[$er['employee_id']][] = $er['room_id'];
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Angajați</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'navbar.php'; ?>

<div class="container py-5">
    <h2 class="mb-4">Angajați și camere</h2>

    <table class="table table-striped table-dark">
        <thead>
            <tr>
                <th>Angajat</th>
                <th>Email</th>
                <th>Camere</th>
                <th>Acțiuni</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($employees as $emp): ?>
            <tr>
                <td><?= htmlspecialchars($emp['name']) ?></td>
                <td><?= htmlspecialchars($emp['email']) ?></td>
                <td>
                    <?php
                    $assigned = $employeeRooms[$emp['id']] ?? [];
                    foreach($assigned as $room_id){
                        $room = array_filter($allRooms, fn($r)=>$r['id']==$room_id);
                        if($room) echo htmlspecialchars(array_values($room)[0]['room_number'])." ";
                    }
                    ?>
                </td>
                <td>
                    <form method="POST" class="d-flex gap-1 flex-wrap">
                        <input type="hidden" name="employee_id" value="<?= $emp['id'] ?>">
                        <input type="hidden" name="bot_token" value="<?= $bot_token ?>">
                        <select name="room_id" required>
                            <option value="">Selectează cameră</option>
                            <?php foreach($allRooms as $room): ?>
                                <option value="<?= $room['id'] ?>"><?= $room['room_number'] ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="action" value="add" class="btn btn-success btn-sm">Adaugă</button>
                        <button type="submit" name="action" value="remove" class="btn btn-danger btn-sm">Șterge</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
