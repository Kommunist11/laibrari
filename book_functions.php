<?php


require_once 'config.php';


function addBook($userId, $bookName, $status = 'Не прочитана') {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("INSERT INTO book (id_users, book_name, status) VALUES (?, ?, ?)");
        $stmt->execute([$userId, $bookName, $status]);
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


function getUserBooks($userId) {
    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT id, book_name, status FROM book WHERE id_users = ? ORDER BY id DESC");
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
        $stmt = $pdo->prepare("SELECT id, book_name, status FROM book WHERE id = ? AND id_users = ?");
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
        
        // Проверяем, принадлежит ли книга пользователю
        $checkStmt = $pdo->prepare("SELECT id FROM book WHERE id = ? AND id_users = ?");
        $checkStmt->execute([$bookId, $userId]);
        
        if ($checkStmt->rowCount() === 0) {
            return ['success' => false, 'message' => 'Книга не найдена или у вас нет прав на её редактирование'];
        }
        
        // Обновляем название книги
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
?>