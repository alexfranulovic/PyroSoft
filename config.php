<?php
/**
 * Oh, here you are! Aren´t you?
 *
 * And yeah, I know this plataform it's similar with WordPress.
 * But what can I do? It's similar but not the same, beacause we are better!
 * Maybe... I guess... Well...
 *
 * This doc contains those informations below:
 *
 * * Setting of DataBase
 * * About showing errors
 * * Criptography
 *
 * Some day there will a web documentation for your doubts, ok?
 *
 */


// ** Setting the DataBase connection ** //
define( 'DB_HOST',     env('database.host') );      //DataBase's server
define( 'DB_USER',     env('database.user') );      //DataBase's user
define( 'DB_PASSWORD', env('database.password') );  //DataBase's password
define( 'DB_NAME',     env('database.name') );      //DataBase's name


/** Tables prefix is used to create a pattern **/
$table_prefix = 'ep_tb_';

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);


if (!$conn)
{
    // Display the error message and error code
    $error_message = "Connection failed: " . mysqli_connect_error();
    $error_code = mysqli_connect_errno();

    // Logging the error message (optional)
    error_log("Database connection error [{$error_code}]: {$error_message}");

    // Displaying the error message and terminating the script
    echo "Database connection error [{$error_code}]: {$error_message}";
    exit;
}


/** DEBUG Mode to Devs **/
ini_set('error_reporting', E_ALL);
ini_set('display_errors', 1);



/* Here you can add something custom beetween this line and the next line. */



/* Yeah, now you must stop edit THIS file. */

