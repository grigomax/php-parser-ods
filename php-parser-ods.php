<?php

/* Massimo Grigolin (grigomax@mcetechnik.it)
 * Parser file ODS versione 1.0 22/02/2017
 * Nome file - php-parser-ods.php
 * 
 * Licenza GPL3
 * 
 * Questa funzione permette di leggere direttamente il file ods di libreoffice
 * 
 * Passandogli il file alla variabile file ritorna un po di funzioni
 * -prima ritorna una tabella
 * -seconda ritorna un arrai con le colonne e le righe
 * -terza inserisce il file su un database temporaneo
 * 
 * NECESSITA:
 * librerie php-XML reader, simplexml, xpath, zip
 * 
 * Eventuale funzione con database, necessita di mysql.
 * Il programma crea una tabella temporanea che viene poi eliminata se non utilizzata...
 * 
 * 
 * CHANGELOG
 * - 1.0 - Inizio programma
 * 
 * 
 */


/*da scommentare in caso di utilizzo del database

//connessione al database.. se necessario..

$db_server = 'localhost'; //indirizzo del server
$db_nomedb = ''; // nome del database
$db_user = ''; //nome utente
$db_password = ''; //password

try
{
    //$conn = new PDO("mysql:host=$db_server;dbname=$db_nomedb", $db_user, $db_password, array(PDO::ATTR_PERSISTENT => TRUE));
    $conn = new PDO("mysql:host=$db_server;dbname=$db_nomedb", $db_user, $db_password, array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
} catch (PDOException $e)
{
    echo 'Errore di connessione: ' . $e->getMessage();

}

*/


/* * Funzione per la lettura dei file ods
 * 
 * @global type $_percorso variabile che collega il file all'interno del progetto
 * @global type $dec variabile che assegna i decimali da usare nel caso richieda
 * @global type $conn Oggetto in PDO di collegamento al database
 * @param type $_cosa   Selezionare il tipo di lavorazione table, array, database
 * @param type $_file   Nome file da processare
 * @param type $_name Nome da assegnare alla tabella temporanea
 * @param type $_foglio numero del foglio da elaborare
 * @param type $_parametri variabile da usare a piacimento, nel caso di verbose = mostra a video le colonne le righe trovate
 */

function parse_file_ods($_cosa, $_file, $_name, $_foglio, $_parametri)
{

    // parametri globali
    global $_percorso; //directory di dove risiede la base del sito.
    global $dec;   //variabile con il numero di decimali
    global $conn; // Oggetto in PDO della connessione database
    
    //carichiamo il file
    $xml = simplexml_load_file('zip://' . $_file . '#content.xml');

    //leggiamo il file
    $fogli = count($xml->xpath('//table:table'));
    $colonne = count($xml->xpath('//table:table-column'));
    $righe = count($xml->xpath('//table:table-row'));
    $celle = count($xml->xpath('//table:table-cell'));
    $Lettera = chr(65 + $colonne);

    if ($_parametri == "verbose")
    {
        echo "<br>File Elaborato = $_file<br>";
        //ora sappiamo che ci sono quattro colonne..
        echo "numero fogli.. $fogli <br>\n";
        echo "numero colonne.. $colonne <br>\n";
        echo "numero righe.. $righe <br>\n";
        echo "numero celle.. $celle <br>\n";
        echo "<br>\n";
    }



//proviamo a selezionare il foglio..
    $pagina = $xml->xpath('//table:table');

    //selezioniamo la pagina singola
    if ($_foglio == "")
    {
        //vuol dire che sono tutti i fogli
        $righe_pagina = $xml->xpath('//table:table-row');
    }
    else
    {
        $righe_pagina = $pagina[$_foglio]->xpath('table:table-row');
    }


    if ($_cosa == "table")
    {
        //proviamo a fare una tabellina

        echo "<table border=\"1\" name=\"$_name\">\n";

        foreach ($righe_pagina AS $riga)
        {

            echo "<tr>\n";
            $cella = $riga->xpath('table:table-cell/text:p');
            $acapo = $riga->xpath('table:table-cell/text:p/text:span');


            for ($index = 0; $index < $colonne; $index++)
            {
                if (@$cella[$index] != "")
                {
                    echo "<td> " . $cella[$index] . "\n";

                    if (@$acapo[$index] != "")
                    {
                        echo "<br> $acapo[$index]\n";
                    }

                    echo "</td>\n";
                }
                else
                {
                    echo "<td>&nbsp</td>\n";
                }
            }

            //echo "<br>\n";
            echo "</tr>\n";
        }

        echo "</table>\n";

        $return = "OK";
    }
    elseif ($_cosa == "database")
    {
        //creaiamo un database temporaneo..
        for ($index = "A"; $index <= $Lettera; $index++)
        {
            @$Colonna .= "$index varchar(100) default '',";
        }

        $query = "CREATE TEMPORARY TABLE IF NOT EXISTS $_name (row int(5), $Colonna foglio char(2) default '') ENGINE=MyISAM DEFAULT CHARSET=utf8";

        //echo $query;

        $result = $conn->exec($query);

        if ($conn->errorCode() != "00000")
        {
            $_errore = $conn->errorInfo();
            echo $_errore['2'];
        }
        
        //funzione veloce collegamento db..
        //$result = domanda_db("exec", $query, $_cosa, $_ritorno, $_parametri);

        //svuotiamo la tabella
        $query = "TRUNCATE TABLE $_name";
        $result = $conn->exec($query);

        if ($conn->errorCode() != "00000")
        {
            $_errore = $conn->errorInfo();
            echo $_errore['2'];
        }

        //$result = domanda_db("exec", $query, $_cosa, $_ritorno, $_parametri);
        //Ora non ci resta che inserire i dati..
        //inseriamo per ogni riga una sua colonna..
        $Row = 0;
        $return = array();

        foreach ($righe_pagina AS $riga)
        {
            $cella = $riga->xpath('table:table-cell/text:p');
            $acapo = $riga->xpath('table:table-cell/text:p/text:span');

            for ($index = 0; $index <= $colonne; $index++)
            {
                if (@$cella[$index] != "")
                {
                    $Riga .= "'" . addslashes($cella[$index]) . "',";
                    $col .= chr(65 + $index).",";
                }
                else
                {
                    $Riga .= "'',";
                    $col .= chr(65 + $index).",";
                }
            }

            //inseriamo la query..

            $query = "INSERT INTO $_name (row, $col foglio) VALUES ('$Row', $Riga '$_foglio')";
            //echo $query;
            $result = $conn->exec($query);

            if ($conn->errorCode() != "00000")
            {
                $_errore = $conn->errorInfo();
                echo $_errore['2'];
            }

            //$result = domanda_db("exec", $query, $_cosa, $_ritorno, $_parametri);

            $Riga = "";
            $col = "";
            $Row++;
        }

        $return['colonne'] = $colonne;
        $return['righe'] = $Row--;
    }
    else
    {
        //inseriamo tutti i dati su un array
        //inseriamo per ogni riga una sua colonna..
        $Row = 0;
        $return = array();

        foreach ($righe_pagina AS $riga)
        {
            $cella = $riga->xpath('table:table-cell/text:p');
            $acapo = $riga->xpath('table:table-cell/text:p/text:span');

            for ($index = 0; $index < $colonne; $index++)
            {
                if (@$cella[$index] != "")
                {
                    $return[$Row][$index] = $cella[$index];
                }
                else
                {
                    $return[$Row][$index] = "";
                }
            }

            $Row++;
        }

        $return['colonne'] = $colonne;
        $return['righe'] = $Row--;
    }




    return $return;
}



/*
 * ecco alcuni esempi di funzione...
 */
//facciamo partire la funzione

$cesare = parse_file_ods("database", "ciao.ods", "cesare", "", "verbose");

/*
  echo "Numero colonne = $cesare[colonne] <br>\n";
  echo "Numero righe = $cesare[righe] <br>\n";

  echo "colonna = " . $cesare['15']['0'];
  echo " riga = " . $cesare['15']['2'];
  $_ciao = $cesare['15']['2'];
  echo gettype($_ciao);
 */

/*
//facciamo una prova..
//proviamo a fare una query..

$query = "SELECT * FROM cesare where row='20'";

$result = $conn->query($query);
foreach ( $result AS $dati)
{
    echo "<pre>\n";
    var_dump($dati);
    echo "</pre>\n";
}
 * 
 */

?>
