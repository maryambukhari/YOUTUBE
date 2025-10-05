<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT * FROM videos WHERE user_id = ?");
$stmt->execute([$user_id]);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
        }
        .profile-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .profile-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }
        .profile-header img {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-right: 20px;
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .video-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .video-card:hover {
            transform: translateY(-5px);
        }
        .video-card img {
            width: 100%;
            height: 150px;
            object-fit: cover;
        }
        .video-card h3 {
            font-size: 16px;
            margin: 10px;
        }
        .video-card button {
            background: #ff0000;
            color: white;
            border: none;
            padding: 10px;
            width: 100%;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .video-card button:hover {
            background: #cc0000;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">Home</a>
        <a href="upload.php">Upload</a>
        <a href="logout.php">Logout</a>
    </div>
    <div class="profile-container">
        <div class="profile-header">
            <img src="<?php echo $user['profile_picture']; ?>" alt="Profile">
            <div>
                <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
            </div>
        </div>
        <h3>Your Videos</h3>
        <div class="video-grid">
            <?php foreach ($videos as $video): ?>
                <div class="video-card">
                    <img src="<?php echo $video['thumbnail']; ?>" alt="Thumbnail">
                    <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                    <button onclick="deleteVideo(<?php echo $video['id']; ?>)">Delete</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function deleteVideo(videoId) {
            if (confirm('Are you sure you want to delete this video?')) {
                fetch('delete_video.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `video_id=${videoId}`
                }).then(() => window.location.reload());
            }
        }
    </script>
</body>
</html>
