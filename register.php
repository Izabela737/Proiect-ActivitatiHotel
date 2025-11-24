<?php
require_once 'Database.php';

$message = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role     = 'client'; 

    if ($name && $email && $password) {
        $pdo = Database::getInstance()->getConnection();

      
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            $message = "❌ Email-ul este deja folosit.";
        } else {
           
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

          
            $stmt = $pdo->prepare(
                "INSERT INTO users (name, email, password, role) 
                 VALUES (:name, :email, :password, :role)"
            );

           try {
            $stmt->execute([
                'name'     => $name,
                'email'    => $email,
                'password' => $hashedPassword,
                'role'     => $role
            ]);
         
            header("Location: login.php");
            exit();
} catch (PDOException $e) {
    $message = "❌ Eroare la crearea userului: " . $e->getMessage();
}

        }

    } else {
        $message = "Completează toate câmpurile.";
    }
}
?>

<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h1 class="mb-4">Înregistrare</h1>

    <?php if($message): ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label>Nume</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
        </div>
        <div class="mb-3">
            <label>Parolă</label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Crează cont</button>
    </form>
</div>
</body>
</html>
