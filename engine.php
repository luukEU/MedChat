<?php
// engine.php
$KB = require __DIR__ . "/knowledge.php";

function disclaimer(): string {
  return "Let op: demo/leerproject. Geen diagnose. Bij spoed: 112. Bij twijfel: huisarts/huisartsenpost.";
}

function category_label(string $cat, array $KB): string {
  return $KB["labels"][$cat] ?? $cat;
}

function guess_category(string $q, array $KB): string {
  $q2 = mb_strtolower($q);
  $scores = [];

  foreach ($KB["categories"] as $cat => $info) {
    $scores[$cat] = 0;
    foreach (($info["keywords"] ?? []) as $w) {
      if (str_contains($q2, $w)) $scores[$cat]++;
    }
  }

  arsort($scores);
  $best = array_key_first($scores);
  $bestScore = $scores[$best] ?? 0;
  return ($bestScore < 1) ? "onbekend" : $best;
}

function find_red_flags_in_text(string $q, array $KB): array {
  $q2 = mb_strtolower($q);
  $found = [];
  foreach ($KB["red_flags"] as $w) {
    if (str_contains($q2, $w)) $found[] = $w;
  }
  return array_values(array_unique($found));
}

function boolv($meta, $key): bool {
  return isset($meta[$key]) && ($meta[$key] === true || $meta[$key] === "1" || $meta[$key] === 1);
}
function intv($meta, $key): int {
  return isset($meta[$key]) ? (int)$meta[$key] : 0;
}
function floatv($meta, $key): float {
  return isset($meta[$key]) ? (float)$meta[$key] : 0.0;
}

/**
 * Bepaalt welke follow-up vragen nodig zijn (A-modus).
 * Return: array met items: ['key'=>'age','q'=>'Wat is de leeftijd ongeveer?','type'=>'number|yesno']
 */
function plan_followups(string $cat, string $question, array $meta, array $KB): array {
  $q = mb_strtolower($question);
  $need = [];

  // Als vraag super vaag is (geen keywords) -> vraag eerst om verduidelijking
  $guessed = guess_category($question, $KB);
  if ($guessed === "onbekend" && mb_strlen(trim($question)) < 12) {
    $need[] = ["key"=>"clarify", "q"=>"Kun je iets meer uitleggen wat er precies aan de hand is?", "type"=>"text"];
    return $need;
  }

  if ($cat === "vallen") {
    if (!isset($meta["age"])) $need[] = ["key"=>"age","q"=>"Wat is de leeftijd ongeveer?","type"=>"number"];
    if (!isset($meta["unconscious"])) $need[] = ["key"=>"unconscious","q"=>"Is iemand (even) buiten bewustzijn geweest of niet goed aanspreekbaar? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["head_hit"])) $need[] = ["key"=>"head_hit","q"=>"Is het hoofd geraakt? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["blood_thinners"])) $need[] = ["key"=>"blood_thinners","q"=>"Gebruikt iemand bloedverdunners? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["hip_pain"])) $need[] = ["key"=>"hip_pain","q"=>"Is er heup/been pijn of kan iemand niet staan/lopen? (ja/nee)","type"=>"yesno"];
  }

  if ($cat === "medicatie") {
    if (!isset($meta["age"])) $need[] = ["key"=>"age","q"=>"Wat is de leeftijd ongeveer?","type"=>"number"];
    if (!isset($meta["missed_dose"])) $need[] = ["key"=>"missed_dose","q"=>"Gaat het om een dosis die is vergeten? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["side_effects"])) $need[] = ["key"=>"side_effects","q"=>"Zijn er klachten/bijwerkingen op dit moment? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["many_meds"])) $need[] = ["key"=>"many_meds","q"=>"Gebruikt iemand meerdere medicijnen? (ja/nee)","type"=>"yesno"];
  }

  if ($cat === "wondzorg") {
    if (!isset($meta["age"])) $need[] = ["key"=>"age","q"=>"Wat is de leeftijd ongeveer?","type"=>"number"];
    if (!isset($meta["bleeding"])) $need[] = ["key"=>"bleeding","q"=>"Stopt het bloeden niet of is er veel bloed? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["deep_wound"])) $need[] = ["key"=>"deep_wound","q"=>"Is het een diepe wond (randen wijken) / mogelijk hechten? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["infection_signs"])) $need[] = ["key"=>"infection_signs","q"=>"Zijn er infectietekenen (rood/warm/pus/koorts)? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["burn"])) $need[] = ["key"=>"burn","q"=>"Gaat het om een brandwond? (ja/nee)","type"=>"yesno"];
  }

  if ($cat === "diabetes") {
    if (!isset($meta["age"])) $need[] = ["key"=>"age","q"=>"Wat is de leeftijd ongeveer?","type"=>"number"];
    if (!isset($meta["hypo_signs"])) $need[] = ["key"=>"hypo_signs","q"=>"Zijn er hypo-signalen (trillen/zweten/verward)? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["hyper_signs"])) $need[] = ["key"=>"hyper_signs","q"=>"Zijn er hyper-signalen (veel dorst/veel plassen)? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["insulin"])) $need[] = ["key"=>"insulin","q"=>"Wordt er insuline gebruikt? (ja/nee)","type"=>"yesno"];
    if (!isset($meta["glucose_value"])) $need[] = ["key"=>"glucose_value","q"=>"Als er gemeten is: wat is de glucosewaarde (mmol/L)? (laat leeg als onbekend)","type"=>"number_optional"];
  }

  // Houd het compact: max 4 vragen
  return array_slice($need, 0, 4);
}

function format_followup_message(array $followups): string {
  $lines = [];
  $lines[] = "Ik kan je beter helpen als ik 1–4 dingen weet. Antwoord zo:\n1) ... 2) ... 3) ...";
  $lines[] = "";
  $i = 1;
  foreach ($followups as $f) {
    $lines[] = $i . ") " . $f["q"];
    $i++;
  }
  return implode("\n", $lines);
}

/**
 * Parseert antwoorden op followups uit 1 bericht.
 * Verwacht bijv: "1) ja 2) nee 3) 82"
 */
function parse_followup_answers(string $text, array $followups): array {
  $out = [];

  // Maak een simpele mapping nummer -> waarde
  // We pakken "1) ...." of "1: ...." of "1 ...."
  $pattern = '/(\d+)\s*[\)\:\-]?\s*([^\n\r]+)/u';
  preg_match_all($pattern, $text, $matches, PREG_SET_ORDER);

  $byNum = [];
  foreach ($matches as $m) {
    $n = (int)$m[1];
    $val = trim($m[2]);
    $byNum[$n] = $val;
  }

  // Als iemand gewoon "ja nee 82" typt zonder nummers -> fallback split
  if (count($byNum) === 0) {
    $parts = preg_split('/\s+/u', trim($text));
    for ($i=0; $i<count($parts); $i++) {
      $byNum[$i+1] = $parts[$i];
    }
  }

  // Convert per type
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

    // text fallback
    $out[$key] = $raw;
  }

  return $out;
}

/**
 * Definitieve antwoordgenerator (jouw bestaande logica, maar met meta).
 */
function generate_final_answer(string $cat, string $question, array $meta, array $KB): string {
  $q = trim($question);

  // mismatch check
  $guessed = guess_category($q, $KB);
  if ($guessed !== "onbekend" && $guessed !== $cat) {
    $label = category_label($guessed, $KB);
    return "Ik denk dat je vraag beter past bij **{$label}**.\n"
      . "Tip: ga terug en kies die categorie, dan kan ik gerichter antwoorden.\n\n"
      . disclaimer();
  }

  // red flags text
  $flags = find_red_flags_in_text($q, $KB);
  if (count($flags) > 0) {
    return "Ik zie mogelijk een *spoed-signaal* in je vraag (bijv.: " . implode(", ", $flags) . ").\n"
      . "Bij twijfel of als dit echt zo is: bel 112 of neem direct contact op met huisarts/huisartsenpost.\n\n"
      . disclaimer();
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

      if ($unconscious || $bleeding || $hip || $conf) {
        return "NU DOEN: **regel direct medische hulp (112/huisarts spoed)**.\n"
          . "CHECKLIST:\n- Blijf bij de persoon\n- Laat zitten/liggen\n- Forceer niet om te lopen\n\n"
          . disclaimer();
      }

      if ($head && $thinners) {
        return "NU DOEN: **neem contact op met huisarts/huisartsenpost (zelfde dag)**.\n"
          . "Hoofd + bloedverdunners is een reden om sneller te overleggen.\n\n"
          . disclaimer();
      }

      $extra = ($age >= 75) ? "Omdat de persoon {$age}+ is, is het slim om bij twijfel sneller te overleggen met huisarts.\n\n" : "";

      return "NU DOEN: rust, controleren en bij twijfel huisarts.\n"
        . $extra
        . "CHECKLIST:\n- Aanspreekbaar?\n- Hoofd geraakt?\n- Pijn (heup/pols)?\n- Kan iemand veilig staan/lopen?\n\n"
        . "WANNEER HULP:\n- Bij toenemende klachten, duizeligheid of veel pijn\n\n"
        . disclaimer();
    }

    case "medicatie": {
      $missed = boolv($meta, "missed_dose");
      $double = boolv($meta, "double_dose");
      $side = boolv($meta, "side_effects");
      $many = boolv($meta, "many_meds");

      if ($double) {
        return "NU DOEN: **neem contact op met de apotheek** (en bij klachten huisarts).\n\n"
          . "CHECKLIST:\n- Welke medicatie?\n- Hoeveel en hoe laat?\n- Welke klachten?\n\n"
          . disclaimer();
      }

      if ($missed) {
        $extra = $many ? "Omdat er meerdere medicijnen gebruikt worden, is het extra slim om even te checken bij de apotheek.\n\n" : "";
        return "NU DOEN: **niet automatisch dubbel nemen**.\n"
          . $extra
          . "CHECKLIST:\n- Kijk in bijsluiter of bel apotheek\n- Bij ziek gevoel: huisarts\n\n"
          . disclaimer();
      }

      if ($side) {
        return "Bij **bijwerkingen/ziek gevoel**: overleg met apotheek of huisarts (zeker als het erger wordt).\n\n"
          . disclaimer();
      }

      return "Algemeen medicatie:\n- Volg voorschrift\n- Bij twijfel: apotheek\n- Houd medicatie-overzicht bij\n\n"
        . disclaimer();
    }

    case "wondzorg": {
      $bleeding = boolv($meta, "bleeding");
      $deep = boolv($meta, "deep_wound");
      $infect = boolv($meta, "infection_signs");
      $burn = boolv($meta, "burn");

      if ($bleeding) {
        return "NU DOEN: **druk geven** met schone doek/gaas.\n"
          . "Als het niet stopt of veel is: **huisarts/112**.\n\n"
          . disclaimer();
      }

      if ($burn) {
        return "Brandwond (basis):\n- Koel met lauw stromend water (10–20 min)\n- Dek schoon af\n- Bij groot/ernstig: huisarts/112\n\n"
          . disclaimer();
      }

      if ($deep) {
        return "Bij **diepe wond** (randen wijken / mogelijk hechten): neem contact op met huisarts.\n\n"
          . disclaimer();
      }

      if ($infect) {
        return "Bij **infectietekenen** (rood/warm/pus/koorts): contact met huisarts.\n\n"
          . disclaimer();
      }

      return "Basis wondzorg:\n- Handen wassen\n- Spoelen met lauw water\n- Schoon afdekken\n- Bij twijfel: huisarts\n\n"
        . disclaimer();
    }

    case "diabetes": {
      $age = intv($meta, "age");
      $hypo = boolv($meta, "hypo_signs");
      $hyper = boolv($meta, "hyper_signs");
      $ins = boolv($meta, "insulin");
      $gl = floatv($meta, "glucose_value");

      if ($hypo) {
        return "Dit lijkt op **hypo-signalen**.\n"
          . "NU DOEN:\n- Als iemand wegzakt/niet kan slikken/erg verward: **112**\n- Anders: iets met suiker en opnieuw checken\n\n"
          . disclaimer();
      }

      if ($hyper) {
        return "Dit lijkt op **hyper-signalen**.\n"
          . "NU DOEN:\n- Meet als dat kan\n- Bij ernstig ziek gevoel/achteruitgang: huisarts/112\n\n"
          . disclaimer();
      }

      $extra = "";
      if ($age >= 75) $extra .= "- Omdat de persoon {$age}+ is: bij klachten sneller overleggen.\n";
      if ($ins) $extra .= "- Insulinegebruik: hypo/hyper sneller serieus nemen.\n";
      if ($gl > 0) $extra .= "- Je gaf een waarde door: {$gl} mmol/L. Bij extreme waarden + klachten: contact zorgverlener.\n";
      if ($extra !== "") $extra = "Extra:\n{$extra}\n";

      return "Diabetes (basis):\n"
        . $extra
        . "CHECKLIST:\n- Welke klachten?\n- Is er gemeten?\n- Eten/insuline recent?\n\n"
        . disclaimer();
    }

    default:
      return disclaimer();
  }
}