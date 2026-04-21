<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['uid'];

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    $_SESSION = [];
    session_destroy();
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT posts.*, categories.name AS category_name
    FROM posts
    JOIN categories ON posts.category_id = categories.id
    WHERE posts.user_id = ?
    ORDER BY posts.created_at DESC
");

$stmt->execute([$uid]);
$myPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Posts</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #cdd6f4; }
        .navbar {
            background: #16213e;
            padding: 14px 30px;
            display: flex;
            align-items: center;
            gap: 4px;
            border-bottom: 3px solid #e94560;
        }
        .navbar .brand { font-size: 20px; font-weight: bold; color: #e94560; margin-right: auto; text-decoration: none; }
        .navbar a { color: #cdd6f4; text-decoration: none; padding: 6px 12px; border-radius: 6px; font-size: 14px; }
        .navbar a:hover { background: #0f3460; }
        .container { max-width: 900px; margin: 30px auto; padding: 0 20px; }
        h1, h2 { color: #e94560; margin-bottom: 16px; }
        .profile-box {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 28px;
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        .profile-box .field { font-size: 14px; color: #aab; }
        .profile-box .field b { color: #cdd6f4; font-size: 16px; }
        .role-badge {
            display: inline-block;
            background: #e94560;
            color: #fff;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .card {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .card img { width: 100%; height: 280px; object-fit: cover; border-radius: 8px; display: block; margin-bottom: 10px; }
        .card p { margin: 6px 0; line-height: 1.5; }
        .meta { color: #7f8c8d; font-size: 13px; }
        .badge {
            display: inline-block;
            background: #0f3460;
            color: #cdd6f4;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 12px;
        }
        .status-badge { font-size: 11px; padding: 2px 8px; border-radius: 10px; font-weight: bold; }
        .status-pending  { background: #7f6000; color: #ffe; }
        .status-approved { background: #1e5c2e; color: #efe; }
        .status-rejected { background: #5c1e1e; color: #fee; }
        .actions { margin-top: 10px; display: flex; gap: 8px; flex-wrap: wrap; }
        .btn {
            display: inline-block;
            padding: 7px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
        }
        .btn-primary  { background: #e94560; color: #fff; }
        .btn-secondary{ background: #2c3e50; color: #cdd6f4; }
        .btn-success  { background: #27ae60; color: #fff; }
        .btn-danger   { background: #c0392b; color: #fff; }
        .btn:hover { opacity: 0.85; }
        .empty { color: #7f8c8d; font-style: italic; padding: 20px 0; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand">😂 Forum Memes</a>
        <a href="index.php">Home</a>
        <a href="create_post.php">+ Create post</a>
        <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="moderate.php">⚡ Moderation</a>
        <?php endif; ?>
        <a href="logout.php">Log Out</a>
    </nav>

    <div class="container">

        <div class="profile-box">
            <div class="field">
                Login<br>
                <b><?= htmlspecialchars($currentUser['login']) ?></b>
            </div>
            <div class="field">
                Email<br>
                <b><?= htmlspecialchars($currentUser['email']) ?></b>
            </div>
            <div class="field">
                Role<br>
                <span class="role-badge"><?= htmlspecialchars($currentUser['role']) ?></span>
            </div>
        </div>

        <h2>My posts (<?= count($myPosts) ?>)</h2>

        <?php if (empty($myPosts)): ?>
            <p class="empty">You don't have any posts yet. <a href="create_post.php" style="color:#e94560;">Create one!</a></p>
        <?php endif; ?>

        <?php foreach ($myPosts as $item): ?>
            <div class="card">
                <?php
                $images = !empty($item['imaeg_path']) ? (json_decode($item['image_path'], true) ?? [$item['image_path']]) : [];
                foreach ($images as $img):
                ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="meme">
                <?php endforeach; ?>

                <?php if (!empty($item['text'])): ?>
                    <p><?= nl2br(htmlspecialchars($item['text'])) ?></p>
                <?php endif; ?>

                <p class="meta">
                    <span class="badge"><?= htmlspecialchars($item['category_name']) ?></span>
                    &bull; <?= $item['created_at'] ?>
                    &bull; <span class="status-badge status-<?= $item['status'] ?>"><?= $item['status'] ?></span>
                </p>

                <div class="actions">
                    <a class="btn btn-primary" href="post.php?id=<?= $item['id'] ?>">Open</a>
                    <a class="btn btn-secondary" href="edit_post.php?id=<?= $item['id'] ?>">Edit</a>
                    <a class="btn btn-danger" href="delete_post.php?id=<?= $item['id'] ?>" onclick="return confirm('Delete this post?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</body>
</html>