<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

// ✅ Generare token anti-bot/CSRF pentru rezervări
if (!isset($_SESSION['bot_token'])) {
    $_SESSION['bot_token'] = bin2hex(random_bytes(16));
}
$bot_token = $_SESSION['bot_token'];


$loginMessage = "";
$registerMessage = "";

// LOGIN
if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: rooms.php");
            exit();
        } else {
            $loginMessage = "Email sau parola incorecte.";
        }
    } else {
        $loginMessage = "Completează toate câmpurile.";
    }
}

// REGISTER
if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = 'client';
    if ($name && $email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email'=>$email]);
        $user = $stmt->fetch();
        if ($user) {
            $registerMessage = "❌ Email-ul este deja folosit.";
        } else {
            $hashedPassword = password_hash($password,PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name,email,password,role) VALUES (:name,:email,:password,:role)");
            try {
                $stmt->execute([
                    'name'=>$name,
                    'email'=>$email,
                    'password'=>$hashedPassword,
                    'role'=>$role
                ]);
                $registerMessage = "✅ Cont creat cu succes! Te poți loga acum.";
            } catch (PDOException $e) {
                $registerMessage = "❌ Eroare la crearea contului: ".$e->getMessage();
            }
        }
    } else {
        $registerMessage = "Completează toate câmpurile.";
    }
}

$availableRooms = [];
$check_in = $_GET['check_in'] ?? '';
$check_out = $_GET['check_out'] ?? '';

if ($check_in && $check_out && strtotime($check_out) > strtotime($check_in)) {
    $stmt = $pdo->prepare("
        SELECT * FROM rooms WHERE status='available' AND id NOT IN (
            SELECT room_id FROM reservations 
            WHERE (start_date <= :check_out AND end_date >= :check_in)
        )
    ");
    $stmt->execute(['check_in'=>$check_in,'check_out'=>$check_out]);
    $availableRooms = $stmt->fetchAll();
} else {
    $stmt = $pdo->query("SELECT * FROM rooms WHERE status='available'");
    $availableRooms = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Camere Disponibile</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="rooms.css">
<style>
.modal { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.6); justify-content:center; align-items:center; z-index:9999; }
.modal-content { background:#fff; padding:20px; border-radius:8px; min-width:300px; max-width:400px; position:relative; }
.modal-content label { display:block; margin-top:10px; font-weight:600; }
.modal-content input, .modal-content select { width:100%; padding:8px; margin-top:5px; border-radius:5px; border:1px solid #ccc; }
.modal-content button { margin-top:15px; width:100%; padding:10px; border:none; border-radius:6px; background:#0d47a1; color:#fff; font-weight:600; cursor:pointer; }
.close-btn { position:absolute; top:10px; right:15px; font-size:24px; cursor:pointer; }
</style>
</head>
<body>

<?php include 'navbar.php'; ?>

<header class="bg-primary text-white text-center py-5">
    <h1>Camere Disponibile</h1>
    <p class="lead">Selectează datele pentru a vedea camerele disponibile</p>
</header>

<div class="container">
    <section class="mb-4">
        <form method="GET" class="d-flex justify-content-center gap-3 flex-wrap">
            <input type="date" name="check_in" id="check_in" value="<?= htmlspecialchars($check_in) ?>" required>
            <input type="date" name="check_out" id="check_out" value="<?= htmlspecialchars($check_out) ?>" required>
            <button type="submit" class="btn-primary">Caută camere</button>
        </form>
    </section>

    <section>
        <div class="row">
            <?php if(count($availableRooms) === 0): ?>
                <p class="text-center w-100">Nu există camere disponibile pentru aceste date.</p>
            <?php else: ?>
                <?php foreach($availableRooms as $room): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                <h5 class="card-title">Camera <?= htmlspecialchars($room['room_number']) ?></h5>
                                <p><strong>Tip:</strong> <?= htmlspecialchars($room['type']) ?></p>
                                <p><strong>Preț/noapte:</strong> <?= htmlspecialchars($room['price']) ?> RON</p>
                                <a href="#" class="btn-primary" onclick="return checkLogin(event, <?= $room['id'] ?>, <?= $room['price'] ?>)">Rezervă</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</div>

<!-- rezervare -->
<div id="reservationModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeReservationModal()">&times;</span>
        <h4>Rezervă Camera</h4>
        <form id="reservationForm">
            <input type="hidden" name="room_id" id="room_id" value="">
            <input type="hidden" name="check_in" id="modal_check_in">
            <input type="hidden" name="check_out" id="modal_check_out">
            <input type="hidden" name="total_price" id="total_price_input">
            <!-- ✅ Token anti-bot -->
            <input type="hidden" name="bot_token" value="<?= $bot_token ?>">
            <label for="meal_plan">Meal Plan:</label>
            <select name="meal_plan" id="meal_plan" required>
                <option value="no_meal" data-price="0">No meal</option>
                <option value="breakfast" data-price="20">Breakfast (+20 RON)</option>
                <option value="half_board" data-price="60">Half Board (+60 RON)</option>
                <option value="all_inclusive" data-price="100">All Inclusive (+100 RON)</option>
            </select>
            <hr>
            <label>Preț total:</label>
            <div id="total_price" style="font-size:20px; font-weight:bold;">0 RON</div>
            <button type="submit" id="submitReservation">Confirmă rezervarea</button>
        </form>
    </div>
</div>

<script>
const modalCheckIn = document.getElementById('modal_check_in');
const modalCheckOut = document.getElementById('modal_check_out');
const mealPlan = document.getElementById('meal_plan');
const totalPriceBox = document.getElementById('total_price');
const totalPriceInput = document.getElementById('total_price_input');

let roomPrice = 0;

function nightsBetween(){
    let d1 = new Date(modalCheckIn.value);
    let d2 = new Date(modalCheckOut.value);
    let ms = d2 - d1;
    return ms > 0 ? ms / (1000*60*60*24) : 0;
}

function calculateTotal(){
    let nopti = nightsBetween();
    let mealExtra = parseFloat(mealPlan.selectedOptions[0].dataset.price);
    let total = (nopti * roomPrice) + mealExtra;
    totalPriceBox.innerText = total + " RON";
    totalPriceInput.value = total;
}

mealPlan.addEventListener('change', calculateTotal);

function openReservationModal(roomId, price){
    roomPrice = parseFloat(price);
    document.getElementById('room_id').value = roomId;
    modalCheckIn.value = document.getElementById('check_in').value;
    modalCheckOut.value = document.getElementById('check_out').value;
    calculateTotal();
    document.getElementById('reservationModal').style.display = 'flex';
}

function closeReservationModal(){
    document.getElementById('reservationModal').style.display = 'none';
}

function checkLogin(event, roomId, price){
    <?php if(!isset($_SESSION['user_id'])): ?>
        event.preventDefault();
        alert("Trebuie să te loghezi!");
        window.location.href = "cont.php";
        return false;
    <?php else: ?>
        event.preventDefault();
        let ci = document.getElementById('check_in').value;
        let co = document.getElementById('check_out').value;
        if(!ci || !co){ alert("Selectează datele!"); return false; }
        openReservationModal(roomId, price);
        return false;
    <?php endif; ?>
}

const checkInInput = document.getElementById('check_in');
const checkOutInput = document.getElementById('check_out');

const today = new Date().toISOString().split('T')[0];
checkInInput.min = today;

checkInInput.addEventListener('change', () => {
    if (!checkInInput.value) return;

    let checkInDate = new Date(checkInInput.value);
    checkInDate.setDate(checkInDate.getDate() + 1); 

    let minCheckOut = checkInDate.toISOString().split('T')[0];
    checkOutInput.min = minCheckOut;

    if (checkOutInput.value && checkOutInput.value < minCheckOut) {
        checkOutInput.value = minCheckOut;
    }

    modalCheckIn.value = checkInInput.value;
    modalCheckOut.value = checkOutInput.value;
    calculateTotal();
});

const reservationForm = document.getElementById('reservationForm');

reservationForm.addEventListener('submit', function(e) {
    e.preventDefault(); 

    const formData = new FormData(reservationForm);

    fetch('reserve.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert(data); 
        closeReservationModal(); 
    })
    .catch(error => {
        alert("❌ A apărut o eroare la rezervare.");
        console.error(error);
    });
});
</script>

</body>
</html>
