<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['uid']]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser || $currentUser['role'] != 'admin') {
    die('Access denied');
}

if (isset($_GET['post_action']) && isset($_GET['post_id'])) {
    $action = $_GET['post_action'];
    $post_id = (int)($_GET['post_id']);

    if (in_array($action, ['approve', 'reject']) && $post_id > 0) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE posts SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $post_id]);
    }

    header('Location: moderate.php');
    exit;
}

if (isset($_GET['comment_action']) && isset($_GET['comment_id'])) {
    $action = $_GET['comment_action'];
    $comment_id = (int)($_GET['comment_id']);
    
    if (in_array($action, ['approve', 'reject']) && $comment_id > 0) {
        $newStatus = $action === 'approve' ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE comments SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $comment_id]);
    }

    header('Location: moderate.php');
    exit;
}

$stmt = $pdo->query("
    SELECT posts.*, users.login AS author_login, categories.name AS category_name
    FROM posts
    JOIN users ON posts.user_id = users.id
    JOIN categories ON posts.category_id = categories.id
    WHERE posts.status = 'pending'
    ORDER BY posts.created_at DESC
");
$pendingPosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("
    SELECT comments.*, users.login AS author_login
    FROM comments
    JOIN users ON comments.user_id = users.id
    WHERE comments.status = 'pending'
    ORDER BY comments.created_at DESC
");
$pendingComments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation</title>
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
        h1 { color: #e94560; margin-bottom: 24px; }
        h2 { color: #e94560; margin: 24px 0 14px; }
        .section-divider {
            border: none;
            border-top: 1px solid #0f3460;
            margin: 30px 0;
        }
        .card {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 14px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .card img { width: 100%; height: 260px; object-fit: cover; border-radius: 8px; display: block; margin-bottom: 10px; }
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
        .actions { margin-top: 12px; display: flex; gap: 8px; flex-wrap: wrap; }
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
        .btn-success  { background: #27ae60; color: #fff; }
        .btn-warning  { background: #e67e22; color: #fff; }
        .btn-danger   { background: #c0392b; color: #fff; }
        .btn-secondary{ background: #2c3e50; color: #cdd6f4; }
        .btn:hover { opacity: 0.85; }
        .empty { color: #7f8c8d; font-style: italic; padding: 10px 0; }
        .count-badge {
            background: #e94560;
            color: #fff;
            border-radius: 12px;
            font-size: 13px;
            padding: 1px 10px;
            margin-left: 8px;
        }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand">😂 Forum Memes</a>
        <a href="index.php">Home</a>
        <a href="my_posts.php">My posts</a>
        <a href="logout.php">Log Out</a>
    </nav>

    <div class="container">
        <h1>⚡ Moderation panel</h1>

        <h2>
            Pending posts
            <span class="count-badge"><?= count($pendingPosts) ?></span>
        </h2>

        <?php if (empty($pendingPosts)): ?>
            <p class="empty">No pending posts.</p>
        <?php endif; ?>

        <?php foreach ($pendingPosts as $item): ?>
            <div class="card">
                <?php
                $images = !empty($item['image_path']) ? (json_decode($item['image_path'], true) ?? [$item['image_path']]) : [];
                foreach ($images as $img):
                ?>
                    <img src="<?= htmlspecialchars($img) ?>" alt="meme">
                <?php endforeach; ?>

                <?php if (!empty($item['text'])): ?>
                    <p><?= nl2br(htmlspecialchars($item['text'])) ?></p>
                <?php endif; ?>

                <p class="meta">
                    <span class="badge"><?= htmlspecialchars($item['category_name']) ?></span>
                    by <b><?= htmlspecialchars($item['author_login']) ?></b>
                    &bull; <?= $item['created_at'] ?>
                </p>

                <div class="actions">
                    <a class="btn btn-secondary" href="post.php?id=<?= $item['id'] ?>">Open</a>
                    <a class="btn btn-success"   href="moderate.php?post_action=approve&post_id=<?= $item['id'] ?>">✓ Approve</a>
                    <a class="btn btn-warning"   href="moderate.php?post_action=reject&post_id=<?= $item['id'] ?>">✗ Reject</a>
                    <a class="btn btn-danger"    href="delete_post.php?id=<?= $item['id'] ?>" onclick="return confirm('Delete post?');">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>

        <hr class="section-divider">

        <h2>
            Pending comments
            <span class="count-badge"><?= count($pendingComments) ?></span>
        </h2>

        <?php if (empty($pendingComments)): ?>
            <p class="empty">No pending comments.</p>
        <?php endif; ?>

        <?php foreach ($pendingComments as $item): ?>
            <div class="card">
                <p class="meta">
                    <b><?= htmlspecialchars($item['author_login']) ?></b>
                    &bull; <?= $item['created_at'] ?>
                </p>
                <p><?= nl2br(htmlspecialchars($item['text'])) ?></p>

                <div class="actions">
                    <a class="btn btn-secondary" href="post.php?id=<?= $item['post_id'] ?>">Open post</a>
                    <a class="btn btn-success"   href="moderate.php?comment_action=approve&comment_id=<?= $item['id'] ?>">✓ Approve</a>
                    <a class="btn btn-warning"   href="moderate.php?comment_action=reject&comment_id=<?= $item['id'] ?>">✗ Reject</a>
                    <a class="btn btn-danger"    href="comment_delete.php?id=<?= $item['id'] ?>&post_id=<?= $item['post_id'] ?>" onclick="return confirm('Delete comment?')">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>

    </div>
</body>
</html>