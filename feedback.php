<?php
require_once __DIR__ . "/db.php";

session_start();
if (!isset($_SESSION["session_id"])) {
  $_SESSION["session_id"] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION["session_id"];

$KB = require __DIR__ . "/knowledge.php";
$allowed = array_keys($KB["categories"] ?? []);

$cat = $_GET["cat"] ?? "";
if ($cat !== "" && !in_array($cat, $allowed, true)) {
  $cat = "";
}

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, "UTF-8"); }

// Zorg dat de tabel bestaat (handig bij demo's)
$pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
  id INT AUTO_INCREMENT PRIMARY KEY,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  session_id VARCHAR(64) NULL,
  category VARCHAR(50) NULL,
  question TEXT NULL,
  answer TEXT NULL,
  rating TINYINT NULL,
  comment TEXT NULL,
  user_ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Haal laatste vraag+antwoord uit de chat (als er een categorie is)
$lastQuestion = "";
$lastAnswer = "";
if ($cat !== "") {
  $stmt = $pdo->prepare("SELECT role, message FROM chat_messages WHERE session_id = ? AND category = ? ORDER BY id DESC LIMIT 30");
  $stmt->execute([$sessionId, $cat]);
  $rows = $stmt->fetchAll();

  foreach ($rows as $r) {
    if ($lastAnswer === "" && $r["role"] === "assistant") {
      $lastAnswer = $r["message"];
      continue;
    }
    if ($lastQuestion === "" && $r["role"] === "user") {
      $lastQuestion = $r["message"];
      break;
    }
  }
}

$success = false;
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $catPost = $_POST["category"] ?? "";
  if ($catPost !== "" && !in_array($catPost, $allowed, true)) $catPost = "";

  $ratingRaw = $_POST["rating"] ?? "";
  $rating = null;
  if ($ratingRaw !== "") {
    $rating = (int)$ratingRaw; // 1..5
    if ($rating < 1 || $rating > 5) $rating = null;
  }

  $comment = trim($_POST["comment"] ?? "");
  $question = trim($_POST["question"] ?? "");
  $answer = trim($_POST["answer"] ?? "");

  // minimaal 1 van: comment of rating
  if ($comment === "" && $rating === null) {
    $error = "Vul een beoordeling of opmerking in.";
  } else {
    $user_ip = $_SERVER["REMOTE_ADDR"] ?? null;
    $user_agent = substr($_SERVER["HTTP_USER_AGENT"] ?? "", 0, 255);

    $stmt = $pdo->prepare("INSERT INTO feedback (session_id, category, question, answer, rating, comment, user_ip, user_agent)
                           VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
      $sessionId,
      $catPost !== "" ? $catPost : null,
      $question !== "" ? $question : null,
      $answer !== "" ? $answer : null,
      $rating,
      $comment !== "" ? $comment : null,
      $user_ip,
      $user_agent !== "" ? $user_agent : null,
    ]);

    $success = true;
    // reset form
    $cat = $catPost;
    $lastQuestion = $question;
    $lastAnswer = $answer;
  }
}

$topicTitle = "Feedback";
?>
<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= h($topicTitle) ?> – MedChat</title>
  <link rel="stylesheet" href="assets/style.css">
  <script defer src="assets/app.js"></script>
</head>
<body>
  <div class="container">
    <header class="header">
      <div class="row">
        <a class="link" href="<?= $cat ? 'chat.php?cat=' . h($cat) : 'index.php' ?>">← Terug</a>
        <h1 class="h1">📝 Feedback</h1>
      </div>
      <button class="btn ghost" id="themeToggle" type="button">🌙 Dark mode</button>
    </header>

    <div class="notice">
      <b>Help ons verbeteren.</b> Deel kort wat goed ging of wat je mist. Geen persoonsgegevens.
    </div>

    <?php if ($success): ?>
      <div class="notice" style="border-color: rgba(0,200,0,.35);">
        ✅ Bedankt! Je feedback is opgeslagen.
      </div>
    <?php elseif ($error !== ""): ?>
      <div class="notice" style="border-color: rgba(220,0,0,.35);">
        ❌ <?= h($error) ?>
      </div>
    <?php endif; ?>

    <form class="panel" method="post" action="feedback.php<?= $cat ? '?cat=' . urlencode($cat) : '' ?>">
      <div class="panelTitle">Feedbackformulier</div>

      <div class="formGrid">
        <label class="field">
          <span>Onderwerp</span>
          <select class="input" name="category">
            <option value="">Algemeen</option>
            <?php foreach ($allowed as $c): ?>
              <?php $label = $KB["categories"]["$c"]["label"] ?? $c; ?>
              <option value="<?= h($c) ?>" <?= ($c === $cat ? 'selected' : '') ?>><?= h($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>

        <label class="field">
          <span>Beoordeling</span>
          <select class="input" name="rating">
            <option value="">Kies…</option>
            <option value="5">⭐⭐⭐⭐⭐ Heel duidelijk</option>
            <option value="4">⭐⭐⭐⭐ Goed</option>
            <option value="3">⭐⭐⭐ Neutraal</option>
            <option value="2">⭐⭐ Onhandig / vaag</option>
            <option value="1">⭐ Slecht / niet helpend</option>
          </select>
        </label>

        <label class="field" style="grid-column: 1 / -1;">
          <span>Opmerking (wat miste je / wat kan beter?)</span>
          <textarea class="input" name="comment" rows="5" placeholder="Bijv. 'Meer concrete stappen' of 'te snel spoed'..."></textarea>
        </label>

        <details style="grid-column: 1 / -1;" open>
          <summary class="muted" style="cursor:pointer;">(Optioneel) Laatste vraag/antwoord meesturen</summary>
          <div style="margin-top:10px;">
            <label class="field">
              <span>Jouw vraag</span>
              <textarea class="input" name="question" rows="3" placeholder="Bijv. 'Ik ben gevallen'..."><?= h($lastQuestion) ?></textarea>
            </label>
            <label class="field">
              <span>Antwoord van MedChat</span>
              <textarea class="input" name="answer" rows="5" placeholder="Plak hier het antwoord als je wil..."><?= h($lastAnswer) ?></textarea>
            </label>
          </div>
        </details>

      </div>

      <div style="display:flex; gap:10px; margin-top:12px;">
        <button class="btn" type="submit">Feedback verzenden</button>
        <a class="btn ghost" href="<?= $cat ? 'chat.php?cat=' . h($cat) : 'index.php' ?>">Annuleren</a>
      </div>
    </form>

    <footer class="footer">
      <span>© MedChat Demo – Luuk</span>
      <span class="muted">Feedback wordt lokaal opgeslagen</span>
    </footer>
  </div>
</body>
</html>
