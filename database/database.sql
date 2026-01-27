-- Database: kitab_db

DROP DATABASE IF EXISTS kitab_db;
CREATE DATABASE kitab_db;
USE kitab_db;

-- Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    favorite_genres TEXT, -- Stored as comma-separated values
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books Table
CREATE TABLE books (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    image VARCHAR(255) NOT NULL,
    category VARCHAR(50),
    publisher VARCHAR(100),
    pages INT,
    language VARCHAR(50) DEFAULT 'English',
    isbn VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders Table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT, 
    name VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    total DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Order Items Table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (book_id) REFERENCES books(id)
);

-- Seed Data for Books
INSERT INTO books (title, author, description, price, image, category, publisher, pages, language, isbn) VALUES
('The Great Gatsby', 'F. Scott Fitzgerald', 'A novel set in the Jazz Age that explores themes of wealth, class, and the American Dream.', 15.99, 'https://placehold.co/400x600?text=The+Great+Gatsby', 'Fiction', 'Scribner', 180, 'English', '978-0743273565'),
('To Kill a Mockingbird', 'Harper Lee', 'A story of racial injustice and the loss of innocence in the American South.', 12.50, 'https://placehold.co/400x600?text=To+Kill+a+Mockingbird', 'Fiction', 'Harper Perennial', 281, 'English', '978-0061120084'),
('1984', 'George Orwell', 'A dystopian social science fiction novel and cautionary tale.', 14.00, 'https://placehold.co/400x600?text=1984', 'Science Fiction', 'Signet Classics', 328, 'English', '978-0451524935'),
('The Alchemist', 'Paulo Coelho', 'A story about following your dreams and listening to your heart.', 18.25, 'https://placehold.co/400x600?text=The+Alchemist', 'Adventure', 'HarperOne', 208, 'English', '978-0062315007'),
('Sapiens', 'Yuval Noah Harari', 'A brief history of humankind.', 22.00, 'https://placehold.co/400x600?text=Sapiens', 'Non-Fiction', 'Harper', 443, 'English', '978-0062316097'),
('Atomic Habits', 'James Clear', 'An easy & proven way to build good habits & break bad ones.', 20.00, 'https://placehold.co/400x600?text=Atomic+Habits', 'Self-Help', 'Avery', 320, 'English', '978-0735211292');
