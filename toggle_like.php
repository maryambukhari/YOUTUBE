<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['video_id'])) {
    exit;
}

$user_id = $_SESSION['user_id'];
$video_id = $_POST['video_id'];

$stmt = $conn->prepare("SELECT * FROM likes WHERE user_id = ? AND video_id = ?");
$stmt->execute([$user_id, $video_id]);
$like = $stmt->fetch(PDO::FETCH_ASSOC);

if ($like) {
    $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$user_id, $video_id]);
} else {
    $stmt = $conn->prepare("INSERT INTO likes (user_id, video_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $video_id]);
}
