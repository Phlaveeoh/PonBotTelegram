<?php
//$botToken = "5109129284:AAHuJ29Co3OZT6Sm7qJWBTn-4lN7efpmJbQ";
//https://api.telegram.org/bot5499271060:AAG_4NucqqGHkhScfczi6TpcFw7W-g0cq_Q/setwebhook?url=https://r.aspix.it/bot/e/bot.php
$botToken = "5499271060:AAG_4NucqqGHkhScfczi6TpcFw7W-g0cq_Q";
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
        //Se l'utente scrive /start:
    case ("/start"):
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraTipo . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Benvenuto, cosa vuoi cercare?", $tastierino);
        break;
        //Se l'utente scrive /help:
    case ("/help"):
        sendMessage($chatId, "Benvenuto, sono <b>il bot proFinder sviluppato dall'indirizzo informatico dell' IISCG di Gubbio</b> ed ora ti spiegherò cosa posso fare.\n" .
            "Una volta avviato posso aiutarti a ritrovare una classe o un professore in giro per la scuola!\n" .
            "Utilizzarmi è molto semplice, basta seguire le istruzioni che ti dirò mano a mano ed ogni tuo dubbio sarà fugato in men che non si dica!\n" .
            "\n" .
            "<b>COMANDI DISPONIBILI:</b>\n" .
            "<b>/help</b>: Visualizza questo messaggio.\n" .
            "<b>/delete</b>: Elimina tutti i parametri di ricerca che hai inserito.");
        break;
        //Se l'utente scrive /delete:
    case ("/delete"):
        //svuoto la riga del db che contiene il chatID dell'utente
        $dataBase->query("DELETE FROM richieste WHERE chatid=$chatId");
        //Invio tastierino scelta funzione
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraTipo . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Eliminati tutti i parametri di ricerca precedenti \n" .
            "Cosa vuoi cercare?", $tastierino);
        break;
        //Se l'utente scrive "Cerca Prof" oppure "Cerca Classe":
    case ("Cerca Prof"):
    case ("Cerca Classe"):
        //Inserisco il messaggio sul db
        $dataBase->query("UPDATE richieste SET tipo='$valore' WHERE chatid=$chatId");
        //Invio la tastiera con i giorni
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraGiorni . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Scrivi il giorno della settimana:", $tastierino);
        break;
        //Se l'utente scrive un giorno della settimana:
    case ("Lunedì"):
    case ("Martedì"):
    case ("Mercoledì"):
    case ("Giovedì"):
    case ("Venerdì"):
        //In base alla richiesta dell'utente invio una tastiera differente
        $risultato = $dataBase->query("SELECT tipo FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        $rimuovi = '&reply_markup={"remove_keyboard":true}';

        if ($row["tipo"] == "Cerca Prof") {
            //Tastiera con opzione "Orario Completo"
            $tastieraOre = $tastieraOreProf;
        } else {
            //Tastiera senza opzione "Orario Completo"
            $tastieraOre = $tastieraOreClasse;
        }
        //Converto il giorno scritto in lettere in un numero
        $giorno = convertiGiorno($valore);
        //Inserisco il valore nel db
        $dataBase->query("UPDATE richieste SET giorno=$giorno WHERE chatid=$chatId");
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraOre . '],' . '"resize_keyboard":true}';
        sendMessage($chatId, "Ok ora scrivi l'ora del giorno:", $tastierino);
        break;
        //Se l'utente ha scritto un'ora della giornata:
    case ("Prima"):
    case ("Seconda"):
    case ("Terza"):
    case ("Quarta"):
    case ("Quinta"):
    case ("Sesta"):
    case ("Settima"):
    case ("Ottava"):
    case ("Orario Completo");
        //Converto l'ora scritta in lettere in un numero
        $ora = convertiOre($valore);
        //Inserisco il valore nel db
        $dataBase->query("UPDATE richieste SET ora=$ora WHERE chatid=$chatId");
        //In base alla richiesta invio un messaggio personalizzato
        $risultato = $dataBase->query("SELECT tipo FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        $rimuovi = '&reply_markup={"remove_keyboard":true}';

        if ($row["tipo"] == "Cerca Prof") {
            sendMessage($chatId, "Scrivi il nome del prof che vuoi cercare:", $rimuovi);
        } else {
            sendMessage($chatId, "Scrivi la classe che vuoi cercare:", $rimuovi);
        }
        break;
        //Qualsiasi cosa l'utente scrive la considero o un professore o una classe in base alla richiesta dell'utente
    default:
        //profClasse è la variabile in cui memorizzo il valore
        $profClasse = $valore;
        //In base alla richiesta, salvo profClasse nella giusta colonna del db
        $risultato = $dataBase->query("SELECT tipo FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        if ($row["tipo"] == "Cerca Prof") {
            $dataBase->query("UPDATE richieste SET prof='$profClasse' WHERE chatid=$chatId");
        } else {
            $dataBase->query("UPDATE richieste SET classe='$profClasse' WHERE chatid=$chatId");
        }
        //Prendo tutti i parametri scritti dall'utente
        $risultato = $dataBase->query("SELECT * FROM richieste WHERE chatid=$chatId");
        $row = $risultato->fetch();
        $tastierino = '&reply_markup={"keyboard":[' . $tastieraTipo . '],' . '"resize_keyboard":true}';
        //Invio all'utente la risposta alla richiesta ottenuta tramite la funzione di ricerca
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
            $ora = 9;
            break;
    }
    return $ora;
}

//FUNZIONE DI RICERCA
function ricerca($row)
{
    //Leggo il file e lo memorizzo
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

    //In base alla richiesta dell'utente ottengo un risultato
    //L'Utente sta cercando un Professore
    if ($tipo == "Cerca Prof") {
        $risultato = "";
        foreach ($fileLetto as $riga) {
            //Se l'utente ha richiesto l'orario completo per quel giorno lo stampo
            if ($ore == 9) {
                if ($docente == $riga[0] && $giorni == $riga[1]) {
                    $risultato .= " Docente " . strtoUpper($docente) . " " . ($riga[2] + 1) . " ora in Classe: " . $riga[3] . " aula: " . $riga[4] . "\n";
                    $rigaTrovata = true;
                }
                //Altrimenti stampo l'orario specifico
            } else {
                if ($docente == $riga[0] && $giorni == $riga[1] && $ore == $riga[2]) {
                    $risultato = " Docente " . strtoUpper($docente) . " in Classe: " . $riga[3] . " aula: " . $riga[4];
                    $rigaTrovata = true;
                }
            }
        }
        //Altimenti sicuramente sta cercando una Classe
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
    //restituisco ciò che ho ottenuto dalla richiesta
    return $risultato;
}
