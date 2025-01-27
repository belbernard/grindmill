<?php
// session_start();
ini_set("auto_detect_line_endings",true);
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_users.php");
require_once("_tasks.php");

if(!check_serious_attempt('browse')) die();

$name = "Revert database";
$canonic_url = '';
$mssg = '';
require_once("_header.php");

echo "<h2>Revert database</h2>";

if(!identified()) {
	echo "<font color=red>You need to log in to read this page.</font>";
	die();
	}
$_SESSION['try'] = 0;

if(!is_super_admin($login)) {
	echo "<font color=red>This page is reserved for super admin.</font>";
	die();
	}

$dir = "DB_DUMP";
echo "<div style=\"color:blue; text-align:center; width:100%;\">Reverting from backups in ".$dir."</div>";
	
$i = 0;
$table_name = $table_date = array();

if(isset($_POST['action']) AND $_POST['action'] == "revert") {
	echo "<blockquote>";
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"table_"))) {
			$table = explode('__',$key);
			if(in_array($table[1],$table_name)) {
				echo "<span style=\"color:red;\">Table</span> ".$table[1]." <span style=\"color:red;\">dated</span> ".$table[2]." <span style=\"color:red;\">will be ignored because a more recent one had been selected</span><br />";
				continue;
				}
			$table_name[$i] = $table[1];
			$table_date[$i] = $table[2];
			echo "<span style=\"color:blue;\"><span style=\"color:red;\">➡</span> Reverting</span> ".$table_name[$i]." <span style=\"color:blue;\">from</span> ".$table_date[$i]."<br />";
			upload_table($table_name[$i],$table_date[$i]);
			$i++;
			}
		}
	if($i == 0) echo "<span style=\"color:red;\">No table processed</span>";
	echo "</blockquote>";
	}

if($handle = opendir($dir)) {
	while(FALSE !== ($file = readdir($handle))) {
		if(!is_dir($dir.'/'.$file)) continue;
		if(substr($file,0,1) == "." ) continue;
		if(strstr($file,"Icon") != FALSE) continue;
		$time = filemtime($dir.'/'.$file);
	//	$time_list[$file] = $time;
		$time_list[$file] = $file;
	//	echo $file." ".$time."<br />";
		}
	closedir($handle);
	}
arsort($time_list);
$i_record = 0;
$header = array();
foreach($time_list as $file => $time) {
	$stats_file = $dir."/".$file."/stats.txt";
	if(file_exists($stats_file)) {
		$liste_file = fopen($stats_file,"rb");
		$values[$i_record] = array();
		while(!feof($liste_file)) {
			$line = fgets($liste_file);
			$line = trim($line);
			if($line == '') continue;
			$table = explode(' ',$line);
			$table_name = str_replace(".csv",'',$table[0]);
			if($i_record == 0) $header[] = $table_name;
			$values[$i_record][$table_name] = $table[1];
			}
		fclose($liste_file);
		}
	$filedate[$i_record] = $file;
	$i_record++;
	}
$i_record_max = $i_record;

echo "<table>";
echo "<form name=\"workset\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"action\" value=\"revert\">";
$maxcol = count($header);
for($i_record = 0; $i_record < $i_record_max; $i_record++) {
	echo "<tr>";
	if($i_record == 0) {
		echo "<td class=\"tight\" style=\"color:blue; text-align:right;\"><b>Table&nbsp;➡</b></td>";
		for($i = 0; $i < $maxcol; $i++)
			echo "<td class=\"tight\" style=\"text-align:center;\"><b>".$header[$i]."</b></td>";
		echo "</tr><tr>";
		echo "<td class=\"tight\" style=\"color:blue; text-align:right;\"><b>Lines&nbsp;now&nbsp;➡</b></td>";
		for($i = 0; $i < $maxcol; $i++)
			echo "<td class=\"tight\" style=\"color:blue; text-align:right;\">".howmany($header[$i])."</td>";
		echo "</tr><tr>";
		}
	echo "<td class=\"tight\">".$filedate[$i_record]."</td>";
	if(isset($values[$i_record])) {
		for($i = 0; $i < $maxcol; $i++) {
			echo "<td class=\"tight\" style=\"text-align:right; white-space:nowrap;\">";
			$table_name = $header[$i];
			if(isset($values[$i_record][$table_name])) {
				$value = $values[$i_record][$table_name];
				echo "<input type=\"checkbox\" name=\"table__".$table_name."__".$filedate[$i_record]."\" value=\"ok\"> ";
				}
			else $value = '';
			echo $value."</td>";
			} 
		}
	echo "</tr>";
	}
echo "<tr><td colspan=\"6\"><input TYPE=\"submit\" name=\"revert_button\" class=\"button\" value=\"REVERT TO SELECTED TABLE(S)\"></td></tr>";
echo "</form>";
echo "</table>";
echo "</body></html>";

function upload_table($name,$date) {
	global $bdd;
	$path = "DB_DUMP/".$date;
	$filename = $path."/".$name.".csv";
	echo "<small>Copying ‘".$filename."’ to MySQL database<br />";
	if(!file_exists($filename)) {
		echo "Export file ‘".$filename."’ not found. Can't proceed!</small><br />";
		return;
		}
	else {
		$filesize = filesize($filename);
		echo "Table ‘".$name."’ is being analysed (size ".$filesize." bytes)<br />";
		}
	$count = howmany($name);
	echo "Before truncating the MySQL table ".$name." contained ".$count." records<br />";
	$query = "TRUNCATE TABLE ".BASE.".".$name;
	$bdd->exec($query);

	$file_handle = fopen($filename,"rb");
	if($file_handle) {
		while(!feof($file_handle)) {
			$line = fgets($file_handle);
			$line = trim($line);
			if($line == '') continue;
			$query = "INSERT INTO ".BASE.".".$name." VALUES (".$line.")";
		//	echo $query."<br />";
			$bdd->exec($query);
			}
		fclose($file_handle);
		}
	$affected = howmany($name);
	if($affected > 0) {
		echo $affected." records have been inserted successfully.<br />";
		if($affected <> $count)
			echo "<font color=red>Number of records changed!</font><br />";
		}
	else echo "<br />This query was not executed, either because the table was not empty or its format is not the same as the one of your backup. You need to sort out this problem with “MySQLadmin”.</small></font>";
	echo "</small>";
	return;
	}
?>