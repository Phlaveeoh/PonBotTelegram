<?php
    
    $botToken = "5304260156:AAGc_ID6tJeuiXQ9RnAXVa-2nti8-sUI1Cs";
    $website = "https://api.telegram.org/bot".$botToken;
    $update = file_get_contents("php://input");
    $update = json_decode($update, TRUE);
    $content = file_get_contents("php://input");
    $ricevuto = json_decode($content, true);

    $id = $update["message"]["chat"]["id"];
    $testo = $update["message"]["text"];
    $elementi_testo = explode(" ", $testo, 3);

    $giorni = '[{"text":"Lunedì"},{"text":"Martedì"}],'.
    '[{"text":"Mercoledì"},{"text":"Giovedì"},{"text":"Venerdì"}]';
    $ore = '[{"text":"1"},{"text":"2"}],'.
    '[{"text":"3"},{"text":"4"}],[{"text":"5"},{"text":"6"}],'.
    '[{"text":"7"},{"text":"8"}]';
    
    $output= ":( non ho trovato nessuno";
    if(isset($elementi_testo[2]) && isset($elementi_testo[3])){
        $docente = trim(strtolower($elementi_testo[2]));
        $giorno = trim(giornoOra($elementi_testo[0]));
        $ora = trim($elementi_testo[1]-1);
        $classe = substr($docente, "0","1");
        if(is_numeric($classe)){
            if($classe[0] >= 1 && $classe[0] <= 5){
                $output = trovaClasse($docente, $giorno, $ora);
            }
        }else{
            $output = trovaProf($docente, $giorno, $ora);
        }
        sendMsg($GLOBALS["id"], $output);  
    }else{
        // se non mi ha passato tutti i dati creo un file dove inserisco le cose che l'utente passa
        $nomeArchivio = "archivio/$id.json";
        // se file esiste prendo il contenuto
        if( file_exists($nomeArchivio) ){
            $contenuto = file_get_contents($nomeArchivio);
        }else{
            $contenuto="{}";
        }
        $oggetto = JSON_decode($contenuto, true);
        $valore = trim($elementi_testo[0]);
        if($valore == "/start"){
            $tastierino='&reply_markup={"keyboard":['.$GLOBALS["giorni"].'],'.
                '"resize_keyboard":true,"one_time_keyboard":true}';
            $output = "Ecco la tastiera";
            sendMsg($id, $output, $tastierino);
            file_put_contents($nomeArchivio,"");
        }else if($valore == "Lunedì" || $valore == "Martedì" || $valore == "Mercoledì" || $valore == "Giovedì" || $valore == "Venerdì"){
            $oggetto['giorno']=giornoOra($valore);
            $output = "ok, ora scrivi l'ora";
            $tastierino='&reply_markup={"keyboard":['.$GLOBALS["ore"].'],'.
                '"resize_keyboard":true,"one_time_keyboard":true}';
            sendMsg($id, $output, $tastierino);
        } else if(is_numeric($valore)){
            $oggetto['ora']=$valore;
            $output = "ok, ora scrivi chi devo cercare";
            sendMsg($id, $output);
        }else{
            $oggetto["cerca"] = $valore;
        }
        if(isset($oggetto["giorno"])){
            $giorno = $oggetto["giorno"];
        }
        if(isset($oggetto["ora"])){
            $ora = ore($oggetto["ora"]);
        }
        if(isset($oggetto["cerca"])){
            $cerca = $oggetto["cerca"];
        }
        if(isset($oggetto["giorno"]) && isset($oggetto["ora"]) && isset($oggetto["cerca"])){
            $classe = substr($cerca, "0", "1");
            if(is_numeric($classe)){
                if($classe[0] >= 1 && $classe[0] <= 5){
                    $output = trovaClasse($cerca, $giorno, $ora);
                }
            }else{
                $output = trovaProf($cerca, $giorno, $ora);
            }
            $tastierino='&reply_markup={"keyboard":['.$GLOBALS["giorni"].'],'.
                '"resize_keyboard":true,"one_time_keyboard":true}';
            sendMsg($GLOBALS["id"], $output, $tastierino);
            unlink($nomeArchivio);
        }else{
            $dati = JSON_encode($oggetto);
            file_put_contents($nomeArchivio, $dati);
        }
        
    }
    
    function trovaClasse($classe, $giorno, $ora){
        $myfile = fopen("orario.csv", "r") or die("Non riesco a leggere il file");
        $classe = strtoupper($classe);
        while(!feof($myfile)) {
            $riga = fgets($myfile);
            $elementi_riga = explode(";", $riga);
            if($elementi_riga[3] == $classe){
                if($elementi_riga[1] == $giorno){
                    if($elementi_riga[2] == $ora){
                        $out = "la classe $classe si trova nella classe $elementi_riga[4], con il/la prof $elementi_riga[0]";
                    }
                }
            }
        }
        return $out;
    }

    function trovaProf($prof, $giorno, $ora){
        sendMsg($id, "".$prof." ".$giorno." ".$ora);
        $prof = strtolower($prof);
        $myfile = fopen("orario.csv", "r") or die("Non riesco a leggere il file");
        while(!feof($myfile)) {
            $riga = fgets($myfile);
            $elementi_riga = explode(";", $riga);
            $elementi_riga[0] = strtolower($elementi_riga[0]);
            if($elementi_riga[0] == $prof){
                if($elementi_riga[1] == $giorno){
                    if($elementi_riga[2] == $ora){
                        return "il/la professore/ssa $prof, alla ".$elementi_riga[2]."° del giorno ".giornoOra($giorno)."ora si trova nella stanza $elementi_riga[4], con la classe $elementi_riga[3]";
                    }
                }
            }
        }
    }
    /** @param giornoOra: String --output--> Int */
    function giornoOra($ora){
        $out = "";
        if(!is_numeric($ora)){
            switch($ora){
                case "Lunedì":
                    $out =  0;
                    break;
                case "Martedì":
                    $out =  1;
                    break;
                case "Mercoledì":
                    $out =  2;
                    break;
                case "Giovedì":
                    $out =  3;
                    break;
                case "Venerdì":
                    $out =  4;
                    break;
            }
        }else{
            switch($ora){
                case 0:
                    $out =  "Lunedì";
                    break;
                case 1:
                    $out =  "Martedì";
                    break;
                case 2:
                    $out =  "Mercoledì";
                    break;
                case 3:
                    $out =  "Giovedì";
                    break;
                case 4:
                    $out =  "Venerdì";
                    break;
            }
        }
        return $out;
    }
    function ore($ora){
        switch($ora){
            case "1":
                $ora = "0";
                break;
            case "2":
                $ora = "1";
                break;
            case "3":
                $ora = "2";
                break;
            case "4":
                $ora = "3";
                break;
            case "5":
                $ora = "4";
                break;
            case "6":
                $ora = "5";
                break;
            case "7":
                $ora = "7";
                break;
            case "8":
                $ora = "8";
                break;
        }
        return $ora;
    }

    function sendKeyboard($cambia="giorno"){
        $out = "";
        switch($cambia){
            case "giorno":
                $out='&reply_markup={"keyboard":['.$GLOBALS["giorni"].'],'.
                '"resize_keyboard":true,"one_time_keyboard":true}';
                break;
            case "ora":
                $out='&reply_markup={"keyboard":['.$GLOBALS["ore"].'],'.
                '"resize_keyboard":true,"one_time_keyboard":true}';
                break;
        }
        return $out;
    }

    function sendMsg($id, $testo, $tastiera=""){
       $url = $GLOBALS["website"]."/sendMessage?chat_id=$id&text=".urlencode($testo).$tastiera;
       file_get_contents($url);
    }
?>