<?php
//$botToken = "5109129284:AAHuJ29Co3OZT6Sm7qJWBTn-4lN7efpmJbQ";
//https://api.telegram.org/bot5499271060:AAG_4NucqqGHkhScfczi6TpcFw7W-g0cq_Q/setwebhook?url=https://r.aspix.it/bot/e/bot.php
$botToken="5499271060:AAG_4NucqqGHkhScfczi6TpcFw7W-g0cq_Q";
$website = "https://api.telegram.org/bot" . $botToken;

$update = file_get_contents('php://input');
file_put_contents("debug.txt", $update);
$update = json_decode($update, TRUE);

if ($update == NULL || !isset($update['message'])) {
    die();
}
$chatId = $update['message']['from']['id'];
$valore = $update['message']['text'];


//Dichiarazione tastiere
$tastieraTipo = '[{"text":"Cerca Prof"},{"text":"Cerca Classe"}]';
$tastieraGiorni = '[{"text":"Lunedì"},{"text":"Martedì"}],[{"text":"Mercoledì"},{"text":"Giovedì"}],[{"text":"Venerdì"}]';
$tastieraOreProf = '[{"text":"Prima"},{"text":"Seconda"}],[{"text":"Terza"},{"text":"Quarta"}],[{"text":"Quinta"},{"text":"Sesta"}],[{"text":"Settima"},{"text":"Ottava"}],[{"text":"Orario Completo"}]';
$tastieraOreClasse = '[{"text":"Prima"},{"text":"Seconda"}],[{"text":"Terza"},{"text":"Quarta"}],[{"text":"Quinta"},{"text":"Sesta"}],[{"text":"Settima"},{"text":"Ottava"}]';
//Connessione al DB
$dataBase = new PDO('sqlite:sqlite.db');

//Creo riga per la chat attuale
$dataBase->query("INSERT or ignore INTO richieste(chatid) VALUES($chatId)");

//Corpo del bot e gestione messaggi
switch ($valore) {
    case ("/start"):
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraTipo . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Benvenuto, cosa vuoi cercare?", $tastierino);
        break;

    case ("/help"):
    case ("help"):
    case ("aiuto"):
    case ("Aiuto"):
        sendMessage($chatId, "Benvenuto, sono <b>il bot proFinder sviluppato dall'indirizzo informatico dell' IISCG di Gubbio</b> ed ora ti spiegherò cosa posso fare.\n" .
            "Una volta avviato posso aiutarti a ritrovare una classe o un professore in giro per la scuola!\n" .
            "Utilizzarmi è molto semplice, basta seguire le istruzioni che ti dirò mano a mano ed ogni tuo dubbio sarà fugato in men che non si dica!\n" .
            "\n" .
            "<b>COMANDI DISPONIBILI:</b>\n" .
            "<b>/help</b>: Visualizza questo messaggio.\n" .
            "<b>/delete</b>: Elimina tutti i parametri di ricerca che hai inserito.");
        break;

    case ("/delete"):
        $dataBase->query("DELETE FROM richieste WHERE chatid=$chatId");
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraTipo . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Eliminati tutti i parametri di ricerca precedenti \n" .
            "Cosa vuoi cercare?", $tastierino);
        break;

    case ("Cerca Prof"):
    case ("Cerca Classe"):
        $dataBase->query("UPDATE richieste SET tipo='$valore' WHERE chatid=$chatId");

        $tastierino = '&reply_markup={"keyboard":[' . $tastieraGiorni . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Scrivi il giorno della settimana:", $tastierino);
        break;

    case ("Lunedì"):
    case ("Martedì"):
    case ("Mercoledì"):
    case ("Giovedì"):
    case ("Venerdì"):
        $risultato = $dataBase->query("SELECT tipo FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        $rimuovi='&reply_markup={"remove_keyboard":true}';

        if ($row["tipo"] == "Cerca Prof") {
           
         $tastieraOre=$tastieraOreProf;
        } else {
            $tastieraOre=$tastieraOreClasse;
        }
        $giorno = convertiGiorno($valore);
        $dataBase->query("UPDATE richieste SET giorno=$giorno WHERE chatid=$chatId");
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraOre . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Ok ora scrivi l'ora del giorno:", $tastierino);
        break;

    case ("Prima"):
    case ("Seconda"):
    case ("Terza"):
    case ("Quarta"):
    case ("Quinta"):
    case ("Sesta"):
    case ("Settima"):
    case ("Ottava"):
    case ("Orario Completo");
        $ora = convertiOre($valore);
        $dataBase->query("UPDATE richieste SET ora=$ora WHERE chatid=$chatId");
        $risultato = $dataBase->query("SELECT tipo FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        $rimuovi='&reply_markup={"remove_keyboard":true}';

        if ($row["tipo"] == "Cerca Prof") {
           
            sendMessage($chatId, "Scrivi il nome del prof che vuoi cercare:",$rimuovi);
        } else {
            sendMessage($chatId, "Scrivi la classe che vuoi cercare:",$rimuovi);
        }
        break;

    default:
        $profClasse = $valore;
        $risultato = $dataBase->query("SELECT tipo FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        if ($row["tipo"] == "Cerca Prof") {
            $dataBase->query("UPDATE richieste SET prof='$profClasse' WHERE chatid=$chatId");
        } else {
            $dataBase->query("UPDATE richieste SET classe='$profClasse' WHERE chatid=$chatId");
        }

        $risultato = $dataBase->query("SELECT * FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraTipo . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, ricerca($row), $tastierino);
        break;
}

//FUNZIONE CHE INVIA MESSAGGIO
function sendMessage($chatId, $text, $aggiunte = "")
{
    $url = $GLOBALS['website']
        . "/sendMessage?chat_id=$chatId&parse_mode=HTML&text="
        . urlencode($text) . $aggiunte;
    file_get_contents($url);
}

//FUNZIONe CHE CONVERTE GIORNO IN NUMERO
function convertiGiorno($giorno)
{
    switch ($giorno) {
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
    return $giorno;
}

//FUNZIONE CHE CONVERTE ORA IN NUMERO
function convertiOre($ora)
{
    switch ($ora) {
        case ("Prima"):
            $ora = 0;
            break;
        case ("Seconda"):
            $ora = 1;
            break;
        case ("Terza"):
            $ora = 2;
            break;
        case ("Quarta"):
            $ora = 3;
            break;
        case ("Quinta"):
            $ora = 4;
            break;
        case ("Sesta"):
            $ora = 5;
            break;
        case ("Settima"):
            $ora = 7;
            break;
        case ("Ottava"):
            $ora = 8;
            break;
        case ("Orario Completo"):
            $ora=9;
            break;
    }
    return $ora;
}

//FUNZIONE DI RICERCA
function ricerca($row)
{
    if (($file = fopen("orario.csv", "r")) !== FALSE) {
        while (($data = fgetcsv($file, 1000, ";")) !== FALSE) {
            $data[0] = strtolower($data[0]);
            $data[3] = strtolower($data[3]);
            $data[4] = strtolower($data[4]);
            $fileLetto[] = $data;
        }
        fclose($file);
    }
    //INIZIO ALGORITMO RICERCA

    //Prendo i parametri di ricerca
    $giorni = $row["giorno"];
    $ore = $row["ora"];
    $docenteInput = strtolower($row["prof"]);
    $classi = strtolower($row["classe"]);
    $tipo = $row["tipo"];
    $rigaTrovata = false;

    if (($ore == 8 || $ore == 7) && $giorni != 0) {
        error_log("//e// errore rilevato");
        $tastierino = '&reply_markup={"keyboard":[' . $GLOBALS['tastieraTipo'] . '],' . '"resize_keyboard":true}';
       // sendMessage($GLOBALS['chatId'], "Il rientro c'è solo il Lunedì, rifai la richiesta", $tastierino);
        return "Il rientro c'è solo il Lunedì, rifai la richiesta";
    }

    //Correggo eventuali boiate scritte dall'utente
    //STRPOS()
    foreach ($fileLetto as $riga) {
        if ((strpos($riga[0], $docenteInput) !== false)) {
            $docenteInput = $riga[0];
        }
    }
    //LEVENSHTEIN
    $minimo = -1;
    foreach ($fileLetto as $riga) {
        $lev = levenshtein($docenteInput, $riga[0]);

        if ($lev == 0) {
            $docente = $riga[0];
            $minimo = 0;
            break;
        }
        if ($lev <= $minimo || $minimo < 0) {
            $docente = $riga[0];
            $minimo = $lev;
        }
    }

    if ($tipo == "Cerca Prof") {
        $risultato = "";
        foreach ($fileLetto as $riga) {
            if ($ore==9){
                if ($docente == $riga[0] && $giorni == $riga[1] ) {
                    $risultato .= " Docente ". strtoUpper($docente)." ".($riga[2]+1)." ora in Classe: " . $riga[3] . " aula: " . $riga[4]."\n";
                    $rigaTrovata = true;
                }
            }else{
            if ($docente == $riga[0] && $giorni == $riga[1] && $ore == $riga[2]) {
                $risultato = " Docente ". strtoUpper($docente)." in Classe: " . $riga[3] . " aula: " . $riga[4];
                $rigaTrovata = true;
            }
        }
        }
        //Altimenti sicuramente sta cercando un Professore
    } else if ($tipo == "Cerca Classe") {
        $risultato = "";
        foreach ($fileLetto as $riga) {
           
            if ($classi == $riga[3] && $giorni == $riga[1] && $ore == $riga[2]) {
                $risultato = "Prof: " . $riga[0] . " aula:" . $riga[4];
                $rigaTrovata = true;
            }
        }
        
    }
    //Se non ho trovato nulla lo comunico
    if (!$rigaTrovata) {
        $risultato = "non ho trovato nulla";
    }
    return $risultato;
}