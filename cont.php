<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

$showAuthLinks = !isset($_SESSION['user_id']); 

$user = null;
$reservations = [];

if (
    !isset($_SESSION['captcha']) ||
    !is_array($_SESSION['captcha']) ||
    !isset($_SESSION['captcha']['question'], $_SESSION['captcha']['answer'])
) {
    $a = rand(1, 9);
    $b = rand(1, 9);
    $_SESSION['captcha'] = [
        'question' => "$a + $b",
        'answer' => $a + $b
    ];
}

if (!isset($_SESSION['bot_token'])) {
    $_SESSION['bot_token'] = bin2hex(random_bytes(16));
}
$bot_token = $_SESSION['bot_token'];

if(!$showAuthLinks) {
    $userId = $_SESSION['user_id'];

    $stmt = $pdo->prepare("SELECT name, email, role FROM users WHERE id = :id");
    $stmt->execute(['id'=>$userId]);
    $user = $stmt->fetch();

    $_SESSION['user_name'] = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role'] = $user['role'];

    if($user['role'] === 'client') {
        $currentYear = date('Y');
        $stmt = $pdo->prepare("
            SELECT r.*, rm.room_number, rm.type 
            FROM reservations r 
            JOIN rooms rm ON r.room_id = rm.id
            WHERE r.user_id = :user_id AND YEAR(r.start_date) = :year
            ORDER BY r.start_date DESC
        ");
        $stmt->execute(['user_id'=>$userId, 'year'=>$currentYear]);
        $reservations = $stmt->fetchAll();
    }
}

$deleteMessage = "";
if(isset($_POST['delete_account'], $_POST['bot_token']) && $_POST['delete_account'] === 'yes') {
    if ($_POST['bot_token'] !== $_SESSION['bot_token']) {
        $deleteMessage = "❌ Token invalid!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
        $stmt->execute(['id'=>$userId]);
        session_destroy();
        header("Location: index.php");
        exit();
    }
}

$loginMessage = "";
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
            $_SESSION['user_email'] = $userLogin['email'];
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

$registerMessage = "";
if (isset($_POST['register'])) {
    $captchaAnswer = $_POST['captcha'] ?? '';
    if ($captchaAnswer != $_SESSION['captcha']['answer']) {
        $registerMessage = "❌ Răspuns captcha incorect.";
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = 'client';

        if ($name && $email && $password) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email'=>$email]);
            $userCheck = $stmt->fetch();
            if ($userCheck) {
                $registerMessage = "Email-ul este deja folosit.";
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
                    $_SESSION['user_id'] = $pdo->lastInsertId();
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_role'] = $role;
                    $registerMessage = "✅ Cont creat cu succes! Te poți loga acum.";
                    $showAuthLinks = true;
                } catch (PDOException $e) {
                    $registerMessage = "Eroare la crearea contului: ".$e->getMessage();
                }
            }
        } else {
            $registerMessage = "Completează toate câmpurile.";
        }
    }
}

if (isset($_POST['cancel_reservation_id'], $_POST['bot_token'])) {
    if ($_POST['bot_token'] === $_SESSION['bot_token']) {
        $cancelId = $_POST['cancel_reservation_id'];
        $stmt = $pdo->prepare("DELETE FROM reservations WHERE id = :id AND user_id = :user_id AND status = 'pending'");
        $stmt->execute(['id' => $cancelId, 'user_id' => $userId]);
        header("Location: cont.php");
        exit();
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
            <label>Rezolvă: <?= htmlspecialchars($_SESSION['captcha']['question']) ?></label>
            <input type="text" name="captcha" required class="form-control mb-2">
            <button type="submit" name="register" class="btn btn-primary w-100">Crează cont</button>
        </form>
    </div>

<?php else: ?>

    <h2 class="mb-4">Hello, <?= htmlspecialchars($user['name']) ?>!</h2>
    <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
    <h4 class="mt-4">
        <a href="logout.php" class="btn btn-logout">Deloghează-te</a>
    </h4>

    <?php if($user['role'] === 'client'): ?>
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
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($reservations as $res): ?>
                        <tr>
                            <td><?= htmlspecialchars($res['room_number']) ?></td>
                            <td><?= htmlspecialchars($res['type']) ?></td>
                            <td><?= htmlspecialchars($res['start_date']) ?></td>
                            <td><?= htmlspecialchars($res['end_date']) ?></td>
                            <td><?= htmlspecialchars($res['status']) ?>
                                <?php if ($res['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="cancel_reservation_id" value="<?= $res['id'] ?>">
                                        <input type="hidden" name="bot_token" value="<?= $bot_token ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" style="margin:7px">Anulează</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endif; ?>

    <h4 class="mt-5">Șterge contul</h4>
    <?php if($deleteMessage): ?>
        <div class="alert alert-info"><?= htmlspecialchars($deleteMessage) ?></div>
    <?php endif; ?>

    <button id="showDeleteConfirm" class="btn btn-danger">Șterge cont</button>
    <div id="deleteConfirm" style="display:none; margin-top:10px;">
        <p>Ești sigur că vrei să ștergi contul?</p>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="bot_token" value="<?= $bot_token ?>">
            <button type="submit" name="delete_account" value="yes" class="btn btn-danger">Da, șterge contul</button>
        </form>
        <button id="cancelDelete" class="btn btn-secondary">Nu</button>
    </div>
<?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showLoginBtn = document.getElementById('showLogin');
    const showRegisterBtn = document.getElementById('showRegister');
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if(showLoginBtn && showRegisterBtn && loginForm && registerForm) {
        showLoginBtn.addEventListener('click', function(){
            loginForm.style.display='block';
            registerForm.style.display='none';
            showLoginBtn.classList.add('active');
            showRegisterBtn.classList.remove('active');
        });

        showRegisterBtn.addEventListener('click', function(){
            loginForm.style.display='none';
            registerForm.style.display='block';
            showRegisterBtn.classList.add('active');
            showLoginBtn.classList.remove('active');
        });
    }
    document.getElementById('showDeleteConfirm').addEventListener('click', function() {
        this.style.display = 'none'; 
        document.getElementById('deleteConfirm').style.display = 'block'; 
    });
    document.getElementById('cancelDelete').addEventListener('click', function() {
        document.getElementById('deleteConfirm').style.display = 'none';
        document.getElementById('showDeleteConfirm').style.display = 'inline-block';
    });
});
</script>

</body>
</html>
