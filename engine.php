<?php
// engine.php
// Gebruik de grote NL trefwoordenlijst (knowledge_nl.php). knowledge.php blijft als fallback bestaan.
$KB = require __DIR__ . "/knowledge_nl.php";

/** =========================
 *  KB opschonen (dubbele woorden eruit) + consistent maken
 *  ========================= */
function kb_cleanup(array $KB): array {
  // categories keywords
  if (isset($KB["categories"]) && is_array($KB["categories"])) {
    foreach ($KB["categories"] as $cat => $info) {
      if (isset($KB["categories"][$cat]["keywords"]) && is_array($KB["categories"][$cat]["keywords"])) {
        $seen = [];
        $clean = [];
        foreach ($KB["categories"][$cat]["keywords"] as $kw) {
          $k = trim((string)$kw);
          if ($k === "") continue;
          $key = mb_strtolower($k);
          if (isset($seen[$key])) continue;
          $seen[$key] = true;
          $clean[] = $k;
        }
        $KB["categories"][$cat]["keywords"] = $clean;
      }
    }
  }
  // red flags
  if (isset($KB["red_flags"]) && is_array($KB["red_flags"])) {
    $seen = [];
    $clean = [];
    foreach ($KB["red_flags"] as $kw) {
      $k = trim((string)$kw);
      if ($k === "") continue;
      $key = mb_strtolower($k);
      if (isset($seen[$key])) continue;
      $seen[$key] = true;
      $clean[] = $k;
    }
    $KB["red_flags"] = $clean;
  }
  return $KB;
}

$KB = kb_cleanup($KB);

/** =========================
 *  Basis
 *  ========================= */
function disclaimer(): string {
  return "Let op: demo/leerproject. Geen diagnose. Bij spoed: 112. Bij twijfel: huisarts/huisartsenpost.";
}
function category_label(string $cat, array $KB): string {
  return $KB["labels"][$cat] ?? $cat;
}

/** =========================
 *  Tekst-variatie (klinkt minder robot-achtig)
 *  ========================= */
function pick(array $variants): string {
  if (count($variants) === 0) return "";
  return $variants[array_rand($variants)];
}

/** =========================
 *  Normaliseren + fuzzy match (typo-tolerant)
 *  ========================= */
function norm(string $s): string {
  $s = mb_strtolower($s);
  $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s;
  $s = strtolower($s);
  $s = preg_replace('/[^a-z0-9\s]+/u', ' ', $s);
  $s = preg_replace('/\s+/u', ' ', $s);
  return trim($s);
}
function tokens(string $s): array {
  $s = norm($s);
  return $s === "" ? [] : preg_split('/\s+/u', $s);
}
function fuzzy_word_match(string $word, string $kw): bool {
  $word = norm($word);
  $kw   = norm($kw);
  if ($kw === "" || $word === "") return false;
  if ($word === $kw) return true;

  // snelle win: substring (trap in trappen)
  if (str_contains($word, $kw) || str_contains($kw, $word)) return true;

  $len = max(strlen($word), strlen($kw));
  if ($len <= 3) return false; // te kort -> te veel false positives
  $dist = levenshtein($word, $kw);

  if ($len <= 5) return $dist <= 1;
  if ($len <= 8) return $dist <= 2;
  return $dist <= 3;
}
function fuzzy_contains(string $text, string $keyword): bool {
  $t = norm($text);
  $k = norm($keyword);
  if ($k === "" || $t === "") return false;

  // 1) snelpad: substring
  if (str_contains($t, $k)) return true;

  // 2) multi-word keyword: alle delen moeten matchen
  $kParts = preg_split('/\s+/u', $k);
  $tWords = tokens($t);

  foreach ($kParts as $part) {
    $ok = false;
    foreach ($tWords as $w) {
      if (fuzzy_word_match($w, $part)) { $ok = true; break; }
    }
    if (!$ok) return false;
  }
  return true;
}
function contains_any(string $text, array $words): bool {
  foreach ($words as $w) {
    if ($w !== "" && fuzzy_contains($text, $w)) return true;
  }
  return false;
}

/** =========================
 *  Upgrade 1: leeftijd automatisch uit tekst halen
 *  Voorbeelden: "oma van 82", "82 jaar", "82jr", "leeftijd 82"
 *  ========================= */
function extract_age_from_text(string $text): int {
  $t = norm($text);

  // "82 jaar", "82jr", "82 j", "82-jarige"
  if (preg_match('/\b(\d{1,3})\s*(jaar|jr|j)\b/u', $t, $m)) {
    $age = (int)$m[1];
    if ($age >= 0 && $age <= 120) return $age;
  }
  if (preg_match('/\b(\d{1,3})\s*jarige\b/u', $t, $m)) {
    $age = (int)$m[1];
    if ($age >= 0 && $age <= 120) return $age;
  }
  // "van 82"
  if (preg_match('/\bvan\s+(\d{1,3})\b/u', $t, $m)) {
    $age = (int)$m[1];
    if ($age >= 0 && $age <= 120) return $age;
  }
  // "leeftijd 82"
  if (preg_match('/\bleeftijd\s+(\d{1,3})\b/u', $t, $m)) {
    $age = (int)$m[1];
    if ($age >= 0 && $age <= 120) return $age;
  }
  return 0;
}

/** =========================
 *  Upgrade 2 + 3: multi-categorie + gewichten
 *  - guess_categories geeft top N + scores terug
 *  - weegt langere/meer-woord keywords zwaarder
 *  ========================= */
function kw_weight(string $kw): int {
  $k = norm($kw);
  if ($k === "") return 0;
  $w = 1;
  if (str_contains($k, " ")) $w++;          // zinnen/frasen zijn sterker
  if (strlen($k) >= 8) $w++;                // langere woorden zijn sterker
  return min($w, 3);
}

/**
 * Welke trefwoorden matchten (handig voor een "ik lees..." samenvatting).
 */
function matched_keywords_for_cat(string $q, string $cat, array $KB, int $limit = 4): array {
  if (!isset($KB["categories"][$cat])) return [];
  $hits = [];
  foreach (($KB["categories"][$cat]["keywords"] ?? []) as $kw) {
    if ($kw !== "" && fuzzy_contains($q, $kw)) {
      $hits[] = $kw;
      if (count($hits) >= $limit) break;
    }
  }
  return $hits;
}
function guess_categories(string $q, array $KB, int $top = 3): array {
  $scores = [];
  foreach ($KB["categories"] as $cat => $info) {
    $scores[$cat] = 0;
    foreach (($info["keywords"] ?? []) as $kw) {
      if ($kw !== "" && fuzzy_contains($q, $kw)) {
        $scores[$cat] += kw_weight($kw);
      }
    }
  }
  arsort($scores);
  return array_slice($scores, 0, $top, true);
}
function guess_category(string $q, array $KB): string {
  $top = guess_categories($q, $KB, 1);
  $best = array_key_first($top);
  $bestScore = $top[$best] ?? 0;
  return ($bestScore < 1) ? "onbekend" : $best;
}
function is_ambiguous_guess(string $q, array $KB): bool {
  $top = guess_categories($q, $KB, 2);
  $cats = array_keys($top);
  if (count($cats) < 2) return false;
  $s1 = $top[$cats[0]]; $s2 = $top[$cats[1]];
  // twijfel als scores heel dicht bij elkaar liggen (vooral bij lage scores)
  return ($s1 <= 3 && $s2 >= ($s1 - 1));
}

function find_red_flags_in_text(string $q, array $KB): array {
  $found = [];
  foreach ($KB["red_flags"] as $w) {
    if ($w !== "" && fuzzy_contains($q, $w)) $found[] = $w;
  }
  return array_values(array_unique($found));
}

/** =========================
 *  Meta helpers
 *  ========================= */
function boolv($meta, $key): bool {
  return isset($meta[$key]) && ($meta[$key] === true || $meta[$key] === "1" || $meta[$key] === 1);
}
function intv($meta, $key): int {
  return isset($meta[$key]) ? (int)$meta[$key] : 0;
}
function floatv($meta, $key): float {
  return isset($meta[$key]) ? (float)$meta[$key] : 0.0;
}
function keyword_hit_count_for_cat(string $cat, string $question, array $KB): int {
  if (!isset($KB["categories"][$cat])) return 0;
  $count = 0;
  foreach (($KB["categories"][$cat]["keywords"] ?? []) as $kw) {
    if ($kw !== "" && fuzzy_contains($question, $kw)) $count++;
  }
  return $count;
}

/** =========================
 *  “Genoeg detail om direct te antwoorden?”
 *  ========================= */
function question_has_enough_detail(string $cat, string $question, array $KB): bool {
  $severity = [
    "ernstig","hevig","veel","stopt niet","kan niet","niet kunnen",
    "plots","snel erger","flink","pijn","bloed","bewusteloos","verward","benauwd",
    "ik ga dood","ik sterf"
  ];
  $detail = [
    "vallen"    => ["trap","ladder","heup","hoofd","pols","duizelig","bloedverdunner","niet kunnen staan","niet kunnen lopen"],
    "medicatie" => ["vergeten","dosis","dosering","dubbel","teveel","overdosis","bijwerking","misselijk","duizelig","apotheek"],
    "wondzorg"  => ["snijwond","bloeding","stopt niet","pus","rood","warm","zwelling","brandwond","hechten","gapende wond"],
    "diabetes"  => ["hypo","hyper","glucose","mmol","insuline","dorst","veel plassen","trillen","zweten","verward","braken"],
  ];

  if (keyword_hit_count_for_cat($cat, $question, $KB) < 1) return false;
  if (contains_any($question, $severity)) return true;
  if (isset($detail[$cat]) && contains_any($question, $detail[$cat])) return true;

  // leeftijd genoemd helpt ook (bijv. "oma van 82")
  if (extract_age_from_text($question) > 0) return true;

  return false;
}

/** =========================
 *  Followups
 *  - menu alleen bij “onzin”
 *  - leeftijd automatisch vullen als gevonden
 *  ========================= */
function plan_followups(string $cat, string $question, array $meta, array $KB): array {
  $need = [];

  // auto-age
  if (!isset($meta["age"])) {
    $age = extract_age_from_text($question);
    if ($age > 0) $meta["age"] = (string)$age;
  }

  // onzin/geen herkenning -> 1 clarify vraag (geen 1–2–3 menu)
  if ($cat === "onbekend" || guess_category($question, $KB) === "onbekend") {
    return [[
      "key"  => "clarify",
      "q"    => "Ik herken geen medische trefwoorden in je bericht. Kun je kort zeggen wat er aan de hand is (bijv. vallen, wond, medicatie, diabetes) en wat je precies wilt weten?",
      "type" => "text"
    ]];
  }

  // genoeg info -> geen followups
  if (question_has_enough_detail($cat, $question, $KB)) return [];

  // Slimme selectie: max 2 vragen, afhankelijk van wat er al in de tekst staat.
  $ask = function(string $key, string $q, string $type) use (&$need, $meta) {
    if (!isset($meta[$key])) $need[] = ["key"=>$key, "q"=>$q, "type"=>$type];
  };

  if ($cat === "vallen") {
    // Prioriteit op basis van woorden in de vraag
    if (contains_any($question, ["bewusteloos","weggezakt","flauwgevallen"])) $ask("unconscious", "Is iemand (even) buiten bewustzijn geweest of niet goed aanspreekbaar? (ja/nee)", "yesno");
    if (contains_any($question, ["hoofd","bult","klap","gestoten"])) $ask("head_hit", "Is het hoofd geraakt? (ja/nee)", "yesno");
    if (contains_any($question, ["bloedverdunner","acenocoumarol","fenprocoumon","apixaban","rivaroxaban","dabigatran"])) $ask("blood_thinners", "Gebruikt iemand bloedverdunners? (ja/nee)", "yesno");
    if (contains_any($question, ["heup","been","niet lopen","kan niet lopen","niet staan","kan niet staan"])) $ask("hip_pain", "Is er heup/been pijn of kan iemand niet staan/lopen? (ja/nee)", "yesno");
    $ask("age", "Wat is de leeftijd ongeveer?", "number");
  } elseif ($cat === "wondzorg") {
    if (contains_any($question, ["stopt niet","veel bloed","spuitend"])) $ask("bleeding", "Stopt het bloeden niet of is er veel bloed? (ja/nee)", "yesno");
    if (contains_any($question, ["gapende","randen","hechten","diep"])) $ask("deep_wound", "Is het een diepe wond (randen wijken) / mogelijk hechten? (ja/nee)", "yesno");
    if (contains_any($question, ["brandwond","verbrand","kokend","stoom"])) $ask("burn", "Gaat het om een brandwond? (ja/nee)", "yesno");
    $ask("infection_signs", "Zijn er infectietekenen (rood/warm/pus/koorts)? (ja/nee)", "yesno");
    $ask("age", "Wat is de leeftijd ongeveer?", "number");
  } elseif ($cat === "medicatie") {
    if (contains_any($question, ["dubbel","te veel","teveel","overdosis","extra"])) $ask("double_dose", "Is er per ongeluk te veel of dubbel ingenomen? (ja/nee)", "yesno");
    if (contains_any($question, ["vergeten","overgeslagen","niet genomen","te laat"])) $ask("missed_dose", "Gaat het om een dosis die is vergeten? (ja/nee)", "yesno");
    $ask("side_effects", "Zijn er klachten/bijwerkingen op dit moment? (ja/nee)", "yesno");
    $ask("many_meds", "Gebruikt iemand meerdere medicijnen? (ja/nee)", "yesno");
    $ask("age", "Wat is de leeftijd ongeveer?", "number");
  } elseif ($cat === "diabetes") {
    if (contains_any($question, ["trillen","zweten","verward","wegzakken"])) $ask("hypo_signs", "Zijn er hypo-signalen (trillen/zweten/verward)? (ja/nee)", "yesno");
    if (contains_any($question, ["dorst","veel plassen","droge mond","misselijk","braken"])) $ask("hyper_signs", "Zijn er hyper-signalen (veel dorst/veel plassen)? (ja/nee)", "yesno");
    $ask("insulin", "Wordt er insuline gebruikt? (ja/nee)", "yesno");
    $ask("glucose_value", "Als er gemeten is: wat is de glucosewaarde (mmol/L)? (laat leeg als onbekend)", "number_optional");
    $ask("age", "Wat is de leeftijd ongeveer?", "number");
  }

  return array_slice($need, 0, 2);
}

function format_followup_message(array $followups): string {
  // 1 clarify -> geen menu
  if (count($followups) === 1 && ($followups[0]["key"] ?? "") === "clarify") {
    return $followups[0]["q"];
  }

  // 1 gewone vraag -> ook geen menu
  if (count($followups) === 1) {
    return $followups[0]["q"];
  }

  $lines = [];
  $lines[] = "Ik kan je beter helpen met 2 korte dingen. Antwoord bijvoorbeeld zo:\n1) ... 2) ...";
  $lines[] = "";
  $i = 1;
  foreach ($followups as $f) {
    $lines[] = $i . ") " . $f["q"];
    $i++;
  }
  return implode("\n", $lines);
}

/** =========================
 *  Parse followups
 *  ========================= */
function parse_followup_answers(string $text, array $followups): array {
  $out = [];

  $pattern = '/(\d+)\s*[\)\:\-]?\s*([^\n\r]+)/u';
  preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

  $byNum = [];
  foreach ($matches as $m) {
    $byNum[(int)$m[1]] = trim($m[2]);
  }

  if (count($byNum) === 0) {
    $parts = preg_split('/\s+/u', trim($text));
    for ($i=0; $i<count($parts); $i++) $byNum[$i+1] = $parts[$i];
  }

  for ($i=0; $i<count($followups); $i++) {
    $f = $followups[$i];
    $num = $i + 1;
    $raw = $byNum[$num] ?? "";

    $key = $f["key"];
    $type = $f["type"];

    if ($key === "clarify") {
      if ($raw !== "") $out["clarify"] = $raw;
      continue;
    }

    if ($type === "yesno") {
      $r = mb_strtolower($raw);
      $out[$key] = (str_contains($r, "ja") || $r === "1" || $r === "true") ? "1" : "0";
      continue;
    }

    if ($type === "number") {
      $out[$key] = (string)intval(preg_replace('/[^\d]/u', '', $raw));
      continue;
    }

    if ($type === "number_optional") {
      $digits = preg_replace('/[^\d\.]/u', '', $raw);
      $out[$key] = ($digits === "") ? "" : (string)floatval($digits);
      continue;
    }

    $out[$key] = $raw;
  }

  return $out;
}

/** =========================
 *  ChatGPT-achtige antwoordopbouw
 *  - korte samenvatting ("ik lees...")
 *  - duidelijk triage-niveau (groen/oranje/rood)
 *  - NU DOEN / WANNEER BELLEN / WAAROM
 *  ========================= */

function readback_line(string $q, string $cat, array $meta, array $KB): string {
  $hits = matched_keywords_for_cat($q, $cat, $KB, 4);
  $bits = [];
  if (count($hits) > 0) $bits[] = implode(", ", $hits);
  $age = intv($meta, "age");
  if ($age > 0) $bits[] = "leeftijd ~{$age}";
  if (count($bits) === 0) return "";
  return "Ik lees in je bericht: **" . implode(" · ", $bits) . "**.";
}

/**
 * Triage: GREEN (thuis), AMBER (vandaag overleggen), RED (spoed/112).
 * Geeft ook een korte reden terug (waarom).
 */
function triage_assess(string $cat, string $q, array $meta, array $KB): array {
  // red flags in tekst hebben altijd voorrang
  $flags = find_red_flags_in_text($q, $KB);
  if (count($flags) > 0) {
    return ["level"=>"RED", "why"=>"Er staan woorden in je bericht die kunnen passen bij spoed (" . implode(", ", $flags) . ").", "flags"=>$flags];
  }

  $age = intv($meta, "age");

  if ($cat === "vallen") {
    $unconscious = boolv($meta, "unconscious");
    $bleeding = boolv($meta, "bleeding");
    $hip = boolv($meta, "hip_pain");
    $conf = boolv($meta, "confused");
    $head = boolv($meta, "head_hit");
    $thinners = boolv($meta, "blood_thinners");

    if ($unconscious || $bleeding || $hip || $conf) {
      return ["level"=>"RED", "why"=>"Bij bewustzijnsverlies, veel bloed, niet kunnen staan/lopen of verwardheid wil je direct medische hulp.", "flags"=>[]];
    }
    if ($head && $thinners) {
      return ["level"=>"AMBER", "why"=>"Hoofdletsel + bloedverdunners geeft meer risico op een bloeding, daarom dezelfde dag overleggen.", "flags"=>[]];
    }
    if ($age >= 75 && ($head || contains_any($q, ["trap","ladder","hard gevallen"])) ) {
      return ["level"=>"AMBER", "why"=>"Bij 75+ is de kans op complicaties na een val hoger; bij twijfel liever dezelfde dag overleggen.", "flags"=>[]];
    }
    return ["level"=>"GREEN", "why"=>"Op basis van wat je nu beschrijft zijn er geen duidelijke spoedsignalen.", "flags"=>[]];
  }

  if ($cat === "wondzorg") {
    $bleeding = boolv($meta, "bleeding");
    $deep = boolv($meta, "deep_wound");
    $infect = boolv($meta, "infection_signs");
    $burn = boolv($meta, "burn");
    if ($bleeding) {
      return ["level"=>"RED", "why"=>"Als een bloeding niet stopt of er veel bloed is, moet je snel handelen.", "flags"=>[]];
    }
    if ($burn && contains_any($q, ["groot","gezicht","genitali","chemisch","elektr" ])) {
      return ["level"=>"AMBER", "why"=>"Bij grotere/risicovolle brandwonden is dezelfde dag beoordeling verstandig.", "flags"=>[]];
    }
    if ($deep || $infect) {
      return ["level"=>"AMBER", "why"=>"Diepe wonden en infectietekenen moeten vaak door een arts beoordeeld worden.", "flags"=>[]];
    }
    return ["level"=>"GREEN", "why"=>"Lijkt op basis wondzorg; bij verslechtering alsnog overleggen.", "flags"=>[]];
  }

  if ($cat === "medicatie") {
    $double = boolv($meta, "double_dose");
    $side = boolv($meta, "side_effects");
    if ($double) {
      return ["level"=>"AMBER", "why"=>"Bij mogelijk te veel ingenomen medicatie is het verstandig dezelfde dag de apotheek te bellen.", "flags"=>[]];
    }
    if ($side) {
      return ["level"=>"AMBER", "why"=>"Bij klachten/bijwerkingen is overleg met apotheek of huisarts vaak nodig.", "flags"=>[]];
    }
    return ["level"=>"GREEN", "why"=>"Bij medicatievragen zonder klachten kun je meestal rustig checken bij de apotheek/bijsluiter.", "flags"=>[]];
  }

  if ($cat === "diabetes") {
    $hypo = boolv($meta, "hypo_signs");
    $hyper = boolv($meta, "hyper_signs");
    if ($hypo) {
      return ["level"=>"AMBER", "why"=>"Hypo-klachten kunnen snel verergeren; meet en onderneem actie.", "flags"=>[]];
    }
    if ($hyper) {
      return ["level"=>"AMBER", "why"=>"Hyper-klachten vragen om meten en zo nodig overleg.", "flags"=>[]];
    }
    return ["level"=>"GREEN", "why"=>"Zonder duidelijke hypo/hyper-klachten kun je eerst meten en rustig vervolgstappen bepalen.", "flags"=>[]];
  }

  return ["level"=>"GREEN", "why"=>"Geen duidelijke spoedsignalen herkend.", "flags"=>[]];
}

function level_label(string $level): string {
  if ($level === "RED") return "🔴 **SPOED**";
  if ($level === "AMBER") return "🟠 **VANDAAG OVERLEGGEN**";
  return "🟢 **THUIS CHECKEN**";
}

function format_blocks(string $readback, string $level, array $doNow, array $callNow, string $why, string $extra = ""): string {
  $parts = [];
  if ($readback !== "") $parts[] = $readback;
  $parts[] = level_label($level);

  if (count($doNow) > 0) {
    $parts[] = "**Wat je nu kunt doen**\n- " . implode("\n- ", $doNow);
  }
  if (count($callNow) > 0) {
    $parts[] = "**Wanneer bellen / hulp zoeken**\n- " . implode("\n- ", $callNow);
  }
  if ($why !== "") {
    $parts[] = "**Waarom dit advies**\n" . $why;
  }
  if ($extra !== "") {
    $parts[] = $extra;
  }
  $parts[] = disclaimer();
  return implode("\n\n", $parts);
}

/** =========================
 *  Antwoordgenerator
 *  Upgrade 2: meerdere categorieën / verkeerde keuze -> automatisch switchen
 *  ========================= */
function generate_final_answer(string $cat, string $question, array $meta, array $KB): string {
  $q = trim($question);

  // auto-age ook hier (handig als iemand geen followups invult)
  if (!isset($meta["age"])) {
    $age = extract_age_from_text($q);
    if ($age > 0) $meta["age"] = (string)$age;
  }

  // Ambigu? 1 korte vraag i.p.v. menu
  if (is_ambiguous_guess($q, $KB)) {
    $top = guess_categories($q, $KB, 2);
    $cats = array_keys($top);
    $l1 = category_label($cats[0], $KB);
    $l2 = category_label($cats[1], $KB);
    return "Ik twijfel tussen **{$l1}** en **{$l2}**.\n"
      . "Gaat het vooral om *vallen/stoten*, *een wond/bloeding*, *medicatie* of *diabetes/suiker*?\n\n"
      . disclaimer();
  }

  // Als gebruiker verkeerde categorie koos -> automatisch switchen naar beste match
  $guessed = guess_category($q, $KB);
  if ($guessed !== "onbekend" && $guessed !== $cat) {
    $cat = $guessed;
  }

  // ChatGPT-achtige readback + triage
  $readback = readback_line($q, $cat, $meta, $KB);
  $triage = triage_assess($cat, $q, $meta, $KB);
  $level = $triage["level"] ?? "GREEN";
  $why = $triage["why"] ?? "";

  // Als er echte red-flag woorden in de tekst staan: geef altijd een generiek spoedblok terug.
  if ($level === "RED" && !empty($triage["flags"])) {
    $doNow = [
      "Bel **112** als dit nú speelt of als iemand snel achteruit gaat.",
      "Blijf bij de persoon en laat zitten/liggen.",
      "Als het veilig is: geef kort door wat er gebeurt en welke klachten je ziet."
    ];
    $call = [
      "Twijfel je? Neem contact op met huisarts/huisartsenpost.",
    ];
    return format_blocks($readback, "RED", $doNow, $call, $why);
  }

  switch ($cat) {
    case "vallen": {
      $age = intv($meta, "age");
      $unconscious = boolv($meta, "unconscious");
      $bleeding = boolv($meta, "bleeding");
      $hip = boolv($meta, "hip_pain");
      $conf = boolv($meta, "confused");
      $head = boolv($meta, "head_hit");
      $thinners = boolv($meta, "blood_thinners");

      $doNow = [];
      $call = [];

      // Variatie in toon, maar professioneel
      $doNow[] = pick(["Blijf rustig en check of iemand goed aanspreekbaar is.", "Controleer of iemand goed aanspreekbaar is en blijf erbij."]);
      $doNow[] = "Laat de persoon zitten/liggen en forceer niet om te lopen.";
      $doNow[] = "Koel een bult (10–15 min) en kijk elk uur even hoe het gaat.";

      // Concrete alarmsignalen
      $call[] = "Bel **112** bij bewusteloosheid, niet aanspreekbaar, ernstige bloeding, of plots krachtsverlies.";
      $call[] = "Bel huisarts/huisartsenpost bij toenemende hoofdpijn, herhaald braken, sufheid, verwardheid, of als iemand niet kan staan/lopen.";
      if ($head && $thinners) $call[] = "Hoofd geraakt + bloedverdunners: **zelfde dag** huisarts/huisartsenpost.";
      if ($age >= 75) $call[] = "Bij 75+ liever sneller overleggen als je het niet vertrouwt.";

      // Maak "RED" extra duidelijk
      if ($level === "RED") {
        $doNow = ["**Bel direct 112 / huisarts spoed.**", "Blijf bij de persoon en laat zitten/liggen.", "Geef geen eten/drinken als iemand suf of misselijk is."];
      }

      return format_blocks($readback, $level, $doNow, $call, $why);
    }

    case "medicatie": {
      $missed = boolv($meta, "missed_dose");
      $double = boolv($meta, "double_dose");
      $side = boolv($meta, "side_effects");
      $many = boolv($meta, "many_meds");

      $doNow = [];
      $call = [];

      if ($double) {
        $doNow[] = "Neem **contact op met de apotheek** en houd verpakking/bijsluiter erbij.";
        $doNow[] = "Noteer: naam medicijn, hoeveelheid, tijdstip.";
        $call[]  = "Bij klachten (duizelig, suf, benauwd, flauwvallen): huisarts/huisartsenpost. Bij ernstige klachten: 112.";
      } elseif ($missed) {
        $doNow[] = "Neem **niet automatisch dubbel** om in te halen.";
        $doNow[] = "Check de bijsluiter of bel de apotheek voor het juiste inhaal-advies.";
        if ($many) $doNow[] = "Omdat er meerdere medicijnen gebruikt worden: liever even bellen met de apotheek.";
        $call[] = "Bij ziek gevoel of als het om belangrijke medicatie gaat: huisarts/apotheek.";
      } elseif ($side) {
        $doNow[] = "Stop niet zomaar zelf (tenzij bijsluiter dat zegt).";
        $doNow[] = "Bel apotheek/huisarts als de klacht nieuw, heftig of toenemend is.";
        $call[] = "Bij benauwdheid, zwelling gezicht/keel, flauwvallen: 112.";
      } else {
        $doNow[] = "Check het medicijnschema/etiket en de bijsluiter.";
        $doNow[] = "Bij twijfel: bel de apotheek (meestal het snelst en het meest passend).";
        $call[] = "Bij nieuwe of verergerende klachten: huisarts.";
      }

      return format_blocks($readback, $level, $doNow, $call, $why);
    }

    case "wondzorg": {
      $bleeding = boolv($meta, "bleeding");
      $deep = boolv($meta, "deep_wound");
      $infect = boolv($meta, "infection_signs");
      $burn = boolv($meta, "burn");

      $doNow = [];
      $call = [];

      if ($bleeding) {
        $doNow[] = "Geef **stevige druk** met schone doek/gaas (minimaal 10 minuten).";
        $doNow[] = "Houd het lichaamsdeel zo mogelijk omhoog.";
        $call[]  = "Als het niet stopt, spuitend bloed, of iemand wordt duizelig/bleek: huisarts/112.";
      } elseif ($burn) {
        $doNow[] = "Koel met lauw stromend water **10–20 min** (geen ijs).";
        $doNow[] = "Dek schoon/losjes af (niet smeren met zalf/boter).";
        $call[]  = "Bel huisarts/hap bij grote blaren, gezicht/handen/genitaliën, chemische brandwond, of veel pijn.";
      } elseif ($deep) {
        $doNow[] = "Spoel schoon met lauw water en dek af met steriel gaas/pleister.";
        $doNow[] = "Laat beoordelen: randen wijken/gapende wond = mogelijk hechten.";
        $call[]  = "Neem contact op met huisarts (liefst dezelfde dag).";
      } elseif ($infect) {
        $doNow[] = "Houd de wond schoon en droog, en raak zo min mogelijk aan.";
        $doNow[] = "Let op: roodheid die uitbreidt, warmte, pus, koorts, kloppende pijn.";
        $call[]  = "Bij infectietekenen: huisarts.";
      } else {
        $doNow[] = "Was je handen, spoel de wond met lauw water.";
        $doNow[] = "Dek schoon af en wissel verband/pleister dagelijks.";
        $call[]  = "Bel huisarts bij toenemende roodheid/warmte/pus/koorts of als het niet vertrouwt.";
      }

      return format_blocks($readback, $level, $doNow, $call, $why);
    }

    case "diabetes": {
      $age = intv($meta, "age");
      $hypo = boolv($meta, "hypo_signs");
      $hyper = boolv($meta, "hyper_signs");
      $ins = boolv($meta, "insulin");
      $gl = floatv($meta, "glucose_value");


      $doNow = [];
      $call = [];

      if ($hypo) {
        $doNow[] = "Als iemand kan slikken: geef snel suiker (bv. druivensuiker/zoete drank) en meet opnieuw.";
        $doNow[] = "Blijf erbij en kijk of iemand opknapt.";
        $call[]  = "Als iemand wegzakt, niet kan slikken of erg verward is: **112**.";
      } elseif ($hyper) {
        $doNow[] = "Meet de glucose als dat kan.";
        $doNow[] = "Drink water en volg het persoonlijke diabetesplan (als dat er is).";
        $call[]  = "Bij ernstig ziek gevoel, sufheid, snelle achteruitgang of herhaald braken: huisarts/112.";
      } else {
        $doNow[] = "Meet de glucose als dat kan en noteer de waarde.";
        $doNow[] = "Let op klachten (dorst, veel plassen, trillen, zweten, verwardheid).";
        $call[]  = "Bij afwijkende waarde + klachten: overleg met zorgverlener/huisarts.";
      }

      $extra = [];
      if ($age >= 75) $extra[] = "Bij {$age}+ liever sneller overleggen als je het niet vertrouwt.";
      if ($ins) $extra[] = "Insulinegebruik: hypo/hyper sneller serieus nemen.";
      if ($gl > 0) $extra[] = "Je gaf een waarde door: {$gl} mmol/L.";
      $extraText = (count($extra) > 0) ? "**Extra**\n- " . implode("\n- ", $extra) : "";

      return format_blocks($readback, $level, $doNow, $call, $why, $extraText);
    }

    default:
      return disclaimer();
  }
}
