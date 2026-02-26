# MedChat (demo/leerproject)

Kleine PHP-app die werkt als een **triage-chat** op basis van trefwoorden.

> Let op: dit is een demo/leerproject. Geen diagnose. Bij spoed: **112**. Bij twijfel: huisarts/huisartsenpost.

## Wat zit erin?

- **Nederlandse trefwoorden** (uit `knowledge_nl.php`) + **typo-tolerante** herkenning (bijv. "gevalleen").
- **KB-opschoning** bij start: dubbele trefwoorden worden automatisch weggehaald.
- **Minder menu's**: alleen bij onzin/geen herkenning komt er 1 verduidelijkingsvraag.
- **ChatGPT-achtige antwoorden**:
  - korte samenvatting ("Ik lees in je bericht...")
  - triage-niveau: 🟢 / 🟠 / 🔴
  - duidelijke blokken: "Wat je nu kunt doen" + "Wanneer bellen" + "Waarom dit advies"
- **Slimme follow-up vragen**: max. 1–2 vragen, gekozen op basis van de tekst.
- **Leeftijd automatisch** uit tekst: "oma van 82", "82 jaar", "82jr".
- **Multi-categorie** + gewichten: sterkere trefwoorden (zinnen/long keywords) tellen zwaarder.

## Belangrijkste bestanden

- `engine.php` – herkenning, triage logica, antwoord-opbouw
- `knowledge_nl.php` – trefwoorden + labels + velden
- `chat.php` / `save.php` – UI + opslag + follow-up flow

## Tip voor uitbreiden

Wil je het nog minder "vaag" maken?
- Voeg per categorie extra concrete alarm-signalen toe.
- Voeg meer specifieke trefwoorden toe in `knowledge_nl.php`.
