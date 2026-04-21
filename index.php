<?php

session_start();
require_once 'db.php';

$currentUser = null;
if (isset($_SESSION['uid'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['uid']]); 
    $currentUser = $stmt->fetch(PDO::FETCH_ASSOC);
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$filterCategoryId = (int)($_GET['category_id'] ?? 0);
$search = trim($_GET['search'] ?? '');

if (!empty($search)) {
    $stmt = $pdo->prepare("
        SELECT 
            posts.id, posts.text, posts.image_path, posts.created_at,
            users.login AS author_login,
            categories.name AS category_name
        FROM posts
        JOIN users ON posts.user_id = users.id
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.status = 'approved' AND posts.text LIKE ?
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute(['%' . $search . '%']);
} elseif ($filterCategoryId > 0) {
    $stmt = $pdo->prepare("
        SELECT 
            posts.id, posts.text, posts.image_path, posts.created_at,
            users.login AS author_login,
            categories.name AS category_name
        FROM posts
        JOIN users ON posts.user_id = users.id
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.category_id = ? AND posts.status = 'approved'
        ORDER BY posts.created_at DESC
    ");
    $stmt->execute([$filterCategoryId]);
} else {
    $stmt = $pdo->query("
        SELECT 
            posts.id, posts.text, posts.image_path, posts.created_at,
            users.login AS author_login,
            categories.name AS category_name
        FROM posts
        JOIN users ON posts.user_id = users.id
        JOIN categories ON posts.category_id = categories.id 
        WHERE posts.status = 'approved'
        ORDER BY posts.created_at DESC
    ");
}

$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum Memes</title>
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
        h2 { color: #e94560; margin-bottom: 16px; }
        .categories {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 24px;
        }
        .categories a {
            background: #16213e;
            border: 1px solid #0f3460;
            color: #cdd6f4;
            text-decoration: none;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 13px;
            transition: border-color 0.2s;
        }
        .categories a:hover, .categories a.active { border-color: #e94560; color: #e94560; }
        .card {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 10px;
            padding: 16px;
            margin-bottom: 16px;
            overflow-wrap: anywhere;
            word-break: break-word;
        }
        .card img { width: 100%; height: 320px; object-fit: cover; border-radius: 8px; display: block; margin-bottom: 10px; }
        .card p { margin: 8px 0; line-height: 1.5; }
        .meta { color: #7f8c8d; font-size: 13px; }
        .badge {
            display: inline-block;
            background: #0f3460;
            color: #cdd6f4;
            font-size: 12px;
            padding: 2px 10px;
            border-radius: 12px;
        }
        .btn {
            display: inline-block;
            padding: 7px 14px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            margin-right: 6px;
        }
        .btn-primary { background: #e94560; color: #fff; }
        .btn-danger  { background: #c0392b; color: #fff; }
        .btn:hover { opacity: 0.85; }
        .empty { text-align: center; color: #7f8c8d; padding: 40px 0; }
        .search-form {
            display: flex;
            align-items: center;
            gap: 6px;
            margin: 0 12px;
        }
        .search-form input[type="text"] {
            background: #0f3460;
            border: 1px solid #1a5276;
            border-radius: 6px;
            color: #cdd6f4;
            padding: 7px 12px;
            font-size: 14px;
            outline: none;
            width: 220px;
            transition: border-color 0.2s, width 0.2s;
        }
        .search-form input[type="text"]:focus { border-color: #e94560; width: 280px; }
        .search-form input::placeholder { color: #7f8c8d; }
        .search-form button {
            background: #e94560;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 7px 14px;
            font-size: 14px;
            cursor: pointer;
        }
        .search-form button:hover { opacity: 0.85; }
        .search-result-info {
            background: #16213e;
            border: 1px solid #e94560;
            border-radius: 8px;
            padding: 10px 16px;
            margin-bottom: 16px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .search-result-info a { color: #7f8c8d; font-size: 13px; text-decoration: none; }
        .search-result-info a:hover { color: #e94560; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a href="index.php" class="brand">😂 Forum Memes</a>
        <form class="search-form" method="get" action="index.php">
            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="🔍 Search memes...">
            <button type="submit">Search</button>
        </form>
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
        <h2>All Memes</h2>

        <div class="categories">
            <a href="index.php" <?= $filterCategoryId === 0 ? 'class="active"' : '' ?>>All</a>
            <?php foreach ($categories as $item): ?>
                <a 
                    href="index.php?category_id=<?= $item['id'] ?>"
                    <?= $filterCategoryId === $item['id'] ? 'class="active"' : '' ?>
                >
                    <?= htmlspecialchars($item['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (!empty($search)): ?>
            <div class="search-result-info">
                🔍 Results for: <b><?= htmlspecialchars($search) ?></b> — found <?= count($posts) ?>
                <a href="index.php">✕ Clear</a>
            </div>
        <?php endif; ?>

        <?php if (empty($posts)): ?>
            <p class="empty"><?= !empty($search) ? 'Nothing found.' : 'No memes yet. Be the first!' ?></p>
        <?php endif; ?>

        <?php foreach ($posts as $post): ?>
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
                    by <b><?= htmlspecialchars($post['author_login']) ?></b>
                    &bull; <?= $post['created_at'] ?>
                </p>

                <div style="margin-top: 10px;">
                    <a class="btn btn-primary" href="post.php?id=<?= $post['id'] ?>">Open</a>
                    <?php if ($currentUser && $currentUser['role'] === 'admin'): ?>
                        <a class="btn btn-danger" href="delete_post.php?id=<?= $post['id'] ?>" onclick="return confirm('Delete this post?');">Delete</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</body>
</html>