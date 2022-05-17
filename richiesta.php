<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Form richiesta</title>
    <style>
        label {
            display: block
        }
    </style>
    <link rel="stylesheet" href="stylesheet.css">
    <?php
        if (($file = fopen("orario.csv", "r")) !== FALSE) 
        {
          while (($data = fgetcsv($file, 1000, ";")) !== FALSE) 
          {        
            $fileLetto[] = $data; 
          }
          fclose($file);
        }
    ?>
</head>

<body>
    <form action="risposta.php" method="POST">
        <fieldset>
            <legend>Inserisci i dati</legend>
        <label>giorno:
            <select name="giorno">
                <option value="0">lunedi</option>
                <option value="1">martedi</option>
                <option value="2">mercoledi</option>
                <option value="3">giovedi</option>
                <option value="4">venerdi</option>
            </select>
        </label>
        <label>ora:
            <select name="ora">
                <option value="0">prima</option>
                <option value="1"> seconda</option>
                <option value="2">terza</option>
                <option value="3">quarta</option>
                <option value="4">quinta</option>
                <option value="5">sesta</option>
                <option value="6">settima</option>
                <option value="7">ottava</option>
            </select>
        </label>
        <label>docente:
            <input class="input" type="text" name="docente">
        </label>
        <label>
            classe:
            <input class="input" type="text" name="classe">
        </label>
        <input type="submit" value="invio">
        <input type="reset">
        </fieldset>
    </form>
</body>

</html>