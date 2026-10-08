<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste Firebird</title>
</head>
<body>
    <h1>Verificação do driver Firebird</h1>

    <?php
    $drivers = PDO::getAvailableDrivers();

    echo "<p>Drivers PDO disponíveis:</p>";
    echo "<pre>";
    print_r($drivers);
    echo "</pre>";

    if (in_array('firebird', $drivers, true)) {
        echo "<h2 style='color: green;'>Driver Firebird ativado!</h2>";
    } else {
        echo "<h2 style='color: red;'>Driver Firebird ainda não está ativado.</h2>";
    }
    ?>
</body>
</html>