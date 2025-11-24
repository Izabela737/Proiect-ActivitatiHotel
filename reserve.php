<?php
session_start();
require_once 'Database.php';
require_once 'mail/Mailer.php';
$pdo = Database::getInstance()->getConnection();

if(!isset($_SESSION['user_id'])) {
    echo "❌ Trebuie să fii logat pentru a face o rezervare.";
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST') {

    $user_id     = $_SESSION['user_id'];
    $room_id     = $_POST['room_id'] ?? null;
    $check_in    = $_POST['check_in'] ?? null;
    $check_out   = $_POST['check_out'] ?? null;
    $mealplan    = $_POST['meal_plan'] ?? null; 
    $total_price = $_POST['total_price'] ?? 0;

    if($room_id && $check_in && $check_out && $mealplan) {

        
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM reservations
            WHERE room_id = :room_id
              AND (start_date <= :check_out AND end_date >= :check_in)
        ");
        $stmt->execute([
            'room_id'   => $room_id,
            'check_in'  => $check_in,
            'check_out' => $check_out
        ]);

        if($stmt->fetchColumn() > 0) {
            echo "❌ Camera nu este disponibilă în perioada selectată.";
            exit;
        }
        $stmt = $pdo->prepare("
            INSERT INTO reservations 
                (user_id, room_id, start_date, end_date, meal_plan, total_price, status)
            VALUES 
                (:user_id, :room_id, :start_date, :end_date, :meal_plan, :total_price, :status)
        ");

        try {
            $stmt->execute([
                'user_id'     => $user_id,
                'room_id'     => $room_id,
                'start_date'  => $check_in,
                'end_date'    => $check_out,
                'meal_plan'   => $mealplan,
                'total_price' => $total_price,
                'status'      => 'pending'
            ]);


            echo "✅ Rezervarea a fost realizată cu succes!";
        } 
        catch(PDOException $e) {
            echo "❌ Eroare la rezervare: ".$e->getMessage();
        }

    } else {
        echo "❌ Completează toate câmpurile.";
    }
} else {
    echo "❌ Cerere invalidă.";
}
?>
