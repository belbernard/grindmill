<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier.php");
require_once("_tasks.php");

if(isset($_GET['location_id'])) $location_id = $_GET['location_id'];
else $location_id = 0;
if(isset($_GET['village_english'])) $village_english = $_GET['village_english'];
else $village_english = '';
if(isset($_GET['village_devanagari'])) $village_devanagari = $_GET['village_devanagari'];
else $village_devanagari = '';
if(isset($_GET['taluka_english'])) $taluka_english = $_GET['taluka_english'];
else $taluka_english = '';
if(isset($_GET['taluka_devanagari'])) $taluka_devanagari = $_GET['taluka_devanagari'];
else $taluka_devanagari = '';
if(isset($_GET['district_english'])) $district_english = $_GET['district_english'];
else $district_english = '';
if(isset($_GET['district_devanagari'])) $district_devanagari = $_GET['district_devanagari'];
else $district_devanagari = '';

if(isset($_POST['village_english'])) $village_english = trim($_POST['village_english']);
if(isset($_POST['village_devanagari'])) $village_devanagari = trim($_POST['village_devanagari']);
if(isset($_POST['taluka_english'])) $taluka_english = trim($_POST['taluka_english']);
if(isset($_POST['taluka_devanagari'])) $taluka_devanagari = trim($_POST['taluka_devanagari']);
if(isset($_POST['district_english'])) $district_english = trim($_POST['district_english']);
if(isset($_POST['district_devanagari'])) $district_devanagari = trim($_POST['district_devanagari']);
	
$canonic_url = ''; $n = 0;
if($location_id > 0) {
	$query = "SELECT * FROM ".BASE.".locations WHERE location_id = \"".$location_id."\"";
//	$result = mysql_query($query);
//	$n = mysql_num_rows($result);
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "[".$location_id."] => unknown location"; die();
		}
	else $canonic_url = SITE_URL."location.php?location_id=".$location_id;
	}
else if($village_english <> '') {
	$query = "SELECT * FROM ".BASE.".locations WHERE village_english LIKE \"".$village_english."%\" ORDER BY village_english";
//	$result = mysql_query($query);
//	$n = mysql_num_rows($result);
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "[".$village_english."] => unknown village"; die();
		}
	else $canonic_url = SITE_URL."location.php?village_english=".$village_english;
	}
else if($village_devanagari <> '') {
	$query = "SELECT * FROM ".BASE.".locations WHERE village_devanagari LIKE \"".$village_devanagari."%\" ORDER BY village_devanagari";
//	$result = mysql_query($query);
//	$n = mysql_num_rows($result);
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "[".$village_devanagari."] => unknown village"; die();
		}
	else $canonic_url = SITE_URL."location.php?village_devanagari=".$village_devanagari;
	}
else if($taluka_english <> '') {
	$query = "SELECT * FROM ".BASE.".locations WHERE taluka_english LIKE \"".$taluka_english."%\" ORDER BY village_english";
//	$result = mysql_query($query);
//	$n = mysql_num_rows($result);
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "[".$taluka_english."] => unknown taluka"; die();
		}
	else $canonic_url = SITE_URL."location.php?taluka=".$taluka_english;
	}
else if($taluka_devanagari <> '') {
	$query = "SELECT * FROM ".BASE.".locations WHERE taluka_devanagari LIKE \"".$taluka_devanagari."%\" ORDER BY village_devanagari";
//	$result = mysql_query($query);
//	$n = mysql_num_rows($result);
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "[".$taluka_devanagari."] => unknown taluka"; die();
		}
	else $canonic_url = SITE_URL."location.php?taluka_devanagari=".$taluka_devanagari;
	}
else if($district_english <> '') {
	$query = "SELECT * FROM ".BASE.".locations WHERE district_english LIKE \"".$district_english."%\" ORDER BY village_english";
//	$result = mysql_query($query);
//	$n = mysql_num_rows($result);
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "[".$district_english."] => unknown district"; die();
		}
	else $canonic_url = SITE_URL."location.php?district=".$district_english;
	}
else if($district_devanagari <> '') {
	$query = "SELECT * FROM ".BASE.".locations WHERE district_devanagari LIKE \"".$district_devanagari."%\" ORDER BY village_devanagari";
//	$result = mysql_query($query);
//	$n = mysql_num_rows($result);
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n == 0) {
		echo "[".$district_devanagari."] => unknown district"; die();
		}
	else $canonic_url = SITE_URL."location.php?district_devanagari=".$district_devanagari;
	}

$name = '';

if($village_english <> '') $name = "“".ucfirst($village_english)."”";
else if($village_devanagari <> '') $name = "“".$village_devanagari."”";
else if($district_english <> '') $name = "“".ucfirst($district_english)."”";
else if($district_devanagari <> '') $name = "“".$district_devanagari."”";
else if($taluka_english <> '') $name = "“".ucfirst($taluka_english)."”";
else if($taluka_devanagari <> '') $name = "“".$taluka_devanagari."”";
else if($location_id > 0) $name = "Location ".$location_id;

require_once("_header.php");

if($n == 0) {
	echo "<blockquote><p><font color=red>No valid search criterion…</font></p></blockquote>"; die();
	}
// $ligne = mysql_fetch_array($result);
$ligne = $result->fetch();
$result->closeCursor();

if($location_id > 0) {
	$location_features = location_features($location_id);
if($location_features['hamlet_english'] == '')
	$fullname = '';
else
	$fullname = "Hamlet: ".$location_features['hamlet_english']."&nbsp;/&nbsp;".$location_features['hamlet_devanagari'];
	echo "<h2>Grindmill songs of Maharashtra — Location [".$location_id."] in village <a target=\"_blank\" title=\"All locations in this village\" href=\"".SITE_URL."location.php?village_english=".$ligne['village_english']."&amp;village_devanagari=".$ligne['village_devanagari']."\">".$ligne['village_english']."&nbsp;/&nbsp;".$ligne['village_devanagari']."</a><br />".$fullname."</h2>";
	echo "<p style=\"text-align:center\">".map_link($location_features['GPS'],FALSE)."</p>";
	}
else if($village_english <> '')
	echo "<h2>Grindmill songs of Maharashtra — Location(s) in village “".ucfirst($village_english)."…”<br />(".$n." records)</h2>";
else if($village_devanagari <> '')
	echo "<h2>Grindmill songs of Maharashtra — Location(s) in village “".$village_devanagari."…”<br />(".$n." records)</h2>";
else if($taluka_english <> '')
	echo "<h2>Grindmill songs of Maharashtra — Location(s) in taluka “".ucfirst($taluka_english)."…”<br />(".$n." records)</h2>";
else if($taluka_devanagari <> '')
	echo "<h2>Grindmill songs of Maharashtra — Location(s) in taluka “".$taluka_devanagari."…”<br />(".$n." records)</h2>";
else if($district_english <> '')
	echo "<h2>Grindmill songs of Maharashtra — Location(s) in district “".ucfirst($district_english)."…”<br />(".$n." records)</h2>";
else if($district_devanagari <> '')
	echo "<h2>Grindmill songs of Maharashtra — Location(s) in district “".$district_devanagari."…”<br />(".$n." records)</h2>";
echo "<blockquote>";
// $query = "SELECT count(*) from ".BASE.".locations";
// $result = mysql_query($query);
// $ligne = mysql_fetch_array($result);
echo "<br />=> <a target=\"_blank\" title=\"All villages\" href=\"villages.php\">Display all villages</a> (<font color=red>".number_of_locations()."</font> records)";
echo "</blockquote>";
echo "<table style=\"text-align:center\">";
// $result = mysql_query($query);
$result = $bdd->query($query);
// while($ligne = mysql_fetch_array($result)) {
while($ligne = $result->fetch()) {
	$location_id = $ligne['location_id']; 
	echo "<tr style=\"text-align:center\">";
	$number_photos = 0; 
	$photo_landscape_url = $ligne['photo_landscape_url'];
	$location_landscape_picture = $location_portrait_picture = '';
	if($photo_landscape_url <> '') {
		$table_landscape_url = explode(chr(11),$photo_landscape_url);
		$table_landscape_credit = explode(chr(11),$ligne['photo_landscape_credit']);
		$location_landscape_picture = $table_landscape_url[0];
		$location_landscape_credit = $table_landscape_credit[0];
		$number_photos = count($table_landscape_url);
		}
	$photo_portrait_url = $ligne['photo_portrait_url'];
	if($photo_portrait_url <> '') {
		$table_portrait_url = explode(chr(11),$photo_portrait_url);
		$table_portrait_credit = explode(chr(11),$ligne['photo_portrait_credit']);
		$location_portrait_picture = $table_portrait_url[0];
		$location_portrait_credit = $table_portrait_credit[0];
		$number_photos += count($table_portrait_url);
		}
	echo "<td style=\"white-space:nowrap; text-align:center\" ROWSPAN = \"2\">";
	if($location_landscape_picture <> '') {
		$location_landscape_picture = location_picture($location_landscape_picture);
		echo "<a href=\"".$location_landscape_picture."\" target=”_blank\"><img src=\"".$location_landscape_picture."\" width=\"180\" alt=\"photo\"/></a><br />";
		echo "<small>Credit: ".$location_landscape_credit."</small><br /><br />";
		}
	if($location_portrait_picture <> '') {
		$location_portrait_picture = location_picture($location_portrait_picture);
		echo "<a href=\"".$location_portrait_picture."\" target=”_blank\"><img src=\"".$location_portrait_picture."\" width=\"180\" alt=\"photo\"/></a><br />";
		echo "<small>Credit: ".$location_portrait_credit."</small><br /><br />";
		}
	echo "<table style=\"border:1px solid black;\" align=\"center\">";
	echo "<tr>";
	echo "<td style=\"text-align:center\">";
	echo "[<a target=\"_blank\" title=\"Visit this location\" href=\"location.php?location_id=".$location_id."\">".$location_id."</a>]<br /><b><big>".$ligne['village_devanagari']."</big><br />".$ligne['village_english']."</b>";
	echo "</td></tr></table><br />";
	if($ligne['hamlet_devanagari'] <> '')
		echo "Hamlet: ".$ligne['hamlet_devanagari']." - ".$ligne['hamlet_english']."<br />";
	if($ligne['taluka_devanagari'] <> '')
		echo "Taluka: ".$ligne['taluka_devanagari']." - ".$ligne['taluka_english']."<br />";
	if($ligne['district_devanagari'] <> '')
		echo "District: ".$ligne['district_devanagari']." - ".$ligne['district_english']."<br />";
	if($ligne['valley_devanagari'] <> '')
		echo "Valley: ".$ligne['valley_devanagari']." - ".$ligne['valley_english']."<br />";
	if($ligne['state'] <> '')
		echo "State: ".$ligne['state']."<br />";
	if($ligne['corpus'] <> '')
		echo "Corpus: ".$ligne['corpus']."<br />";
	echo "<small>".map_link($ligne['GPS'],FALSE)."</small>";
	echo "<p style=\"text-align:center\"><b><a target=\"_blank\" title=\"Songs\" href=\"".SITE_URL."songs.php?location_id=".$location_id."\">Songs in this location</a></b></p>";
	$query_performers = "SELECT * FROM ".BASE.".performers WHERE location_id = \"".$location_id."\" ORDER BY performer_name_english";
	$result_performers = $bdd->query($query_performers);
	$nb_performers = $result_performers->rowCount();
	if($nb_performers > 0) {
		echo "<br /><b>Performers:</b><br />";
		while($ligne_performer = $result_performers->fetch()) {
			echo "<a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."performer.php?performer_id=".$ligne_performer['performer_id']."\">".$ligne_performer['performer_name_devanagari']." - <small>".$ligne_performer['performer_name_english']."</a></small><br />";
			}
		} 
	echo "</td>\n";
	$url = "edit-location.php?location_id=".$location_id;
	echo "<td lang=\"mr\" style=\"min-width:400px;\"";
	if($ligne['info_marathi'] <> '') echo " style=\"min-width:300px;\"";
	echo ">";
	echo $ligne['info_marathi'];
	if(identified()) {
		if($ligne['info_marathi'] == '')
			echo "<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Create Marathi description…</a>";
		else echo "&nbsp;[<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Edit…</a>]";
		}
	echo "</td>\n";
	echo "<td lang=\"en\" style=\"min-width:400px;\"";
	if($ligne['info_english'] <> '') echo " style=\"min-width:300px;\"";
	echo ">";
	echo $ligne['info_english'];
	if(identified()) {
		if($ligne['info_english'] == '')
			echo "<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Create English description…</a>";
		else echo "&nbsp;[<a target=\"_blank\" title=\"".$url."\" href=\"".$url."\">Edit…</a>]";
		}
	echo "</td>\n";
	echo "</tr>\n";
	if($number_photos > 1) {
		echo "<tr>\n<td></td>\n";
		echo "<td colspan=\"2\" style=\"white-space: nowrap;\"><b>Download pictures:</b><ul>";
		for($i = 0; $i < count($table_landscape_url); $i++) {
			$location_picture = location_picture($table_landscape_url[$i]);
		//	if(fopen($location_picture,'r')) echo "ok<br />";
			echo "<li><small><a href=\"".location_picture($table_landscape_url[$i])."\">".name_location_picture($table_landscape_url[$i])."</a> (Credit: ".$table_landscape_credit[$i].")</small></li>\n";
			}
		for($i = 0; $i < count($table_portrait_url); $i++) {
			echo "<li><small><a href=\"".location_picture($table_portrait_url[$i])."\">".name_location_picture($table_portrait_url[$i])."</a> (Credit: ".$table_portrait_credit[$i].")</small></li>\n";
			}
		echo "</ul></td>\n";
		}
	else echo "<tr><td></td><td colspan=\"2\"></td>";
	echo "</tr>";
	echo "<tr><td colspan=\"3\"><hr></td></tr>";
	}
$result->closeCursor();
echo "</table>";
echo "</body>";
echo "</html>";
?>