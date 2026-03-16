<?php
session_start();
require_once 'config.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    
    if (empty($login) || empty($email) || empty($password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Некорректный email адрес';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен содержать минимум 6 символов';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } else {
        try {
            $pdo = getDB();
            
            // Проверка существования пользователя
            $stmt = $pdo->prepare("SELECT id FROM auth WHERE login = ? OR email = ?");
            $stmt->execute([$login, $email]);
            
            if ($stmt->fetch()) {
                $error = 'Пользователь с таким логином или email уже существует';
            } else {
                
                // Хеширование пароля
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Сохранение пользователя
                $stmt = $pdo->prepare("INSERT INTO auth (login, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$login, $email, $hashed_password]);
                
                $success = 'Регистрация прошла успешно! Теперь вы можете войти.';
                header('Location:profile.php');
            }
        } catch (PDOException $e) {
            $error = 'Ошибка базы данных: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрация</title>
    <link rel="stylesheet" href="stile.css">
</head>
<body>
    <div class="register-container">
        <h2>Регистрация</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="login" placeholder="Логин" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" required>
            <input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            <input type="password" name="password" placeholder="Пароль (мин. 6 символов)" required>
            <input type="password" name="confirm_password" placeholder="Подтвердите пароль" required>
            <button type="submit">Зарегистрироваться</button>
        </form>
        
        <div class="buttons-container">
            <a href="login.php"><button class="login-btn">Уже есть аккаунт? Войти</button></a>
            
            <hr>
            
            <!-- Кнопка входа как администратор -->
            <a href="admin.php"><button class="admin-btn">👑 Войти как администратор</button></a>
            <p class="note">(доступ без пароля для демонстрации)</p>
        </div>
    </div>
</body>
</html>