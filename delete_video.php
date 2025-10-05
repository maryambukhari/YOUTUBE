<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['video_id'])) {
    exit;
}

$video_id = $_POST['video_id'];
$stmt = $conn->prepare("DELETE FROM videos WHERE id = ? AND user_id = ?");
$stmt->execute([$video_id, $_SESSION['user_id']]);
