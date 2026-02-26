<?php
session_start();
require_once 'config.php';

// Перенаправление если уже авторизован
if (isset($_SESSION['auth_id'])) {
    header('Location: profile.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин/email и пароль';
    } else {
        try {
            $pdo = getDB();
            
            // Поиск пользователя по логину или email
            $stmt = $pdo->prepare("SELECT id, login, email, password FROM auth WHERE login = ? OR email = ?");
            $stmt->execute([$login, $login]);
            $auth = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($auth && password_verify($password, $auth['password'])) {
                // Успешная авторизация
                $_SESSION['auth_id'] = $auth['id'];
                $_SESSION['auth_login'] = $auth['login'];
                $_SESSION['auth_email'] = $auth['email'];
                
                header('Location:profile.php');
                exit;
            } else {
                $error = 'Неверный логин/email или пароль';
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
    <title>Вход на сайт</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 400px; margin: 50px auto; padding: 20px; }
        input { width: 100%; padding: 8px; margin: 10px 0; box-sizing: border-box; }
        button { width: 100%; padding: 10px; background: #008CBA; color: white; border: none; cursor: pointer; }
        .error { color: red; margin: 10px 0; }
        a { display: block; text-align: center; margin-top: 20px; }
    </style>
</head>
<body>
    <h2>Вход на сайт</h2>
    
    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST">
        <input type="text" name="login" placeholder="Логин или Email" value="<?php echo htmlspecialchars($_POST['login'] ?? ''); ?>" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <button type="submit">Войти</button>
    </form>
    
    <a href="register.php">Нет аккаунта? Зарегистрироваться</a>
</body>
</html>