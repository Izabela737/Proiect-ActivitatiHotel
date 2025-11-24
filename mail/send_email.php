<?php

require_once('Mailer.php');

$mailer = new Mailer();
$mailer->sendMail("florin-petrisor.tanasa@s.unibuc.ro", "Petrisor Tanasa", "Test", "Mesajul ce va fi transmis");