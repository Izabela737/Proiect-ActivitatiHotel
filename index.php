<?php
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();


$stmt = $pdo->prepare("
    SELECT * FROM reviews 
    ORDER BY rating DESC, created_at DESC 
    LIMIT 3
");

$stmt->execute();
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HotelManager</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="index.css">
</head>

<body>

<?php include 'navbar.php'; ?>

<header class="bg-primary text-white text-center py-5">
    <h1>HotelM</h1>
    <p class="lead">Confort. Eleganță. Experiență premium.</p>
</header>

<div class="container">
    <section class="mb-5">
        <h2 class="mb-3 text-center">Despre HotelM</h2>
        <div class="row">
            <div class="col-md-6">
                <p>
                    HotelM este un hotel modern situat într-o zonă liniștită, ideal pentru familii,
                    cupluri sau turiști care caută relaxare și servicii premium.
                </p>
                <p>
                    Oferim camere confortabile, facilități moderne și personal profesionist. 
                    Restaurantul nostru servește preparate diverse pentru toate gusturile.
                </p>
                <p>
                  Cu ajutorul aplicației HotelManager, clienții pot vizualiza meniul zilnic, solicita servicii de curățenie pentru cameră și realimentarea minibarului.
                   Astfel, aceștia își pot personaliza șederea și se bucură de o experiență cât mai confortabilă și plăcută în hotel.
                </p>
            </div>
            <div class="col-md-6">
                <div id="hotelCarousel" class="carousel slide shadow rounded" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active">
                            <img src="poze/hotel1.jpg" class="d-block w-100" alt="Hotel picture 1">
                        </div>
                        <div class="carousel-item">
                            <img src="poze/hotel2.jpg" class="d-block w-100" alt="Hotel picture 2">
                        </div>
                        <div class="carousel-item">
                            <img src="poze/hotel3.jpg" class="d-block w-100" alt="Hotel picture 3">
                        </div>
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#hotelCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#hotelCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- REVIEW-URI -->
    <section class="mb-5">
        <h2 class="text-center mb-3">Ce spun clienții</h2>

        <?php if (count($reviews) === 0): ?>
            <p class="text-center">Nu există review-uri momentan.</p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($reviews as $rev): ?>
                    <div class="col-md-4 mb-3">
                        <div class="card shadow-sm h-100">
                            <div class="card-body">
                                 <div class="mb-2">
                <strong style="font-size: 1.1rem; color: #ffffff;">
                    <?= htmlspecialchars($rev['user_name']) ?>
                </strong>
            </div> 

            <hr style="border-color:#ffffff40;">

            <!-- RATING + COMENTARIU -->
            <h5 class="card-title" style="color:#ffd700;">⭐ <?= $rev['rating'] ?>/5</h5>

            <p class="card-text" style="color:#e6e6e6;">
                <?= htmlspecialchars($rev['comment']) ?>
            </p>

                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="text-center mt-3">
            <a href="review.php" class="btn btn-primary">Vezi toate review-urile</a>
        </div>
    </section>

</div>

<footer class="footer">
    &copy; 2025 HotelManager.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
