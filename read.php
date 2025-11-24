<!DOCTYPE html>
 
<?php
 
if (isset($_GET['id'])) {
    $id = $_GET['id'];
 
$host = "localhost";    
$db   = "hotel_db";       
$user = "root";         
$pass = "";             
$charset = "utf8mb4";
 
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
 
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
 

    $sql = "SELECT * FROM stiri WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(
        [
            'id' => $id
        ]
    );
    $record = $stmt->fetch();
 
   
 
    $title = $record['titlu'];
    $shortDescription = $record['short_description'];
    $description = $record['description'];
 
    echo "<table border='1'>
            <tr>
                <th>Title</th>
                <th>Short Description</th>
                <th>Description</th>
            </tr>
            <tr>
                <td>" . htmlspecialchars($title) . "</td>
                <td>" . htmlspecialchars($shortDescription) . "</td>
                <td>" . nl2br(htmlspecialchars($description)) . "</td>
            </tr>
        </table>";
 
    if ($record) {
        echo "✅ Record found!<br>";
        echo "Title: " . htmlspecialchars($record['titlu']) . "<br>";
        echo "Short Description: " . htmlspecialchars($record['short_description']) . "<br>";
        echo "Description: " . nl2br(htmlspecialchars($record['description'])) . "<br>";
    } else {
        echo "❌ No record found with ID: $id";
    }
} catch (PDOException $e) {
    die("❌ Connection failed: " . $e->getMessage());
}
}
 
?>
 
<html lang="en">
<head>
</head>
<body>
 
 
</body>
<?php
?>
</html>