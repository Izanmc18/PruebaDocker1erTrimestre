<?php
$host = getenv('DB_HOST') ?: 'mysql';
$db   = getenv('DB_NAME') ?: 'survey';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: 'root';

$ip = $_SERVER['SERVER_ADDR'] ?? 'desconocida';
$hostname = gethostname();


try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['voto'])) {
        if ($_POST['voto'] === 'si') {
            $pdo->exec("UPDATE votos SET si = si + 1 WHERE id = 1");
        } elseif ($_POST['voto'] === 'no') {
            $pdo->exec("UPDATE votos SET no = no + 1 WHERE id = 1");
        }

        header("Location: /");
    }

    $stmt = $pdo->query("SELECT si, no FROM votos WHERE id = 1");
    $row  = $stmt->fetch(PDO::FETCH_ASSOC);
    $si   = $row['si'] ?? 0;
    $no   = $row['no'] ?? 0;
} catch (PDOException $e) {
    die("Error de BD: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Encuesta Linares</title>
</head>
<body>
    <h1>¿Independizar Linares de la provincia de Jaén?</h1>
    <form method="POST">
        <label>
            <input type="radio" name="voto" value="si" required> Sí
        </label>
        <br>
        <label>
            <input type="radio" name="voto" value="no" required> No
        </label>
        <br><br>
        <button type="submit">Votar</button>
    </form>

    <h2>Resultados totales</h2>
    <p>Sí: <?php echo $si; ?></p>
    <p>No: <?php echo $no; ?></p>

    <br>
    <p>Contenedor: <?php echo $hostname; ?></p>
    <p>IP del contenedor: <?php echo $ip; ?></p>
</body>
</html>
