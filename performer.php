<?php
// session_start();
require_once("_base_urls.php");
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '')
	require_once("_relier_edit.php");
	// user is allowed to write
else require_once("_relier.php");
	// user only allowed to read
require_once("_tasks.php");

if(isset($_GET['mode'])) $mode = $_GET['mode'];
else $mode = '';
if(isset($_GET['performer_id'])) $performer_id = $_GET['performer_id'];
else $performer_id = "all";
if(isset($_GET['merge_id'])) $merge_id = $_GET['merge_id'];
else $merge_id = 0;
if(isset($_GET['location_id'])) $location_id = $_GET['location_id'];
else $location_id = 0;
if(isset($_GET['sort'])) $sort = $_GET['sort'];
else $sort = "English";

if(isset($_GET['performer_name_english'])) $performer_name_english = trim($_GET['performer_name_english']);
else $performer_name_english = '';
if(isset($_GET['performer_name_devanagari'])) $performer_name_devanagari = trim($_GET['performer_name_devanagari']);
else $performer_name_devanagari = '';

if(isset($_POST['performer_name_english'])) $performer_name_english = trim($_POST['performer_name_english']);
if(isset($_POST['performer_name_devanagari'])) $performer_name_devanagari = trim($_POST['performer_name_devanagari']);

$performer_created = FALSE; 
$performer_created_msg = '';

if(is_editor($login) AND $merge_id > 0) {
	$merge_list = array();
	$query = "SELECT * FROM ".BASE.".performers WHERE performer_id = \"".$merge_id."\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	if($result) $result->closeCursor();
	$thislocation_id = $ligne['location_id'];
	$location_features = location_features($thislocation_id);
	$thisvillage = $location_features['village_english'];
	$thishamlet = $location_features['hamlet_english'];
	$thisname_english = $ligne['performer_name_english'];
	$thisname_devanagari = $ligne['performer_name_devanagari'];
	$performer_gender = $ligne['performer_gender'];
	$performer_caste_english = $ligne['performer_caste_english'];
	$performer_caste_devanagari = $ligne['performer_caste_devanagari'];
	$performer_biodata_marathi = $performer_biodata_english = '';
	$photograph = "no"; $merge_to = $merge_id; $merge_picture = '';
	echo "<br />➡ Merging ".$thisname_english." - ".$thisvillage." / ".$thishamlet."<br />";
	$query = "SELECT * FROM ".BASE.".performers WHERE performer_name_english = \"".$thisname_english."\" AND location_id = \"".$thislocation_id."\"";
	$result = $bdd->query($query);
	while($ligne = $result->fetch()) {
		$performer_id = $ligne['performer_id'];
		$merge_list[] = $performer_id ;
		if($performer_biodata_marathi <> '') $performer_biodata_marathi .= "<br />";
		if($performer_biodata_english <> '') $performer_biodata_english .= "<br />";
		$performer_biodata_marathi .= $ligne['performer_biodata_marathi'];
		$performer_biodata_english .= $ligne['performer_biodata_english'];
		$performer_picture = $ligne['performer_picture'];
		$performer_picture_url = performer_picture_url($performer_id,$performer_picture);
		if($performer_picture_url <> '') {
			if(!$photograph) {
				$photograph = "yes";
				$merge_to = $performer_id;
				$performer_photo_credit = $ligne['performer_photo_credit'];
				$merge_picture = $performer_picture;
				}
			else {
				echo "<p>Duplicate photograph: <a target=\"_blank\" href=\"".$performer_picture_url."\">pict</a> (".$performer_id.") Cannot merge!</p>";
				$merge_to = 0;
				}
			}
		}
	if($result) $result->closeCursor();
	if($merge_to > 0) {
		// Update performer ids to merge_to
		foreach($merge_list AS $merge_list_id) {
			if($merge_list_id == $merge_to) continue;
	//		echo $merge_list_id."<br />";
			$query_update = "UPDATE ".BASE.".songs SET performer_id = \"".$merge_to."\" WHERE performer_id = \"".$merge_list_id."\"";
	//		echo $query_update;
			$result_update = $bdd->query($query_update);
			if(!$result_update) {
				echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_update."<br />";
				}
			else $result_update->closeCursor();
			}
		// Merge metadata to the record which is kept
		$picture_url = '';
		if($picture == "yes") $picture_url = $picture;
		$query_merge = "UPDATE ".BASE.".performers SET location_id = \"".$thislocation_id."\", performer_name_english = \"".$thisname_english."\", performer_name_devanagari = \"".$thisname_devanagari."\", performer_gender = \"".$performer_gender."\", performer_caste_english = \"".$performer_caste_english."\", performer_caste_devanagari = \"".$performer_caste_devanagari."\", performer_biodata_marathi = \"".$performer_biodata_marathi."\", performer_biodata_english = \"".$performer_biodata_english."\", performer_picture = \"".$performer_picture."\", performer_photo_credit = \"".$performer_photo_credit."\" WHERE performer_id = \"".$merge_to."\"";
	//	echo $query_merge;
		$result_merge = $bdd->query($query_merge);
		if(!$result_merge) {
			echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_merge."<br />";
			}
		else $result_merge->closeCursor();
		// Delete duplicate records
		foreach($merge_list AS $merge_list_id) {
			if($merge_list_id == $merge_to) continue;
			echo "<p>Deleted #".$merge_list_id."</p>";
			$query_delete = "DELETE FROM ".BASE.".performers WHERE performer_id = \"".$merge_list_id."\"";
		//	echo $query_delete;
			$result_delete = $bdd->query($query_delete);
			if(!$result_delete) {
				echo "<br /><span style=\"color:red;\">ERROR modifying table:</span> ".$query_delete."<br />";
				}
			else $result_delete->closeCursor();
			}
		}
	$performer_id = "all";
	}

if(is_editor($login) AND isset($_POST['create_performer']) AND $_POST['create_performer'] == "yes") {
	$query = "SELECT MAX(performer_id) AS performer_id FROM ".BASE.".performers";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	if($result) $result->closeCursor();
	$new_performer_id = $ligne['performer_id'] + 1;
	$performer_created = TRUE;
	$performer_created_msg = "<font color=red>➡ Created new performer</font> #<a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."performer.php?performer_id=".$new_performer_id."\">".$new_performer_id."</a>";
	$date = date("Y-m-d");
	$new_name_english = trim($_POST['new_name_english']);
	$new_name_devanagari = trim($_POST['new_name_devanagari']);
	$new_gender = trim($_POST['new_gender']);
	if($new_gender <> 'F' AND $new_gender <> 'M') {
		if($new_gender <> '') $performer_created_msg .= "<br /><font color=red>ERROR: gender:</font> ‘".$new_gender."’";
		$new_gender = 'F';
		}
	$new_caste_english = trim($_POST['new_caste_english']);
	$new_caste_devanagari = trim($_POST['new_caste_devanagari']);
	$new_picture = trim($_POST['new_picture']);
	$new_photo_credit = trim($_POST['new_photo_credit']);
	$new_biodata_marathi = trim($_POST['new_biodata_marathi']);
	$new_biodata_english = trim($_POST['new_biodata_english']);
	$new_location_id = intval($_POST['new_location_id']);
	
	$location_features = location_features($new_location_id);
	if($location_features['village_english'] == '') {
		$performer_created_msg .= "<br /><font color=red>ERROR: unknown location:</font> #".$new_location_id;
		$new_location_id = 0;
		}
	$query_update = "INSERT INTO ".BASE.".performers (performer_id, performer_name_english, performer_name_devanagari, performer_gender, performer_caste_english, performer_caste_devanagari, performer_biodata_marathi, performer_biodata_english, performer_picture, performer_photo_credit, location_id, date_modified, login) VALUES (\"".$new_performer_id."\",\"".$new_name_english."\",\"".$new_name_devanagari."\",\"".$new_gender."\",\"".$new_caste_english."\",\"".$new_caste_devanagari."\",\"".$new_biodata_marathi."\",\"".$new_biodata_english."\",\"".$new_picture."\",\"".$new_photo_credit."\",\"".$new_location_id."\",\"".$date."\",\"".$login."\")";
	$result_update = $bdd->query($query_update);
	if(!$result_update) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query_update."<br />";
		die();
		}
	$result_update->closeCursor();
	}

if(is_editor($login) AND isset($_POST['delete_performer']) AND $_POST['delete_performer'] > 0) {
	$delete_performer_id = $_POST['delete_performer'];
	$query_delete = "DELETE FROM ".BASE.".performers WHERE performer_id = \"".$delete_performer_id."\"";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query_delete."<br />";
		die();
		}
	else $result_delete->closeCursor();
	$query_delete = "DELETE FROM ".BASE.".stories WHERE performer_id = \"".$delete_performer_id."\"";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query_delete."<br />";
		die();
		}
	else $result_delete->closeCursor();
	$query_delete = "DELETE FROM ".BASE.".song_metadata WHERE performer_id = \"".$delete_performer_id."\"";
	$result_delete = $bdd->query($query_delete);
	if(!$result_delete) {
		echo "<br /><font color=red>ERROR modifying table:</font> ".$query_delete."<br />";
		die();
		}
	else $result_delete->closeCursor();
	}

$query = $canonic_url = '';
$all_performers = FALSE;

if($mode == '') {
	if($performer_id > 0) {
		$query = "SELECT * FROM ".BASE.".performers WHERE performer_id = \"".$performer_id."\"";
		$canonic_url = SITE_URL."performer.php?performer_id=".$performer_id;
		}
	if($performer_id == "all") {
		$query = "SELECT * FROM ".BASE.".performers";
		if($sort == "English") $query .= " ORDER BY performer_name_english";
		if($sort == "Marathi") $query .= " ORDER BY performer_name_devanagari";
		if($sort == "location") $query .= " ORDER BY location_id";
		$canonic_url = SITE_URL."performer.php?performer_id=".$performer_id."&sort=".$sort;

		$all_performers = TRUE;
		}
	if($location_id > 0) {
		$query = "SELECT * FROM ".BASE.".performers WHERE location_id = \"".$location_id."\"";
		$canonic_url = SITE_URL."performer.php?location_id=".$location_id;
		}
	if($performer_name_english <> '') {
		$query = "SELECT * FROM ".BASE.".performers WHERE performer_name_english LIKE \"".$performer_name_english."%\" ORDER BY performer_name_english";
		$canonic_url = SITE_URL."performer.php?performer_name_english=".$performer_name_english;
		}
	if($performer_name_devanagari <> '') {
		$query = "SELECT * FROM ".BASE.".performers WHERE performer_name_devanagari LIKE \"".$performer_name_devanagari."%\" ORDER BY performer_name_devanagari";
		$canonic_url = SITE_URL."performer.php?performer_name_devanagari=".$performer_name_devanagari;
		}
	
	$n = 0;
	if($query <> '') {
		$result = $bdd->query($query);
		$n = $result->rowCount();
		$ligne = $result->fetch();
		if($result) $result->closeCursor();
		}
	}
// $name = $ligne['performer_name_english'];
$name = "Performer(s)";

if(isset($_SESSION['login'])) $login = $_SESSION['login'];
else $login = '';

require_once("_header.php");

if($mode == "create") {
	if(!identified()) {
		echo "<font color=red>You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</font>"; 
		die();
		}
	$_SESSION['try'] = 0;
	$old_time = time() - 3600;
	$sql = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
	$result = $bdd->query($sql);
	if($result) $result->closeCursor();
	}
else {
	if($n == 0) {
		echo "<font color=red>No performer found!</font>"; die();
		}
	if($all_performers) echo "<h2><br />Grindmill songs of Maharashtra — ".$n." performers</h2>";
	else {
		echo "<h2><br />Grindmill songs of Maharashtra — Performer";
		if($performer_id > 0) 
			echo "<br />“".$ligne['performer_name_english']."”<br />";
		else if($performer_name_english <> '')
			echo "(s) “".$performer_name_english."…”<br />";
		else if($performer_name_devanagari <> '')
			echo "(s) “".$performer_name_devanagari."…”<br />";
		else if($location_id > 0)
			echo "(s) in location [".$location_id."]<br />";
		echo $n." record(s)<br />&nbsp;<br />&nbsp;</h2>";
		}
		
	set_time_limit(600);
	echo "<table>";
	// $result = mysql_query($query);
	$result = $bdd->query($query);
	$old_person = '';
	if($all_performers) {
		echo "<tr style=\"background-color:ghostwhite;\">";
		if(identified()) echo "<td></td>";
		echo "<td>Songs</td><td>Name (English";
		if($sort <> "English") echo " <a href=\"performer.php?performer_id=all&sort=English\">sort</a>";
		echo ")</td><td>Name (Marathi";
		if($sort <> "Marathi") echo " <a href=\"performer.php?performer_id=all&sort=Marathi\">sort</a>";
		echo ")</td><td>Location (<a href=\"performer.php?performer_id=all&sort=location\">sort</a>)</td><td>Biodata (Marathi)</td><td>Biodata (English)</td>";
		echo "</tr>";
		}
	while($ligne = $result->fetch()) {
		echo "<tr>";
		$performer_id = $ligne['performer_id'];
		$location_id = $ligne['location_id'];
		$location_features = location_features($location_id);
		$query_count = "SELECT count(*) from ".BASE.".songs WHERE performer_id = \"".$performer_id."\"";
		$result_count = $bdd->query($query_count);
		$number_songs = $result_count->fetchColumn();
		if($result_count) $result_count->closeCursor();
		$query_recordings = "SELECT song_id FROM ".BASE.".songs WHERE performer_id = \"".$performer_id."\" AND recording_DAT_index <> \"\"";
		$result_recordings = $bdd->query($query_recordings);
		$number_recordings = $result_recordings->rowCount();
		if($result_recordings) $result_recordings->closeCursor();
	//	$performer_picture_url = performer_picture_url($performer_id,$ligne['performer_picture']);
		if($all_performers) {
			if(trim($ligne['performer_name_english']) == '') continue;
			$village = $location_features['village_english'];
			if($location_features['hamlet_english'] <> '') $village .= " / ".$location_features['hamlet_english'];
		/*	echo "<td>";
			if($performer_picture_url <> '') echo "<a href=\"".$performer_picture_url."\" target=\"_blank\">pict</a>";
			echo "</td>"; */
			$name_english = $ligne['performer_name_english'];
			$url_edit = "edit-performer.php?performer_id=".$performer_id;
			$url_view = "performer.php?performer_id=".$performer_id;
			$url_merge = "performer.php?merge_id=".$performer_id;
			$url_location = "location.php?location_id=".$location_id;
			if(identified()) {
				echo "<td style=\"white-space:nowrap;\"><small>";
				echo "(<a target=\"_blank\" title=\"".$url_edit."\" href=\"".$url_edit."\">Edit</a>) #".$performer_id;
				$new_person = str_replace(' ','',$name_english.$village);
				if($new_person == $old_person) echo " <font color=\"red\">?</font><br />➡ <a target=\"_blank\" title=\"".$url_merge."\" href=\"".$url_merge."\">merge</a>";
				echo "</small></td>";
				}
			echo "<td style=\"white-space:nowrap;\"><small>";
			if($number_songs > 0) {
				echo "<a target=\"_blank\" title=\"Songs by this performer\" href=\"".SITE_URL."songs.php?performer_id=".$performer_id."\">".$number_songs." songs</a>";
				if($number_recordings > 0) echo "&nbsp;".SOUND_ICON;
				}
			echo "</small></td>";
			$start_english = extractWords($ligne['performer_biodata_english'],4);
			$start_devanagari =	extractWords($ligne['performer_biodata_marathi'],4);
			echo "<td style=\"white-space:nowrap; background-color:Cornsilk;\"><a target=\"_blank\" title=\"".$url_view."\" href=\"".$url_view."\">".$name_english."</a></td>";
			echo "<td style=\"white-space:nowrap; background-color:Cornsilk;\"><a target=\"_blank\" title=\"".$url_view."\" href=\"".$url_view."\">".$ligne['performer_name_devanagari']."</a></td>";
			echo "<td style=\"white-space:nowrap;\"><small><a target=\"_blank\" title=\"".$url_location."\" href=\"".$url_location."\">".$village."</a></small></td>";
			echo "<td style=\"white-space:nowrap;\"><small>".$start_devanagari."</small></td>";
			echo "<td style=\"white-space:nowrap;\"><small>".$start_english."</small></td>";
			$old_person = str_replace(' ','',$name_english.$village);
			}
		else {
			echo "<td valign=\"top\" nowrap align=\"center\" style=\"text-align:center\">";
			if($performer_picture_url <> '') {
				echo "<a href=\"".$performer_picture_url."\" target=\"_blank\"><img src=\"".$performer_picture_url."\" width=\"180\" alt=\"photo\"/></a><br />";
				echo "<small>Credit: ".$ligne['performer_photo_credit']."</small><br /><br />";
				}
			echo "<table border=\"1\" cellpadding=\"4\" align=\"center\"><tr><td style=\"text-align:center\">";
			echo "<small>[<a target=\"_blank\" title=\"Record of this performer\" href=\"".SITE_URL."performer.php?performer_id=".$ligne['performer_id']."\">".$ligne['performer_id']."</a>]</small><br /><b><big>".$ligne['performer_name_devanagari']."</big><br />".$ligne['performer_name_english']."</b><br />";
			echo "</td></tr></table><br />";
			echo "Cast: ".$ligne['performer_caste_devanagari']." / ".$ligne['performer_caste_english']."<br />";	
			echo "Village: <a target=\"_blank\" title=\"Details of all locations in this village\" href=\"".SITE_URL."location.php?location_id=".$location_id."\">".$village_devanagari." / ".$village."</a><br />";
			if($location_features['hamlet_devanagari'] <> '')
				echo "Hamlet: ".$location_features['hamlet_devanagari']." / ".$location_features['hamlet_english']."<br />";
			if($location_features['taluka_devanagari'] <> '')
				echo "Taluka: ".$location_features['taluka_devanagari']." / ".$location_features['taluka_english']."<br />";
			if($location_features['district_devanagari'] <> '')
				echo "District: ".$location_features['district_devanagari']." / ".$location_features['district_english']."<br />";
			if($location_features['valley_devanagari'] <> '')
				echo "Valley: ".$location_features['valley_devanagari']." / ".$location_features['valley_english']."<br />";
			echo "Gender: ".$ligne['performer_gender']."";
			$url = "edit-performer.php?performer_id=".$performer_id;
			if(identified())
				echo "<br /><br />[<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Edit biodata…</a>]";
			if($number_songs > 0) {
				echo "<p style=\"text-align:center\"><b><a target=\"_blank\" title=\"Songs by this performer\" href=\"".SITE_URL."songs.php?performer_id=".$performer_id."\">Songs by ".$ligne['performer_name_english']."</a></b> (".$number_songs.")";
				if($number_recordings > 0) echo "&nbsp;".SOUND_ICON;
				echo "</p>";
				}
			echo "</td>";
			echo "<td lang=\"mr\" style=\"min-width:400px;\">";
			$url = "edit-performer.php?performer_id=".$performer_id;
			echo $ligne['performer_biodata_marathi'];
			if(identified()) {
				if($ligne['performer_biodata_marathi'] <> '')
					echo "&nbsp;[<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Edit…</a>]";
				else echo "<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Create Marathi biodata…</a>";
				}
			echo "</td>";
			echo "<td lang=\"en\" style=\"min-width:400px;\">";
			echo $ligne['performer_biodata_english'];
			if(identified()) {
				if($ligne['performer_biodata_english'] <> '')
					echo "&nbsp;[<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Edit…</a>]";
				else echo "<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Create English biodata…</a>";
				}
			echo "</td>";
			}
		echo "</tr>";
		if(!$all_performers) echo "<tr><td colspan=\"3\"><hr></td></tr>";
		}
	if($result) $result->closeCursor();
	echo "</table>";
	}

if(is_editor($login)) {
	if($performer_created) echo $performer_created_msg;
	$flag = "recent_entries";
	echo "<h3>Create a new performer</h3>";
	$url_this_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1)."?".$_SERVER['QUERY_STRING'];
	echo "<table>";
//	echo "<form name=\"create_performer\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
	echo "<form name=\"create_performer\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
	echo "<tr>";
	echo "<input type=\"hidden\" name=\"create_performer\" value=\"yes\">";
	echo "<td class=\"tight\" colspan=\"2\" style=\"background-color:Cornsilk; padding:12px;\">";
	echo "Name (English): <input type='text' name='new_name_english' size='50'><br />";
	echo "Name (Marathi): <input type='text' name='new_name_devanagari' size='50'><br />";
	echo "Gender (M/F): <input type='text' name='new_gender' size='1' value=\"F\"><br />";
	echo "Caste (English): <input type='text' name='new_caste_english' size='50'><br />";
	echo "Caste (Marathi): <input type='text' name='new_caste_devanagari' size='50'><br />";
	echo "Location ID: <input type='text' name='new_location_id' size='4'> (<a target=\"_blank\" href=\"villages.php\">Link to all villages</a>)<br />";
	echo "Photograph (yes/no): <input type='text' name='new_picture' size='3' value=\"no\"><br />";
	echo "Photograph credit: <input type='text' name='new_photo_credit' size='30'>";
	echo "</td>";
	echo "<td class=\"tight\" rowspan=\"2\" style=\"background-color:Cornsilk;\">";
	echo "<input type=\"submit\" class=\"button\" value=\"CREATE\">";
	echo "</td>";
	echo "</tr>";
	echo "<tr>";
	echo "<td class=\"tight\" rowspan=\"2\" style=\"background-color:Cornsilk; padding:12px;\">";
	echo "<b>Biodata (Marathi):</b><br />";
	echo "<textarea name=\"new_biodata_marathi\" ROWS=\"15\" style=\"width:300px;\">";
	echo "</textarea>";
	echo "</td>";
	echo "<td class=\"tight\" rowspan=\"2\" style=\"background-color:Cornsilk; padding:12px;\">";
	echo "<b>Biodata (English):</b><br />";
	echo "<textarea name=\"new_biodata_english\" ROWS=\"15\" style=\"width:300px;\">";
	echo "</textarea>";
	echo "</td>";
	echo "</tr>";
	echo "</form>";
	echo "</table>";
	
	echo "<a name=\"".$flag."\"></a>";
	$date = date("Y-m-d");
	$title = "My recent entries";
	$old = FALSE;
	if(isset($_POST['old_entries'])) {
		$date = "0000-00-00";
		$title = "My old entries";
		$old = TRUE;
		}
	$query = "SELECT * FROM ".BASE.".performers WHERE login = \"".$login."\" AND date_modified >= \"".$date."\" ORDER BY date_modified DESC";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "<blockquote>";
		if(!$old) {
			echo "<form name=\"old_entries\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"old_entries\" value=\"yes\">";
			echo "<input type=\"submit\" class=\"button\" value=\"DISPLAY MY OLD ENTRIES\">";
			echo "</form>";
			}
		else echo "<font color=red>No old entries in my name</font>";
		echo "</blockquote>";
		}
	else {
		echo "<h3>".$title."</h3>";
		echo "<table>";
		$color = "Cornsilk";
		while($ligne=$result->fetch()) {
			$performer_id = $ligne['performer_id'];
			echo "<tr>";
			echo "<form name=\"my_entries\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"delete_performer\" value=\"".$performer_id."\">";
			if($old) echo "<input type=\"hidden\" name=\"old_entries\" value=\"yes\">";
			echo "<td class=\"tight\"><a target=\"_blank\" title=\"Edit details\" href=\"".SITE_URL."edit-performer.php?performer_id=".$performer_id."\">".$performer_id."</a></td>";
			echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:200px;\">".$ligne['performer_name_devanagari']."<br />".$ligne['performer_name_english']."</td>";
			$performer_picture = $ligne['performer_picture'];
			$performer_picture_url = performer_picture_url($performer_id,$performer_picture);
			if($performer_picture_url <> '') $photograph = "yes";
			else $photograph = "no";
			$performer_photo_credit = $ligne['performer_photo_credit'];
			$location_id = $ligne['location_id'];
			$location_features = location_features($location_id);
			echo "<td class=\"tight\" style=\"background-color:".$color."; min-width:500px;\">";
			if($photograph == "yes") {
				echo "<div style=\"float:right;\">";
				echo "<a href=\"".$performer_picture_url."\" target=\"_blank\"><img src=\"".$performer_picture_url."\" width=\"180\" alt=\"photo\"/></a><br />";
				echo "<small>Credit: ".$performer_photo_credit."</small><br /><br />";
				echo "</div>";
				}
			$village = $location_features['village_english'];
			if($village == '') {
				echo "<font color=red>Unknown location</font><br />";
				}
			else {
				echo "<ul>";
				$village_devanagari = $location_features['village_devanagari'];
				echo "<li>Village: ".$village_devanagari." / ".$village."</li>";
				if($location_features['hamlet_devanagari'] <> '')
					echo "<li>Hamlet: ".$location_features['hamlet_devanagari']." / ".$location_features['hamlet_english']."</li>";
				if($location_features['taluka_devanagari'] <> '')
					echo "<li>Taluka: ".$location_features['taluka_devanagari']." / ".$location_features['taluka_english']."</li>";
				if($location_features['district_devanagari'] <> '')
					echo "<li>District: ".$location_features['district_devanagari']." / ".$location_features['district_english']."</li>";
				if($location_features['valley_devanagari'] <> '')
					echo "<li>Valley: ".$location_features['valley_devanagari']." / ".$location_features['valley_english']."</li>";
				echo "</ul>";
				}
			echo "</td>";
			
			echo "<td class=\"tight\" style=\"text-align:center; background-color:".$color.";\"><input type=\"submit\" class=\"button\" value=\"DELETE\">";
			echo "</form>";
			echo "</tr>";
			}
		echo "</table>";
		if($result) $result->closeCursor();
		if(!$old) {
			echo "<blockquote><form name=\"old_entries\" method=\"post\" action=\"".$url_this_page."#".$flag."\" enctype=\"multipart/form-data\">";
			echo "<input type=\"hidden\" name=\"old_entries\" value=\"yes\">";
			echo "<input type=\"submit\" class=\"button\" value=\"DISPLAY MY OLD ENTRIES\">";
			echo "</form></blockquote>";
			}
		}
	}

echo "</body>";
echo "</html>";
?>