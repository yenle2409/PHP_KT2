<?php
include 'connect.php';
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}
$id = (int)$_GET['id'];
$stmt = $conn->prepare('UPDATE images SET likes = likes + 1 WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
header('Location: index.php');
exit;
?>