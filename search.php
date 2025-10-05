<?php
session_start();
include 'db.php';

$query = isset($_GET['q']) ? $_GET['q'] : '';
$stmt = $conn->prepare("SELECT v.*, u.username FROM videos v JOIN users u ON v.user_id = u.id WHERE v.title LIKE ?");
$stmt->execute(['%' . $query . '%']);
$videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
        }
        .navbar {
            background-color: #ff0000;
            padding: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }
        .navbar input {
            padding: 8px;
            width: 300px;
            border: none;
            border-radius: 20px;
            outline: none;
        }
        .navbar a {
            color: white;
            text-decoration: none;
            margin: 0 15px;
        }
        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
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
        .video-card p {
            font-size: 14px;
            color: #666;
            margin: 0 10px 10px;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <a href="index.php">Home</a>
        <input type="text" placeholder="Search videos..." onkeypress="if(event.key === 'Enter') searchVideos(this.value)">
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php">Profile</a>
            <a href="upload.php">Upload</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="signup.php">Sign Up</a>
            <a href="login.php">Login</a>
        <?php endif; ?>
    </div>
    <h2 style="margin: 20px;">Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
    <div class="video-grid">
        <?php foreach ($videos as $video): ?>
            <div class="video-card" onclick="window.location.href='video.php?id=<?php echo $video['id']; ?>'">
                <img src="<?php echo $video['thumbnail']; ?>" alt="Thumbnail">
                <h3><?php echo htmlspecialchars($video['title']); ?></h3>
                <p>By <?php echo htmlspecialchars($video['username']); ?> • <?php echo $video['views']; ?> views</p>
            </div>
        <?php endforeach; ?>
    </div>

    <script>
        function searchVideos(query) {
            window.location.href = `search.php?q=${encodeURIComponent(query)}`;
        }
    </script>
</body>
</html>
