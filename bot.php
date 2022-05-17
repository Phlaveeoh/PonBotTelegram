<?php 

$botToken = "5109129284:AAHuJ29Co3OZT6Sm7qJWBTn-4lN7efpmJbQ";
$website = "https://api.telegram.org/bot".$botToken;

$update = file_get_contents('php://input');
$update = json_decode($update, TRUE);

$chatId = $update['message']['from']['id'];
$valore = $update['message']['text'];

//Dichiarazione tastiere
$tastieraTipo = '[{"text":"Cerca Prof"},{"text":"Cerca Classe"}]';
$tastieraGiorni = '[{"text":"Lunedì"},{"text":"Martedì"}],[{"text":"Mercoledì"},{"text":"Giovedì"}],[{"text":"Venerdì"}]';
$tastieraOre = '[{"text":"Prima"},{"text":"Seconda"}],[{"text":"Terza"},{"text":"Quarta"}],[{"text":"Quinta"},{"text":"Sesta"}],[{"text":"Settima"},{"text":"Ottava"}]';

//Connessione al DB
$dataBase = new PDO('sqlite:sqlite.db');

//Creo riga per la chat attuale
$dataBase->query("INSERT or ignore INTO richieste(chatid) VALUES($chatId)");

switch ($valore) {
    case("/start"):
        $tastierino='&reply_markup={"keyboard":['.$tastieraTipo.'],'.'"resize_keyboard":true}';
        sendMessage($chatId, "Benvenuto, cosa voi cercare?", $tastierino);
        break;
    case("Cerca Prof"):
    case("Cerca Classe"):
        $dataBase->query("UPDATE richieste SET tipo='$valore' WHERE chatid=$chatId");
        $tastierino='&reply_markup={"keyboard":['.$tastieraGiorni.'],'.'"resize_keyboard":true}';
        sendMessage($chatId, "Ok ora scrivi il giorno della Settimana", $tastierino);
        break;
    case ("Lunedì"):
    case ("Martedì"):
    case ("Mercoledì"):
    case ("Giovedì"):
    case ("Venerdì"):
        switch ($valore) {
            case ("Lunedì"):
                $giorno = 0;
                break;
            case ("Martedì"):
                $giorno = 1;
                break;
            case ("Mercoledì"):
                $giorno = 2;
                break;
            case ("Giovedì"):
                $giorno = 3;
                break;
            case ("Venerdì"):
                $giorno = 4;
                break;
        }
        $dataBase->query("UPDATE richieste SET giorno=$giorno WHERE chatid=$chatId");
        $tastierino='&reply_markup={"keyboard":['.$tastieraOre.'],'.'"resize_keyboard":true}';
        sendMessage($chatId, "Ok ora scrivi l'ora del giorno", $tastierino);
        break;
}

function sendMessage($chatId,$text,$aggiunte=""){
    $url=$GLOBALS['website']
    ."/sendMessage?chat_id=$chatId&parse_mode=HTML&text="
    .urlencode($text).$aggiunte;
    file_get_contents($url);
    }
?>