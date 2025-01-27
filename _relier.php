<?php
define('BASE',"$$$");
$local_database_host  = "$$$";
$local_database_user = "$$$";
$local_database_pwd = "$$$";

try {
    $bdd = new PDO("mysql:host=".$local_database_host.";dbname=".BASE, $local_database_user, $local_database_pwd);
    }
catch(PDOException $e) {
    die('Erreur : ' . $e->getMessage());
    }
$bdd->query("SET NAMES 'UTF8'");
?>