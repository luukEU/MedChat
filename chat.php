<?php
require_once "db.php";

$KB = require __DIR__ . "/knowledge.php";
$allowed = array_keys($KB["categories"] ?? []);
$cat = $_GET["cat"] ?? "";
if (!in_array($cat, $allowed, true)) {
  header("Location: index.php");
  exit;
}

$fields = $KB["categories"][$cat]["fields"] ?? [];

session_start();
if (!isset($_SESSION["session_id"])) {
  $_SESSION["session_id"] = bin2hex(random_bytes(16));
}
$sessionId = $_SESSION["session_id"];

$stmt = $pdo->prepare("
  SELECT role, message, created_at
  FROM chat_messages
  WHERE session_id = ? AND category = ?
  ORDER BY id ASC
  LIMIT 250
");
$stmt->execute([$sessionId, $cat]);
$messages = $stmt->fetchAll();

function h($s){ return htmlspecialchars($s, ENT_QUOTES, "UTF-8"); }

$topicTitle = $KB["categories"][$cat]["label"] ?? $cat;
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
        <a class="link" href="index.php">← Terug</a>
        <h1 class="h1"><?= h($topicTitle) ?></h1>
      </div>

      <div class="row">
        <form method="post" action="clear.php" onsubmit="return confirm('Weet je zeker dat je deze chat wilt legen?');">
          <input type="hidden" name="category" value="<?= h($cat) ?>">
          <button class="btn ghost" type="submit">🧹 Chat legen</button>
        </form>

        <button class="btn ghost" id="themeToggle" type="button">🌙 Dark mode</button>
      </div>
    </header>

    <div class="notice">
      <b>Demo/leerproject.</b> Geen diagnose. Bij bewustzijnverlies, ernstige pijn, veel bloed,
      benauwdheid of snelle achteruitgang: <b>112</b>. Bij twijfel: huisarts/huisartsenpost.
    </div>

    <div class="chatbox" id="chatbox">
      <?php if (count($messages) === 0): ?>
        <div class="empty">
          <p><b>Tip:</b> Stel je vraag zo concreet mogelijk.</p>
          <p class="muted">Voorbeeld: “Oma (82) is gevallen en heeft heuppijn, wat is verstandig?”</p>
        </div>
      <?php endif; ?>

      <?php foreach ($messages as $m): ?>
        <div class="msg <?= h($m["role"]) ?>">
          <div class="bubble"><?= nl2br(h($m["message"])) ?></div>
          <div class="meta"><?= h($m["created_at"]) ?></div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Extra info panel (optioneel) -->
    <div class="panel">
      <div class="panelTitle">Extra info (optioneel)</div>

      <div class="formGrid">
        <?php foreach ($fields as $name => $cfg): ?>
          <?php $type = $cfg["type"] ?? ""; ?>

          <?php if ($type === "number"): ?>
            <label class="field">
              <span><?= h($cfg["label"] ?? $name) ?></span>
              <input class="input"
                     type="number"
                     name="<?= h($name) ?>"
                     form="chatForm"
                     min="<?= (int)($cfg["min"] ?? 0) ?>"
                     max="<?= (int)($cfg["max"] ?? 999) ?>"
                     step="1">
            </label>

          <?php elseif ($type === "checkbox"): ?>
            <label class="check">
              <input type="checkbox"
                     name="<?= h($name) ?>"
                     value="1"
                     form="chatForm">
              <span><?= h($cfg["label"] ?? $name) ?></span>
            </label>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Chat form -->
    <form class="chatform" id="chatForm" method="post" action="save.php" autocomplete="off">
      <input type="hidden" name="category" value="<?= h($cat) ?>">
      <input class="input" type="text" name="question" placeholder="Stel je vraag..." required maxlength="700">
      <button class="btn" type="submit">Verstuur</button>
    </form>

    <div class="quick">
      <span class="muted">Snelle voorbeelden:</span>

      <?php if ($cat === "vallen"): ?>
        <button class="chip" type="button" data-fill="Iemand van 80 is gevallen en heeft heuppijn. Wat nu?">Heuppijn</button>
        <button class="chip" type="button" data-fill="Hoofd gestoten en gebruikt bloedverdunners. Wat is slim?">Hoofd + bloedverdunners</button>

      <?php elseif ($cat === "medicatie"): ?>
        <button class="chip" type="button" data-fill="Ik ben een dosis vergeten. Wat is verstandig?">Dosis vergeten</button>
        <button class="chip" type="button" data-fill="Kan ik medicijnen combineren?">Combineren</button>

      <?php elseif ($cat === "wondzorg"): ?>
        <button class="chip" type="button" data-fill="Snijwond blijft bloeden. Wat nu?">Bloeding</button>
        <button class="chip" type="button" data-fill="Wond is rood en warm. Wanneer huisarts?">Infectie</button>

      <?php else: ?>
        <button class="chip" type="button" data-fill="Ik denk dat ik een hypo heb. Wat zijn signalen?">Hypo</button>
        <button class="chip" type="button" data-fill="Veel dorst en veel plassen. Kan dat hoge suiker zijn?">Hyper</button>
      <?php endif; ?>
    </div>
  </div>

  <script>
    const cb = document.getElementById("chatbox");
    cb.scrollTop = cb.scrollHeight;

    document.querySelectorAll("[data-fill]").forEach(btn => {
      btn.addEventListener("click", () => {
        const input = document.querySelector('input[name="question"]');
        input.value = btn.getAttribute("data-fill");
        input.focus();
      });
      <script>
  function toggleFeedback(){
    const el = document.getElementById("fbMore");
    el.style.display = (el.style.display === "none") ? "block" : "none";
  }

  async function sendFeedback(rating){
    // Deze 4 waarden moet jij vullen vanuit je eigen code (waar je de laatste vraag/antwoord opslaat)
    const session_id = window.SESSION_ID || "";
    const category   = window.LAST_CATEGORY || "";
    const question   = window.LAST_QUESTION || "";
    const answer     = window.LAST_ANSWER || "";
    const comment    = document.getElementById("fbComment")?.value || "";

    const fd = new FormData();
    fd.append("session_id", session_id);
    fd.append("category", category);
    fd.append("question", question);
    fd.append("answer", answer);
    fd.append("rating", rating);
    fd.append("comment", comment);

    const res = await fetch("feedback_save.php", { method:"POST", body: fd });
    const data = await res.json();

    if (data.ok){
      alert("Dankjewel! Feedback opgeslagen.");
      const more = document.getElementById("fbMore");
      if (more) more.style.display = "none";
    } else {
      alert("Feedback opslaan mislukt.");
    }
  }
</script>
    });
  </script>
</body>
</html>