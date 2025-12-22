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
            

            $reservation_id = $pdo->lastInsertId();
            

            $stmt = $pdo->prepare("SELECT room_number, type FROM rooms WHERE id = :room_id");
            $stmt->execute(['room_id' => $room_id]);
            $room = $stmt->fetch(PDO::FETCH_ASSOC);
            $room_name = $room['room_number'] ?? 'Nespecificat'; 
            $room_type = $room['type'] ?? 'Nespecificat';

            if (!empty($_SESSION['user_email'])) {
                $mailer = new Mailer();
                
                $subject = "Confirmare rezervare HotelM - ID: #" . $reservation_id;
 
                $messageHTML = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        .header { background: #4CAF50; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px; }
        .details { background: #f9f9f9; padding: 15px; border-left: 4px solid #4CAF50; margin: 15px 0; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>HotelM - Confirmare Rezervare</h1>
        </div>
        
        <div class='content'>
            <p>Salut <strong>{$_SESSION['user_name']}</strong>,</p>
            <p>Rezervarea ta a fost înregistrată cu succes și se află în așteptarea confirmării.</p>
            
            <div class='details'>
                <h3>📋 Detalii rezervare:</h3>
                <p><strong>Număr rezervare:</strong> #{$reservation_id}</p>
                <p><strong>Număr cameră:</strong> {$room_name} ({$room_type})</p>
                <p><strong>Check-in:</strong> {$check_in}</p>
                <p><strong>Check-out:</strong> {$check_out}</p>
                <p><strong>Plan masă:</strong> {$mealplan}</p>
                <p><strong>Total de plată:</strong> {$total_price} RON</p>
                <p><strong>Status:</strong> <span style='color: orange; font-weight: bold;'>Pending (în așteptare)</span></p>
            </div>
            
            <p><strong>❗ Informații importante:</strong></p>
            <ul>
                <li>Rezervarea va fi confirmată în maxim 24 de ore</li>
                <li>Veți primi un email când rezervarea este confirmată</li>
                <li>Pentru anulări, contactați-ne cu cel puțin 48h înainte de check-in</li>
                <li>Check-in: după ora 14:00</li>
                <li>Check-out: până la ora 12:00</li>
            </ul>
            
            <p>Dacă aveți întrebări, nu ezitați să ne contactați.</p>
        </div>
        
        <div class='footer'>
            <p>Cu drag,<br>
            <strong>Echipa HotelM</strong><br>
            📞 0800 123 456<br>
            ✉️ contact@hotelm.ro</p>
            <p>Acest email a fost generat automat. Vă rugăm să nu răspundeți.</p>
        </div>
    </div>
</body>
</html>
                ";
                
                $messageText = "
Salut {$_SESSION['user_name']},

Rezervarea ta la HotelM a fost înregistrată cu succes.

📋 Detalii rezervare:
- Numar rezervare: #{$reservation_id}
- Numar cameră: {$room_name} ({$room_type})
- Check-in: {$check_in}
- Check-out: {$check_out}
- Plan masă: {$mealplan}
- Total: {$total_price} RON
- Status: pending (în așteptare confirmare)

❗ Informații importante:
• Rezervarea va fi confirmată în maxim 24 de ore
• Vei primi un email când rezervarea este confirmată
• Pentru anulări, contactează-ne cu cel puțin 48h înainte de check-in
• Check-in: după ora 14:00
• Check-out: până la ora 12:00

Dacă ai întrebări, nu ezita să ne contactezi.

Cu drag,
Echipa HotelM
📞 0800 123 456
✉️ contact@hotelm.ro

Acest email a fost generat automat. Vă rugăm să nu răspundeți.
                ";
                
                $sendResult = $mailer->sendMail(
                    $_SESSION['user_email'],
                    $_SESSION['user_name'],
                    $subject,
                    $messageHTML,
                    $messageText
                );

                if ($sendResult === true) {
                    $email_sent = true;
                     echo "<br>📧 Email-ul a fost 'trimis' (pe localhost e doar simulare).";
                } else {
                    $email_sent = false;
                     echo "<br>⚠️ Email-ul nu s-a putut trimite: " . $sendResult;
                }
            } else {
                $email_sent = false;
            }

            if ($email_sent) {
                echo "✅ Rezervarea a fost realizată cu succes! Un email de confirmare a fost trimis către {$_SESSION['user_email']}.";
            } else {
                echo "✅ Rezervarea a fost realizată cu succes!";
            }

        } catch(PDOException $e) {
            echo "❌ Eroare la rezervare: " . $e->getMessage();
        }

    } else {
        echo "❌ Completează toate câmpurile.";
    }
} else {
    echo "❌ Cerere invalidă.";
}
?>