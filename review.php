<?php
session_start();
require_once 'Database.php';
$pdo = Database::getInstance()->getConnection();

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_SESSION['user_id']) &&
    isset($_POST['rating'], $_POST['comment'], $_POST['csrf']) &&
    $_POST['csrf'] === $_SESSION['csrf_token']
) {
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['comment']);

    if ($rating >= 1 && $rating <= 5 && $comment !== '' && strlen($comment) <= 500) {
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, user_name, rating, comment)
            VALUES (:user_id, :user_name, :rating, :comment)
        ");
        $stmt->execute([
            'user_id'   => $_SESSION['user_id'],
            'user_name' => $_SESSION['user_name'],
            'rating'    => $rating,
            'comment'   => $comment
        ]);
    }
    header("Location: review.php");
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM reviews ORDER BY created_at DESC");
$stmt->execute();
$reviews = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ro">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Review-uri HotelM</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="review.css">
</head>
<body>

<?php include 'navbar.php'; ?>

<div class="container py-5">

<?php if (isset($_SESSION['user_id'])): ?>
<section class="mb-5">
    <h2 class="text-center mb-4">Lasă un review</h2>
    <div class="card p-4 shadow" style="background:#1e2b40;">
        <form method="POST">
            <input type="hidden" name="csrf" value="<?= $csrf_token ?>">

            <div class="mb-3">
                <label class="form-label">Rating</label>
                <select name="rating" class="form-select" required>
                    <option value="">Alege...</option>
                    <?php for($i=1;$i<=5;$i++): ?>
                        <option value="<?= $i ?>">⭐ <?= $i ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="mb-3">
                <label class="form-label">Comentariu</label>
                <textarea name="comment" class="form-control" rows="4" maxlength="500" required></textarea>
            </div>

            <button class="btn btn-primary w-100">Trimite review</button>
        </form>
    </div>
</section>
<?php endif; ?>

<section>
<h2 class="text-center mb-4">Ultimele review-uri</h2>

<?php if (count($reviews) === 0): ?>
    <p class="text-center">Nu există review-uri.</p>
<?php else: ?>
<div class="row">
<?php foreach ($reviews as $rev): ?>
<div class="col-md-4 mb-3">
<div class="card h-100 shadow-sm" style="background:#1e2b40;color:white;">
<div class="card-body">

<strong><?= htmlspecialchars($rev['user_name']) ?></strong><br>
<small><?= $rev['created_at'] ?></small>
<hr>

<h5 style="color:#ffd700;">⭐ <?= $rev['rating'] ?>/5</h5>
<p><?= htmlspecialchars($rev['comment']) ?></p>

<div class="d-flex gap-2 mt-3">

<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $rev['user_id']): ?>
<button
    class="btn btn-warning btn-sm edit-btn"
    data-id="<?= $rev['id'] ?>"
    data-rating="<?= $rev['rating'] ?>"
    data-comment="<?= htmlspecialchars($rev['comment'], ENT_QUOTES) ?>"
    data-csrf="<?= $csrf_token ?>"
>
    Editare
</button>
<?php endif; ?>

<?php if (
    isset($_SESSION['user_id']) &&
    ($_SESSION['user_id'] == $rev['user_id'] || ($_SESSION['user_role'] ?? '') === 'manager')
): ?>
<button
    class="btn btn-danger btn-sm delete-btn"
    data-id="<?= $rev['id'] ?>"
    data-csrf="<?= $csrf_token ?>"
>
    Ștergere
</button>
<?php endif; ?>

</div>

</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</section>

</div>

<div class="modal fade" id="editModal" tabindex="-1">
<div class="modal-dialog">
<div class="modal-content" style="background:#1e2b40;color:white;">
<div class="modal-header">
    <h5 class="modal-title">Editează review</h5>
    <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <input type="hidden" id="edit_id">
    <input type="hidden" id="edit_csrf">

    <div class="mb-3">
        <label>Rating</label>
        <select id="edit_rating" class="form-select">
            <?php for($i=1;$i<=5;$i++): ?>
                <option value="<?= $i ?>">⭐ <?= $i ?></option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="mb-3">
        <label>Comentariu</label>
        <textarea id="edit_comment" class="form-control" rows="4"></textarea>
    </div>
</div>

<div class="modal-footer">
    <button class="btn btn-primary" id="saveEditBtn">Salvează</button>
</div>
</div>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>

const editModal = new bootstrap.Modal(document.getElementById('editModal'));

document.querySelectorAll(".edit-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        edit_id.value = btn.dataset.id;
        edit_rating.value = btn.dataset.rating;
        edit_comment.value = btn.dataset.comment;
        edit_csrf.value = btn.dataset.csrf;
        editModal.show();
    });
});

saveEditBtn.addEventListener("click", () => {
    fetch("edit_review.php", {
        method: "POST",
        headers: {"Content-Type":"application/x-www-form-urlencoded"},
        body:
            "id=" + edit_id.value +
            "&rating=" + edit_rating.value +
            "&comment=" + encodeURIComponent(edit_comment.value) +
            "&csrf=" + edit_csrf.value
    })
    .then(r => r.text())
    .then(res => {
        if (res === "success") location.reload();
        else alert("Eroare: " + res);
    });
});


document.querySelectorAll(".delete-btn").forEach(btn => {
    btn.addEventListener("click", () => {
        if (!confirm("Sigur vrei să ștergi review-ul?")) return;

        fetch("delete_review.php", {
            method: "POST",
            headers: {"Content-Type":"application/x-www-form-urlencoded"},
            body: "id=" + btn.dataset.id + "&csrf=" + btn.dataset.csrf
        })
        .then(r => r.text())
        .then(res => {
            if (res === "success") location.reload();
            else alert("Eroare: " + res);
        });
    });
});
</script>

</body>
</html>
