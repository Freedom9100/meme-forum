<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['uid'];
$comment_id = (int)($_GET['id'] ?? 0);
$post_id = (int)($_GET['post_id'] ?? 0);

if ($comment_id <= 0) {
    header('Location: index.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$uid]);
$currentUser = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->prepare("SELECT * FROM comments WHERE id = ? AND post_id = ?");
$stmt->execute([$comment_id, $post_id]);
$comment = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$comment) {
    header('Location: index.php');
    exit;
}

if ($comment['user_id'] != $uid && $currentUser['role'] != 'admin') {
    die('Access denied');
}

$stmt = $pdo->prepare("DELETE FROM comments WHERE id = ?");
$stmt->execute([$comment_id]);

header('Location: post.php?id=$post_id');
exit;
?>