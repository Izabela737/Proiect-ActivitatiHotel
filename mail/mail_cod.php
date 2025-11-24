<?php

require_once('class.phpmailer.php');
require_once('mail_config.php');

// Mesajul
$message = "Mesajul ce va fi transmis";

// În caz că vre-un rând depășește N caractere, trebuie să utilizăm
// wordwrap()
$message = wordwrap($message, 160, "<br />\n");


// Username:	account@ptanasa.daw.ssmr.ro
// Password:	123456
// POP/IMAP Server:	mail.ptanasa.daw.ssmr.ro
// SMTP Server:	mail.ptanasa.daw.ssmr.ro port 587

$mail = new PHPMailer(true); 

$mail->IsSMTP();

try {

  $username = "account@ptanasa.daw.ssmr.ro";
  $password = "123456";
 
  $mail->SMTPDebug  = 0;                     
  $mail->SMTPAuth   = true; 

  $to='florin-petrisor.tanasa@s.unibuc.ro';
  $nume='Petrisor Tanasa';

  $mail->SMTPSecure = "ssl";                 
  $mail->Host       = "mail.ptanasa.daw.ssmr.ro";      
  $mail->Port       = 465;                   
  $mail->Username   = $username;  			// GMAIL username
  $mail->Password   = $password;            // GMAIL password
  //$mail->AddReplyTo('ssmrro@vsl.daw.ssmr.ro', 'Daw Email');
  $mail->AddAddress($to, $nume);
 
  $mail->SetFrom('account@ptanasa.daw.ssmr.ro', 'Daw Email');
  $mail->Subject = 'Test';
  $mail->AltBody = 'To view this post you need a compatible HTML viewer!'; 
  $mail->MsgHTML($message);
  $mail->Send();
  echo "Message Sent OK</p>\n";
} catch (phpmailerException $e) {
  echo $e->errorMessage(); //error from PHPMailer
} catch (Exception $e) {
  echo $e->getMessage(); //error from anything else!
}
?>
