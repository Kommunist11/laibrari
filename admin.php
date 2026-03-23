<?php
session_start();
require_once 'config.php';
require_once 'book_functions.php';

function getAllUsersWithBooks() {
    try {
        $pdo = getDB();
        
        $stmt = $pdo->query("SELECT id, login, email, role FROM auth ORDER BY id DESC");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($users as &$user) {
            $stmt = $pdo->prepare("SELECT id, book_name, description, image, status FROM book WHERE id_users = ? ORDER BY id DESC");
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

function getStats() {
    try {
        $pdo = getDB();
        
        $usersCount = $pdo->query("SELECT COUNT(*) FROM auth")->fetchColumn();
        $booksCount = $pdo->query("SELECT COUNT(*) FROM book")->fetchColumn();
        
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
                        <?php foreach ($user['books'] as $book): ?>
                            <div style="border: 1px solid #ecf0f1; margin-bottom: 15px; padding: 15px; border-radius: 5px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                    <div>
                                        <strong style="font-size: 16px;">📖 <?php echo htmlspecialchars($book['book_name']); ?></strong>
                                        <div style="margin-top: 5px;">
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
                                    <div>
                                        <span class="badge">ID: <?php echo $book['id']; ?></span>
                                    </div>
                                </div>
                                
                                <?php if (!empty($book['image']) && file_exists($book['image'])): ?>
                                    <div style="margin: 10px 0; text-align: center;">
                                        <img src="<?php echo htmlspecialchars($book['image']); ?>" alt="Обложка" style="max-width: 150px; max-height: 200px; border-radius: 5px;">
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($book['description'])): ?>
                                    <div style="margin: 10px 0; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                                        <strong>📝 Описание:</strong>
                                        <p style="margin: 5px 0 0 0; font-size: 14px;"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
</html>