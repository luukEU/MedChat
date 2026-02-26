<!doctype html>
<html lang="nl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MedChat Demo</title>
  <link rel="stylesheet" href="assets/style.css">
  <script defer src="assets/app.js"></script>
</head>
<body>
  <div class="container">
    <header class="header">
      <div>
        <h1>MedChat Demo</h1>
        <p class="subtitle">Kies een onderwerp en stel je vraag.</p>
      </div>
      <div class="row">
        <a class="btn ghost" href="feedback.php">📝 Feedback</a>
        <button class="btn ghost" id="themeToggle" type="button" aria-label="Toggle dark mode">
          🌙 Dark mode
        </button>
      </div>
    </header>

    <div class="notice">
      <b>Let op:</b> Dit is een demo/leerproject en geen medisch advies. Geen diagnose.
      Bij spoed of ernstige klachten: <b>112</b>. Bij twijfel: huisarts/huisartsenpost.
      Gebruik geen echte persoonsgegevens.
    </div>

    <h2 class="sectionTitle">Onderwerpen</h2>

    <div class="grid">
      <a class="card" href="chat.php?cat=vallen">
        <div class="tag">Zorg & veiligheid</div>
        <h3>Vallen / valpreventie</h3>
        <p>Valrisico, wat nu doen, wanneer hulp inschakelen.</p>
      </a>

      <a class="card" href="chat.php?cat=medicatie">
        <div class="tag">Medicatie</div>
        <h3>Medicatie-inname</h3>
        <p>Vergeten dosis, veiligheid, wanneer apotheek/huisarts.</p>
      </a>

      <a class="card" href="chat.php?cat=wondzorg">
        <div class="tag">EHBO basis</div>
        <h3>Wondzorg (basis)</h3>
        <p>Snijwond, bloeding, schoonmaken, wanneer naar huisarts.</p>
      </a>

      <a class="card" href="chat.php?cat=diabetes">
        <div class="tag">Chronisch</div>
        <h3>Diabetes (basis)</h3>
        <p>Hypo/hyper signalen, meten, algemene basisinfo.</p>
      </a>
    </div>

    <footer class="footer">
      <span>© MedChat Demo – Luuk</span>
      <span class="muted">Lokaal draaien in WAMP</span>
    </footer>
  </div>
</body>
</html>