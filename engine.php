<?php
// engine.php

function disclaimer(): string {
  return "Let op: dit is een demo/leerproject en geen medisch advies. Ik kan geen diagnose stellen. Bij spoed: 112. Bij twijfel: huisarts/huisartsenpost.";
}

function guess_category(string $q): string {
  $q = mb_strtolower($q);

  $map = [
    "vallen" => ["gevallen","val","struik","uitgegleden","heup","pols","hoofd","blauwe plek","valpreventie","evenwicht","rollator","botbreuk"],
    "medicatie" => ["medicatie","pil","pillen","tablet","dosis","vergeten","bijsluiter","apotheek","recept","antibiotica","bloedverdunner","paracetamol","ibuprofen"],
    "wondzorg" => ["wond","bloeden","snijwond","snee","verband","pleister","infectie","pus","rood","zwelling","hechten","brandwond","schaafwond"],
    "diabetes" => ["diabetes","glucose","suiker","hypo","hyper","insuline","meten","mmol","hba1c","dorst","veel plassen","koolhydraten"]
  ];

  $scores = ["vallen"=>0,"medicatie"=>0,"wondzorg"=>0,"diabetes"=>0];
  foreach ($map as $cat => $words) {
    foreach ($words as $w) {
      if (str_contains($q, $w)) $scores[$cat]++;
    }
  }

  arsort($scores);
  $bestCat = array_key_first($scores);
  $bestScore = $scores[$bestCat] ?? 0;

  return ($bestScore < 1) ? "onbekend" : $bestCat;
}

function red_flags(string $q): array {
  $q = mb_strtolower($q);
  $flags = [];
  $words = [
    "bewusteloos","niet aanspreekbaar","stuipen","hevige pijn","veel bloed",
    "benauwd","borstpijn","ernstig","plots","verward","niet kunnen staan",
    "flauwvallen","bloeding stopt niet"
  ];
  foreach ($words as $w) {
    if (str_contains($q, $w)) $flags[] = $w;
  }
  return array_values(array_unique($flags));
}

// --- subtopics
function sub_fallen(string $q): string {
  $q = mb_strtolower($q);
  if (str_contains($q,"hoofd")) return "hoofd";
  if (str_contains($q,"heup") || str_contains($q,"niet kunnen staan") || str_contains($q,"niet lopen")) return "heup";
  if (str_contains($q,"bloedverdunner")) return "bloedverdunners";
  if (str_contains($q,"duizelig") || str_contains($q,"verward")) return "duizelig";
  return "algemeen";
}

function sub_meds(string $q): string {
  $q = mb_strtolower($q);
  if (str_contains($q,"vergeten") || str_contains($q,"dosis")) return "vergeten";
  if (str_contains($q,"combin") || str_contains($q,"samen")) return "combineren";
  if (str_contains($q,"bijsluiter")) return "bijsluiter";
  return "algemeen";
}

function sub_wound(string $q): string {
  $q = mb_strtolower($q);
  if (str_contains($q,"bloeden") || str_contains($q,"bloeding")) return "bloeding";
  if (str_contains($q,"rood") || str_contains($q,"warm") || str_contains($q,"pus") || str_contains($q,"infectie")) return "infectie";
  if (str_contains($q,"brandwond")) return "brandwond";
  return "algemeen";
}

function sub_diabetes(string $q): string {
  $q = mb_strtolower($q);
  if (str_contains($q,"hypo") || str_contains($q,"trillen") || str_contains($q,"zweten") || str_contains($q,"verward")) return "hypo";
  if (str_contains($q,"hyper") || str_contains($q,"dorst") || str_contains($q,"veel plassen") || str_contains($q,"misselijk")) return "hyper";
  if (str_contains($q,"meten") || str_contains($q,"mmol") || str_contains($q,"glucose")) return "meten";
  if (str_contains($q,"eten") || str_contains($q,"koolhydraat")) return "voeding";
  return "algemeen";
}

function category_label(string $cat): string {
  return [
    "vallen" => "Vallen / valpreventie",
    "medicatie" => "Medicatie-inname / veiligheid",
    "wondzorg" => "Wondzorg (basis)",
    "diabetes" => "Diabetes (basis)",
  ][$cat] ?? $cat;
}

function generate_answer(string $cat, string $question): string {
  $q = trim($question);
  $guessed = guess_category($q);

  // Past het bij deze categorie?
  if ($guessed !== "onbekend" && $guessed !== $cat) {
    return "Ik denk dat je vraag beter past bij **" . category_label($guessed) . "**.\n"
      . "Tip: ga terug en kies die categorie, dan kan ik gerichter antwoorden.\n\n"
      . "Waarom? Ik herken in je vraag woorden die meer bij dat onderwerp horen.\n\n"
      . disclaimer();
  }

  // Rode vlaggen
  $flags = red_flags($q);
  if (count($flags) > 0) {
    return "Ik zie mogelijk een *spoed-signaal* in je vraag (bijv.: " . implode(", ", $flags) . ").\n"
      . "Ik kan geen diagnose stellen. Als dit echt speelt of je twijfelt: bel 112 of neem direct contact op met de huisarts/huisartsenpost.\n\n"
      . disclaimer();
  }

  // Antwoorden per categorie + subtopic
  switch ($cat) {
    case "vallen": {
      $sub = sub_fallen($q);

      if ($sub === "heup") {
        return "Bij vallen met **heuppijn** of **niet kunnen staan/lopen** is het verstandig om snel medische hulp te regelen.\n"
          . "- Laat iemand zitten/liggen en forceer niet om te lopen.\n"
          . "- Bij heftige pijn of duidelijke beperking: huisarts/112 (afhankelijk van situatie).\n\n"
          . disclaimer();
      }

      if ($sub === "hoofd") {
        return "Bij **hoofd stoten** na een val is het belangrijk om goed te letten op klachten.\n"
          . "- Bij suf worden, verwardheid, braken of erger wordende hoofdpijn: huisarts/112.\n"
          . "- Als het meevalt: iemand laten rusten en extra controleren.\n\n"
          . disclaimer();
      }

      if ($sub === "bloedverdunners") {
        return "Als iemand **bloedverdunners** gebruikt en is gevallen (zeker met hoofdletsel), dan is het slimmer om eerder contact op te nemen met huisarts/huisartsenpost.\n"
          . "Dat is omdat bloedingen soms minder snel zichtbaar zijn.\n\n"
          . disclaimer();
      }

      if ($sub === "duizelig") {
        return "Als iemand na een val **duizelig of verward** is, is dat een signaal om extra voorzichtig te zijn.\n"
          . "- Laat iemand zitten/liggen.\n"
          . "- Neem contact op met huisarts/huisartsenpost bij twijfel.\n\n"
          . disclaimer();
      }

      return "Algemeen bij vallen:\n"
        . "1) Check: aanspreekbaar? hoofd geraakt? veel pijn? kan iemand staan?\n"
        . "2) Bij twijfel of duidelijke klachten: huisarts/112.\n"
        . "3) Denk ook aan preventie: goede schoenen, hulpmiddelen, verlichting, spullen uit de looproute.\n\n"
        . disclaimer();
    }

    case "medicatie": {
      $sub = sub_meds($q);

      if ($sub === "vergeten") {
        return "Als je een **dosis bent vergeten**:\n"
          . "- Neem niet automatisch dubbel.\n"
          . "- Kijk in de bijsluiter of bel de apotheek voor advies.\n"
          . "- Als je je ziek voelt of het gaat om belangrijke medicatie: neem sneller contact op.\n\n"
          . disclaimer();
      }

      if ($sub === "combineren") {
        return "Over **medicijnen combineren** kan ik geen persoonlijk advies geven.\n"
          . "- Sommige combinaties kunnen problemen geven.\n"
          . "- Het veiligste is: apotheek of huisarts bellen (zeker bij meerdere medicijnen).\n\n"
          . disclaimer();
      }

      if ($sub === "bijsluiter") {
        return "Bij vragen over de **bijsluiter**:\n"
          . "- Let op: dosering, waarschuwingen, bijwerkingen, wanneer stoppen/arts bellen.\n"
          . "- Bij twijfel: apotheek is meestal de snelste en beste plek om te checken.\n\n"
          . disclaimer();
      }

      return "Algemeen bij medicatie:\n"
        . "- Gebruik medicatie volgens voorschrift.\n"
        . "- Bij bijwerkingen of twijfel: apotheek/huisarts.\n"
        . "- Bewaar een actueel medicatie-overzicht.\n\n"
        . disclaimer();
    }

    case "wondzorg": {
      $sub = sub_wound($q);

      if ($sub === "bloeding") {
        return "Bij een wond die **blijft bloeden**:\n"
          . "- Druk geven met een schone doek/gaas.\n"
          . "- Hoog houden als dat kan.\n"
          . "- Als het niet stopt of veel is: huisarts/112.\n\n"
          . disclaimer();
      }

      if ($sub === "infectie") {
        return "Tekenen die kunnen passen bij **infectie**:\n"
          . "- Rood/warm, zwelling, pus, toenemende pijn, koorts.\n"
          . "- Dan is het verstandig om contact op te nemen met huisarts.\n\n"
          . disclaimer();
      }

      if ($sub === "brandwond") {
        return "Bij **brandwonden** (basis):\n"
          . "- Koel met lauw stromend water (meestal 10–20 minuten).\n"
          . "- Dek schoon af.\n"
          . "- Bij grotere of ernstige brandwonden: huisarts/112.\n\n"
          . disclaimer();
      }

      return "Basis wondzorg:\n"
        . "- Handen wassen, wond spoelen met lauwwarm water.\n"
        . "- Schoon afdekken met pleister/gaas.\n"
        . "- Bij diepe wond, aanhoudend bloeden of infectietekenen: huisarts.\n\n"
        . disclaimer();
    }

    case "diabetes": {
      $sub = sub_diabetes($q);

      if ($sub === "hypo") {
        return "Dit klinkt als een vraag over een **hypo (lage suiker)**.\n"
          . "- Signalen: trillen, zweten, honger, bleek, verward.\n"
          . "- Als iemand niet goed kan slikken, wegzakt of verward is: **112**.\n"
          . "- Anders: snel iets met suiker en daarna opnieuw checken.\n\n"
          . disclaimer();
      }

      if ($sub === "hyper") {
        return "Dit lijkt meer op **hyper (hoge suiker)**.\n"
          . "- Signalen kunnen zijn: veel dorst, veel plassen, moe, soms misselijk.\n"
          . "- Meet als dat kan.\n"
          . "- Bij ernstige klachten of snelle achteruitgang: huisarts/112.\n\n"
          . disclaimer();
      }

      if ($sub === "meten") {
        return "Over **meten van glucose** (basis):\n"
          . "- Noteer de waarde + tijd + klachten.\n"
          . "- Bij extreem lage/hoge waarden of klachten: contact met zorgverlener.\n\n"
          . disclaimer();
      }

      if ($sub === "voeding") {
        return "Voeding bij diabetes (basis):\n"
          . "- Let op koolhydraten en regelmaat.\n"
          . "- Beweging helpt ook.\n"
          . "- Persoonlijk advies gaat via diëtist/diabetesverpleegkundige.\n\n"
          . disclaimer();
      }

      return "Diabetes (basis):\n"
        . "- Let op hypo- en hyper-signalen.\n"
        . "- Meetwaarden + klachten samen zijn belangrijk.\n"
        . "- Bij twijfel: contact met diabetesverpleegkundige/huisarts.\n\n"
        . disclaimer();
    }

    default:
      return disclaimer();
  }
}