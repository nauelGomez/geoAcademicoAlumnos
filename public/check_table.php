<?php
// Check table structure
$host = '127.0.0.1';
$database = 'dmendoza_pesge_demo';
$username = 'root';
$password = '8695';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== TAREAS_RESOLUCIONES TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE tareas_resoluciones");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    echo "\n=== TAREAS_CONSULTAS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE tareas_consultas");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
    echo "\n=== TAREAS_ENVIOS TABLE STRUCTURE ===\n";
    $stmt = $pdo->query("DESCRIBE tareas_envios");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " - " . $row['Type'] . "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
