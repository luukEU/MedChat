<?php
/**
 * knowledge_nl.php
 * Grote NL trefwoordenlijst (demo/leerproject).
 * Tip: Je kunt dit bestand gewoon vervangen/uitbreiden wanneer je meer woorden wil.
 */
return [
  "categories" => [

    "vallen" => [
      "label" => "Vallen / stoten / bot- of spierletsel",
      "keywords" => [
        // algemeen vallen / uitglijden / struikelen
        "gevallen","geval","val","viel","neergevallen","omgevallen","onderuit","gevallen op",
        "uitgegleden","glad","struikelen","struikel","struikte","uitglijder","uitglijden",
        "evenwicht kwijt","balans kwijt","wankel","instabiel","door de benen gezakt","door benen gezakt",
        // trap / hoogte
        "trap","traptreden","van de trap","van de trappen","trap af","trap op","trap gevallen","trap gevalleen","trappie",
        "van hoogte","van een ladder","ladder","van een stoel","stoel gevallen","van bed gevallen","uit bed gevallen",
        // stoten / klappen
        "gestoten","stoot","knal","klap","hard gevallen","hard op de grond","op de grond gevallen",
        "hoofd gestoten","hoofd geraakt","op hoofd gevallen","bult","blauwe plek","kneuzing","kneuzen","gekneusd",
        // botten / gewrichten
        "breuk","botbreuk","gebroken","breken","pols gebroken","arm gebroken","been gebroken","heup gebroken",
        "enkel","enkel verzwikt","verzwikt","verstapt","knie","knie verdraaid","schouder uit de kom","uit de kom",
        "rug","rugpijn na val","nek","nekpijn","whiplash",
        // mobiliteit
        "kan niet lopen","niet lopen","kan niet staan","niet staan","niet opstaan","niet bewegen","moeilijk lopen","hinkelen",
        "krachtsverlies na val","tintelingen na val",
        // hulpmiddelen
        "rollator","stok","kruk","krukken","wandelstok"
      ],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number"],
        "unconscious" => ["label"=>"(Even) buiten bewustzijn / niet goed aanspreekbaar", "type"=>"checkbox"],
        "head_hit" => ["label"=>"Hoofd geraakt", "type"=>"checkbox"],
        "blood_thinners" => ["label"=>"Bloedverdunners", "type"=>"checkbox"],
        "hip_pain" => ["label"=>"Heup/been pijn of niet kunnen staan/lopen", "type"=>"checkbox"],
        "confused" => ["label"=>"Verward/duizelig", "type"=>"checkbox"],
        "bleeding" => ["label"=>"Ernstige bloeding", "type"=>"checkbox"],
      ],
    ],

    "wondzorg" => [
      "label" => "Wond / bloeding / brandwond / bijten",
      "keywords" => [
        // wonden
        "wond","wondje","open wond","snijwond","gesneden","snee","scheurwond","schaafwond","prikwond","steekwond",
        "splinter","glas in","stekel","doorboord","nagel door","spijker door",
        // bloeding
        "bloeden","bloed","bloedt","bloedend","veel bloed","spuitend bloed","bloeding","bloedverlies","stopt niet",
        "neusbloeding","neus bloedt",
        // hechtingen / randen
        "hechten","gehecht","hechting","randen wijken","gapende wond",
        // infectie
        "ontstoken","ontsteking","infectie","etter","pus","rood","warm","zwelling","dik","kloppend","koorts bij wond",
        // brandwond
        "brandwond","verbrand","verbranding","kokend water","hete pan","oven","stoom","chemische brandwond","zuur op huid",
        // beten
        "beet","gebeten","hondenbeet","kattenbeet","mensenbeet","tekenbeet","teek","teken",
        // ogen
        "iets in mijn oog","in mijn oog","oog beschadigd","oogwond"
      ],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number"],
        "bleeding" => ["label"=>"Bloeding stopt niet / veel bloed", "type"=>"checkbox"],
        "deep_wound" => ["label"=>"Diepe wond / mogelijk hechten", "type"=>"checkbox"],
        "infection_signs" => ["label"=>"Infectietekenen", "type"=>"checkbox"],
        "burn" => ["label"=>"Brandwond", "type"=>"checkbox"],
      ],
    ],

    "medicatie" => [
      "label" => "Medicatie / bijwerkingen / vergeten / dubbele dosis",
      "keywords" => [
        "medicatie","medicijn","medicijnen","pil","pillen","tablet","tabletten","capsule","capsules",
        "dosis","dosering","overdosis","teveel genomen","te veel genomen","dubbel genomen","per ongeluk extra",
        "vergeten","overgeslagen","niet genomen","te laat genomen",
        "bijsluiter","apotheek","recept","voorschrift",
        "bijwerking","bijwerkingen","allergisch op medicijn","uitslag van medicijn",
        "misselijk van medicijn","duizelig van medicijn","slaperig van medicijn",
        // veelvoorkomende middelen (niet compleet)
        "paracetamol","ibuprofen","naproxen","diclofenac",
        "antibiotica","amoxicilline",
        "bloedverdunner","acenocoumarol","fenprocoumon","apixaban","rivaroxaban","dabigatran",
        "oxycodon","morfine","tramadol",
        "antidepressiva","sertraline","citalopram",
        "slaapmiddel","diazepam","lorazepam"
      ],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number"],
        "missed_dose" => ["label"=>"Dosis vergeten", "type"=>"checkbox"],
        "double_dose" => ["label"=>"Dubbel/teveel genomen", "type"=>"checkbox"],
        "side_effects" => ["label"=>"Bijwerkingen/klachten", "type"=>"checkbox"],
        "many_meds" => ["label"=>"Meerdere medicijnen", "type"=>"checkbox"],
      ],
    ],

    "diabetes" => [
      "label" => "Diabetes / suiker / hypo-hyper",
      "keywords" => [
        "diabetes","suikerziekte","glucose","suiker","bloedsuiker","bloedglucose","mmol","meten","sensor",
        "hypo","hypoglycemie","lage suiker","suiker te laag","insuline","spuiten","insulinepen",
        "hyper","hyperglycemie","hoge suiker","suiker te hoog",
        "trillen","zweten","klam","verward","slaperig","honger aanval",
        "veel dorst","dorst","droge mond","veel plassen","moe","misselijk","braken"
      ],
      "fields" => [
        "age" => ["label"=>"Leeftijd", "type"=>"number"],
        "hypo_signs" => ["label"=>"Hypo-signalen", "type"=>"checkbox"],
        "hyper_signs" => ["label"=>"Hyper-signalen", "type"=>"checkbox"],
        "insulin" => ["label"=>"Gebruikt insuline", "type"=>"checkbox"],
        "glucose_value" => ["label"=>"Glucosewaarde", "type"=>"number"],
      ],
    ],

  ],

  /**
   * Rode vlaggen: als deze woorden/zinnen in de tekst staan -> spoedadvies tonen.
   * Let op: dit is een demo-lijst, dus liever te streng dan te laks.
   */
  "red_flags" => [
    // algemene spoed
    "112","spoed","nood","noodgeval","dringend","nu meteen","meteen hulp",
    // bewustzijn/neurologie
    "bewusteloos","niet aanspreekbaar","weggevallen","weggezakt","flauwgevallen","stuipen","toeval","epilepsie aanval",
    "verward","raar praten","spraak problemen","scheve mond","krachtsverlies","verlamming","arm niet","been niet",
    // ademhaling/hart
    "benauwd","kan niet ademen","niet kunnen ademen","geen adem","kortademig","piepen","blauw","blauwe lippen",
    "borstpijn","druk op de borst","hartkloppingen","hartslag heel hoog",
    // bloeding
    "spuitend bloed","ernstige bloeding","veel bloed","bloed stopt niet",
    // ernstige pijn / snel verslechteren
    "hevige pijn","ondraaglijke pijn","plotseling","acuut","ineens erg slecht","snel erger","gaat snel achteruit",
    // taal die mensen vaak typen bij paniek
    "ik ga dood","ga dood","ik sterf","ik denk dat ik dood ga","help ik ga dood","ik voel me sterven"
  ],

  "labels" => [
    "vallen" => "Vallen",
    "medicatie" => "Medicatie",
    "wondzorg" => "Wondzorg",
    "diabetes" => "Diabetes",
  ],
];
