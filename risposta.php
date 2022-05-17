<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="stylesheet.css">
    <title>fhf</title>
    <?php
    if (($file = fopen("orario.csv", "r")) !== FALSE) 
    {
      while (($data = fgetcsv($file, 1000, ";")) !== FALSE) 
      {
        $data[0] = strtolower($data[0]);
        $data[3] = strtolower($data[3]);
        $data[4] = strtolower($data[4]);
        $fileLetto[] = $data; 
      }
      fclose($file);
    }
    ?>
</head>
<body>
    <?php
        $giorni = $_POST["giorno"];
        $ore = $_POST["ora"];
        $docenteInput = strtolower($_POST["docente"]);
        $classi = strtolower($_POST["classe"]);
        $rigaTrovata = false;

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

        if ($docente != "") {
            foreach ($fileLetto as $riga) {
                if($docente == $riga[0] && $giorni == $riga[1] && $ore == $riga[2]) {
                    echo "<fieldset>Classe: " . $riga[3] . " aula: " . $riga[4] . "</fieldset>";
                    $rigaTrovata = true;
                }
            }
        } else {
            foreach ($fileLetto as $riga) {
                if($classi == $riga[3] && $giorni == $riga[1] && $ore == $riga[2]) {
                    echo "<fieldset>Prof: " . $riga[0] . " aula:" . $riga[4] . "</fieldset>";
                    $rigaTrovata = true;
                }
            }
        }

        if (!$rigaTrovata) {
            echo "<fieldset>non ho torvato nulla</fieldset>";
        }
    ?>
</body>
</html>