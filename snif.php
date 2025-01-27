<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_users.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");
ini_set('memory_limit','300M');
ini_set('max_execution_time',4000);

if(!check_serious_attempt('browse')) die();

$name = "Snif";
$canonic_url = '';
$mssg = '';

if(!is_admin($login)) {
	echo "<font color=red>You need to log in to read this page.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

if(!is_super_admin($login)) {
	echo "<font color=red>Access denied</font>";
	die();
	}

$query = "SELECT * FROM ".BASE.".snif ORDER BY freq DESC";
$result = $bdd->query($query);
if($result) $n = $result->rowCount();

require_once("_header.php");
echo "<h2>Check unidentified access (".$n.")</h2>";

while($ligne = $result->fetch()) {
	$ip = $ligne['ip'];
//	$url = "https://www.ip-tracker.org/lookup/whois-lookup.php?query=".$ip;
	$url = "https://www.ip-tracker.org/lookup.php?ip=".$ip;
	$link = "<a target=\"_blank\" href=\"".$url."\">".$ip."</a>";
	$freq = $ligne['freq'];
	$first_time = $ligne['first_time'];
	$first_timetag = date('d/m/Y H:i',$first_time);
	$last_time = $ligne['last_time'];
	$last_timetag = date('d/m/Y H:i',$last_time);
	echo $link." (".$freq.") <small>".$first_timetag." ➡ ".$last_timetag."</small><br />";
	}
		
echo "</body>";
echo "</html>";
?>