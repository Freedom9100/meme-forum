<?php

session_start();
require_once 'db.php';

$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    die('Invalid post ID');
}

$currentUser = null;
if (isset($_SESSION['uid'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['uid']]);
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->prepare("
    SELECT
        posts.*,
        users.login AS author_login,
        users.email AS author_email,
        users.role AS author_role,
        categories.name AS category_name
    FROM posts
    JOIN users ON posts.user_id = users.id
    JOIN categories ON posts.category_id = categories.id
    WHERE posts.id = ?
");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    die('Post not found');
}

$commentError = '';
if (isset($_POST['add_comment'])) {
    if (!$currentUser) {
        header('Location: login.php');
        exit;
    }

    $commentText = trim($_POST['comment_text'] ?? '');
    
    if (empty($commentText)) {
        $commentError = 'Type comment text';
    } else {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, user_id, text) VALUES (?, ?, ?)");
        $stmt->execute([$post_id, $currentUser['id'], $commentText]);

        header("Location: post.php?id=$post_id");
        exit;
    }
}

if ($currentUser && $currentUser['role'] == 'admin') {
    $stmt = $pdo->prepare("
        SELECT comments.*, users.login AS author_login
        FROM comments
        JOIN users ON comments.user_id = users.id
        WHERE comments.post_id = ?
        ORDER BY comments.created_at ASC
    ");
    $stmt->execute([$post_id]);
} else {
    $stmt = $pdo->prepare("
        SELECT comments.*, users.login AS author_login
        FROM comments
        JOIN users ON comments.user_id = users.id
        WHERE comments.post_id = ? AND comments.status = 'approved'
        ORDER BY comments.created_at ASC
    ");
    $stmt->execute([$post_id]);
}

$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post</title>
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
        .container { max-width: 860px; margin: 30px auto; padding: 0 20px; }
        .card {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .card img { width: 100%; max-height: 480px; object-fit: cover; border-radius: 8px; display: block; margin-bottom: 14px; }
        .card p { margin: 8px 0; line-height: 1.6; }
        .meta { color: #7f8c8d; font-size: 13px; margin: 10px 0; }
        .author-box {
            background: #0f3460;
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 12px;
            font-size: 13px;
            color: #aab;
        }
        .author-box b { color: #cdd6f4; }
        .badge {
            display: inline-block;
            background: #0f3460;
            color: #cdd6f4;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 12px;
        }
        .actions { margin-top: 14px; display: flex; gap: 8px; flex-wrap: wrap; }
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
        .btn-danger   { background: #c0392b; color: #fff; }
        .btn:hover { opacity: 0.85; }
        h2, h3 { color: #e94560; margin-bottom: 14px; }
        .comment {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            overflow-wrap: anywhere;
        }
        .comment .comment-header { display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap; }
        .comment .comment-header b { color: #e94560; }
        .comment .comment-header .time { color: #7f8c8d; font-size: 12px; }
        .status-badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 10px;
            font-weight: bold;
        }
        .status-pending  { background: #7f6000; color: #ffe; }
        .status-approved { background: #1e5c2e; color: #efe; }
        .status-rejected { background: #5c1e1e; color: #fee; }
        .comment-form textarea {
            width: 100%;
            background: #0f3460;
            border: 1px solid #1a5276;
            border-radius: 6px;
            color: #cdd6f4;
            padding: 10px;
            font-size: 14px;
            resize: vertical;
            outline: none;
        }
        .comment-form textarea:focus { border-color: #e94560; }
        .comment-form { margin-top: 6px; }
        .error { color: #e74c3c; font-size: 13px; display: block; margin: 6px 0; }
        .info { color: #7f8c8d; font-size: 14px; }
        .empty { color: #7f8c8d; font-style: italic; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand">😂 Forum Memes</a>
        <a href="index.php">Home</a>
        <?php if ($currentUser): ?>
            <a href="my_posts.php">My posts</a>
            <a href="create_post.php">+ Create post</a>
            <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="moderate.php">⚡ Moderation</a>
            <?php endif; ?>
            <a href="logout.php">Log Out</a>
        <?php else: ?>
            <a href="login.php">Log In</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>

    <div class="container">

        <div class="card">
            <?php
            $images = !empty($post['image_path']) ? (json_decode($post['image_path'], true) ?? [$post['image_path']]) : [];
            foreach ($images as $img):
            ?>
                <img src="<?= htmlspecialchars($img) ?>" alt="meme">
            <?php endforeach; ?>

            <?php if (!empty($post['text'])): ?>
                <p><?= nl2br(htmlspecialchars($post['text'])) ?></p>
            <?php endif; ?>

            <p class="meta">
                <span class="badge"><?= htmlspecialchars($post['category_name']) ?></span>
                &bull; <?= $post['created_at'] ?>
            </p>

            <div class="author-box">
                <b>Author:</b> <?= htmlspecialchars($post['author_login']) ?> &bull;
                <?= htmlspecialchars($post['author_email']) ?> &bull;
                Role: <?= htmlspecialchars($post['author_role']) ?>
            </div>

            <div class="actions">
                <?php if ($currentUser && $currentUser['id'] == $post['user_id']): ?>
                    <a class="btn btn-secondary" href="edit_post.php?id=<?= $post['id'] ?>">Edit</a>
                <?php endif; ?>
                <?php if ($currentUser && ($currentUser['id'] == $post['user_id'] || $currentUser['role'] == 'admin')): ?>
                    <a class="btn btn-danger" href="delete_post.php?id=<?= $post['id'] ?>" onclick="return confirm('Delete post?');">Delete</a>
                <?php endif; ?>
                <a class="btn btn-secondary" href="index.php">← Back</a>
            </div>
        </div>

        <h2>Comments (<?= count($comments) ?>)</h2>

        <?php if (empty($comments)): ?>
            <p class="empty">No comments yet.</p>
        <?php endif; ?>

        <?php foreach ($comments as $item): ?>
            <div class="comment">
                <div class="comment-header">
                    <b><?= htmlspecialchars($item['author_login']) ?></b>
                    <span class="time"><?= $item['created_at'] ?></span>
                    <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                        <span class="status-badge status-<?= $item['status'] ?>"><?= $item['status'] ?></span>
                    <?php endif; ?>
                </div>
                <p><?= nl2br(htmlspecialchars($item['text'])) ?></p>
                <?php if ($currentUser && $currentUser['id'] == $item['user_id']): ?>
                    <a class="btn btn-danger" style="padding: 4px 10px; font-size: 12px; margin-top: 6px;" href="comment_delete.php?id=<?= $item['id'] ?>&post_id=<?= $post_id ?>" onclick="return confirm('Delete comment?');">Delete</a>
                <?php endif; ?>
                <?php if ($currentUser && $currentUser['role'] === 'admin' && $currentUser['id'] != $item['user_id']): ?>
                    <a class="btn btn-danger" style="padding: 4px 10px; font-size: 12px; margin-top: 6px;" href="comment_delete.php?id=<?= $item['id'] ?>&post_id=<?= $post_id ?>" onclick="return confirm('Delete?');">Delete (admin)</a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <?php if ($currentUser): ?>
            <h3>Add comment</h3>
            <?php if (!empty($commentError)): ?>
                <span class="error"><?= $commentError ?></span>
            <?php endif; ?>
            <div class="comment-form">
                <form method="post">
                    <textarea name="comment_text" rows="4" placeholder="Write your comment..."></textarea>
                    <div style="margin-top: 8px;">
                        <button class="btn btn-primary" type="submit" name="add_comment">Send</button>
                    </div>
                </form>
                <p class="info" style="margin-top: 8px; font-size: 12px;">Comment will appear after moderation.</p>
            </div>
        <?php else: ?>
            <p class="info"><a href="login.php" style="color: #e94560;">Log in</a> to add a comment.</p>
        <?php endif; ?>

    </div>
</body>
</html>