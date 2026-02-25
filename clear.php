<?php
require_once "db.php";

session_start();
if (!isset($_SESSION["session_id"])) {
  header("Location: index.php");
  exit;
}
$sessionId = $_SESSION["session_id"];

$allowed = ["vallen","medicatie","wondzorg","diabetes"];
$cat = $_POST["category"] ?? "";
if (!in_array($cat, $allowed, true)) {
  header("Location: index.php");
  exit;
}

$stmt = $pdo->prepare("DELETE FROM chat_messages WHERE session_id = ? AND category = ?");
$stmt->execute([$sessionId, $cat]);

header("Location: chat.php?cat=" . urlencode($cat));
exit;