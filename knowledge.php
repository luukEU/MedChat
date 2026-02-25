<?php
// knowledge.php
// Alles wat je snel wilt aanpassen als developer staat hier.

return [
  "categories" => [
    "vallen" => [
      "label" => "Vallen / valpreventie",
      "keywords" => ["gevallen","val","struik","uitgegleden","heup","pols","hoofd","blauwe plek","valpreventie","evenwicht","rollator","botbreuk"],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number", "min"=>0, "max"=>120],
        "head_hit" => ["label"=>"Hoofd geraakt", "type"=>"checkbox"],
        "blood_thinners" => ["label"=>"Bloedverdunners", "type"=>"checkbox"],
        "hip_pain" => ["label"=>"Heup/been pijn of niet kunnen staan", "type"=>"checkbox"],
        "unconscious" => ["label"=>"Buiten bewustzijn / niet aanspreekbaar", "type"=>"checkbox"],
        "confused" => ["label"=>"Verward/duizelig", "type"=>"checkbox"],
        "bleeding" => ["label"=>"Ernstige bloeding / stopt niet", "type"=>"checkbox"],
      ],
    ],

    "medicatie" => [
      "label" => "Medicatie-inname / veiligheid",
      "keywords" => ["medicatie","pil","pillen","tablet","dosis","vergeten","bijsluiter","apotheek","recept","antibiotica","bloedverdunner","paracetamol","ibuprofen"],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number", "min"=>0, "max"=>120],
        "missed_dose" => ["label"=>"Dosis vergeten", "type"=>"checkbox"],
        "double_dose" => ["label"=>"Dubbel genomen / twijfel", "type"=>"checkbox"],
        "side_effects" => ["label"=>"Bijwerkingen/ziek gevoel", "type"=>"checkbox"],
        "many_meds" => ["label"=>"Gebruikt meerdere medicijnen", "type"=>"checkbox"],
      ],
    ],

    "wondzorg" => [
      "label" => "Wondzorg (basis)",
      "keywords" => ["wond","bloeden","snijwond","snee","verband","pleister","infectie","pus","rood","zwelling","hechten","brandwond","schaafwond"],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number", "min"=>0, "max"=>120],
        "bleeding" => ["label"=>"Bloeding stopt niet / veel bloed", "type"=>"checkbox"],
        "deep_wound" => ["label"=>"Diepe wond (randen wijken) / mogelijk hechten", "type"=>"checkbox"],
        "infection_signs" => ["label"=>"Rood/warm/pus/koorts (infectietekenen)", "type"=>"checkbox"],
        "burn" => ["label"=>"Brandwond", "type"=>"checkbox"],
      ],
    ],

    "diabetes" => [
      "label" => "Diabetes (basis)",
      "keywords" => ["diabetes","glucose","suiker","hypo","hyper","insuline","meten","mmol","hba1c","dorst","veel plassen","koolhydraten"],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number", "min"=>0, "max"=>120],
        "weight" => ["label"=>"Gewicht (kg)", "type"=>"number", "min"=>0, "max"=>350],
        "hypo_signs" => ["label"=>"Hypo-signalen (trillen/zweten/verward)", "type"=>"checkbox"],
        "hyper_signs" => ["label"=>"Hyper-signalen (dorst/veel plassen)", "type"=>"checkbox"],
        "insulin" => ["label"=>"Gebruikt insuline", "type"=>"checkbox"],
        "glucose_value" => ["label"=>"Glucosewaarde (optioneel)", "type"=>"number", "min"=>0, "max"=>40],
      ],
    ],
  ],

  // Woorden die altijd “spoed” kunnen zijn (algemeen)
  "red_flags" => [
    "bewusteloos","niet aanspreekbaar","stuipen","hevige pijn","veel bloed",
    "benauwd","borstpijn","ernstig","plots","verward","niet kunnen staan",
    "flauwvallen","bloeding stopt niet"
  ],

  // Labels voor doorverwijzen
  "labels" => [
    "vallen" => "Vallen / valpreventie",
    "medicatie" => "Medicatie",
    "wondzorg" => "Wondzorg",
    "diabetes" => "Diabetes",
  ],
];