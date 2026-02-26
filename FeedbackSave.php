<?php
// feedback_save.php
header("Content-Type: application/json; charset=utf-8");

require_once __DIR__ . "/db.php"; // <-- jouw bestaande DB connectie

// Input
$session_id = $_POST["session_id"] ?? null;
$category   = $_POST["category"] ?? null;
$question   = trim($_POST["question"] ?? "");
$answer     = trim($_POST["answer"] ?? "");
$ratingRaw  = $_POST["rating"] ?? null; // 1 of 0
$comment    = trim($_POST["comment"] ?? "");

if ($question === "" || $answer === "") {
  http_response_code(400);
  echo json_encode(["ok" => false, "error" => "Question/answer ontbreekt"]);
  exit;
}

$rating = null;
if ($ratingRaw !== null && $ratingRaw !== "") {
  $rating = (int)$ratingRaw;
  if ($rating !== 0 && $rating !== 1) $rating = null;
}

$user_ip = $_SERVER["REMOTE_ADDR"] ?? null;
$user_agent = substr($_SERVER["HTTP_USER_AGENT"] ?? "", 0, 255);

// Insert SQL
$sql = "INSERT INTO feedback (session_id, category, question, answer, rating, comment, user_ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

try {
  // === PDO ===
  if (isset($pdo) && $pdo instanceof PDO) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
      $session_id,
      $category,
      $question,
      $answer,
      $rating,
      ($comment === "" ? null : $comment),
      $user_ip,
      ($user_agent === "" ? null : $user_agent)
    ]);
    echo json_encode(["ok" => true]);
    exit;
  }

  // === mysqli (conn of mysqli) ===
  $mysqli = null;
  if (isset($conn) && $conn instanceof mysqli) $mysqli = $conn;
  if (isset($mysqliConn) && $mysqliConn instanceof mysqli) $mysqli = $mysqliConn;
  if (isset($db) && $db instanceof mysqli) $mysqli = $db;
  if (isset($mysqli) && $mysqli instanceof mysqli) {
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) throw new Exception("Prepare failed: " . $mysqli->error);

    // types: s=string, i=int
    // session_id(s), category(s), question(s), answer(s), rating(i or null), comment(s), ip(s), ua(s)
    // mysqli heeft geen echte null-typing; we sturen rating als int, en leeg -> null met set_null via workaround:
    $ratingVal = ($rating === null) ? null : (int)$rating;

    $stmt->bind_param(
      "ssssisss",
      $session_id,
      $category,
      $question,
      $answer,
      $ratingVal,
      $comment,
      $user_ip,
      $user_agent
    );

    // leeg comment -> null in db (optioneel)
    if ($comment === "") $comment = null;

    $stmt->execute();
    $stmt->close();

    echo json_encode(["ok" => true]);
    exit;
  }

  throw new Exception("Geen $pdo of mysqli verbinding gevonden in db.php");

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["ok" => false, "error" => "Opslaan mislukt"]);
  exit;
}