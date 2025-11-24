<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<nav class="navbar navbar-expand-lg navbar-dark" style="background-color: #081525; margin:0; padding:10px 20px;">
  <div class="container-fluid" style="padding-left:0; padding-right:0;">
    <a class="navbar-brand" href="index.php">HotelManager</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="rooms.php">Camere Disponibile</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="myroom.php">Camera Mea</a>
        </li>
        <li class="nav-item position-relative">
          <a class="nav-link" href="cont.php">
            Contul Meu
            <span class="login-indicator <?= isset($_SESSION['user_id']) ? 'online' : 'offline' ?>"></span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="review.php">Review-uri</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<style>
.login-indicator {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    margin-left: 5px;
    vertical-align: middle;
}

.login-indicator.online {
    background-color: #28a745; 
}

.login-indicator.offline {
    background-color: #dc3545;
}
</style>

