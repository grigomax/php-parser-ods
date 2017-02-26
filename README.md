# php-parser-ods
Function for Read a ods file oasis

/* Massimo Grigolin (grigomax@mcetechnik.it)
 * Parser file ODS versione 1.0 22/02/2017
 * Nome file - php-parse-ods.php
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
*/

CHANGELOG
 - 1.0 - Inizio programma
