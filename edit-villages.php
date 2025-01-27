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
require_once("_edit_tasks.php");

$name = "Edit villages";
$canonic_url = '';
require_once("_header.php");

echo "<a name=\"top\"></a>";
echo "[<a href=\"#bottom\">Bottom of page</a>]";
echo "<h2>Grindmill songs of Maharashtra — Villages</h2><br />";
set_time_limit(600);

if(!check_serious_attempt('browse')) die();
if(!identified()) {
	echo "<font color=red>You logged out, or your edit session expired.<br />You need to log in if you wish to resume editing this record.</font>";
	die();
	}
if(!is_admin($login)) {
	echo "<font color=red>Only admin can modify this table.</font>";
	die();
	}
$_SESSION['try'] = 0;
$old_time = time() - 3600;
$sql = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
$result = $bdd->query($sql);
$result->closeCursor();

$field_devanagari = $field_english = "…";
$select_all = TRUE;
$stop = FALSE;
// $stop = TRUE;
$hilite = array(); 

echo "<blockquote>=> <a target=\"_blank\" title=\"All villages\" href=\"villages.php\">Display all villages</a></blockquote>";

if(isset($_GET['discrepencies'])) {
	$different = FindDiscrepencies(0);
	if(count($different) > 0) {
		echo "<table>";
		echo "<tr>";
		echo "<td>";
		echo "<form name=\"try_another\" method=\"post\" action=\"edit-villages.php?discrepencies=find\" enctype=\"multipart/form-data\">";
		echo "<input type=\"submit\" class=\"button\" value=\"TRY ANOTHER ONE =>\">";
		echo "</form>";
		echo "</td>";
		echo "<th class=\"tight\" colspan=\"3\">Next discrepency</th>";
		echo "</tr>";
		echo "<tr style=\"background-color:Bisque;\">";
		echo "<td style=\"background-color:white\"></td>";
		echo "<td></td><td style=\"text-align:center;\" colspan=\"2\"><b>".ucfirst($different['field'])."</b></td>";
		echo "</tr>";
		echo "<tr style=\"background-color:Bisque;\">";
		echo "<td style=\"background-color:white\"></td>";
		echo "<td class=\"tight\">Id</td><td class=\"tight\">Devanagari</td><td class=\"tight\">English</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td></td>";
		$id1 = $different['location_id1'];
		$id2 = $different['location_id2'];
		echo "<td class=\"tight\" style=\"background-color:Bisque;\"><a title=\"Show details\" target=\"_blank\" href=\"location.php?location_id=".$id1."\">".$id1."</a></td>";
		echo "<td class=\"tight\" style=\"font-size: 120%;\">&nbsp;".$different['word_devanagari1']."&nbsp;</td>";
		echo "<td class=\"tight\" style=\"font-size: 120%;\">&nbsp;".$different['word_english1']."&nbsp;</td>";
		echo "</tr>";
		echo "<tr>";
		echo "<td></td>";
		echo "<td class=\"tight\" style=\"background-color:Bisque;\"><a title=\"Show details\" target=\"_blank\" href=\"location.php?location_id=".$id2."\">".$id2."</a></td>";
		echo "<td class=\"tight\" style=\"font-size: 120%;\">&nbsp;".$different['word_devanagari2']."&nbsp;</td>";
		echo "<td class=\"tight\" style=\"font-size: 120%;\">&nbsp;".$different['word_english2']."&nbsp;</td>";
		echo "</tr>";
		echo "</table>";
		$field_devanagari = $different['word_devanagari1'];
		$field_english = $different['word_english1'];
		$stop = TRUE;
		}
	}
else echo "<blockquote>=> <a href=\"".$url_this_page."?discrepencies=find\">Display next discrepency</a><br /></blockquote>";

foreach($_POST as $key => $value) {
	if(is_integer(strpos($key,"change_translation_"))) {
		$location_id = str_replace("change_translation_",'',$key);
		$location_features = location_features($location_id);
		echo "<small>Changing [<a title=\"Show details\" target=\"_blank\" href=\"location.php?location_id=".$location_id."\">".$location_id."</a>] ".$location_features['village_english']."</small><br />";
		$hilite[$location_id] = TRUE;
		$field_devanagari = trim($_POST['field_devanagari']);
		$field_english = trim($_POST['field_english']);
		if($location_features['village_devanagari'] == $field_devanagari)
			change_field("locations","village_english",$field_english,"location_id",$location_id);
		if($location_features['village_english'] == $field_english)
			change_field("locations","village_devanagari",$field_devanagari,"location_id",$location_id);
		if($location_features['hamlet_devanagari'] == $field_devanagari)
			change_field("locations","hamlet_english",$field_english,"location_id",$location_id);
		if($location_features['hamlet_english'] == $field_english)
			change_field("locations","hamlet_devanagari",$field_devanagari,"location_id",$location_id);
		if($location_features['taluka_devanagari'] == $field_devanagari)
			change_field("locations","taluka_english",$field_english,"location_id",$location_id);
		if($location_features['taluka_english'] == $field_english)
			change_field("locations","taluka_devanagari",$field_devanagari,"location_id",$location_id);
		if($location_features['valley_devanagari'] == $field_devanagari)
			change_field("locations","valley_english",$field_english,"location_id",$location_id);
		if($location_features['valley_english'] == $field_english)
			change_field("locations","valley_devanagari",$field_devanagari,"location_id",$location_id);
		if($location_features['district_devanagari'] == $field_devanagari)
			change_field("locations","district_english",$field_english,"location_id",$location_id);
		if($location_features['district_english'] == $field_english)
			change_field("locations","district_devanagari",$field_devanagari,"location_id",$location_id);
		$stop = TRUE;		
		}
	}

if(isset($_POST['action']) AND $_POST['action'] == "fix_translation") {
	$field_devanagari = trim($_POST['field_devanagari']);
	$field_english = trim($_POST['field_english']);
	$notfound = FALSE;
	if($field_devanagari <> '' AND $field_english <> '') {
		$sql = "SELECT * FROM ".BASE.".locations WHERE village_devanagari = \"".$field_devanagari."\" OR  hamlet_devanagari = \"".$field_devanagari."\" OR taluka_devanagari = \"".$field_devanagari."\" OR valley_devanagari = \"".$field_devanagari."\" OR district_devanagari = \"".$field_devanagari."\" OR village_english = \"".$field_english."\" OR  hamlet_english = \"".$field_english."\" OR taluka_english = \"".$field_english."\" OR valley_english = \"".$field_english."\" OR district_english = \"".$field_english."\"";
		$result = $bdd->query($sql);
		$n = $result->rowCount();
		if($n > 0) {
			echo "<br />Make “".$field_devanagari."” equivalent to “".$field_english."” in fields of these locations?<br />";
			echo "<form name=\"translate\" method=\"post\" action=\"edit-villages.php\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"field_devanagari\" value=\"".$field_devanagari."\">";
			echo "<input type=\"hidden\" name=\"field_english\" value=\"".$field_english."\">";
			echo "<table>";
			echo "<tr>";
			echo "<td colspan=\"4\">";
			echo "<input type=\"submit\" class=\"button\" value=\"MODIFY SELECTED LOCATIONS\">";
			echo "</form>";
			echo "</td>";
			echo "<td colspan=\"4\">";
			echo "<form name=\"translate\" method=\"post\" action=\"edit-villages.php\" enctype=\"multipart/form-data\">";
			echo "<input type=\"submit\" class=\"button\" value=\"CANCEL\">";
			echo "</form>";
			echo "</td>";
			echo "</tr>";
			echo "<th class=\"tight\" style=\"text-align:center;\">id</th><th class=\"tight\" colspan=2>Village</th><th class=\"tight\" colspan=2>Hamlet</th><th class=\"tight\" colspan=2>Taluka</th><th class=\"tight\" colspan=2>Valley</th><th class=\"tight\" colspan=2>District</th><th></th>";
			while($ligne = $result->fetch()) {
				$location_id = $ligne['location_id'];
				$location_features = location_features($location_id);
				echo "<tr>";
				$diff = FALSE;
				echo "<td class=\"tight\" style=\"white-space:nowrap;\"><a title=\"Show details\" target=\"_blank\" href=\"location.php?location_id=".$location_id."\">".$location_id."</a></td>";
				if($location_features['village_devanagari'] <> $field_devanagari AND $location_features['village_english'] == $field_english) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['village_devanagari']."</td>";
				if($location_features['village_english'] <> $field_english AND$location_features['village_devanagari'] == $field_devanagari) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['village_english']."</td>";
				if($location_features['hamlet_devanagari'] <> $field_devanagari AND $location_features['hamlet_english'] == $field_english) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['hamlet_devanagari']."</td>";
				if($location_features['hamlet_english'] <> $field_english AND $location_features['hamlet_devanagari'] == $field_devanagari) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['hamlet_english']."</td>";
				if($location_features['taluka_devanagari'] <> $field_devanagari AND $location_features['taluka_english'] == $field_english) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['taluka_devanagari']."</td>";
				if($location_features['taluka_english'] <> $field_english AND $location_features['taluka_devanagari'] == $field_devanagari) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['taluka_english']."</td>";
				if($location_features['valley_devanagari'] <> $field_devanagari AND $location_features['valley_english'] == $field_english) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['valley_devanagari']."</td>";
				if($location_features['valley_english'] <> $field_english AND $location_features['valley_devanagari'] == $field_devanagari) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['valley_english']."</td>";
				if($location_features['district_devanagari'] <> $field_devanagari AND $location_features['district_english'] == $field_english) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo$location_features['district_devanagari']."</td>";
				if($location_features['district_english'] <> $field_english AND $location_features['district_devanagari'] == $field_devanagari) {
					$diff = TRUE;
					echo "<td class=\"tight\" style=\"white-space:nowrap; background-color:Gold;\">";
					}
				else echo "<td class=\"tight\" style=\"white-space:nowrap;\">"; echo $location_features['district_english']."</td>";
				echo "<td>";
				echo "<input type=\"checkbox\" name=\"change_translation_".$location_id."\" value=\"ok\" ";
				if($diff) echo "checked";
				echo " />&nbsp;";
				echo "</td>";
				echo "</tr>";			
				}
			echo "</table>";
			$stop = TRUE;
			}
		else $notfound = TRUE;
		}
	else $notfound = TRUE;
	if($notfound) {
		echo "<blockquote><font color=red>ERROR translating: no valid Devanagari or English word!</font><br /></blockquote>";
		$stop = TRUE;
		}
	}

echo "<table>";
echo "<tr>";
echo "<td class=\"tight\" align=center style=\"text-align:center; background-color:Bisque;\" colspan=\"3\"><b>Fix Devanagari/English consistency in whole fields</b><br /><i>Use with care! This is cannot be undone…</i></td>";
echo "</tr>";
echo "<tr style=\"background-color:Bisque;\">";
echo "<td class=\"tight\" style=\"text-align:center;\"><b>Devanagari</b></td>";
echo "<td></td>";
echo "<td class=\"tight\" style=\"text-align:center;\"><b>English</b> (case-sensitive)</td>";
echo "</tr>";
echo "<tr style=\"background-color:Gold;\">";
echo "<FORM ACTION = \"edit-villages.php\" METHOD = \"POST\">";
echo "<input type=\"hidden\" name=\"action\" value=\"fix_translation\">";
echo "<td class=\"tight\" style=\"text-align:center;\">";
echo "<input maxlength=\"99\" size=\"20\" style=\"text-align:center; color:red; font-size: 170%; \" name=\"field_devanagari\" value=\"".$field_devanagari."\" />";
echo "</td>";
echo "<td class=\"tight\">";
echo "<input type=\"submit\" class=\"button\" name=\"submit\" value=\"<== MAKE EQUIVALENT ==>\" />";
echo "</td>";
echo "<td class=\"tight\" style=\"text-align:center;\">";
echo "<input maxlength=\"99\" size=\"20\" style=\"text-align:center; color:red; font-size: 170%;\" name=\"field_english\" value=\"".$field_english."\" />";
echo "</td>";
echo "</form>";
echo "</tr>";
if($stop) {
	echo "<tr style=\"text-align:center; background-color:Bisque;\">";
	echo "<FORM ACTION = \"edit-villages.php\" METHOD = \"POST\">";
	echo "<td></td>";
	echo "<td class=\"tight\" style=\"text-align:center; background-color:Gold;\"><input type=\"submit\" class=\"button\" name=\"submit\" value=\"CANCEL\" /></td>";
	echo "<td></td>";
	echo "</form>";
	echo "</tr>";
	}
echo "</table>";


if($stop) die();

$village_devanagari_new = $village_english_new = $hamlet_devanagari_new = $hamlet_english_new = $taluka_devanagari_new = $taluka_english_new = $valley_devanagari_new = $valley_english_new = $district_devanagari_new = $district_english_new = $state_new = $corpus_new = $gps_new = '';

if(isset($_POST['action']) AND $_POST['action'] == "delete") {
	$location_id = $_POST['location_id'];
	$location_id_check = $_POST['location_id_check'];
	if($location_id <> $location_id_check)
		echo "<blockquote><font color=red>ERROR: can't delete</font> (‘".$location_id."’ ≠ ‘".$location_id_check."’)<br /></blockquote>";
	else {
		$sql = "SELECT performer_id FROM ".BASE.".performers WHERE location_id = \"".$location_id."\"";
		$result = $bdd->query($sql);
		$n = $result->rowCount();
		if($n > 0) {
			$location_features = location_features($location_id);
			echo "<blockquote><font color=red>Can't delete</font> [".$location_id."] ".$location_features['village_english']." / ".$location_features['village_devanagari']." <font color=red>because it is attributed to the following performers:</font><br /><small>";
			while($ligne = $result->fetch()) {
				$performer_id = $ligne['performer_id'];
				$url = "performer.php?performer_id=".$performer_id;
				echo "<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">".$performer_id."</a> ";
				}
			$result->closeCursor();
			echo "</small></blockquote>";
			}
		else {
			echo "<blockquote><font color=green>Deleting location_id =</font> ".$location_id."</blockquote>";
			$sql_delete = "DELETE FROM ".BASE.".locations WHERE location_id = \"".$location_id."\"";
			$result_delete = $bdd->query($sql_delete);
			if(!$result_delete) {
				echo "<br /><font color=red>".$sql."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result_delete->closeCursor();
			}
		}	
	}
else {	
	if(isset($_POST['location_id']) AND $_POST['location_id'] > 0) {
		$location_id = $_POST['location_id'];
		$village_devanagari = $_POST['village_devanagari'];
		$village_english = $_POST['village_english'];
		$hamlet_devanagari = $_POST['hamlet_devanagari'];
		$hamlet_english = $_POST['hamlet_english'];
		$taluka_devanagari = $_POST['taluka_devanagari'];
		$taluka_english = $_POST['taluka_english'];
		$valley_devanagari = $_POST['valley_devanagari'];
		$valley_english = $_POST['valley_english'];
		$district_devanagari = $_POST['district_devanagari'];
		$district_english = $_POST['district_english'];
		$state = $_POST['state'];
		$corpus = $_POST['corpus'];
		$gps = $_POST['GPS'];
		$date_modified = date("Y-m-d H:i:s");
		if($_POST['new'] == 0)
			$sql_modif = "UPDATE ".BASE.".locations SET village_devanagari = \"".$village_devanagari."\", village_english = \"".$village_english."\", hamlet_devanagari = \"".$hamlet_devanagari."\", hamlet_english = \"".$hamlet_english."\", taluka_devanagari = \"".$taluka_devanagari."\", taluka_english = \"".$taluka_english."\", valley_devanagari = \"".$valley_devanagari."\", valley_english = \"".$valley_english."\", district_devanagari = \"".$district_devanagari."\", district_english = \"".$district_english."\", state = \"".$state."\", corpus = \"".$corpus."\", GPS = \"".$gps."\", date_modified = \"".$date_modified."\", login = \"".$_SESSION['login']."\" WHERE location_id = \"".$location_id."\"";
		if($_POST['new'] == 1)
			$sql_modif = "INSERT INTO ".BASE.".locations (location_id, village_devanagari, village_english, hamlet_devanagari, hamlet_english, taluka_devanagari, taluka_english, valley_devanagari, valley_english, district_devanagari, district_english, state, corpus, GPS, date_modified, login) VALUES (\"".$location_id."\", \"".$village_devanagari."\", \"".$village_english."\", \"".$hamlet_devanagari."\", \"".$hamlet_english."\", \"".$taluka_devanagari."\", \"".$taluka_english."\", \"".$valley_devanagari."\", \"".$valley_english."\", \"".$district_devanagari."\", \"".$district_english."\", \"".$state."\", \"".$corpus."\", \"".$gps."\", \"".$date_modified."\", \"".$_SESSION['login']."\")";
	//	echo $sql_modif."<br />";
		$result_modif = $bdd->query($sql_modif);
		if(!$result_modif) {
			echo "<br /><font color=red>".$sql_modif."<br />";
			echo "ERROR: FAILED</font>";
			die();
			}	
		$result_modif->closeCursor();	
		}
	}

echo "<center>";
echo "<table class=\"village\">";
echo "<tr>";
echo "<th class=\"tight\" style=\"text-align:center;\">id</th><th class=\"tight\">Village Dev.</th><th class=\"tight\">Village Eng.</th><th class=\"tight\">Hamlet Dev.</th><th class=\"tight\">Hamlet Eng.</th><th class=\"tight\">Taluka Dev.</th><th class=\"tight\">Taluka Eng.</th><th class=\"tight\">Valley Dev.</th><th class=\"tight\">Valley Eng.</th><th class=\"tight\">District Dev.</th><th class=\"tight\">District Eng.</th><th class=\"tight\">State</th><th class=\"tight\">Corpus</th><th class=\"tight\">GPS</th><th class=\"tight\">Date</th><th></th><th><small>(Repeat the Id)</small></th>";
echo "</tr>";
$query = "SELECT * FROM ".BASE.".locations ORDER BY village_english";
$result = $bdd->query($query);
$i = 0;
while($ligne = $result->fetch()) {
	$location_id = $ligne['location_id'];
	$village_devanagari = $ligne['village_devanagari'];
	$village_english = $ligne['village_english'];
	$hamlet_devanagari = $ligne['hamlet_devanagari'];
	$hamlet_english = $ligne['hamlet_english'];
	$taluka_devanagari = $ligne['taluka_devanagari'];
	$taluka_english = $ligne['taluka_english'];
	$valley_devanagari = $ligne['valley_devanagari'];
	$valley_english = $ligne['valley_english'];
	$district_devanagari = $ligne['district_devanagari'];
	$district_english = $ligne['district_english'];
	$state = $ligne['state'];
	$corpus = $ligne['corpus'];
	$gps = $ligne['GPS'];
	$date_modified =  date("Y-m-d",strtotime($ligne['date_modified']));
	$user = $ligne['login'];
	$query2 = "SELECT location_id FROM ".BASE.".songs WHERE location_id=\"".$location_id."\"";
	$result2 = $bdd->query($query2);
	$n_songs = $result2->rowCount();
	$result2->closeCursor();
	if(isset($hilite[$location_id])) $color = "Gold";
	else $color = "Bisque";
	echo "<tr style=\"background-color:".$color.";\">";
	echo "<td class=\"tight\">".$location_id."</td>";
	echo "<FORM ACTION = \"edit-villages.php\" METHOD = \"POST\">";
	echo "<input type=\"hidden\" name=\"location_id\" value=\"".$location_id."\">";
	echo "<input type=\"hidden\" name=\"new\" value=\"0\">";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"village_devanagari\" value=\"".$village_devanagari."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"village_english\" value=\"".$village_english."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"hamlet_devanagari\" value=\"".$hamlet_devanagari."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"hamlet_english\" value=\"".$hamlet_english."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"18\" name=\"taluka_devanagari\" value=\"".$taluka_devanagari."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"18\" name=\"taluka_english\" value=\"".$taluka_english."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"12\" name=\"valley_devanagari\" value=\"".$valley_devanagari."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"12\" name=\"valley_english\" value=\"".$valley_english."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"district_devanagari\" value=\"".$district_devanagari."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"12\" name=\"district_english\" value=\"".$district_english."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"13\" name=\"state\" value=\"".$state."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"18\" name=\"corpus\" value=\"".$corpus."\" /></td>";
	echo "<td class=\"tight\"><input maxlength=\"99\" size=\"10\" name=\"GPS\" value=\"".$gps."\" /></td>";
	echo "<td class=\"tight\" style=\"white-space:nowrap;\"><small>".$date_modified." ".$user."</small></td>";
	echo "<td class=\"tight\" bgcolor=red><input type=\"submit\" class=\"button\" name=\"submit\" value=\"SAVE\" /></td>";
	echo "</form>";
	echo "<FORM ACTION = \"edit-villages.php\" METHOD = \"POST\">";
	echo "<input type=\"hidden\" name=\"location_id\" value=\"".$location_id."\">";
	echo "<td class=\"tight\" style=\"white-space:nowrap;\">";
	if($n_songs > 0) echo "<div style=\"display:none;\">";
	else echo "<div>";
	echo "<input type=\"submit\" class=\"button\" name=\"delete\" value=\"DELETE\" />";
	echo "&nbsp;<input size=\"4\" name=\"location_id_check\" value=\"\" />";
	echo "</div>";
	echo "</td>";
	echo "<input type=\"hidden\" name=\"action\" value=\"delete\">";
	echo "</form>";
	echo "</tr>";
	}
$result->closeCursor();
$query = "SELECT MAX(location_id) AS location_id FROM ".BASE.".locations";
$result = $bdd->query($query);
$location_id_new = $result->fetchColumn() + 1;
$result->closeCursor();
echo "<tr style=\"background-color:Gold;\">";
echo "<td class=\"tight\"><font color=red>".$location_id_new."</font></td>";
echo "<FORM ACTION = \"edit-villages.php\" METHOD = \"POST\">";
echo "<input type=\"hidden\" name=\"location_id\" value=\"".$location_id_new."\">";
echo "<input type=\"hidden\" name=\"new\" value=\"1\">";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"village_devanagari\" value=\"".$village_devanagari_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"village_english\" value=\"".$village_english_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"hamlet_devanagari\" value=\"".$hamlet_devanagari_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"hamlet_english\" value=\"".$hamlet_english_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"18\" name=\"taluka_devanagari\" value=\"".$taluka_devanagari_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"18\" name=\"taluka_english\" value=\"".$taluka_english_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"12\" name=\"valley_devanagari\" value=\"".$valley_devanagari_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"12\" name=\"valley_english\" value=\"".$valley_english_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"15\" name=\"district_devanagari\" value=\"".$district_devanagari_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"12\" name=\"district_english\" value=\"".$district_english_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"13\" name=\"state\" value=\"".$state_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"18\" name=\"corpus\" value=\"".$corpus_new."\" /></td>";
echo "<td class=\"tight\"><input maxlength=\"99\" size=\"10\" name=\"GPS\" value=\"".$gps_new."\" /></td>";
echo "<td bgcolor=red><input type=\"submit\" class=\"button\" name=\"submit\" value=\"NEW ENTRY\" /></td>";
echo "</form></tr>";
echo "<tr>";
echo "<th></th><th class=\"tight\">Village Dev.</th><th class=\"tight\">Village Eng.</th><th class=\"tight\">Hamlet Dev.</th><th class=\"tight\">Hamlet Eng.</th><th class=\"tight\">Taluka Dev.</th><th class=\"tight\">Taluka Eng.</th><th class=\"tight\">Valley Dev.</th><th class=\"tight\">Valley Eng.</th><th class=\"tight\">District Dev.</th><th class=\"tight\">District Eng.</th><th class=\"tight\">State</th><th class=\"tight\">Corpus</th><th class=\"tight\">GPS</th>";
echo "</tr>";
echo "</table>";
echo "</center>";
echo "<a name=\"bottom\"></a>";
echo "[<a href=\"#top\">Top of page</a>]";
echo "</body>";
echo "</html>";
?>