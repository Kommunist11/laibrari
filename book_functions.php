<?php


require_once 'config.php';


function addBook($userId, $bookName, $status = 'Не прочитана', $description = '', $image = '') {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO book (id_users, book_name, description, image, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $bookName, $description, $image, $status]);
        return ['success' => true, 'message' => 'Книга успешно добавлена!'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Ошибка при добавлении книги: ' . $e->getMessage()];
    }
}

function deleteBook($bookId, $userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("DELETE FROM book WHERE id = ? AND id_users = ?");
        $stmt->execute([$bookId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Книга удалена'];
        } else {
            return ['success' => false, 'message' => 'Книга не найдена или у вас нет прав на её удаление'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Ошибка при удалении книги: ' . $e->getMessage()];
    }
}

function updateBookStatus($bookId, $userId, $newStatus) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE book SET status = ? WHERE id = ? AND id_users = ?");
        $stmt->execute([$newStatus, $bookId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Статус обновлен'];
        } else {
            return ['success' => false, 'message' => 'Книга не найдена или у вас нет прав на изменение'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Ошибка при обновлении статуса: ' . $e->getMessage()];
    }
}

function updateBookDescription($bookId, $userId, $newDescription) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("UPDATE book SET description = ? WHERE id = ? AND id_users = ?");
        $stmt->execute([$newDescription, $bookId, $userId]);
        
        if ($stmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Описание обновлено'];
        } else {
            return ['success' => false, 'message' => 'Книга не найдена или у вас нет прав на изменение'];
        }
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Ошибка при обновлении описания: ' . $e->getMessage()];
    }
}

function getUserBooks($userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, book_name, description, image, status FROM book WHERE id_users = ? ORDER BY id DESC");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Ошибка получения книг: ' . $e->getMessage());
        return [];
    }
}

function getUserData($userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT login, email FROM auth WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Ошибка получения данных пользователя: ' . $e->getMessage());
        return false;
    }
}

function bookExists($bookId, $userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM book WHERE id = ? AND id_users = ?");
        $stmt->execute([$bookId, $userId]);
        return $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        error_log('Ошибка проверки существования книги: ' . $e->getMessage());
        return false;
    }
}

function getUserBook($bookId, $userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, book_name, description, image, status FROM book WHERE id = ? AND id_users = ?");
        $stmt->execute([$bookId, $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('Ошибка получения книги: ' . $e->getMessage());
        return false;
    }
}

function countUserBooks($userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM book WHERE id_users = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('Ошибка подсчета книг: ' . $e->getMessage());
        return 0;
    }
}

function updateBookName($bookId, $userId, $newBookName) {
    try {
        $pdo = getDB();
        
        $checkStmt = $pdo->prepare("SELECT id FROM book WHERE id = ? AND id_users = ?");
        $checkStmt->execute([$bookId, $userId]);
        
        if ($checkStmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Книга не найдена или у вас нет прав на её редактирование'];
        }
        
        $updateStmt = $pdo->prepare("UPDATE book SET book_name = ? WHERE id = ? AND id_users = ?");
        $updateStmt->execute([$newBookName, $bookId, $userId]);
        
        if ($updateStmt->rowCount() > 0) {
            return ['success' => true, 'message' => 'Название книги успешно обновлено'];
        } else {
            return ['success' => false, 'message' => 'Не удалось обновить название книги'];
        }
    } catch (PDOException $e) {
        error_log('Ошибка при обновлении названия книги: ' . $e->getMessage());
        return ['success' => false, 'message' => 'Ошибка при обновлении названия: ' . $e->getMessage()];
    }
}

function uploadImage($file) {
    $target_dir = "uploads/";
    
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $imageFileType = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $unique_filename = uniqid() . '_' . time() . '.' . $imageFileType;
    $target_file = $target_dir . $unique_filename;
    
    $check = getimagesize($file["tmp_name"]);
    if($check === false) {
        return ['success' => false, 'message' => 'Файл не является изображением'];
    }
    
    if($file["size"] > 5000000) {
        return ['success' => false, 'message' => 'Файл слишком большой (макс. 5MB)'];
    }
    
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
        return ['success' => false, 'message' => 'Разрешены только JPG, JPEG, PNG & GIF'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'filename' => $target_file];
    } else {
        return ['success' => false, 'message' => 'Ошибка при загрузке файла'];
    }
}
?>