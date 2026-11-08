<?php
// Messages sent through the Contact page (frontend/contact.php). They are saved in the database so none is lost,
// the shop is also emailed when the server can send mail, and an admin reads them under Messages in the admin area.
// Needs config/db.php.

const CONTACT_MIN_MESSAGE = 10;
const CONTACT_MAX_MESSAGE = 2000;
const CONTACT_PER_SESSION = 3;    // messages one visitor can send ...
const CONTACT_WINDOW = 600;       // ... in this many seconds
const CONTACT_PER_HOUR = 30;      // messages the whole site accepts per hour (a stop for floods)

const CONTACT_TOPICS = [
    'order' => 'A question about an order',
    'returns' => 'Returns and refunds',
    'selling' => 'Selling a book',
    'other' => 'Something else',
];

// Created on first use, so an existing database needs no manual step
function k_contact_table(mysqli $conn): void
{
    $conn->query(
        "CREATE TABLE IF NOT EXISTS contact_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(255) NOT NULL,
            topic VARCHAR(30) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            KEY created_at (created_at),
            CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}
