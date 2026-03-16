<?php
session_start();
require_once 'config.php';
require_once 'book_functions.php';

// Получаем всех пользователей и их книги
function getAllUsersWithBooks() {
    try {
        $pdo = getDB();
        
        // Получаем всех пользователей
        $stmt = $pdo->query("SELECT id, login, email, role FROM auth ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Для каждого пользователя получаем его книги
        foreach ($users as &$user) {
            $stmt = $pdo->prepare("SELECT id, book_name, status FROM book WHERE id_users = ? ORDER BY id DESC");
            $stmt->execute([$user['id']]);
            $user['books'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $user['books_count'] = count($user['books']);
        }
        
        return $users;
    } catch (PDOException $e) {
        error_log('Ошибка получения данных для админки: ' . $e->getMessage());
        return [];
    }
}

// Получаем статистику
function getStats() {
    try {
        $pdo = getDB();
        
        // Общее количество пользователей
        $usersCount = $pdo->query("SELECT COUNT(*) FROM auth")->fetchColumn();
        
        // Общее количество книг
        $booksCount = $pdo->query("SELECT COUNT(*) FROM book")->fetchColumn();
        
        // Статистика по статусам книг
        $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM book GROUP BY status");
        $statusStats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        
        return [
            'users' => $usersCount,
            'books' => $booksCount,
            'status_stats' => $statusStats
        ];
    } catch (PDOException $e) {
        error_log('Ошибка получения статистики: ' . $e->getMessage());
        return [
            'users' => 0,
            'books' => 0,
            'status_stats' => []
        ];
    }
}

$users = getAllUsersWithBooks();
$stats = getStats();

// Выход из админки
if (isset($_GET['logout'])) {
    header('Location: register.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <div class="admin-title">
                <span class="admin-icon">👑</span>
                <h1>Панель администратора</h1>
            </div>
            <div>
                <a href="register.php" class="back-btn">← Назад к регистрации</a>
                <a href="?logout=1" class="admin-logout-btn">Выйти</a>
            </div>
        </div>
        
        <div class="stats-container">
            <div class="stat-card">
                <h3>Всего пользователей</h3>
                <div class="number"><?php echo $stats['users']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Всего книг</h3>
                <div class="number"><?php echo $stats['books']; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>Прочитано</h3>
                <div class="number"><?php echo $stats['status_stats']['Прочитана'] ?? 0; ?></div>
            </div>
            
            <div class="stat-card">
                <h3>В процессе</h3>
                <div class="number"><?php echo $stats['status_stats']['В процессе'] ?? 0; ?></div>
            </div>
        </div>
        
        <h2>Пользователи и их книги</h2>
        
        <?php if (empty($users)): ?>
            <div class="no-books">Нет зарегистрированных пользователей</div>
        <?php else: ?>
            <?php foreach ($users as $user): ?>
                <div class="user-card">
                    <div class="user-header">
                        <div class="user-info">
                            <h3><?php echo htmlspecialchars($user['login']); ?></h3>
                            <p>Email: <?php echo htmlspecialchars($user['email']); ?></p>
                            <p>ID: <?php echo $user['id']; ?></p>
                        </div>
                        <div>
                            <span class="badge">Книг: <?php echo $user['books_count']; ?></span>
                        </div>
                    </div>
                    
                    <?php if (empty($user['books'])): ?>
                        <div class="no-books">У пользователя пока нет книг</div>
                    <?php else: ?>
                        <table class="books-table">
                            <thead>
                                <tr>
                                    <th>ID книги</th>
                                    <th>Название</th>
                                    <th>Статус</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($user['books'] as $book): ?>
                                    <tr>
                                        <td>#<?php echo $book['id']; ?></td>
                                        <td><?php echo htmlspecialchars($book['book_name']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            if ($book['status'] == 'Прочитана') $status_class = 'status-read';
                                            elseif ($book['status'] == 'Не прочитана') $status_class = 'status-unread';
                                            else $status_class = 'status-progress';
                                            ?>
                                            <span class="status <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($book['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>