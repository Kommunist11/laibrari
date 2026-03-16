<?php
session_start();
require_once 'config.php';
require_once 'book_functions.php';

// Проверка авторизации
if (!isset($_SESSION['id_users'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$userId = $_SESSION['id_users'];

// добавления книги
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $book_name = trim($_POST['book_name'] ?? '');
    $status = trim($_POST['status'] ?? 'Не прочитана');
    
    if (empty($book_name)) {
        $_SESSION['error'] = 'Введите название книги';
    } else {
        $result = addBook($userId, $book_name, $status);
        if ($result['success']) {
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    header('Location: profile.php');
    exit;
}

// удаления книги
if (isset($_GET['delete_book'])) {
    $bookId = (int)$_GET['delete_book'];
    $result = deleteBook($bookId, $userId);
    if ($result['success']) {
        $_SESSION['message'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }
    
    header('Location: profile.php');
    exit;
}

// обновления статуса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $new_status = trim($_POST['new_status'] ?? '');
    
    if ($book_id > 0 && !empty($new_status)) {
        $result = updateBookStatus($book_id, $userId, $new_status);
        if ($result['success']) {
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = 'Некорректные данные для обновления статуса';
    }
    
    header('Location: profile.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book_name'])) {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $new_book_name = trim($_POST['new_book_name'] ?? '');
    
    if ($book_id > 0 && !empty($new_book_name)) {
        $result = updateBookName($book_id, $userId, $new_book_name);
        if ($result['success']) {
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = 'Введите новое название книги';
    }
    
    header('Location: profile.php');
    exit;
}


if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

if (isset($_SESSION['error'])) {
    $error = $_SESSION['error'];
    unset($_SESSION['error']);
}

// Получение данных пользователя и книг
$auth = getUserData($userId);
if ($auth === false) {
    $error = 'Ошибка получения данных пользователя';
    $auth = ['login' => '', 'email' => ''];
}

$books = getUserBooks($userId);
$booksCount = count($books);

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body>
    <div class="profile-container">
        <h2>Профиль пользователя</h2>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="profile-info">
            <p><strong>Логин:</strong> <?php echo htmlspecialchars($auth['login'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($auth['email'] ?? ''); ?></p>
        </div>
        
        <div class="add-book">
            <h3>Добавить книгу</h3>
            <form method="POST">
                <input type="text" name="book_name" placeholder="Название книги" required>
                <select name="status">
                    <option value="Не прочитана">Не прочитана</option>
                    <option value="В процессе">В процессе</option>
                    <option value="Прочитана">Прочитана</option>
                </select>
                <button type="submit" name="add_book">Добавить книгу</button>
            </form>
        </div>
        
        <div class="books-list">
            <h3>Мои книги (<?php echo $booksCount; ?>)</h3>
            
            <?php if (empty($books)): ?> 
                <p>У вас пока нет книг. Добавьте первую книгу!</p>
            <?php else: ?>
                <?php foreach ($books as $book): ?>
                    <div class="book-item">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?php echo htmlspecialchars($book['book_name']); ?></strong><br>
                            </div>
                            <div>
                                <?php
                                $status_class = '';
                                if ($book['status'] == 'Прочитана') $status_class = 'status-read';
                                elseif ($book['status'] == 'Не прочитана') $status_class = 'status-unread';
                                else $status_class = 'status-progress';
                                ?>
                                <span class="status <?php echo $status_class; ?>">
                                    <?php echo htmlspecialchars($book['status']); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="book-actions">
                            <!-- Форма обновления статуса -->
                            <form method="POST" class="update-form">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <select name="new_status">
                                    <option value="Не прочитана" <?php echo $book['status'] == 'Не прочитана' ? 'selected' : ''; ?>>Не прочитана</option>
                                    <option value="В процессе" <?php echo $book['status'] == 'В процессе' ? 'selected' : ''; ?>>В процессе</option>
                                    <option value="Прочитана" <?php echo $book['status'] == 'Прочитана' ? 'selected' : ''; ?>>Прочитана</option>
                                </select>
                                <button type="submit" name="update_status">Обновить статус</button>
                            </form>
                            
                            <!-- Форма редактирования названия книги -->
                            <form method="POST" class="edit-name-form" onsubmit="return confirm('Изменить название книги?')">
                                <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                <input type="text" name="new_book_name" value="<?php echo htmlspecialchars($book['book_name']); ?>" required>
                                <button type="submit" name="update_book_name">Изменить название</button>
                            </form>
                            
                            <!-- Ссылка на удаление -->
                            <a href="?delete_book=<?php echo $book['id']; ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить эту книгу?')">Удалить</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <p style="margin-top: 20px;"><a href="?logout=1" class="logout">Выйти</a></p>
    </div>
</body>
</html>