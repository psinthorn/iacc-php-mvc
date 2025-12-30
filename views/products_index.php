<!DOCTYPE html>
<html>
<head>
    <title>Products</title>
    <meta charset="UTF-8">
</head>
<body>

<h1>Products</h1>

<?php foreach ($products as $product): ?>

    <h2>Price: <?= htmlspecialchars($product["price"]) ?></h2>
    <p>Quantity: <?= htmlspecialchars($product["quantity"]) ?></p>
    <p>Type: <?= htmlspecialchars($product["type"]) ?></p>


<?php endforeach; ?>

</body>
</html>