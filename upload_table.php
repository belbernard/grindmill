<?php
// http://ccrss.org/database/upload_table.php?table=t_map
ini_set('max_execution_time',4000);
ini_set('memory_limit','300M');
// session_start();
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_users.php");

if($user_role[$_SESSION['login']] <> "admin") die();

if(isset($_GET['table'])) $table = $_GET['table'];
else die();
echo "Votre table ".$table." est en cours de restauration.......<br />";

$filename = $table.".csv";

/*
system("cd //Applications/MAMP/htdocs/OVI");
$command = "cat ".$source." | //Applications/MAMP/Library/bin/mysql --host=".$local_database_host." --user=".$local_database_user." --password=".$local_database_pwd." grindmill";
echo $command."<br />";
system($command); */

$query = "TRUNCATE TABLE ".$table;
$result = $bdd->query($query);
$result->closeCursor();

if(file_exists($filename)) echo "<br />".$filename."<br />";

/* $query2 = "LOAD DATA LOCAL INFILE '".$filename."' INTO TABLE ".BASE.".".$table." FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\n'";
echo $query2."<br />"; */
// mysqli_query($base,$query2);
/*$result2 = $bdd->query($query2);
if(!$result2) echo "Error on query<br />";
else {
	$result2->closeCursor();
	echo "<br />C'est fini. Votre base est en place sur cet hébergement.";
	} */

// $mysql_path = '';
$cmd1 = "cd //Applications/MAMP/htdocs/OVI/output";
$local_database_pwd = '';
$cmd2 = $mysql_path."mysql -h ".$local_database_host." -u ".$local_database_user." -p ".$local_database_pwd." ".BASE." < ".$table.".sql";
//Applications/MAMP/Library/bin/mysql -h hostingmysql324.amen.fr -u belbernard -p grindmill < t_map.sql
echo $cmd1."<br />";
echo $cmd2."<br />";
exec($cmd1);
echo "<br />";
exec($cmd2);

// https://docs.ovh.com/fr/fr/web/hosting/mutualise-guide-importation-dune-base-de-donnees-mysql/
// system("cat nom_de_la_base.sql | mysql --host=serveur_sql --user=nom_de_la_base --password=mot_de_passe nom_de_la_base");
?>