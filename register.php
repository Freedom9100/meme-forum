<?php 

session_start();
require_once 'db.php';

$errors = [];

$login = '';
$email = '';
$password = '';
$password_confirm = '';

$success = '';

if (isset($_POST['register'])) {
    $login = trim($_POST['login'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = ($_POST['password'] ?? '');
    $password_confirm = ($_POST['password_confirm'] ?? '');

    if (empty($login)) {
        $errors['login'] = 'Type your login';
    }

    if (empty($email)) {
        $errors['email'] = 'Type your email';
    }elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid format email';
    }

    if (empty($password)) {
        $errors['password'] = 'Type your password';
    } elseif (mb_strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }

    if (empty($password_confirm)) {
        $errors['password_confirm'] = 'Type your password again';
    }

    if (!empty($password) && !empty($password_confirm) && $password !== $password_confirm) {
        $errors['password_confirm'] = 'Password confirmation does not match';
    }

    if (!empty($login)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $userExists = $stmt->fetch();

        if ($userExists) {
            $errors['login'] = 'Login is already taken';
        }
    }

    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $emailExists = $stmt->fetch();

        if ($emailExists) {
            $errors['email'] = 'Email is already taken';
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (login, email, password, role) VALUES (?, ?, ?, 'user')");
        $stmt->execute([$login, $email, $passwordHash]);

        $success = 'Registration successful';
        $login = '';
        $email = '';
        $password = '';
        $password_confirm = '';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #cdd6f4; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .form-box {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 14px;
            padding: 36px 32px;
            width: 100%;
            max-width: 420px;
        }
        .form-box h1 { color: #e94560; margin-bottom: 6px; font-size: 26px; }
        .subtitle { color: #7f8c8d; font-size: 14px; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
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
        .success-box {
            background: #1e5c2e;
            color: #efe;
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
        <h1>Registration</h1>
        <p class="subtitle">Create your account</p>

        <?php if (!empty($success)): ?>
            <div class="success-box"><?= $success ?> <a href="login.php" style="color:#efe;">Log in →</a></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Login</label>
                <input type="text" id="login" name="login" value="<?= htmlspecialchars($login) ?>">
                <?php if (isset($errors['login'])): ?>
                    <span class="error"><?= $errors['login'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="text" id="email" name="email" value="<?= htmlspecialchars($email) ?>">
                <?php if (isset($errors['email'])): ?>
                    <span class="error"><?= $errors['email'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Password</label>
                <input type="password" id="password" name="password" value="<?= htmlspecialchars($password) ?>">
                <?php if (isset($errors['password'])): ?>
                    <span class="error"><?= $errors['password'] ?></span>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label>Password Confirmation</label>
                <input type="password" id="password_confirm" name="password_confirm" value="<?= htmlspecialchars($password_confirm) ?>">
                <?php if (isset($errors['password_confirm'])): ?>
                    <span class="error"><?= $errors['password_confirm'] ?></span>
                <?php endif; ?>
            </div>

            <button class="btn" type="submit" name="register">Register</button>
        </form>

        <div class="bottom-link">
            Already have an account? <a href="login.php">Log in</a>
        </div>
    </div>
</body>
</html>