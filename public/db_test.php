<?php
try {
    $pdo = new PDO('mysql:host=localhost;dbname=b2b2c503', 'root', '123456');
    echo "数据库连接成功！<br>";
    
    // 测试查询
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "数据库中的表：<br>";
    foreach($tables as $table) {
        echo "- " . $table . "<br>";
    }
} catch(PDOException $e) {
    echo "数据库连接失败：" . $e->getMessage();
}
?>