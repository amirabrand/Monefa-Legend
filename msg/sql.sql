CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT NOT NULL,
    message_type ENUM('permanent', 'temporary') NOT NULL DEFAULT 'permanent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    delete_at TIMESTAMP NULL
);

CREATE TABLE user_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message_id INT NOT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    FOREIGN KEY (message_id) REFERENCES messages(id)
);
CREATE TABLE user_deleted_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message_id INT NOT NULL,
    deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (message_id) REFERENCES messages(id)
);
