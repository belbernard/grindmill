<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier.php");
require_once("_relier_edit.php");
require_once("_tasks.php");
$name = "Classification scores";
$canonic_url = SITE_URL."classification-scores.php";
require_once("_header.php");
echo "<h2>Grindmill songs of Maharashtra — Classification scores</h2>";

$query = "SELECT count(*) from ".BASE.".songs";
$result = $bdd->query($query);
$number_of_songs = $result->fetchColumn();
$result->closeCursor();
echo "<br /><span style=\"color:red;\">".$number_of_songs."</span> songs in this database<br />";

echo "<table>";
$query = "SELECT DISTINCT semantic_class_title_prefix, semantic_class_id FROM ".BASE.".classification ORDER BY semantic_class_id";
$result = $bdd->query($query);
$n = $result->rowCount();
$head = $old_section = '';
while($ligne = $result->fetch()) {
	$semantic_class_id = $ligne['semantic_class_id'];
	$semantic_class_id_head = substr($semantic_class_id,0,3);
	if($semantic_class_id_head == $head) continue;
	$head = $semantic_class_id_head;
	$semantic_class_title_prefix_list = $old_prefix_head = '';
	$query2 = "SELECT DISTINCT semantic_class_title_prefix FROM ".BASE.".classification WHERE semantic_class_id LIKE \"".$semantic_class_id_head."%\"";
	$result2 = $bdd->query($query2);
	while($ligne2 = $result2->fetch()) {
		$semantic_class_title_prefix = $ligne2['semantic_class_title_prefix'];
		$table = explode ("/",$semantic_class_title_prefix);
		$semantic_class_title_prefix_head = trim($table[0]);
	/*	if($semantic_class_title_prefix_head == $old_prefix_head) continue;
		$old_prefix_head = $semantic_class_title_prefix_head; */
		if(is_integer(strpos($semantic_class_title_prefix_list,$semantic_class_title_prefix_head))) continue;
		if($semantic_class_title_prefix_list <> '') $semantic_class_title_prefix_list .= "</small></td><td class=\"nice\"><small>";
		$semantic_class_title_prefix_list .= $semantic_class_title_prefix_head;
		}
	$result2->closeCursor();
	echo "<tr>";
	$section = substr($semantic_class_id,0,1);
	$query3 = "SELECT count(*) from ".BASE.".songs WHERE semantic_class_id LIKE \"".$semantic_class_id_head."%\"";
	$result3 = $bdd->query($query3);
	$n3 = $result3->fetchColumn();
	$result3->closeCursor();
	echo "<td class=\"nice\">";
	if($section <> $old_section) echo $section;
	$old_section = $section;
	echo "</td>";
	echo "<td class=\"nice\">";
	echo $semantic_class_id_head;
	echo "</td>";
	echo "<td class=\"nice\">";
	echo "<span style=\"color:red;\">".$n3."</span> songs";
	echo "</td>";
	echo "<td class=\"nice\"><small>";
	echo $semantic_class_title_prefix_list;
	echo "</small></td>";
	echo "</tr>";
	}
$result->closeCursor();
echo "</table>";
echo "</body>";
echo "</html>";
?>