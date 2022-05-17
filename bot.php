<?php 

$botToken = "5109129284:AAHuJ29Co3OZT6Sm7qJWBTn-4lN7efpmJbQ";
$website = "https://api.telegram.org/bot".$botToken;

$update = file_get_contents('php://input');
$update = json_decode($update, TRUE);

$chatId = $update['message']['from']['id'];

//Dichiarazione tastiere
$tastieraTipo = '[{"text":"Cerca Prof"},{"text":"Cerca classe"}]';
$tastieraGiorni = '[{"text":"Lunedì"},{"text":"Martedì"}],[{"text":"Mercoledì"},{"text":"Giovedì"}],[{"text":"Venerdì"}]';
$tastieraOre = '[{"text":"Prima"},{"text":"Seconda"}],[{"text":"Terza"},{"text":"Quarta"}],[{"text":"Quinta"},{"text":"Sesta"}],[{"text":"Settima"},{"text":"Ottava"}]';

//Connessione al DB
$dataBase = new PDO('sqlite:sqlite.db');

//Creo riga per la chat attuale
$dataBase->query("INSERT or ignore INTO richieste(chatid) VALUES($chatId)");


$tastierino='&reply_markup={"keyboard":['.$tastieraOre.'],'.'"resize_keyboard":true}';
sendMessage($chatId, "Benvenuto, cosa voi cercare?", $tastierino);

function sendMessage($chatId,$text,$aggiunte=""){
    $url=$GLOBALS['website']
    ."/sendMessage?chat_id=$chatId&parse_mode=HTML&text="
    .urlencode($text).$aggiunte;
    file_get_contents($url);
    }
?>