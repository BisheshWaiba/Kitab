<?php
require_once dirname(__DIR__) . '/config/db.php';

$books = [
    // Arts & Photography
    ['title' => 'The Art of Color', 'author' => 'Johannes Itten', 'price' => 1200, 'category' => 'Arts & Photography', 'image' => 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?q=80&w=400'],
    ['title' => 'Photography Masterclass', 'author' => 'Henry Carroll', 'price' => 1500, 'category' => 'Arts & Photography', 'image' => 'https://images.unsplash.com/photo-1452784444945-3f422708fe5e?q=80&w=400'],
    ['title' => 'Modern Architecture', 'author' => 'Kenneth Frampton', 'price' => 2500, 'category' => 'Arts & Photography', 'image' => 'https://images.unsplash.com/photo-1487958449943-2429e8be8625?q=80&w=400'],

    // Travel
    ['title' => 'Beyond the Horizon', 'author' => 'Lois Pryce', 'price' => 950, 'category' => 'Travel', 'image' => 'https://images.unsplash.com/photo-1476514525535-07fb3b4ae5f1?q=80&w=400'],
    ['title' => 'The Great Railway Bazaar', 'author' => 'Paul Theroux', 'price' => 1100, 'category' => 'Travel', 'image' => 'https://images.unsplash.com/photo-1473448912268-2022ce9509d8?q=80&w=400'],
    ['title' => 'Vagabonding', 'author' => 'Rolf Potts', 'price' => 850, 'category' => 'Travel', 'image' => 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?q=80&w=400'],

    // Nature
    ['title' => 'The Hidden Life of Trees', 'author' => 'Peter Wohlleben', 'price' => 1350, 'category' => 'Nature', 'image' => 'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?q=80&w=400'],
    ['title' => 'Our Planet', 'author' => 'Alastair Fothergill', 'price' => 3200, 'category' => 'Nature', 'image' => 'https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?q=80&w=400'],
    ['title' => 'Wild Kingdom', 'author' => 'Stephen Moss', 'price' => 1800, 'category' => 'Nature', 'image' => 'https://images.unsplash.com/photo-1437622368342-7a3d73a34c8f?q=80&w=400'],
];

foreach ($books as $book) {
    $title = $book['title'];
    $author = $book['author'];
    $price = $book['price'];
    $category = $book['category'];
    $image = $book['image'];
    $description = "A curated pick in the $category category.";

    $sql = "INSERT INTO books (title, author, price, category, image, description) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisss", $title, $author, $price, $category, $image, $description);

    if ($stmt->execute()) {
        echo "Added: $title\n";
    } else {
        echo "Error adding $title: " . $conn->error . "\n";
    }
}
?>