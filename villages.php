<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier.php");
require_once("_tasks.php");
$name = "Villages";
$canonic_url = SITE_URL."villages.php";
require_once("_header.php");
echo "<h2>Grindmill songs of Maharashtra — Villages</h2><br />";
echo "<blockquote>";
$query = "SELECT count(*) from ".BASE.".classification";
$result_count = $bdd->query($query);
$number_classes = $result_count->fetchColumn();
$result_count->closeCursor();
echo "=> <a target=\"_blank\" title=\"Complete classification\" href=\"classification.php\">Display complete classification scheme</a> (".$number_classes." classes)";
echo "</blockquote>";
set_time_limit(600);
if(is_admin($login))
	echo "<blockquote>[<a target=\"_blank\" title=\"Edit this table\" href=\"edit-villages.php\">Edit this table</a>]</blockquote><br />";
echo "<center>";
echo "<table class=\"village\">";
echo "<tr>";
echo "<th class=\"tight\" style='text-align:center;'>id</th><th class=\"tight\">Songs</th><th class=\"tight\">Performers</th><th class=\"tight\" colspan=2>Village</th><th class=\"tight\">Hamlet</th><th class=\"tight\">Taluka</th><th class=\"tight\">Valley</th><th class=\"tight\">District</th><th class=\"tight\">Corpus</th><th class=\"tight\">GPS</th>";
echo "</tr>";
$query = "SELECT * FROM ".BASE.".locations ORDER BY village_english";
$result = $bdd->query($query);
while($ligne = $result->fetch()) {
	if(trim($ligne['village_english']) == '') continue;
	echo "<tr>";
	echo "<td class=\"tight\" style='text-align:center;'><a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."location.php?location_id=".$ligne['location_id']."\">".$ligne['location_id']."</a>&nbsp;</td>";
	$query2 = "SELECT location_id FROM ".BASE.".songs WHERE location_id=\"".$ligne['location_id']."\"";
	$result2 = $bdd->query($query2);
	$n_songs = $result2->rowCount();
	$result2->closeCursor();
	echo "<td class=\"tight\">&nbsp;<a target=\"_blank\" title=\"Show songs\" href=\"".SITE_URL."songs.php?location_id=".$ligne['location_id']."\">".$n_songs." songs</a></td>";
	$query3 = "SELECT location_id FROM ".BASE.".performers WHERE location_id=\"".$ligne['location_id']."\"";
	$result3 = $bdd->query($query3);
	$n_performers = $result3->rowCount();
	$result3->closeCursor();
	echo "<td class=\"tight\"><a target=\"_blank\" title=\"List of performers\" href=\"".SITE_URL."performer.php?location_id=".$ligne['location_id']."\">".$n_performers." performers</a></td>";
	echo "<td class=\"tight\"><a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."location.php?village_devanagari=".$ligne['village_devanagari']."\">".$ligne['village_devanagari']."</a></td>";
	echo "<td class=\"tight\"><a target=\"_blank\" title=\"Details\" href=\"".SITE_URL."location.php?village_english=".$ligne['village_english']."\">".$ligne['village_english']."</a>&nbsp;</td>";
	if($ligne['hamlet_devanagari'] <> '')
		echo "<td class=\"tight\">".$ligne['hamlet_devanagari']." - ".$ligne['hamlet_english']."</td>";
	else echo "<td class=\"tight\"></td>";
	if($ligne['taluka_devanagari'] <> '')
		echo "<td class=\"tight\"><a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?taluka_devanagari=".$ligne['taluka_devanagari']."\">".$ligne['taluka_devanagari']."</a> - <a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?taluka_english=".$ligne['taluka_english']."\">".$ligne['taluka_english']."</a></td>";
	else echo "<td class=\"tight\"></td>";
	if($ligne['valley_devanagari'] <> '')
		echo "<td class=\"tight\">".$ligne['valley_devanagari']." - ".$ligne['valley_english']."</td>";
	else echo "<td class=\"tight\"></td>";
	if($ligne['district_devanagari'] <> '')
		echo "<td class=\"tight\"><a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?district_devanagari=".$ligne['district_devanagari']."\">".$ligne['district_devanagari']."</a> - <a target=\"_blank\" title=\"Show all locations\" href=\"".SITE_URL."location.php?district_english=".$ligne['district_english']."\">".$ligne['district_english']."</a></td>";
	else echo "<td class=\"tight\"></td>";
	echo "<td class=\"tight\">".$ligne['corpus']."</td>";
	echo "<td class=\"tight\">".map_link($ligne['GPS'],TRUE)."</td>";
	echo "</tr>";
	}
$result->closeCursor();
echo "</table>";
echo "</center>";
echo "</body>";
echo "</html>";
?>