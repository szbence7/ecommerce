<?php
require_once 'includes/db.php';

$product_images = [
    1 => "https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop", // Headphones
    2 => "https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=400&h=300&fit=crop", // Watch
    3 => "https://images.unsplash.com/photo-1546868871-7041f2a55e12?w=400&h=300&fit=crop", // Smart Watch
    4 => "https://images.unsplash.com/photo-1585386959984-a4155224a1ad?w=400&h=300&fit=crop", // Perfume
    5 => "https://images.unsplash.com/photo-1572569511254-d8f925fe2cbb?w=400&h=300&fit=crop", // Backpack
    6 => "https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=400&h=300&fit=crop", // Nike Shoes
    7 => "https://images.unsplash.com/photo-1625772452859-1c03d5bf1137?w=400&h=300&fit=crop", // Sunglasses
    8 => "https://images.unsplash.com/photo-1583394838336-acd977736f90?w=400&h=300&fit=crop", // Headset
    9 => "https://images.unsplash.com/photo-1531297484001-80022131f5a1?w=400&h=300&fit=crop", // Laptop
    10 => "https://images.unsplash.com/photo-1592899677977-9c10ca588bbd?w=400&h=300&fit=crop"  // Smartphone
];

try {
    foreach ($product_images as $id => $image_url) {
        $stmt = $pdo->prepare("UPDATE products SET image = ? WHERE id = ?");
        $result = $stmt->execute([$image_url, $id]);
        if ($result) {
            echo "Updated product ID {$id} with image: {$image_url}<br>";
        } else {
            echo "Failed to update product ID {$id}<br>";
        }
    }
    echo "<br>Update complete! <a href='index.php'>Go back to products</a>";
} catch (PDOException $e) {
    echo "Error updating images: " . $e->getMessage();
}
?>
