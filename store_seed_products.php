<?php
// Run once (in the browser) after importing `store_schema.sql`.
// Inserts some sample medicines with images under `assets/store/images/`.

include 'database.php';

$products = [
    ['name' => 'Paracetamol 650mg', 'price' => 50, 'image' => 'assets/store/images/dolo650.jpg'],
    ['name' => 'Cetirizine 10mg', 'price' => 35, 'image' => 'assets/store/images/cetirizine.jpg'],
    ['name' => 'Amoxicillin 500mg', 'price' => 120, 'image' => 'assets/store/images/amoxicillin.jpg'],
    ['name' => 'Azithromycin 500mg', 'price' => 180, 'image' => 'assets/store/images/azithromycin.jpg'],
    ['name' => 'ORS Sachet', 'price' => 25, 'image' => 'assets/store/images/ors.png'],
    ['name' => 'Pantoprazole 40mg', 'price' => 95, 'image' => 'assets/store/images/pantoprazole.webp'],
    ['name' => 'Ibuprofen 400mg', 'price' => 60, 'image' => 'assets/store/images/ibuprofen.webp'],
    ['name' => 'Hand Sanitizer', 'price' => 70, 'image' => 'assets/store/images/sanitizer.jpg'],
    ['name' => 'Thermometer', 'price' => 150, 'image' => 'assets/store/images/thermometer.jpg'],
    ['name' => 'BP Monitor', 'price' => 1299, 'image' => 'assets/store/images/bp_monitor.webp'],
];

$stmt = $conn->prepare("
    INSERT INTO products (name, price, image)
    SELECT ?, ?, ?
    WHERE NOT EXISTS (SELECT 1 FROM products WHERE name = ?)
");
foreach ($products as $p) {
    $stmt->bind_param("sdss", $p['name'], $p['price'], $p['image'], $p['name']);
    $stmt->execute();
}
$stmt->close();

echo "✅ Seeded " . count($products) . " products.";
