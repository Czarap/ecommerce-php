<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Icon Test</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            padding: 20px;
            font-family: Arial, sans-serif;
        }
        .icon-test {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        .icon-box {
            text-align: center;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .icon-box i {
            font-size: 32px;
            margin-bottom: 10px;
            display: block;
        }
    </style>
</head>
<body>
    <h1>Icon Test Page</h1>
    
    <div class="icon-test">
        <?php
        $icons = [
            'Electronics' => 'bi-cpu',
            'Clothing' => 'bi-bag',
            'Books' => 'bi-book',
            'Home' => 'bi-house',
            'Sports' => 'bi-trophy',
            'Beauty' => 'bi-heart',
            'Toys' => 'bi-controller',
            'Food' => 'bi-cup-hot',
            'Automotive' => 'bi-car-front',
            'Health' => 'bi-heart-pulse',
            'Garden' => 'bi-flower1',
            'Pet Supplies' => 'bi-egg',
            'Office' => 'bi-briefcase',
            'Music' => 'bi-music-note'
        ];

        foreach ($icons as $name => $icon) {
            echo "<div class='icon-box'>";
            echo "<i class='bi {$icon}'></i>";
            echo "<div>{$name}</div>";
            echo "<div><code>{$icon}</code></div>";
            echo "</div>";
        }
        ?>
    </div>

    <h2>Current Database Icons</h2>
    <div class="icon-test">
        <?php
        $stmt = $conn->query("SELECT name, icon FROM categories ORDER BY id");
        while ($row = $stmt->fetch_assoc()) {
            echo "<div class='icon-box'>";
            echo "<i class='bi {$row['icon']}'></i>";
            echo "<div>{$row['name']}</div>";
            echo "<div><code>{$row['icon']}</code></div>";
            echo "</div>";
        }
        ?>
    </div>
</body>
</html> 