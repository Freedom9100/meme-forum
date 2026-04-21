<?php 

session_start();
require_once 'db.php';

$errors = [];

$login = '';
$password = '';

if (isset($_POST['login_btn'])) {
    $login = trim($_POST['login'] ?? '');
    $password = ($_POST['password'] ?? '');

    if (empty($login)) {
        $errors['login'] = 'Type your login';
    }

    if (empty($password)) {
        $errors['password'] = 'Type your password';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
            exit;
        } else {
            $errors['common'] = 'Invalid login or password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #cdd6f4; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .form-box {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 14px;
            padding: 36px 32px;
            width: 100%;
            max-width: 400px;
        }
        .form-box h1 { color: #e94560; margin-bottom: 6px; font-size: 26px; }
        .subtitle { color: #7f8c8d; font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #aab; font-size: 14px; }
        input[type="text"], input[type="password"] {
            width: 100%;
            background: #0f3460;
            border: 1px solid #1a5276;
            border-radius: 6px;
            color: #cdd6f4;
            padding: 10px 12px;
            font-size: 14px;
            outline: none;
        }
        input:focus { border-color: #e94560; }
        .error { color: #e74c3c; font-size: 13px; margin-top: 4px; display: block; }
        .error-box {
            background: #5c1e1e;
            color: #fdd;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 14px;
            text-align: center;
        }
        .btn {
            width: 100%;
            padding: 11px;
            background: #e94560;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-size: 15px;
            cursor: pointer;
            margin-top: 6px;
        }
        .btn:hover { opacity: 0.85; }
        .bottom-link { text-align: center; margin-top: 18px; font-size: 13px; color: #7f8c8d; }
        .bottom-link a { color: #e94560; text-decoration: none; }
        .brand { text-align: center; color: #e94560; font-size: 22px; font-weight: bold; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="form-box">
        <div class="brand">😂 Forum Memes</div>
        <h1>Log In</h1>
        <p class="subtitle">Welcome back!</p>

        <?php if (isset($errors['common'])): ?>
            <div class="error-box"><?= $errors['common'] ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Login</label>
                <input type="text" name="login" value="<?= htmlspecialchars($login) ?>">
                <?php if (isset($errors['login'])): ?>
                    <span class="error"><?= $errors['login'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" value="<?= htmlspecialchars($password) ?>">
                <?php if (isset($errors['password'])): ?>
                    <span class="error"><?= $errors['password'] ?></span>
                <?php endif; ?>
            </div>

            <button class="btn" type="submit" name="login_btn">Log In</button>
        </form>

        <div class="bottom-link">
            Don't have an account? <a href="register.php">Register</a>
        </div>
    </div>
</body>
</html>