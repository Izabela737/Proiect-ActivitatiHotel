<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

if(!isset($_SESSION['user_id'])) {
    $showAuthLinks = true;
} else {
    $showAuthLinks = false;
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = :id");
    $stmt->execute(['id'=>$userId]);
    $user = $stmt->fetch();

    $currentYear = date('Y');
    $stmt = $pdo->prepare("SELECT r.*, rm.room_number, rm.type FROM reservations r 
                           JOIN rooms rm ON r.room_id = rm.id
                           WHERE r.user_id = :user_id AND YEAR(r.start_date) = :year
                           ORDER BY r.start_date DESC");
    $stmt->execute(['user_id'=>$userId, 'year'=>$currentYear]);
    $reservations = $stmt->fetchAll();
}

$deleteMessage = "";
if(isset($_POST['delete_account']) && $_POST['delete_account'] === 'yes') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $stmt->execute(['id'=>$userId]);
    session_destroy();
    header("Location: index.php");
    exit();
}


$loginMessage = "";
$registerMessage = "";
if (isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    if ($email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email'=>$email]);
        $userLogin = $stmt->fetch();
        if ($userLogin && password_verify($password, $userLogin['password'])) {
            $_SESSION['user_id'] = $userLogin['id'];
            $_SESSION['user_name'] = $userLogin['name'];
            $_SESSION['user_role'] = $userLogin['role'];
            header("Location: cont.php");
            exit();
        } else {
            $loginMessage = "Email sau parola incorecte.";
        }
    } else {
        $loginMessage = "Completează toate câmpurile.";
    }
}

if (isset($_POST['register'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = 'client';
    if ($name && $email && $password) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email'=>$email]);
        $userCheck = $stmt->fetch();
        if ($userCheck) {
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
?>

<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Contul Meu</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="cont.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container py-5">

<?php if($showAuthLinks): ?>
    <!-- Login/Register -->
    <div class="auth-central-box">
        <?php if($loginMessage): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($loginMessage) ?></div>
        <?php endif; ?>
        <?php if($registerMessage): ?>
            <div class="alert alert-info"><?= htmlspecialchars($registerMessage) ?></div>
        <?php endif; ?>
        <div class="auth-tabs text-center mb-3">
            <button id="showLogin" class="active btn btn-outline-light btn-sm">Login</button>
            <button id="showRegister" class="btn btn-outline-light btn-sm">Înregistrare</button>
        </div>
        <form id="loginForm" method="POST">
            <input type="email" name="email" placeholder="Email" required class="form-control mb-2">
            <input type="password" name="password" placeholder="Parolă" required class="form-control mb-2">
            <button type="submit" name="login" class="btn btn-primary w-100">Login</button>
        </form>
        <form id="registerForm" method="POST" style="display:none;">
            <input type="text" name="name" placeholder="Nume" required class="form-control mb-2">
            <input type="email" name="email" placeholder="Email" required class="form-control mb-2">
            <input type="password" name="password" placeholder="Parolă" required class="form-control mb-2">
            <button type="submit" name="register" class="btn btn-primary w-100">Crează cont</button>
        </form>
    </div>
<?php else: ?>
    <h2 class="mb-4">Hello, <?= htmlspecialchars($user['name']) ?>!</h2>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <h4 class="mt-4">
    <a href="logout.php" class="btn btn-logout">Deloghează-te</a>
    </h4>
    <h4 class="mt-4">Rezervările mele (<?= date('Y') ?>)</h4>
    <?php if(count($reservations) === 0): ?>
        <p>Nu ai rezervări în acest an.</p>
    <?php else: ?>
        <table class="table table-dark table-striped mt-2">
            <thead>
                <tr>
                    <th>Camera</th>
                    <th>Tip</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($reservations as $res): ?>
                    <tr>
                        <td><?= htmlspecialchars($res['room_number']) ?></td>
                        <td><?= htmlspecialchars($res['type']) ?></td>
                        <td><?= htmlspecialchars($res['start_date']) ?></td>
                        <td><?= htmlspecialchars($res['end_date']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
    

    <h4 class="mt-5">Șterge contul</h4>
<?php if($deleteMessage): ?>
    <div class="alert alert-info"><?= htmlspecialchars($deleteMessage) ?></div>
<?php endif; ?>

<button id="showDeleteConfirm" class="btn btn-danger">Șterge cont</button>

<div id="deleteConfirm" style="display:none; margin-top:10px;">
    <p>Ești sigur că vrei să ștergi contul?</p>
    <form method="POST" style="display:inline;">
        <button type="submit" name="delete_account" value="yes" class="btn btn-danger">Da, șterge contul</button>
    </form>
    <button id="cancelDelete" class="btn btn-secondary">Nu</button>
</div>

<?php endif; ?>

</div>
<script>

document.getElementById('showDeleteConfirm').addEventListener('click', function() {
    this.style.display = 'none'; 
    document.getElementById('deleteConfirm').style.display = 'block'; 
});


document.getElementById('cancelDelete').addEventListener('click', function() {
    document.getElementById('deleteConfirm').style.display = 'none';
    document.getElementById('showDeleteConfirm').style.display = 'inline-block';
});
</script>
<script>
document.getElementById('showLogin').addEventListener('click', function(){
    document.getElementById('loginForm').style.display='block';
    document.getElementById('registerForm').style.display='none';
    this.classList.add('active');
    document.getElementById('showRegister').classList.remove('active');
});
document.getElementById('showRegister').addEventListener('click', function(){
    document.getElementById('loginForm').style.display='none';
    document.getElementById('registerForm').style.display='block';
    this.classList.add('active');
    document.getElementById('showLogin').classList.remove('active');
});
</script>

</body>
</html>
