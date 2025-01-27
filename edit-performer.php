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

$name = "Edit performer";
$canonic_url = '';
$mssg = $upload_message = '';

if(isset($_GET['performer_id'])) $performer_id = $_GET['performer_id'];
else $performer_id = 0;

$query = "SELECT * FROM ".BASE.".performers where performer_id = \"".$performer_id."\"";
/* $result = mysql_query($query);
$n = mysql_num_rows($result); */
$result = $bdd->query($query);
$n = $result->rowCount();
if($n == 0) {
	echo "<font color=red>Error: unknown performer</font>";
	die();
	}
// $ligne = mysql_fetch_array($result);
$ligne = $result->fetch();
$result->closeCursor();
$fullname = $ligne['performer_name_devanagari']." - ".$ligne['performer_name_english'];
		
$name = "Edit ".$fullname;
$url_performer = "performer.php?performer_id=".$performer_id;
	
require_once("_header.php");

echo "<h2>Edit performer [".$performer_id."] biodata<br /><a target=\"_blank\" title=\"".$url_performer."\" href=\"".$url_performer."\">".$fullname."</a></h2>";

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

// EXPORT CHANGES
/* if(isset($_POST['pwd_export'])) $pwd_export = $_POST['pwd_export'];
else $pwd_export = 'nil';
if($pwd_export == $good_one[$login]) {
	$message = ExportPerformers();
	echo "<blockquote><br /><br />".$message."</blockquote>";
	} */

if(isset($_POST['delete_picture'])) {
	delete_picture("performer",$performer_id);
	unset($_POST['change_metadata']);
	}

$query = "SELECT * FROM ".BASE.".performers WHERE performer_id = \"".$performer_id."\"";
$result = $bdd->query($query);
$ligne = $result->fetch();
// $result->closeCursor();

// UPDATE METADATA
if(isset($_POST['change_metadata']) AND $_POST['change_metadata'] == "yes") {
	if(isset($_FILES["file"]) AND $_FILES["file"]["name"] <> '') {
		$table = explode(".",$_FILES["file"]["name"]);
		$extension = strtolower(end($table));
		if(($_FILES["file"]["size"] < MAXFILESIZE) AND in_array($extension,$allowedExts)) {
			if($_FILES["file"]["error"] > 0) {
				$upload_message .= "<span style=\"color:red;\">Error: ".$_FILES["file"]["error"]."</span><br />";
				}
			else {
				$filename = $performer_id.".".$extension;
				$path = PICT_PATH2."performers/".$filename;
				$upload_message .= "<span style=\"color:red;\">Uploaded ".$path."<br />Type: ".$_FILES["file"]["type"]."<br />Size ".($_FILES["file"]["size"] / 1024) ." Kb</span>";
				move_uploaded_file($_FILES["file"]["tmp_name"],$path);
				$_POST['performer_picture'] = "yes";
				resize_image($performer_id,400,"performer");
				
				$mailfrom = "bernarbel@gmail.com";
				$mailto = "bernarbel@gmail.com";
				$subject = "[Grindmill database] Uploaded performer's portrait";
				$url_this_page = "https://ccrss.org/database/performer.php?performer_id=".$performer_id;
				$message = "A new picture has been uploaded by ".$_SESSION['login']." for performer #".$performer_id.": <a href=\"".$url_this_page."\">".$fullname."</a>";
// echo $message;
				send_mail($mailfrom,$_SESSION['login'],$mailto,$subject,$message);
				}
			}
		else {
			$upload_message .= "<span style=\"color:red;\">File is not accepted: ".$_FILES["file"]["name"]."<br />(It should be ".(MAXFILESIZE / 1000000)." Mb in JPEG or PNG format)</span>";
			}
		}
	
	$ok_storing = TRUE; $changed = FALSE;
	echo "<blockquote><small>Metadata is stored when modified…<br /><font color=red>";
	if(($name_english=trim($_POST['name_english'])) <> '') {
		if(strlen($name_english) > 149) {
			echo "ERROR: Name (English) should be less than 150 chars!<br />";
			$ok_storing = FALSE;
			}
		if($name_english <> $ligne['performer_name_english']) {
			$changed = TRUE;
			echo "Name (English) = ".$name_english." —> replacing “".$ligne['performer_name_english']."”<br />";
			}
		}
	else {
		echo "ERROR: Name (English) is empty!<br />";
		$ok_storing = FALSE;
		}
	if(($name_devanagari=trim($_POST['name_devanagari'])) <> '') {
		if(strlen($name_devanagari) > 149) {
			echo "ERROR: Name (Marathi) should be less than 150 chars!<br />";
			$ok_storing = FALSE;
			}
		if($name_devanagari <> $ligne['performer_name_devanagari']) {
			$changed = TRUE;
			echo "Name (Marathi) = ".$name_devanagari." —> replacing “".$ligne['performer_name_devanagari']."”<br />";
			}
		}
	else {
		echo "ERROR: Name (Marathi) is empty!<br />";
		$ok_storing = FALSE;
		}
	if(($gender=strtoupper(trim($_POST['gender']))) <> '') {
		if($gender <> 'M' AND $gender <> 'F') {
			echo "ERROR: Gender should be ‘M’ or ‘F’!<br />";
			$ok_storing = FALSE;
			}
		if($gender <> $ligne['performer_gender']) {
			$changed = TRUE;
			echo "Gender = ‘".$gender."’ —> replacing “".$ligne['performer_gender']."”<br />";
			}
		}
	else {
		echo "ERROR: Gender is empty!<br />";
		$ok_storing = FALSE;
		}
	if(($caste_english=trim($_POST['caste_english'])) <> '') {
		if(strlen($caste_english) > 99) {
			echo "ERROR: Caste (English) should be less than 100 chars!<br />";
			$ok_storing = FALSE;
			}
		if($caste_english <> $ligne['performer_caste_english']) {
			$changed = TRUE;
			echo "Caste (English) = ".$caste_english." —> replacing “".$ligne['performer_caste_english']."”<br />";
			}
		}
	else {
		echo "ERROR: Caste (English) is empty!<br />";
		$ok_storing = FALSE;
		}
	if(($caste_devanagari=trim($_POST['caste_devanagari'])) <> '') {
		if(strlen($caste_devanagari) > 99) {
			echo "ERROR: Caste (Marathi) should be less than 100 chars!<br />";
			$ok_storing = FALSE;
			}
		if($caste_devanagari <> $ligne['performer_caste_devanagari']) {
			$changed = TRUE;
			echo "Caste (Marathi) = ".$caste_devanagari." —> replacing “".$ligne['performer_caste_devanagari']."”<br />";
			}
		}
	else {
		echo "ERROR: Caste (Marathi) is empty!<br />";
		$ok_storing = FALSE;
		}
	if(($performer_location_id=intval($_POST['performer_location_id'])) > 0) {
		if($performer_location_id > 9999) {
			echo "ERROR: Location ID should be an integer in range 1 … 9999<br />";
			$ok_storing = FALSE;
			}
		if($performer_location_id <> $ligne['location_id']) {
			$changed = TRUE;
			echo "Location ID = ‘".$performer_location_id."’ —> replacing “".$ligne['location_id']."”<br />";
			}
		}
	else {
		echo "ERROR: Location ID is empty!<br />";
		$ok_storing = FALSE;
		}
	$performer_picture_url = performer_picture_url($performer_id,$ligne['performer_picture']);
	if($performer_picture_url <> '') $oldpicture = "yes";
	else $oldpicture = "no";
	if(($picture=strtolower(trim($_POST['performer_picture']))) <> '') {
		if($picture <> 'yes' AND $picture <> 'no') {
			echo "ERROR: Picture should be ‘yes’ or ‘no’!<br />";
			$ok_storing = FALSE;
			}
		if($picture <> $oldpicture) {
			$changed = TRUE;
			echo "Picture = ‘".$picture."’ —> replacing “".$oldpicture."”<br />";
			}
		}
	else {
		echo "ERROR: Picture is empty!<br />";
		$ok_storing = FALSE;
		}
	if(($photo_credit=trim($_POST['photo_credit'])) <> '') {
		if(strlen($photo_credit) > 299) {
			echo "ERROR: Photo credit should be less than 300 chars!<br />";
			$ok_storing = FALSE;
			}
		if($photo_credit <> $ligne['performer_photo_credit']) {
			$changed = TRUE;
			echo "Photo credit = ".$photo_credit." —> replacing “".$ligne['performer_photo_credit']."”<br />";
			}
		}
	echo "</font>";
	if(!$ok_storing) {
		echo "<br />Errors found. No change has been done. All fields reverted to former values.<br />";
		}
	else {
		if($changed) {
			$picture_url = '';
			if($picture == "yes") $picture_url = $picture;
			$query2 = "UPDATE ".BASE.".performers SET location_id = \"".$performer_location_id."\", performer_name_english = \"".$name_english."\", performer_name_devanagari = \"".$name_devanagari."\", performer_gender = \"".$gender."\", performer_caste_english = \"".$caste_english."\", performer_caste_devanagari = \"".$caste_devanagari."\", performer_picture = \"".$picture_url."\", performer_photo_credit = \"".$photo_credit."\" WHERE performer_id = \"".$performer_id."\"";
	//		echo "<br />".$query2."<br />";
			$result2 = $bdd->query($query2);
			if(!$result2) {
				echo "<br /><font color=red>".$query2."<br />";
				echo "ERROR: FAILED</font>";
				die();
				}
			$result2->closeCursor();
			
			// Get version number for these metadata
			$query5 = "SELECT version FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND performer_location_id > \"0\" ORDER BY version DESC";
			$result5 = $bdd->query($query5);
			$n = $result5->rowCount();
			if($n > 0) {
				$ligne5 = $result5->fetch();
				$version_metadata = 1 + $ligne5['version'];
				}
			else $version_metadata = 1;
			$result5->closeCursor();
			
			// Create first entry in STORIES
			if($version_metadata == 1) {
				$query4 = "INSERT INTO ".BASE.".stories (performer_id, version, login, performer_name_english, performer_name_devanagari, performer_gender, performer_caste_english, performer_caste_devanagari, performer_picture, performer_photo_credit, performer_location_id, story_marathi, story_english) VALUES (\"".$ligne['performer_id']."\",\"0\",\"".$login."\", \"".$ligne['performer_name_english']."\", \"".$ligne['performer_name_devanagari']."\", \"".$ligne['performer_gender']."\", \"".$ligne['performer_caste_english']."\", \"".$ligne['performer_caste_devanagari']."\", \"".$ligne['performer_picture']."\", \"".$ligne['performer_photo_credit']."\", \"".$ligne['location_id']."\",\"\",\"\")";
				$result4 = $bdd->query($query4);
				if(!$result4) {
					echo "<br /><font color=red>".$query4."<br />";
					echo "ERROR: query4 FAILED</font>";
					die();
					}
				$result4->closeCursor();		
				}
			
			// Create new entry in STORIES
			$query6 = "INSERT INTO ".BASE.".stories (performer_id, version, login, performer_name_english, performer_name_devanagari, performer_gender, performer_caste_english, performer_caste_devanagari, performer_picture, performer_photo_credit, performer_location_id, story_marathi, story_english) VALUES (\"".$performer_id."\",\"".$version_metadata."\",\"".$login."\", \"".$name_english."\", \"".$name_devanagari."\", \"".$gender."\", \"".$caste_english."\", \"".$caste_devanagari."\", \"".$picture_url."\", \"".$photo_credit."\", \"".$performer_location_id."\",\"\",\"\")";
		//	echo $query6."<br />";
	//		$result6 = mysql_query($query6);
			$result6 = $bdd->query($query6);
			if(!$result6) {
				echo "<br /><font color=red>".$query6."<br />";
				echo "ERROR: query6 FAILED</font>";
				die();
				}
			$result6->closeCursor();
			}
		}
	echo "</small></blockquote>";
	}
	
if(isset($_POST['performer_biodata_english'])) {
	$performer_biodata_english = reshape_entry($_POST['performer_biodata_english']);
	$performer_biodata_english_old = reshape_entry($_POST['performer_biodata_english_old']);
	$performer_biodata_english = fix_typo($performer_biodata_english,0);
	if($performer_biodata_english <> $performer_biodata_english_old) {
		$query = "UPDATE ".BASE.".performers SET performer_biodata_english = \"".$performer_biodata_english."\" WHERE performer_id = \"".$performer_id."\"";
	//	echo $query."<br />";
		$result = $bdd->query($query);
		if(!$result) {
			echo "<br /><font color=red>".$query."<br />";
			echo "ERROR: FAILED</font>";
			die();
			}
		$result->closeCursor();
		$query = "SELECT version, story_english FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_english <> \"\" AND performer_location_id = \"0\" ORDER BY version DESC";
	/*	$result = mysql_query($query);
		$n = mysql_num_rows($result); */
		$result = $bdd->query($query);
		$n = $result->rowCount();
		$preceding_text = '';
		if($n == 0) $version = 1;
		else {
		//	$ligne = mysql_fetch_array($result);
			$ligne = $result->fetch();
			$version = 1 + $ligne['version'];
			$preceding_text = $ligne['story_english'];
			}
		$result->closeCursor();
		if($performer_biodata_english <> $preceding_text OR $performer_biodata_english == '') {
			// This avoids that a new (identical) version is created if the page is reloaded
			if($version == 1 AND $performer_biodata_english_old <> '') {
				$query = "INSERT INTO ".BASE.".stories (performer_id, version, story_english, story_marathi, login) VALUES (\"".$performer_id."\",\"0\",\"".$performer_biodata_english_old."\",\"\",\"\")";
				$result = $bdd->query($query);
				if(!$result) {
					echo "<br /><font color=red>".$query."<br />";
					echo "ERROR: FAILED  version = 1</font>";
					die();
					}
				$result->closeCursor();
				}
			if($performer_biodata_english == '') $text = '~';
			else $text = $performer_biodata_english;
			$query = "INSERT INTO ".BASE.".stories (performer_id, version, story_english, story_marathi, login) VALUES (\"".$performer_id."\",\"".$version."\",\"".$text."\",\"\",\"".$login."\")";
		//	echo $query."<br />";
			$result = $bdd->query($query);
			if(!$result) {
				echo "<br /><font color=red>".$query."<br />";
				echo "ERROR: FAILED  version > 1</font>";
				die();
				}
			echo "<blockquote><small>Saved English version ".$version.":<br /><font color=red>".$text."</font></small></blockquote><br />";
			$result->closeCursor();
			}
		}
	}

if(isset($_POST['performer_biodata_marathi'])) {
	/* $performer_biodata_marathi = str_replace("\n","<br />",$_POST['performer_biodata_marathi']);
	$performer_biodata_marathi = str_replace("\r",'',$performer_biodata_marathi); */
	$performer_biodata_marathi = reshape_entry($_POST['performer_biodata_marathi']);
	/* $performer_biodata_marathi_old = str_replace("\n","<br />",$_POST['performer_biodata_marathi_old']);
	$performer_biodata_marathi_old = str_replace("\r",'',$performer_biodata_marathi_old); */
	$performer_biodata_marathi_old = reshape_entry($_POST['performer_biodata_marathi_old']);
//	$performer_biodata_marathi = fix_typo($performer_biodata_marathi,0);
	if($performer_biodata_marathi <> $performer_biodata_marathi_old) {
		$query = "UPDATE ".BASE.".performers SET performer_biodata_marathi = \"".$performer_biodata_marathi."\" WHERE performer_id = \"".$performer_id."\"";
	//	echo $query."<br />";
	//	$result = mysql_query($query);
		$result = $bdd->query($query);
		if(!$result) {
			echo "<br /><font color=red>".$query."<br />";
			echo "ERROR: FAILED</font>";
			die();
			}
		$result->closeCursor();
		$query = "SELECT version, story_marathi FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_marathi <> \"\" AND performer_location_id = \"0\" ORDER BY version DESC";
	//	$result = mysql_query($query);
		$result = $bdd->query($query);
		$preceding_text = '';
	//	$n = mysql_num_rows($result);
		$n = $result->rowCount();
		if($n == 0) $version = 1;
		else {
		//	$ligne = mysql_fetch_array($result);
			$ligne = $result->fetch();
			$version = 1 + $ligne['version'];
			$preceding_text = $ligne['story_marathi'];
			}
		$result->closeCursor();
		if($performer_biodata_marathi <> $preceding_text OR $performer_biodata_marathi == '') {
			// This avoids that a new (identical) version is created if the page is reloaded
			if($version == 1 AND $performer_biodata_marathi_old <> '') {
				$query = "INSERT INTO ".BASE.".stories (performer_id, version, story_marathi, story_english, login) VALUES (\"".$performer_id."\",\"0\",\"".$performer_biodata_marathi_old."\",\"\",\"\")";
			//	$result = mysql_query($query);
				$result = $bdd->query($query);
				if(!$result) {
					echo "<br /><font color=red>".$query."<br />";
					echo "ERROR: FAILED</font>";
					die();
					}
				$result->closeCursor();
				}
			if($performer_biodata_marathi == '') $text = '~';
			else $text = $performer_biodata_marathi;
			$query = "INSERT INTO ".BASE.".stories (performer_id, version, story_marathi, story_english, login) VALUES (\"".$performer_id."\",\"".$version."\",\"".$text."\",\"\",\"".$login."\")";
		//	echo $query."<br />";
		//	$result = mysql_query($query);
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

$query = "SELECT * FROM ".BASE.".performers WHERE performer_id = \"".$performer_id."\"";
$result = $bdd->query($query);
$n = $result->rowCount();
// echo $n." ".$query."<br />";
if($n == 0) {
echo "<font color=red>Error: unknown performer</font> [".$performer_id."]";
	die();
	}
echo "<table width=100%>";

// ENGLISH BIODATA
echo "<tr>";
// $ligne = mysql_fetch_array($result);
$ligne = $result->fetch();
$performer_biodata_english = $performer_biodata_english_old = trim($ligne['performer_biodata_english']);
$query_english = "SELECT version FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_english <> \"\" ORDER BY version DESC";
$result_english = $bdd->query($query_english);
$n_english = $result_english->rowCount();
if($n_english == 0) $version_english = -1;
else {
	$ligne_english = $result_english->fetch();
	$version_english = $maxversion_english = $ligne_english['version'];
	if(isset($_GET['change_english']) AND isset($_GET['set_english_version'])) {
		$version_english = $_GET['set_english_version'];
		}
	$query3 = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_english <> \"\" AND version = \"".$version_english."\"";
//	echo $query3;
	$result3 = $bdd->query($query3);
	$n = $result3->rowCount();
	if($n == 0) {
		if($version_english == 0) $version_english = 1;
		$result3->closeCursor();
		$query3 = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_english <> \"\" AND performer_location_id = \"0\" AND version = \"".$version_english."\"";
//	echo $query3;
		$result3 = $bdd->query($query3);
		$n = $result3->rowCount();
		if($n == 0) {
			echo "<font color=red>ERROR: can't reach version ".$version_english."</font><br />";
			}
		}
	$ligne3 = $result3->fetch();
	$result3->closeCursor();
	$performer_biodata_english = trim($ligne3['story_english']);
	if($performer_biodata_english == '~') $performer_biodata_english = '';
	$author = $ligne3['login'];
	$timestamp = $ligne3['date'];
	}
$result_english->closeCursor();

$url_this_page = "edit-performer.php?performer_id=".$performer_id;
echo "<form name=\"search\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
$performer_biodata_english = str_replace("<br />","\n",$performer_biodata_english);
$performer_biodata_english_old = str_replace("<br />","\n",$performer_biodata_english_old);
echo "<td class=\"tight\" style=\"background-color:Cornsilk; padding:12px;\">";
echo "<b>English biodata:</b><br />";
echo "<textarea name=\"performer_biodata_english\" ROWS=\"15\" style=\"width:600px;\">";
echo $performer_biodata_english;
echo "</textarea>";
echo "<input type=\"hidden\" name=\"performer_biodata_english_old\" value=\"".$performer_biodata_english_old."\">";
if($version_english >= 0) {
	echo "<br />";	
	if($version_english >= 1) echo "<a href=\"".$url_this_page."&change_english=1&set_english_version=".($version_english - 1)."\">previous &lt;&lt;</a>&nbsp;&nbsp;";
	echo "Version ".$version_english." - <small>";
	if($author <> '') echo "<i>".$author."</i> - ";
	echo $timestamp."</small>";
	if($version_english < $maxversion_english) echo "&nbsp;&nbsp;<a href=\"".$url_this_page."&change_english=1&set_english_version=".($version_english + 1)."\">&gt;&gt; next</a>";
	}
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk;\"><input type=\"submit\" class=\"button\" value=\"SAVE\">";
echo "</td>";
echo "</form>";
echo "</tr>";

// MARATHI BIODATA
echo "<tr>";
$result = $bdd->query($query);
$ligne = $result->fetch();
$performer_biodata_marathi = $performer_biodata_marathi_old = trim($ligne['performer_biodata_marathi']);
$query_marathi = "SELECT version FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_marathi <> \"\" AND performer_location_id = \"0\" ORDER BY version DESC";
$result_marathi = $bdd->query($query_marathi);
$n_marathi = $result_marathi->rowCount();
if($n_marathi == 0) $version_marathi = -1;
else {
	$ligne_marathi = $result_marathi->fetch();
	$version_marathi = $maxversion_marathi = $ligne_marathi['version'];
	if(isset($_GET['change_marathi']) AND isset($_GET['set_marathi_version'])) {
		$version_marathi = $_GET['set_marathi_version'];
		}
	$query3 = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_marathi <> \"\" AND version = \"".$version_marathi."\" AND performer_location_id = \"0\"";
//	echo $query3;
	$result3 = $bdd->query($query3);
	$n = $result3->rowCount();
	if($n == 0) {
		if($version_marathi == 0) $version_marathi = 1;
		$result3->closeCursor();
		$query3 = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND story_marathi <> \"\" AND version = \"".$version_marathi."\" AND performer_location_id = \"0\"";
//	echo $query3;
		$result3 = $bdd->query($query3);
		$n = $result3->rowCount();
		if($n == 0) {
			echo "<font color=red>ERROR: can't reach version ".$version_marathi."</font><br />";
			}
		}
	$ligne3 = $result3->fetch();
	$result3->closeCursor();
	$performer_biodata_marathi = trim($ligne3['story_marathi']);
	if($performer_biodata_marathi == '~') $performer_biodata_marathi = '';
	$author = $ligne3['login'];
	$timestamp = $ligne3['date'];
	}
$result_marathi->closeCursor();

$url_this_page = "edit-performer.php?performer_id=".$performer_id;
echo "<form name=\"search\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
$performer_biodata_marathi = str_replace("<br />","\n",$performer_biodata_marathi);
$performer_biodata_marathi_old = str_replace("<br />","\n",$performer_biodata_marathi_old);
echo "<td class=\"tight\" style=\"background-color:Cornsilk; padding:12px;\">";
echo "<b>Marathi biodata:</b><br />";
echo "<textarea name=\"performer_biodata_marathi\" ROWS=\"15\" style=\"width:600px;\">";
echo $performer_biodata_marathi;
echo "</textarea>";
echo "<input type=\"hidden\" name=\"performer_biodata_marathi_old\" value=\"".$performer_biodata_marathi_old."\">";
if($version_marathi >= 0) {
	echo "<br />";	
	if($version_marathi >= 1) echo "<a href=\"".$url_this_page."&change_marathi=1&set_marathi_version=".($version_marathi - 1)."\">previous &lt;&lt;</a>&nbsp;&nbsp;";
	echo "Version ".$version_marathi." - <small>";
	if($author <> '') echo "<i>".$author."</i> - ";
	echo $timestamp."</small>";
	if($version_marathi < $maxversion_marathi) echo "&nbsp;&nbsp;<a href=\"".$url_this_page."&change_marathi=1&set_marathi_version=".($version_marathi + 1)."\">&gt;&gt; next</a>";
	}
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk;\"><input type=\"submit\" class=\"button\" value=\"SAVE\">";
echo "</td>";
echo "</form>";
echo "</tr>";

// METADATA
echo "<tr>";
echo "<a name=\"metadata\"></a>";
$result = $bdd->query($query);
$ligne = $result->fetch();
$performer_picture = $ligne['performer_picture'];
$performer_location_id = $ligne['location_id'];
$performer_name_english = $ligne['performer_name_english'];
$performer_name_devanagari = $ligne['performer_name_devanagari'];
$performer_gender = $ligne['performer_gender'];
$performer_caste_english = $ligne['performer_caste_english'];
$performer_caste_devanagari = $ligne['performer_caste_devanagari'];
$performer_photo_credit  = $ligne['performer_photo_credit'];
// $author = $ligne['login'];

$url_this_page = "edit-performer.php?performer_id=".$performer_id;
$query_metadata = "SELECT version FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND performer_location_id > \"0\" ORDER BY version DESC";
$result_metadata = $bdd->query($query_metadata);
$n_metadata = $result_metadata->rowCount();
if($n_metadata == 0) $version_metadata = $maxversion_metadata = -1;
else {
	$ligne_metadata = $result_metadata->fetch();
	$version_metadata = $maxversion_metadata = $ligne_metadata['version'];
	if(isset($_GET['change_metadata_version']) AND isset($_GET['set_metadata_version'])) {
		$version_metadata = $_GET['set_metadata_version'];
		}
	$query3 = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND performer_location_id > \"0\" AND version = \"".$version_metadata."\"";
	$result3 = $bdd->query($query3);
	$n = $result3->rowCount();
	if($n == 0) {
		if($version_metadata == 0) $version_metadata = 1;
		$result3->closeCursor();
		$query3 = "SELECT * FROM ".BASE.".stories WHERE performer_id = \"".$performer_id."\" AND performer_location_id > \"0\" AND version = \"".$version_metadata."\"";
//	echo $query3;
		$result3 = $bdd->query($query3);
		$n = $result3->rowCount();
		if($n == 0) {
			echo "<font color=red>ERROR: can't reach version ".$version_metadata."</font><br />";
			}
		}
	$ligne3 = $result3->fetch();
	$performer_location_id = $ligne3['performer_location_id'];
	$performer_name_english = $ligne3['performer_name_english'];
	$performer_name_devanagari = $ligne3['performer_name_devanagari'];
	$performer_gender = $ligne3['performer_gender'];
	$performer_caste_english = $ligne3['performer_caste_english'];
	$performer_caste_devanagari  = $ligne3['performer_caste_devanagari'];
	$author = $ligne3['login'];
	$timestamp = $ligne3['date'];
	}

$performer_picture_url = performer_picture_url($performer_id,$performer_picture);
/* if($performer_picture_url <> '') $photograph = "yes"; */
if($performer_picture <> '') $photograph = "yes";
else $photograph = "no";
$location_features = location_features($performer_location_id);
	
echo "<form name=\"search\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<td class=\"tight\" style=\"background-color:Cornsilk; padding:12px;\">";
echo "<b>Metadata:</b><br /><br />";
if($photograph == "yes") {
	echo "<div style=\"float:right;\">";
	echo "<a href=\"".$performer_picture_url."\" target=\"_blank\"><img src=\"".$performer_picture_url."\" width=\"180\" alt=\"photo\"/></a><br />";
	echo "<small>Credit: ".$performer_photo_credit."</small><br /><br />";
	echo "</div>";
	}
echo "Name (English): <input type='text' name='name_english' size='50' value=\"".$performer_name_english."\"><br />";
echo "Name (Marathi): <input type='text' name='name_devanagari' size='50' value=\"".$performer_name_devanagari."\"><br />";
echo "Gender (M/F): <input type='text' name='gender' size='1' value=\"".$performer_gender."\"><br />";
echo "Caste (English): <input type='text' name='caste_english' size='50' value=\"".$performer_caste_english."\"><br />";
echo "Caste (Marathi): <input type='text' name='caste_devanagari' size='50' value=\"".$performer_caste_devanagari."\"><br />";
echo "Location ID: <input type='text' name='performer_location_id' size='4' value=\"".$performer_location_id."\"> (<a target=\"_blank\" href=\"villages.php\">Link to all villages</a>)<br />(<a target=\"_blank\" href=\"location.php?location_id=".$performer_location_id."\">Details</a>)";

$village = $location_features['village_english'];
if($village == '') {
	echo " <font color=red>???</font><br />";
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
echo "Photograph (yes/no): <input type='text' name='performer_picture' size='3' value=\"".$photograph."\"><br />";
echo "Photograph credit: <input type='text' name='photo_credit' size='30' value=\"".$performer_photo_credit."\"><br />";

echo "<br /><label for=\"file\">Upload or change picture&nbsp;➡&nbsp;</label>";
echo "<input type=\"file\" name=\"file\" id=\"file\"></small>";
if($upload_message <> '') echo "<blockquote>".$upload_message."</blockquote>";

if($photograph == "yes") echo "<input type=\"submit\" name=\"delete_picture\" class=\"button\" style=\"background-color:Violet;\" value=\"Delete picture\">";

if($version_metadata >= 0) {
	echo "<br /><br />";	
	if($version_metadata >= 1) echo "<a href=\"".$url_this_page."&change_metadata_version=1&set_metadata_version=".($version_metadata - 1)."#metadata\">previous &lt;&lt;</a>&nbsp;&nbsp;";
	echo "Version ".$version_metadata." - <small>";
	if($author <> '') echo "<i>".$author."</i> - ";
	echo $timestamp."</small>";
	if($version_metadata < $maxversion_metadata) echo "&nbsp;&nbsp;<a href=\"".$url_this_page."&change_metadata_version=1&set_metadata_version=".($version_metadata + 1)."#metadata\">&gt;&gt; next</a>";
	}

echo "<input type=\"hidden\" name=\"change_metadata\" value=\"yes\">";
echo "</td>";
echo "<td class=\"tight\" style=\"background-color:Cornsilk;\"><input type=\"submit\" class=\"button\" value=\"SAVE\">";
echo "</td>";
echo "</form>";
echo "</tr>";
echo "</table>";
echo "</body>";
echo "</html>";
?>