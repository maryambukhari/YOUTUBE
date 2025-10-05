<?php
session_start();
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Clone</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .navbar {
            background-color: #ff0000;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 20px;
            font-weight: bold;
            transition: color 0.3s ease;
        }
        .navbar a:hover {
            color: #ffe6e6;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
        }
        h2 {
            color: #222;
            margin-bottom: 20px;
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .video-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            cursor: pointer;
            animation: slideIn 0.5s ease;
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
            color: #222;
        }
        .video-card p {
            font-size: 14px;
            color: #666;
            margin: 0 10px 10px;
        }
        .error-message {
            color: red;
            text-align: center;
            padding: 10px;
        }
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        @media (max-width: 768px) {
            .video-grid {
                grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            }
            .video-card img {
                height: 120px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">Home</a>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php">Profile</a>
            <a href="upload.php">Upload</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="signup.php">Sign Up</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>Trending Videos</h2>
        <?php
        try {
            $stmt = $conn->query("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id ORDER BY v.views DESC");
            $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($videos)) {
                echo '<p class="error-message">No videos available.</p>';
            } else {
                echo '<div class="video-grid">';
                foreach ($videos as $video) {
                    ?>
                    <div class="video-card" onclick="window.location.href='video.php?id=<?php echo $video['id']; ?>'">
                        <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="Thumbnail" onerror="this.src='uploads/thumbnails/default_thumbnail.jpg'">
                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                        <p>By <?php echo htmlspecialchars($video['username']); ?> • <?php echo number_format($video['views']); ?> views</p>
                    </div>
                    <?php
                }
                echo '</div>';
            }
        } catch (PDOException $e) {
            error_log("Trending Videos Error: " . $e->getMessage());
            echo '<p class="error-message">Error loading trending videos.</p>';
        }
        ?>

        <h2>Recommended Videos</h2>
        <?php
        try {
            $stmt = $conn->query("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id ORDER BY v.views ASC LIMIT 4");
            $recommended_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (empty($recommended_videos)) {
                echo '<p class="error-message">No recommended videos available.</p>';
            } else {
                echo '<div class="video-grid">';
                foreach ($recommended_videos as $video) {
                    ?>
                    <div class="video-card" onclick="window.location.href='video.php?id=<?php echo $video['id']; ?>'">
                        <img src="<?php echo htmlspecialchars($video['thumbnail']); ?>" alt="Thumbnail" onerror="this.src='uploads/thumbnails/default_thumbnail.jpg'">
                        <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                        <p>By <?php echo htmlspecialchars($video['username']); ?> • <?php echo number_format($video['views']); ?> views</p>
                    </div>
                    <?php
                }
                echo '</div>';
            }
        } catch (PDOException $e) {
            error_log("Recommended Videos Error: " . $e->getMessage());
            echo '<p class="error-message">Error loading recommended videos.</p>';
        }
        ?>
    </div>
</body>
</html>
