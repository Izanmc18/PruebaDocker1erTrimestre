<?php
$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'survey';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT texto FROM chistes ORDER BY RAND() LIMIT 1");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $chiste = $row ? $row['texto'] : 'No hay chistes en la base de datos.';
} catch (PDOException $e) {
    $chiste = "Error de BD: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Chiste informático</title>
</head>
<body>
    <h1>Chiste informático aleatorio</h1>
    <p><?php echo $chiste; ?></p>
</body>
</html>
