<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");

if(!check_serious_attempt('browse')) die(); 

$name = "Transliteration rules";
$canonic_url = SITE_URL."transliteration-rules.php";

require_once("_header.php");

echo "<h2>Roman Devanagari transliteration rules</h2>";

if(!identified()) {
	echo "<span style=\"color:red;\">You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</span>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

echo "<br />&nbsp;<br /><blockquote>";

$date = date('Y-m-d H:i:s');

if(is_admin($login) AND isset($_POST['delete_rules'])) {
	echo "<p><span style=\"color:blue;\">Deleting rule(s):</span></p>";
	$i = 0; echo "<small>";
	foreach($_POST as $key => $value) {
		if(is_integer(strpos($key,"rule_delete"))) {
			$rule_id = str_replace("rule_delete",'',$key);
			$query = "SELECT * FROM ".BASE.".dev_roman WHERE id = ".$rule_id;
			$result = $bdd->query($query);
			$ligne = $result->fetch();
			$roman = $ligne['roman'];
			$devanagari = $ligne['devanagari'];
			$result->closeCursor();
			if($devanagari == '') continue;
			add_to_rule_history('','',$devanagari,$roman,0,$rule_id);
			echo "<small>#".$rule_id."</small> ".$devanagari." —> ".$roman."<br />";
			$query_delete = "DELETE FROM ".BASE.".dev_roman WHERE id = \"".$rule_id."\"";
			$result_delete = $bdd->query($query_delete);
			if(!$result_delete) {
				echo "<span style=\"color:red;\">ERROR deleting:</span> ".$query_delete."<br />";
				die();
				}
			else $result_delete->closeCursor();
			$i++;
			}
		}
	echo "</small>";
	if($i == 0) echo "<span style=\"color:red;\">No rule has been selected!</span>";
	}

if(isset($_GET['mode'])) $mode = $_GET['mode'];
else $mode = "devanagari";
if(isset($_GET['limit']) AND $_GET['limit'] > 0) $limit = $_GET['limit'];
else $limit = 50;
if(isset($_GET['order'])) $order = $_GET['order'];
else {
	if($mode == "recent") $order = "desc";
	else if($mode == "roman") $order = "asc";
	else if($mode == "rule") $order = "asc";
	else if($mode == "devanagari") $order = "desc";
	}

if(is_admin($login)) echo "<p><span style=\"color:red;\">➡</span> <a href=\"rules-history.php\" target=\"_blank\">Detailed history of changes</a></p>";
	
echo "<h3>List of transliteration rules:</h3>";

echo "<p>Sorted on <span style=\"color:red;\">";
switch($mode) {
	case "recent":
		echo "dates"; break;
	case "rule":
		echo "rule ids"; break;
	case "roman":
		echo "Roman words"; break;
	case "devanagari":
		echo "Devanagari words"; break;
	}
echo "</span> (<span style=\"color:blue;\">".$order."</span>) ➡ ";
if($order == "desc")
	echo "<a href=\"transliteration-rules.php?mode=".$mode."&limit=".$limit."&order=asc\">Reverse order</a>";
else
	echo "<a href=\"transliteration-rules.php?mode=".$mode."&limit=".$limit."&order=desc\">Reverse order</a>";
echo "</p>"; 

echo "<p>";
if($mode <> "roman") echo "<span style=\"color:red;\">➡</span> [<a href=\"transliteration-rules.php?mode=roman&limit=".$limit."\">Sort on Roman words</a>]<br />";
if($mode <> "devanagari") echo "<span style=\"color:red;\">➡</span> [<a href=\"transliteration-rules.php?mode=devanagari&limit=".$limit."\">Sort on Devanagari words</a>]<br />";
if($mode <> "recent") echo "<span style=\"color:red;\">➡</span> [<a href=\"transliteration-rules.php?mode=recent&limit=".$limit."\">Sort on dates</a>]<br />";
if($mode <> "rule") echo "<span style=\"color:red;\">➡</span> [<a href=\"transliteration-rules.php?mode=rule&limit=".$limit."\">Sort on rule ids</a>]<br />";
echo "</p>";  

if(is_admin($login))
	echo "<form method=\"post\" action=\"transliteration-rules.php?mode=".$mode."&limit=".$limit."&order=".$order."\" enctype=\"multipart/form-data\">";
$query = "SELECT * FROM ".BASE.".dev_roman";
if($mode == "roman") $query .= " ORDER BY roman ".$order;
if($mode == "devanagari") $query .= " ORDER BY devanagari ".$order;
if($mode == "recent") $query .= " ORDER BY date ".$order.", roman";
if($mode == "rule") $query .= " ORDER BY id ".$order;
$query .= " LIMIT ".$limit;
//  echo $query."<br />";
$result = $bdd->query($query);
$n = $result->rowCount();
// echo $n."<br />";
$i_ligne = 0;
while($ligne = $result->fetch()) {
	$id = $ligne['id'];
	$roman = $ligne['roman'];
	$devanagari = $ligne['devanagari'];
	$song_id = $ligne['song_id'];
	$date = $ligne['date'];
	$table = explode(' ',$date);
	$date = $table[0];
	$bugsign = " ☛";
	$bugs = bugs($song_id,$bugsign);
//	if($date == 0 AND $limit == 50) break;
	$query2 = "SELECT login FROM ".BASE.".rules_history WHERE rule_id = \"".$id."\"";
	$result2 = $bdd->query($query2);
	$ligne2 = $result2->fetch();
	$this_login = $ligne2['login'];
	$result2->closeCursor();
	echo "<small>#".$id."</small> ";
	if(is_admin($login))
		echo "<input type=\"checkbox\" name=\"rule_delete".$id."\" value=\"ok\" />";
	echo $devanagari." —> ".$roman;
	echo " <small><a href=\"\" id=\"".$i_ligne."\"></a>";
	$i_ligne++;
	$url = "edit-songs.php?start=".$song_id;
	if($song_id > 0) echo " <font color=red>in song".$bugs."</font> [<a href=\"".$url."\" target=\"_blank\">".$song_id."</a>]";
	echo "<i>";
	if($date > 0) echo " ".$date;
	echo " ".$this_login;
	echo "</i>";
	echo "</small><br />";
	}
$result->closeCursor();
$old_limit = $limit;
$limit += 50;
echo "<p>[<a href=\"transliteration-rules.php?mode=".$mode."&limit=".$limit."&order=".$order."#".$old_limit."\">Show more…</a>]";

if(is_admin($login)) {
	echo "&nbsp;&nbsp;&nbsp;<input type=\"submit\" class=\"button\" name=\"delete_rules\" value=\"DELETE CHECKED RULES\">";
	echo "</form>";
	}
echo "</p>";

echo "</body>";
echo "</html>";
?>