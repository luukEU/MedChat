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

// meta velden uit form (checkbox/number)
$meta = $_POST;
unset($meta["question"], $meta["category"]);
$metaJson = json_encode($meta, JSON_UNESCAPED_UNICODE);

// Save user message
$stmt = $pdo->prepare("INSERT INTO chat_messages (session_id, category, role, message, meta) VALUES (?,?,?,?,?)");
$stmt->execute([$sessionId, $cat, "user", $q, $metaJson]);

// ---- Follow-up state per category in session
if (!isset($_SESSION["triage"])) $_SESSION["triage"] = [];
$state = $_SESSION["triage"][$cat] ?? null;

// CASE 1: we were waiting for follow-up answers
if ($state && isset($state["pending"]) && is_array($state["pending"])) {
  $pending = $state["pending"];
  $contextQ = $state["orig_question"] ?? "";
  $contextMeta = $state["meta"] ?? [];

  // parse the user's message into meta
  $parsed = parse_followup_answers($q, $pending);

  // merge: existing context meta <- form meta <- parsed answers
  $mergedMeta = array_merge($contextMeta, $meta, $parsed);

  // if it was "clarify", append to original question
  if (!empty($parsed["clarify"])) {
    $contextQ = trim($contextQ . " | extra uitleg: " . $parsed["clarify"]);
  }

  $answer = generate_final_answer($cat, $contextQ, $mergedMeta, (require __DIR__ . "/knowledge.php"));

  // save assistant message
  $stmt->execute([$sessionId, $cat, "assistant", $answer, json_encode($mergedMeta, JSON_UNESCAPED_UNICODE)]);

  // clear pending
  unset($_SESSION["triage"][$cat]);

  header("Location: chat.php?cat=" . urlencode($cat));
  exit;
}

// CASE 2: new question -> decide if we need follow-ups first
$KB = require __DIR__ . "/knowledge.php";
$followups = plan_followups($cat, $q, $meta, $KB);

if (count($followups) > 0) {
  // save assistant follow-up message
  $msg = format_followup_message($followups);
  $stmt->execute([$sessionId, $cat, "assistant", $msg, $metaJson]);

  // store state
  $_SESSION["triage"][$cat] = [
    "pending" => $followups,
    "orig_question" => $q,
    "meta" => $meta
  ];

  header("Location: chat.php?cat=" . urlencode($cat));
  exit;
}

// CASE 3: enough info -> final answer now
$answer = generate_final_answer($cat, $q, $meta, $KB);
$stmt->execute([$sessionId, $cat, "assistant", $answer, $metaJson]);

header("Location: chat.php?cat=" . urlencode($cat));
exit;