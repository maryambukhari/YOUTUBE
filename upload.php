<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    // Handle video upload
    $video_path = '';
    if (isset($_FILES['video']) && $_FILES['video']['error'] === 0) {
        $target_dir = "uploads/videos/";
        $video_path = $target_dir . uniqid() . '_' . basename($_FILES['video']['name']);
        move_uploaded_file($_FILES['video']['tmp_name'], $video_path);
    }

    // Handle thumbnail upload
    $thumbnail = 'default_thumbnail.jpg';
    if (isset($_FILES['thumbnail']) && $_FILES['thumbnail']['error'] === 0) {
        $target_dir = "uploads/thumbnails/";
        $thumbnail = $target_dir . uniqid() . '_' . basename($_FILES['thumbnail']['name']);
        move_uploaded_file($_FILES['thumbnail']['tmp_name'], $thumbnail);
    }

    $stmt = $conn->prepare("INSERT INTO videos (user_id, title, description, thumbnail, video_path) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $title, $description, $thumbnail, $video_path])) {
        echo "<script>window.location.href='profile.php';</script>";
    } else {
        $error = "Upload failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Video</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: linear-gradient(135deg, #ff0000, #ff5555);
            margin: 0;
        }
        .upload-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            width: 400px;
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .upload-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        .upload-container input, .upload-container textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .upload-container button {
            width: 100%;
            padding: 10px;
            background: #ff0000;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .upload-container button:hover {
            background: #cc0000;
        }
        .error {
            color: red;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="upload-container">
        <h2>Upload Video</h2>
        <?php if (isset($error)): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="title" placeholder="Video Title" required>
            <textarea name="description" placeholder="Description"></textarea>
            <input type="file" name="video" accept="video/*" required>
            <input type="file" name="thumbnail" accept="image/*">
            <button type="submit">Upload</button>
        </form>
    </div>
</body>
</html>
