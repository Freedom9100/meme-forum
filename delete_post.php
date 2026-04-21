<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['uid'];
$post_id = (int)($_GET['id'] ?? 0);

if ($post_id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$currentUser) {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM posts WHERE id = ?");
$stmt->execute([$post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: index.php');
    exit;
}

if ($post['user_id'] != $uid && $currentUser['role'] != 'admin') {
    die ('Access denied');
}

if (!empty($post['image_path']) && file_exists($post['image_path'])) {
    unlink($post['image_path']);
}

$stmt = $pdo->prepare("DELETE FROM posts WHERE id = ?");
$stmt->execute([$post_id]);

if ($currentUser['role'] == 'admin') {
    header('Location: index.php');
} else {
    header('Location: my_posts.php');
}
exit;
?>