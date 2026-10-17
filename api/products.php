<?php

header("Content-Type: application/json");

require_once "db.php";

$sql = "SELECT * FROM products";

$result = $conn->query($sql);

$products = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

echo json_encode($products);

$conn->close();

?>
