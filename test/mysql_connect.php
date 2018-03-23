<?php

require_once ('constants.php'); // Definition for DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_FLAGS

try
{
    $options = array(
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
        PDO::ATTR_PERSISTENT => true
    );
    $dbh = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASSWORD, $options);
}
catch (PDOException $e)
{
    print "We are experiencing an unexpected error. If this error persists, please send an email to oyesanmi@iu.edu";
    //mail("oyesanmi@iu.edu", "SEPGEN DB error", $e->getMessage().PHP_EOL.date("Y-m-d H:i:s"));
    die();
}
?>
