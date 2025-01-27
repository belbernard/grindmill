<?php
// session_start();
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

$page = urlencode(substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1));
if(isset($_SESSION['login'])) $login = $_SESSION['login'];
else $login = '';
if($login <> '') {
	$session = session_id();
	$query_there = "SELECT id FROM ".BASE.".history WHERE login = \"".$login."\" AND session = \"".$session."\" AND page = \"".$page."\"";
	$result_there = $bdd->query($query_there);
	$n_there = $result_there->rowCount();
	if($n_there == 0 AND $login <> "Bernard") {
		$query_update = "INSERT INTO ".BASE.".history (login, session, page) VALUES (\"".$login."\", \"".$session."\", \"".$page."\")";
		$result_update = $bdd->query($query_update);
		if(!$result_update) {
			}
		else $result_update->closeCursor();
		}
	}
?>
