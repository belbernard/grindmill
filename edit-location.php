<?php
// session_start();
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_users.php");
require_once("_tasks.php");

if(!check_serious_attempt('browse')) die();

$name = "Edit location";
$canonic_url = '';
$mssg = '';

if(isset($_GET['location_id'])) $location_id = $_GET['location_id'];
else $location_id = 0;

$location_features = location_features($location_id);
if(!isset($location_features['village_english'])) {
	echo "<font color=red>Error: unknown location</font>";
	die();
	}

if($location_features['hamlet_english'] == '')
	$fullname = $location_features['village_english']." - ".$location_features['village_devanagari'];
else
	$fullname = $location_features['village_english']." / ".$location_features['hamlet_english']." - ".$location_features['village_devanagari']." / ".$location_features['hamlet_devanagari'];
		
$name = "Edit ".$fullname;
$url_location = "location.php?location_id=".$location_id;
	
require_once("_header.php");

echo "<h2>Edit location [".$location_id."] descriptions<br /><a target=\"_blank\" title=\"".$url_location."\" href=\"".$url_location."\">".$fullname."</a></h2>";

if(!identified()) {
	echo "<font color=red>You logged out, or your edit session expired.<br />You need to log in if you wish to resume editing this record.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

$old_time = time() - 3600;
$sql = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
// $result = mysql_query($sql);
$result = $bdd->query($sql);
$result->closeCursor();

if(isset($_POST['pwd_export'])) $pwd_export = $_POST['pwd_export'];
else $pwd_export = 'nil';
if(isset($good_one[$login]) AND $pwd_export == $good_one[$login]) {
	$message = ExportLocations();
	echo "<blockquote><br /><br />".$message."</blockquote>";
	}
echo "</blockquote>";

if(isset($_POST['info_english'])) {
	/* $info_english = str_replace("\n","<br />",$_POST['info_english']);
	$info_english = str_replace("\r",'',$info_english); */
	$info_english = reshape_entry($_POST['info_english']);
	/* $info_english_old = str_replace("\n","<br />",$_POST['info_english_old']);
	$info_english_old = str_replace("\r",'',$info_english_old); */
	$info_english_old = reshape_entry($_POST['info_english_old']);
	$info_english = fix_typo($info_english,0);
	if($info_english <> $info_english_old) {
		$query = "UPDATE ".BASE.".locations SET info_english = \"".$info_english."\" WHERE location_id = \"".$location_id."\"";
	//	echo $query."<br />";
	//	$result = mysql_query($query);
		$result = $bdd->query($query);
		if(!$result) {
			echo "<br /><font color=red>".$query."<br />";
			echo "ERROR: FAILED</font>";
			die();
			}
		$result->closeCursor();
		$query = "SELECT version, story_english FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_english <> \"\" ORDER BY version DESC";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		$preceding_text = '';
		if($n == 0) $version = 1;
		else {
	//		$ligne = mysql_fetch_array($result);
			$ligne = $result->fetch();
			$version = 1 + $ligne['version'];
			$preceding_text = $ligne['story_english'];
			}
		$result->closeCursor();
		if($info_english <> $preceding_text OR $info_english == '') {
			// This avoids that a new (identical) version is created if the page is reloaded
			if($version == 1 AND $info_english_old <> '') {
				$query = "INSERT INTO ".BASE.".stories (location_id, version, story_english, story_marathi, login) VALUES (\"".$location_id."\",\"0\",\"".$info_english_old."\",\"\",\"\")";
			//	$result = mysql_query($query);
				$result = $bdd->query($query);
				if(!$result) {
					echo "<br /><font color=red>".$query."<br />";
					echo "ERROR: FAILED</font>";
					die();
					}
				$result->closeCursor();
				}
			if($info_english == '') $text = '~';
			else $text = $info_english;
			$query = "INSERT INTO ".BASE.".stories (location_id, version, story_english, story_marathi, login) VALUES (\"".$location_id."\",\"".$version."\",\"".$text."\",\"\",\"".$login."\")";
		//	echo $query."<br />";
		//	$result = mysql_query($query);
			$result = $bdd->query($query);
			if(!$result) {
				echo "<br /><font color=red>".$query."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result->closeCursor();
			echo "<blockquote><small>Saved English version ".$version.":<br /><font color=red>".$text."</font></small></blockquote><br />";
			}
		}
	}

if(isset($_POST['info_marathi'])) {
	/* $info_marathi = str_replace("\n","<br />",$_POST['info_marathi']);
	$info_marathi = str_replace("\r",'',$info_marathi); */
	$info_marathi = reshape_entry($_POST['info_marathi']);
	/* $info_marathi_old = str_replace("\n","<br />",$_POST['info_marathi_old']);
	$info_marathi_old = str_replace("\r",'',$info_marathi_old); */
	$info_marathi_old = reshape_entry($_POST['info_marathi_old']);
//	$info_marathi = fix_typo($info_marathi,0);
	if($info_marathi <> $info_marathi_old) {
		$query = "UPDATE ".BASE.".locations SET info_marathi = \"".$info_marathi."\" WHERE location_id = \"".$location_id."\"";
	//	echo $query."<br />";
	//	$result = mysql_query($query);
		$result = $bdd->query($query);
		if(!$result) {
			echo "<br /><font color=red>".$query."<br />";
			echo "ERROR: FAILED</font>";
			die();
			}
		$result->closeCursor();
		$query = "SELECT version, story_marathi FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_marathi <> \"\" ORDER BY version DESC";
	//	$result = mysql_query($query);
		$result = $bdd->query($query);
		$preceding_text = '';
	//	$n = mysql_num_rows($result);
		$n = $result->rowCount();
		if($n == 0) $version = 1;
		else {
	//		$ligne = mysql_fetch_array($result);
			$ligne = $result->fetch();
			$version = 1 + $ligne['version'];
			$preceding_text = $ligne['story_marathi'];
			}
		$result->closeCursor();
		if($info_marathi <> $preceding_text OR $info_marathi == '') {
			// This avoids that a new (identical) version is created if the page is reloaded
			if($version == 1 AND $info_marathi_old <> '') {
				$query = "INSERT INTO ".BASE.".stories (location_id, version, story_marathi, story_english, login) VALUES (\"".$location_id."\",\"0\",\"".$info_marathi_old."\",\"\",\"\")";
			//	$result = mysql_query($query);
				$result = $bdd->query($query);
				if(!$result) {
					echo "<br /><font color=red>".$query."<br />";
					echo "ERROR: FAILED</font>";
					die();
					}
				$result->closeCursor();
				}
			if($info_marathi == '') $text = '~';
			else $text = $info_marathi;
			$query = "INSERT INTO ".BASE.".stories (location_id, version, story_marathi, story_english, login) VALUES (\"".$location_id."\",\"".$version."\",\"".$text."\",\"\",\"".$login."\")";
		//	echo $query."<br />";
	//		$result = mysql_query($query);
			$result = $bdd->query($query);
			if(!$result) {
				echo "<br /><font color=red>".$query."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result->closeCursor();
			echo "<blockquote><small>Saved Marathi version ".$version.":<br /><font color=red>".$text."</font></small></blockquote><br />";
			}
		}
	}

$query = "SELECT * FROM ".BASE.".locations WHERE location_id = \"".$location_id."\"";
/* $result = mysql_query($query);
$n = mysql_num_rows($result); */
$result = $bdd->query($query);
$n = $result->rowCount();
if($n == 0) {
echo "<font color=red>Error: unknown location</font> [".$location_id."]";
	die();
	}
echo "<table width=100%>";
	
echo "<tr>";
// $ligne = mysql_fetch_array($result);
$ligne = $result->fetch();
$info_english = $info_english_old = trim($ligne['info_english']);
$query_english = "SELECT version FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_english <> \"\" ORDER BY version DESC";
// $result_english = mysql_query($query_english);
// $n_english = mysql_num_rows($result_english);
$result_english = $bdd->query($query_english);
$n_english = $result_english->rowCount();
if($n_english == 0) $version_english = -1;
else {
//	$ligne_english = mysql_fetch_array($result_english);
	$ligne_english = $result_english->fetch();
	$version_english = $maxversion_english = $ligne_english['version'];
	if(isset($_GET['change_english']) AND isset($_GET['set_english_version'])) {
		$version_english = $_GET['set_english_version'];
		}
	$query3 = "SELECT * FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_english <> \"\" AND version = \"".$version_english."\"";
//	echo $query3;
/*	$result3 = mysql_query($query3);
	$n = mysql_num_rows($result3); */
	$result3 = $bdd->query($query3);
	$n = $result3->rowCount();
	if($n == 0) {
		if($version_english == 0) $version_english = 1;
		$query3 = "SELECT * FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_english <> \"\" AND version = \"".$version_english."\"";
//		echo $query3;
	/*	$result3 = mysql_query($query3);
		$n = mysql_num_rows($result3); */
		$result3 = $bdd->query($query3);
		$n = $result3->rowCount();
		if($n == 0) {
			echo "<font color=red>ERROR: can't reach version ".$version_english."</font><br />";
			}
		}
//	$ligne3 = mysql_fetch_array($result3);
	$ligne3 = $result3->fetch();
	$info_english = trim($ligne3['story_english']);
	if($info_english == '~') $info_english = '';
	$author = $ligne3['login'];
	$timestamp = $ligne3['date'];
	$result3->closeCursor();
	}
$result_english->closeCursor();

$url_this_page = "edit-location.php?location_id=".$location_id;
echo "<form name=\"search\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
$info_english = str_replace("<br />","\n",$info_english);
$info_english_old = str_replace("<br />","\n",$info_english_old);
echo "<td class=\"tight\" style=\"background-color:Cornsilk; padding:12px;\">";
echo "<b>English description:</b><br />";
echo "<TEXTAREA NAME=\"info_english\" ROWS=\"15\" COLS=\"140\">";
echo $info_english;
echo "</TEXTAREA>";
echo "<input TYPE=\"hidden\" NAME=\"info_english_old\" value=\"".$info_english_old."\">";
if($version_english >= 0) {
	echo "<br />";	
	if($version_english >= 1) echo "<a href=\"".$url_this_page."&change_english=1&set_english_version=".($version_english - 1)."\">previous &lt;&lt;</a>&nbsp;&nbsp;";
	echo "Version ".$version_english." - <small>";
	if($author <> '') echo "<i>".$author."</i> - ";
	echo $timestamp."</small>";
	if($version_english < $maxversion_english) echo "&nbsp;&nbsp;<a href=\"".$url_this_page."&change_english=1&set_english_version=".($version_english + 1)."\">&gt;&gt; next</a>";
	}
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk;\"><input TYPE=\"submit\" class=\"button\" value=\"SAVE\">";
echo "</td>";
echo "</form>";
echo "</tr>";


echo "<tr>";
/* $result = mysql_query($query);
$ligne = mysql_fetch_array($result); */
$result = $bdd->query($query);
$ligne = $result->fetch();
$info_marathi = $info_marathi_old = trim($ligne['info_marathi']);
$query_marathi = "SELECT version FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_marathi <> \"\" ORDER BY version DESC";
/* $result_marathi = mysql_query($query_marathi);
$n_marathi = mysql_num_rows($result_marathi); */
$result_marathi = $bdd->query($query_marathi);
$n_marathi = $result_marathi->rowCount();
if($n_marathi == 0) $version_marathi = -1;
else {
//	$ligne_marathi = mysql_fetch_array($result_marathi);
	$ligne_marathi = $result_marathi->fetch();
	$version_marathi = $maxversion_marathi = $ligne_marathi['version'];
	if(isset($_GET['change_marathi']) AND isset($_GET['set_marathi_version'])) {
		$version_marathi = $_GET['set_marathi_version'];
		}
	$query3 = "SELECT * FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_marathi <> \"\" AND version = \"".$version_marathi."\"";
//	echo $query3;
/*	$result3 = mysql_query($query3);
	$n = mysql_num_rows($result3); */
	$result3 = $bdd->query($query3);
	$n = $result3->rowCount();
	if($n == 0) {
		if($version_marathi == 0) $version_marathi = 1;
		$query3 = "SELECT * FROM ".BASE.".stories WHERE location_id = \"".$location_id."\" AND story_marathi <> \"\" AND version = \"".$version_marathi."\"";
//		echo $query3;
	/*	$result3 = mysql_query($query3);
		$n = mysql_num_rows($result3); */
		$result3 = $bdd->query($query3);
		$n = $result3->rowCount();
		if($n == 0) {
			echo "<font color=red>ERROR: can't reach version ".$version_marathi."</font><br />";
			}
		}
//	$ligne3 = mysql_fetch_array($result3);
	$ligne3 = $result3->fetch();
	$info_marathi = trim($ligne3['story_marathi']);
	if($info_marathi == '~') $info_marathi = '';
	$author = $ligne3['login'];
	$timestamp = $ligne3['date'];
	$result3->closeCursor();
	}
$result_marathi->closeCursor();

$url_this_page = "edit-location.php?location_id=".$location_id;
echo "<form name=\"search\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
$info_marathi = str_replace("<br />","\n",$info_marathi);
$info_marathi_old = str_replace("<br />","\n",$info_marathi_old);
echo "<td class=\"tight\" style=\"background-color:Cornsilk; padding:12px;\">";
echo "<b>Marathi description:</b><br />";
echo "<TEXTAREA NAME=\"info_marathi\" ROWS=\"15\" COLS=\"140\">";
echo $info_marathi;
echo "</TEXTAREA>";
echo "<input TYPE=\"hidden\" NAME=\"info_marathi_old\" value=\"".$info_marathi_old."\">";
if($version_marathi >= 0) {
	echo "<br />";	
	if($version_marathi >= 1) echo "<a href=\"".$url_this_page."&change_marathi=1&set_marathi_version=".($version_marathi - 1)."\">previous &lt;&lt;</a>&nbsp;&nbsp;";
	echo "Version ".$version_marathi." - <small>";
	if($author <> '') echo "<i>".$author."</i> - ";
	echo $timestamp."</small>";
	if($version_marathi < $maxversion_marathi) echo "&nbsp;&nbsp;<a href=\"".$url_this_page."&change_marathi=1&set_marathi_version=".($version_marathi + 1)."\">&gt;&gt; next</a>";
	}
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk;\"><input TYPE=\"submit\" class=\"button\" value=\"SAVE\">";
echo "</td>";
echo "</form>";
echo "</tr>";

echo "</table>";
if(identified()) {
	echo "<table>";
	echo "<tr>";
	echo "<form name=\"search\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<td>";
	echo "Export translation changes to ‘<a href=\"".EXPORT."LocationDescriptions.tab\">LocationDescriptions.tab</a>’";
	echo "</td>";
	echo "<td>";
	echo "Password: <input TYPE='password' NAME='pwd_export' size='12' value=\"\">";
	echo "</td>";
	echo "<td>";
	echo "<input TYPE=\"submit\" class=\"button\" value=\"EXPORT\">";
	echo "</td>";
	echo "</form>";
	echo "</tr>";
	echo "</table>";
	}
echo "</body>";
echo "</html>";
?>