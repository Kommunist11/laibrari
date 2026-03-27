<?php
session_start();
require_once 'config.php';
require_once 'book_functions.php';

if (!isset($_SESSION['id_users'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$userId = $_SESSION['id_users'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $book_name = trim($_POST['book_name'] ?? '');
    $status = trim($_POST['status'] ?? 'Не прочитана');
    $description = trim($_POST['description'] ?? '');
    $image = '';
    
    if (empty($book_name)) {
        $_SESSION['error'] = 'Введите название книги';
    } else {
        if (isset($_FILES['book_image']) && $_FILES['book_image']['error'] == 0) {
            $uploadResult = uploadImage($_FILES['book_image']);
            if ($uploadResult['success']) {
                $image = $uploadResult['filename'];
            } else {
                $_SESSION['error'] = $uploadResult['message'];
                header('Location: profile.php');
                exit;
            }
        }
        
        $result = addBook($userId, $book_name, $status, $description, $image);
        if ($result['success']) {
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    
    header('Location: profile.php');
    exit;
}

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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_description'])) {
    $book_id = (int)($_POST['book_id'] ?? 0);
    $new_description = trim($_POST['new_description'] ?? '');
    
    if ($book_id > 0) {
        $result = updateBookDescription($book_id, $userId, $new_description);
        if ($result['success']) {
            $_SESSION['message'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }
    } else {
        $_SESSION['error'] = 'Некорректные данные';
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

$auth = getUserData($userId);
if ($auth === false) {
    $error = 'Ошибка получения данных пользователя';
    $auth = ['login' => '', 'email' => ''];
}

$books = getUserBooks($userId);
$booksCount = count($books);
$stats = [
    'total' => $booksCount,
    'read' => count(array_filter($books, function($book) { return $book['status'] == 'Прочитана'; })),
    'progress' => count(array_filter($books, function($book) { return $book['status'] == 'В процессе'; })),
    'unread' => count(array_filter($books, function($book) { return $book['status'] == 'Не прочитана'; }))
];

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
    <title>Моя библиотека</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-title">
                <span class="admin-icon">📚</span>
                <h1>Моя библиотека</h1>
            </div>
            <div>
                <a href="?logout=1" class="admin-logout-btn">Выйти</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Всего книг</h3>
                <div class="number"><?php echo $stats['total']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Прочитано</h3>
                <div class="number"><?php echo $stats['read']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>В процессе</h3>
                <div class="number"><?php echo $stats['progress']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Не прочитано</h3>
                <div class="number"><?php echo $stats['unread']; ?></div>
            </div>
        </div>
        
        <div class="profile-info" style="margin-bottom: 20px;">
            <h3>👤 Информация профиля</h3>
            <p><strong>Логин:</strong> <?php echo htmlspecialchars($auth['login'] ?? ''); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($auth['email'] ?? ''); ?></p>
        </div>
        
<div class="add-book">
    <h3>➕ Добавить книгу</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="text" name="book_name" placeholder="Название книги" required>
        <textarea name="description" placeholder="Описание книги (сюжет, впечатления, заметки....)" rows="4"></textarea>
        <input type="file" name="book_image" accept="image/*">
        <select name="status">
            <option value="Не прочитана">Не прочитана</option>
            <option value="В процессе">В процессе</option>
            <option value="Прочитана">Прочитана</option>
        </select>
        <button type="submit" name="add_book">Добавить книгу</button>
    </form>
</div>
        
        <h2>Мои книги (<?php echo $booksCount; ?>)</h2>
        
        <?php if (empty($books)): ?> 
            <div class="no-books">📖 У вас пока нет книг. Добавьте первую книгу!</div>
        <?php else: ?>
            <?php foreach ($books as $book): ?>
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($book['book_name']); ?></h3>
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
                        <div>
                            <span class="badge">ID: <?php echo $book['id']; ?></span>
                        </div>
                    </div>
                    
                    <?php if (!empty($book['image']) && file_exists($book['image'])): ?>
                        <div style="margin: 15px 0; text-align: center;">
                            <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="Обложка книги" style="max-width: 200px; max-height: 300px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($book['description'])): ?>
                        <div style="margin: 15px 0; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                            <strong>📝 Описание:</strong>
                            <p style="margin: 10px 0 0 0; line-height: 1.6;"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                        </div>
                    <?php endif; ?>
                
                    <div class="book-actions">
                        <form method="POST" class="update-form">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <select name="new_status">
                                <option value="Не прочитана" <?php echo $book['status'] == 'Не прочитана' ? 'selected' : ''; ?>>Не прочитана</option>
                                <option value="В процессе" <?php echo $book['status'] == 'В процессе' ? 'selected' : ''; ?>>В процессе</option>
                                <option value="Прочитана" <?php echo $book['status'] == 'Прочитана' ? 'selected' : ''; ?>>Прочитана</option>
                            </select>
                            <button type="submit" name="update_status">Обновить статус</button>
                        </form>
                        
                        <form method="POST" class="edit-name-form" onsubmit="return confirm('Изменить название книги?')">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <input type="text" name="new_book_name" value="<?php echo htmlspecialchars($book['book_name']); ?>" required>
                            <button type="submit" name="update_book_name">Изменить название</button>
                        </form>
                        
                        <button onclick="showDescriptionForm(<?php echo $book['id']; ?>, '<?php echo addslashes(htmlspecialchars($book['description'])); ?>')" class="edit-desc-btn">✏️ Редактировать описание</button>
                        
                        <a href="?delete_book=<?php echo $book['id']; ?>" class="delete-btn" onclick="return confirm('Вы уверены, что хотите удалить эту книгу?')">🗑️ Удалить</a>
                    </div>
                    
                    <div id="desc-form-<?php echo $book['id']; ?>" style="display: none; margin-top: 15px;">
                        <form method="POST">
                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                            <textarea name="new_description" rows="4" placeholder="Введите описание книги..." style="width: 100%;"><?php echo htmlspecialchars($book['description']); ?></textarea>
                            <button type="submit" name="update_description" style="margin-top: 10px;">Сохранить описание</button>
                            <button type="button" onclick="hideDescriptionForm(<?php echo $book['id']; ?>)" style="margin-top: 10px; background: #95a5a6;">Отмена</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <script>
    function showDescriptionForm(bookId, currentDesc) {
        var form = document.getElementById('desc-form-' + bookId);
        if (form.style.display === 'none') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
    
    function hideDescriptionForm(bookId) {
        document.getElementById('desc-form-' + bookId).style.display = 'none';
    }
    </script>
</body>
</html>