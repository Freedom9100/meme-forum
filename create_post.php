<?php

session_start();
require_once 'db.php';

if (!isset($_SESSION['uid'])) {
    header('Location: login.php');
    exit;
}

$uid = $_SESSION['uid'];
$errors = [];
$text = '';
$selectedCategory = 0;

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['create_post'])) {
    $text = trim($_POST['text'] ?? '');
    $selectedCategory = (int)($_POST['category_id'] ?? 0);

    $imagePaths = [];
    $allowedExt = ['jpg', 'jpeg', 'png'];
    $maxSize = 5 * 1024 * 1024;

    if (isset($_FILES['image'])) {
        foreach ($_FILES['image']['tmp_name'] as $key => $tmpName) {
            if ($_FILES['image']['error'][$key] !== 0) {
                continue;
            }
            $ext = strtolower(pathinfo($_FILES['image']['name'][$key], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExt)) {
                $errors['image'] = 'Allowed only jpg, jpeg, png';
                break;
            }
            $fileName = uniqid('meme_') . '.' . $ext;
            $uploadPath = 'uploads/memes/' . $fileName;
            if (move_uploaded_file($tmpName, $uploadPath)) {
                $imagePaths[] = $uploadPath;
            } else {
                $errors['image'] = 'Upload image error';
            }
        }
    }

    $hasImage = !empty($imagePaths);
    $imagePathJson = $hasImage ? json_encode($imagePaths) : null;

    if (empty($text) && !$hasImage) {
        $errors['content'] = 'Fill text or upload image';
    }

    if ($selectedCategory <= 0) {
        $errors['category'] = 'Select category';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare("INSERT INTO posts (text, image_path, category_id, user_id) VALUES (?, ?, ?, ?)");
        $stmt->execute([$text ?: null, $imagePathJson, $selectedCategory, $uid]);

        header('Location: my_posts.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create post</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; background: #1a1a2e; color: #cdd6f4; }
        .page-center { max-width: 600px; margin: 40px auto; padding: 0 20px; }
        h1 { color: #e94560; margin-bottom: 24px; }
        .card {
            background: #16213e;
            border: 1px solid #0f3460;
            border-radius: 12px;
            padding: 28px;
        }
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 6px; color: #aab; font-size: 14px; }
        textarea, select {
            width: 100%;
            background: #0f3460;
            border: 1px solid #1a5276;
            border-radius: 6px;
            color: #cdd6f4;
            padding: 10px;
            font-size: 14px;
            outline: none;
        }
        textarea { resize: vertical; }
        textarea:focus, select:focus { border-color: #e94560; }
        input[type="file"] { color: #cdd6f4; font-size: 14px; }
        .error { color: #e74c3c; font-size: 13px; margin-top: 4px; display: block; }
        .error-box {
            background: #5c1e1e;
            color: #fdd;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .btn-primary  { background: #e94560; color: #fff; }
        .btn-secondary{ background: #2c3e50; color: #cdd6f4; }
        .btn:hover { opacity: 0.85; }
        .hint { font-size: 12px; color: #7f8c8d; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="page-center">
        <h1>Create post</h1>
        <div class="card">
            <form method="post" enctype="multipart/form-data">

                <?php if (isset($errors['content'])): ?>
                    <div class="error-box"><?= $errors['content'] ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label>Meme text</label>
                    <textarea name="text" rows="5" placeholder="Write something funny..."><?= htmlspecialchars($text) ?></textarea>
                </div>

                <div class="form-group">
                    <label>Image (jpg, jpeg, png — max 5MB)</label>
                    <input type="file" name="image[]" accept=".jpg,.jpeg,.png" multiple>
                    <?php if (isset($errors['image'])): ?>
                        <span class="error"><?= $errors['image'] ?></span>
                    <?php endif; ?>
                    <p class="hint">At least one of: text or image is required.</p>
                </div>

                <div class="form-group">
                    <label>Category</label>
                    <select name="category_id">
                        <option value="0">— Select category —</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= $item['id'] ?>" <?= $selectedCategory == $item['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($item['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (isset($errors['category'])): ?>
                        <span class="error"><?= $errors['category'] ?></span>
                    <?php endif; ?>
                </div>

                <div style="display: flex; gap: 10px; margin-top: 6px;">
                    <button class="btn btn-primary" type="submit" name="create_post">Publish</button>
                    <a class="btn btn-secondary" href="my_posts.php">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>