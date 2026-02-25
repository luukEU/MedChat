<?php
require_once "db.php";
require_once "engine.php";

session_start();
if (!isset($_SESSION["session_id"])) {
  $_SESSION["session_id"] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION["session_id"];

$allowed = ["vallen","medicatie","wondzorg","diabetes"];
$cat = $_POST["category"] ?? "";
$q = trim($_POST["question"] ?? "");

if (!in_array($cat, $allowed, true) || $q === "") {
  header("Location: index.php");
  exit;
}

$q = mb_substr($q, 0, 700);

// Save user message
$stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, category, role, message) VALUES (?,?,?,?)");
$stmt->execute([$sessionId, $cat, "user", $q]);

// Generate + save assistant message
$answer = generate_answer($cat, $q);
$stmt->execute([$sessionId, $cat, "assistant", $answer]);

header("Location: chat.php?cat=" . urlencode($cat));
exit;