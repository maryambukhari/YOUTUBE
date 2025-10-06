<?php
session_start();
include 'db.php';

// Initialize variables
$video = null;
$comments = [];
$liked = false;
$like_count = 0;
$subscribed = false;
$subscriber_count = 0;
$error_message = '';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit;
}

$video_id = (int)$_GET['id'];

// Update views
try {
    $stmt = $conn->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
    $stmt->execute([$video_id]);
} catch (PDOException $e) {
    error_log("View Update Error: " . $e->getMessage());
    $error_message = "Error updating video views.";
}

// Fetch video details
try {
    $stmt = $conn->prepare("SELECT v.*, u.username, u.id as channel_id FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id = ?");
    $stmt->execute([$video_id]);
    $video = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$video) {
        $error_message = "Video not found.";
        echo "<script>window.location.href='index.php';</script>";
        exit;
    }
    // Check local video file (if not a YouTube link)
    if (strpos($video['video_path'], 'https://www.youtube.com/embed/') !== 0) {
        $video_file = $_SERVER['DOCUMENT_ROOT'] . '/' . $video['video_path'];
        if (!file_exists($video_file) || !is_readable($video_file)) {
            $error_message = "Video file is missing or inaccessible: " . htmlspecialchars($video['video_path']);
        }
    }
} catch (PDOException $e) {
    error_log("Video Fetch Error: " . $e->getMessage());
    $error_message = "Error loading video details.";
}

// Fetch comments
try {
    $stmt = $conn->prepare("SELECT DISTINCT c.*, u.username FROM comments c JOIN users u ON c.user_id = u.id WHERE c.video_id = ? ORDER BY c.created_at DESC");
    $stmt->execute([$video_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Comments Fetch Error: " . $e->getMessage());
    $error_message = "Error loading comments.";
}

// Check if user liked the video
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$_SESSION['user_id'], $video_id]);
        $liked = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Like Check Error: " . $e->getMessage());
    }
}
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE video_id = ?");
    $stmt->execute([$video_id]);
    $like_count = $stmt->fetch(PDO::FETCH_ASSOC)['like_count'];
} catch (PDOException $e) {
    error_log("Like Count Error: " . $e->getMessage());
}

// Check if user is subscribed
if (isset($_SESSION['user_id'])) {
    try {
        $stmt = $conn->prepare("SELECT * FROM subscriptions WHERE subscriber_id = ? AND channel_id = ?");
        $stmt->execute([$_SESSION['user_id'], $video['channel_id']]);
        $subscribed = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Subscription Check Error: " . $e->getMessage());
    }
}
try {
    $stmt = $conn->prepare("SELECT COUNT(*) as subscriber_count FROM subscriptions WHERE channel_id = ?");
    $stmt->execute([$video['channel_id']]);
    $subscriber_count = $stmt->fetch(PDO::FETCH_ASSOC)['subscriber_count'];
} catch (PDOException $e) {
    error_log("Subscriber Count Error: " . $e->getMessage());
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment']) && isset($_SESSION['user_id'])) {
    $comment_text = trim($_POST['comment_text']);
    try {
        $stmt = $conn->prepare("SELECT * FROM comments WHERE user_id = ? AND video_id = ? AND comment_text = ?");
        $stmt->execute([$_SESSION['user_id'], $video_id, $comment_text]);
        if (!$stmt->fetch(PDO::FETCH_ASSOC) && !empty($comment_text)) {
            $stmt = $conn->prepare("INSERT INTO comments (user_id, video_id, comment_text) VALUES (?, ?, ?)");
            $stmt->execute([$_SESSION['user_id'], $video_id, $comment_text]);
        }
        echo "<script>window.location.reload();</script>";
    } catch (PDOException $e) {
        error_log("Comment Insert Error: " . $e->getMessage());
        $error_message = "Error posting comment.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($video['title'] ?? 'Video'); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
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
        .video-container {
            max-width: 1200px;
            margin: 20px auto;
            display: flex;
            gap: 20px;
        }
        .video-player {
            flex: 3;
        }
        .video-player iframe, .video-player video {
            width: 100%;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-height: 500px;
            aspect-ratio: 16 / 9;
        }
        .video-info {
            padding: 15px;
        }
        .video-info h2 {
            margin: 0;
            font-size: 24px;
            color: #222;
        }
        .video-info p {
            color: #666;
            font-size: 14px;
        }
        .action-buttons {
            display: flex;
            gap: 10px;
            margin: 10px 0;
        }
        .action-buttons button {
            padding: 10px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .action-buttons .like-btn {
            background: <?php echo $liked ? '#ff0000' : '#e0e0e0'; ?>;
            color: <?php echo $liked ? 'white' : '#333'; ?>;
        }
        .action-buttons .like-btn:hover {
            background: <?php echo $liked ? '#cc0000' : '#d0d0d0'; ?>;
        }
        .action-buttons .subscribe-btn {
            background: <?php echo $subscribed ? '#ff0000' : '#e0e0e0'; ?>;
            color: <?php echo $subscribed ? 'white' : '#333'; ?>;
        }
        .action-buttons .subscribe-btn:hover {
            background: <?php echo $subscribed ? '#cc0000' : '#d0d0d0'; ?>;
        }
        .action-buttons span {
            font-size: 14px;
            color: #666;
            align-self: center;
        }
        .comments-section {
            margin-top: 20px;
            background: white;
            padding: 15px;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .comments-section textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            resize: vertical;
            margin-bottom: 10px;
        }
        .comments-section button {
            padding: 10px 20px;
            background: #ff0000;
            color: white;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .comments-section button:hover {
            background: #cc0000;
        }
        .comment {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
            animation: slideIn 0.5s ease;
        }
        .comment:last-child {
            border-bottom: none;
        }
        .comment strong {
            color: #222;
        }
        .comment p {
            margin: 5px 0;
            color: #333;
        }
        .related-videos {
            flex: 1;
        }
        .related-video-card {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: transform 0.3s ease;
        }
        .related-video-card:hover {
            transform: translateX(5px);
        }
        .related-video-card img {
            width: 120px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
        .related-video-card h4 {
            font-size: 14px;
            margin: 0;
            color: #222;
        }
        .related-video-card p {
            font-size: 12px;
            color: #666;
            margin: 5px 0 0;
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
            .video-container {
                flex-direction: column;
            }
            .related-videos {
                margin-top: 20px;
            }
            .video-player iframe, .video-player video {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>
    <?php if ($error_message): ?>
        <p class="error-message"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>
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
    <div class="video-container">
        <div class="video-player">
            <?php if ($video && empty($error_message)): ?>
                <?php if (strpos($video['video_path'], 'https://www.youtube.com/embed/') === 0): ?>
                    <iframe src="<?php echo htmlspecialchars($video['video_path']); ?>" frameborder="0" allowfullscreen></iframe>
                <?php else: ?>
                    <video controls>
                        <source src="<?php echo htmlspecialchars($video['video_path']); ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                <?php endif; ?>
                <div class="video-info">
                    <h2><?php echo htmlspecialchars($video['title']); ?></h2>
                    <p>By <?php echo htmlspecialchars($video['username']); ?> • <?php echo number_format($video['views']); ?> views</p>
                    <p><?php echo htmlspecialchars($video['description'] ?? 'No description available.'); ?></p>
                    <div class="action-buttons">
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <button class="like-btn" onclick="toggleLike(<?php echo $video['id']; ?>)">
                                <?php echo $liked ? 'Unlike' : 'Like'; ?> (<?php echo $like_count; ?>)
                            </button>
                            <button class="subscribe-btn" onclick="toggleSubscribe(<?php echo $video['channel_id']; ?>)">
                                <?php echo $subscribed ? 'Unsubscribe' : 'Subscribe'; ?> (<?php echo $subscriber_count; ?>)
                            </button>
                        <?php else: ?>
                            <span>Please <a href="login.php">login</a> to like or subscribe.</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="error-message">Unable to load video. <?php echo htmlspecialchars($error_message); ?></p>
            <?php endif; ?>
            <div class="comments-section">
                <h3>Comments (<?php echo count($comments); ?>)</h3>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="POST">
                        <textarea name="comment_text" placeholder="Add a comment..." required></textarea>
                        <button type="submit" name="comment">Comment</button>
                    </form>
                <?php else: ?>
                    <p>Please <a href="login.php">login</a> to comment.</p>
                <?php endif; ?>
                <?php if (empty($comments)): ?>
                    <p>No comments yet. Be the first to comment!</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment">
                            <strong><?php echo htmlspecialchars($comment['username']); ?>:</strong>
                            <p><?php echo htmlspecialchars($comment['comment_text']); ?></p>
                            <small><?php echo date('M d, Y', strtotime($comment['created_at'])); ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="related-videos">
            <h3>Related Videos</h3>
            <?php
            try {
                $stmt = $conn->query("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id WHERE v.id != $video_id ORDER BY RAND() LIMIT 5");
                $related_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (empty($related_videos)) {
                    echo '<p>No related videos available.</p>';
                } else {
                    foreach ($related_videos as $related): ?>
                        <div class="related-video-card" onclick="window.location.href='video.php?id=<?php echo $related['id']; ?>'">
                            <img src="<?php echo htmlspecialchars($related['thumbnail']); ?>" alt="Thumbnail" onerror="this.src='uploads/thumbnails/default_thumbnail.jpg'">
                            <div>
                                <h4><?php echo htmlspecialchars($related['title']); ?></h4>
                                <p><?php echo htmlspecialchars($related['username']); ?> • <?php echo number_format($related['views']); ?> views</p>
                            </div>
                        </div>
                    <?php endforeach;
                }
            } catch (PDOException $e) {
                error_log("Related Videos Error: " . $e->getMessage());
                echo '<p class="error-message">Error loading related videos.</p>';
            }
            ?>
        </div>
    </div>

    <script>
        function toggleLike(videoId) {
            fetch('toggle_like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `video_id=${videoId}`
            }).then(response => {
                if (response.ok) window.location.reload();
                else alert('Error toggling like.');
            });
        }

        function toggleSubscribe(channelId) {
            fetch('toggle_subscribe.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `channel_id=${channelId}`
            }).then(response => {
                if (response.ok) window.location.reload();
                else alert('Error toggling subscription.');
            });
        }
    </script>
</body>
</html>
