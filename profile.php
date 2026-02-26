<?php
session_start();
require_once 'config.php';

// Проверка авторизации
if (!isset($_SESSION['auth_id'])) {
    header('Location: login.php');
    exit;
}

// Получение данных пользователя
try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT login, email FROM auth WHERE id = ?");
    $stmt->execute([$_SESSION['auth_id']]);
    $auth = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = 'Ошибка базы данных: ' . $e->getMessage();
}

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
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; }
        .profile-info { background: #f9f9f9; padding: 20px; border-radius: 5px; }
        .logout { color: red; text-decoration: none; }
    </style>
</head>
<body>
    <h2>Профиль пользователя</h2>
    
    <div class="profile-info">
        <p><strong>Логин:</strong> <?php echo htmlspecialchars($auth['login']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($auth['email']); ?></p>
        
    </div>
    
    <p><a href="?logout=1" class="logout">Выйти</a></p>
</body>
</html>