<?php
// session_start();
require_once("_base_urls.php");
// require_once("_relier.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_tasks.php");
$name = "Classification scheme";
$canonic_url = SITE_URL."classification.php";
$bottom_of_page = "[<a href=\"#bottom\">Go to bottom of page</a>]";
require_once("_header.php");
echo "<h2>Grindmill songs of Maharashtra — Classification scheme</h2>";
set_time_limit(600);

$query = "SELECT semantic_class_id FROM ".BASE.".classification";
$result = $bdd->query($query);
$n = $result->rowCount();
$result->closeCursor();
if($n == 0) {
	echo "Classification is empty... Check database!"; die();
	}
$time_start = time();
if(is_super_admin($login)) {
	$query = "SELECT id FROM ".BASE.".dev_roman";
	$result = $bdd->query($query);
	$old_n_dev_roman = $result->rowCount();
	$result->closeCursor();
	}

if(isset($_POST['print_untranslated'])) {
	if(isset($_POST['this_class'])) {
		$semantic_class = $_POST['this_class'];
		$semantic_class_id = $_POST['this_class_id'];
		echo "<blockquote><p><font color=\"red\">Exporting untranslated songs in</font> <font color=\"MediumTurquoise\">".$semantic_class."</font><font color=\"red\"> to </font><font color=\"blue\">".$login."</font><br />";

		$query = "SELECT devanagari, song_id FROM ".BASE.".songs WHERE semantic_class_id = \"".$semantic_class_id."\" AND translation_english = \"\"";
		$result = $bdd->query($query);
		$n = $result->rowCount();
		if($n == 0) {
			echo "<p>No untranslated song in this class…</p>";
			}
		else {
			$filename = "WORK_FILES/".$login."/".$semantic_class_id."_untranslated.txt";
			$handle = fopen($filename,'w');
			if($handle) {
				fwrite($handle,$semantic_class." = ".$semantic_class_id."\n\n");
				while($ligne = $result->fetch()) {
					$this_line = str_replace("<br />","\n",$ligne['devanagari']);
					fwrite($handle,$ligne['song_id']."\n");
					fwrite($handle,$this_line."\n\n");
					}
				fclose($handle);
				echo "👉 <a href=\"".$filename."\" download=\"".$semantic_class_id."_untranslated.txt\">Download the export</a>";
				}
			}
		$result->closeCursor();
		echo "</p></blockquote>";
		}
	}

$query = "SELECT count(*) from ".BASE.".songs";
$result = $bdd->query($query);
$number_of_songs = $result->fetchColumn();
$result->closeCursor();
echo "<br /><span style=\"color:red;\">".$number_of_songs."</span> songs in this database<br />";
echo "<span style=\"color:red;\">".$n."</span> classes are listed below, ";

if(isset($_GET["sort"])) $sort = $_GET["sort"];
else $sort = 'section';

if($sort == "section")
	$query = "SELECT DISTINCT semantic_class_title_prefix FROM ".BASE.".classification ORDER BY semantic_class_id";
else $query = "SELECT DISTINCT semantic_class_title_prefix FROM ".BASE.".classification ORDER BY semantic_class_title_prefix";
$result = $bdd->query($query);
$n = $result->rowCount();
echo " among which <span style=\"color:red;\">".$n."</span> class titles<br />";
echo "➡ <a target=\"_blank\" href=\"classification-scores.php\">Display classification scores</a><br />";
echo SOUND_ICON." number of recordings<br />";
echo TRANSLATION_ICON." number of translations<br /><br />";

$number_songs = array();
while($ligne = $result->fetch()) {
	$semantic_class_title_prefix = $ligne['semantic_class_title_prefix'];
	$query2 = "SELECT count(*) from ".BASE.".songs WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\"";
	$result2 = $bdd->query($query2);
	$n2 = $result2->fetchColumn();
	$result2->closeCursor();
	$query3 = "SELECT semantic_class_id FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" LIMIT 1";
	$result3 = $bdd->query($query3);
	$ligne = $result3->fetch();
	$result3->closeCursor();
	$semantic_class_id = $ligne['semantic_class_id'];
	$query3 = "SELECT count(*) from ".BASE.".songs WHERE semantic_class_id LIKE \"".$semantic_class_id."%\"";
	$result3 = $bdd->query($query3);
	$n3 = $result3->fetchColumn();
	$result3->closeCursor();
	$n = $n2;
	if($n3 > $n2) $n = $n3;
	$number_songs[$semantic_class_title_prefix] = $n;
	}
$result->closeCursor();
natsort($number_songs);
$number_songs = array_reverse($number_songs,true);

$total_number = 0;
echo "<div id=\"top\"></div>";
if($sort == "number") {
	echo "➡ Sorted by <span style=\"color:red;\">number of songs</span><br />";
	echo "➡ <a href=\"classification.php?sort=alpha\">Click this link</a> to sort alphabetically<br />";
	echo "➡ <a href=\"classification.php?sort=section\">Click this link</a> to sort by section<br /><br />";
	foreach($number_songs as $semantic_class_title_prefix => $freq) {
		$query4 = "SELECT semantic_class_id, semantic_class FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" LIMIT 1";
		$result4 = $bdd->query($query4);
		$ligne4 = $result4->fetch();
		$result4->closeCursor();
		$semantic_class_id = $ligne4['semantic_class_id'];
		if(FALSE AND $number_songs[$semantic_class_title_prefix] >= 337 AND is_super_admin($login) AND $login == "Bernard") {
			$query = "SELECT song_id FROM ".BASE.".songs WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\"";
			$result = $bdd->query($query);
			$n = $result->rowCount();
			while($ligne = $result->fetch()) {
				$song_id = $ligne['song_id'];
				LearnTransliterationFromSong($song_id);
				}
			$result->closeCursor();
			echo "Lexique ➡ ".$n." songs<br />"; 
			}
		display_class('',$semantic_class_id,$semantic_class_title_prefix,$number_songs);
		}
	}
if($sort == "alpha") {
	echo "➡ Sorted alphabetically<br />";
	echo "➡ <a href=\"classification.php?sort=number\">Click this link</a> to sort by number of songs<br />";
	echo "➡ <a href=\"classification.php?sort=section\">Click this link</a> to sort by section<br /><br />";
	$result = $bdd->query($query);
	while($ligne = $result->fetch()) {
		$semantic_class_title_prefix = $ligne['semantic_class_title_prefix'];
		$query4 = "SELECT semantic_class_id FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" LIMIT 1";
		$result4 = $bdd->query($query4);
		$ligne4 = $result4->fetch();
		$result4->closeCursor();
		$semantic_class_id = $ligne4['semantic_class_id'];
		display_class('',$semantic_class_id,$semantic_class_title_prefix,$number_songs);
		}
	}
if($sort == "section") {
	echo "➡ Sorted by section<br />";
	echo "➡ <a href=\"classification.php?sort=alpha\">Click this link</a> to sort alphabetically<br />";
	echo "➡ <a href=\"classification.php?sort=number\">Click this link</a> to sort by number of songs<br /><br />";
	$result = $bdd->query($query);
	while($ligne = $result->fetch()) {
		$semantic_class_title_prefix = $ligne['semantic_class_title_prefix'];
		$query4 = "SELECT semantic_class_id, semantic_class FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" LIMIT 1";
	//	echo $query4."<br />";
		$result4 = $bdd->query($query4);
		$ligne4 = $result4->fetch();
		$result4->closeCursor();
		$semantic_class_id = $ligne4['semantic_class_id'];
		$semantic_class = $ligne4['semantic_class'];
	//	echo "semantic_class_id = ".$semantic_class_id."<br />";
		$section = substr($semantic_class_id,0,6)."…";
		display_class($section,$semantic_class_id,$semantic_class_title_prefix,$number_songs);
		}
	}
echo "<div id=\"bottom\"></div>";
echo "[<a href=\"#top\">Go to top of page</a>]<br />";
if(is_super_admin($login) AND $login == "Bernard") {
	echo "<small>".$total_number."</small><br />";
	$time_end = time();
	echo "<small>Exec time = ".($time_end - $time_start)." seconds</small><br />";
	$query = "SELECT id FROM ".BASE.".dev_roman";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	echo "<small>Now ".$n." lines in dev_roman. Learned ".($n - $old_n_dev_roman)." new transliterations.</small>";
	$result->closeCursor();
	}
echo "</body>";
echo "</html>";

function display_class($section,$semantic_class_id,$semantic_class_title_prefix,$number_songs) {
	global $total_number,$bdd,$login;
	$n = $number_songs[$semantic_class_title_prefix];
	$total_number += $n;
	$url_this_page = "classification.php";
	$url_this_class = SITE_URL."songs.php?semantic_class_title_prefix_id=".$semantic_class_id;
	if($section <> '') echo "<small>".$section."</small> ➡ ";
	echo "<b>".$semantic_class_title_prefix."</b> (<span style=\"color:black;\">".$n."</span> <a target=\"_blank\" title=\"Display all these songs\" href=\"".$url_this_class."\">songs</a>)";
	if(is_translator($login)) echo " <small><span style=\"color:MediumTurquoise;\">➡</span>&nbsp;<a target=\"_blank\" href=\"edit-classification.php?class=".$semantic_class_title_prefix."\">Edit or comment this class…</a></small>";
	echo "</form>";
	$query = "SELECT title_comment, title_comment_mr FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\"";
	$result = $bdd->query($query);
	$ligne = $result->fetch();
	$result->closeCursor();
	$title_comment = $ligne['title_comment'];
	$title_comment_mr = $ligne['title_comment_mr'];
	if($title_comment <> '' OR $title_comment_mr <> '') {
		echo "<table style=\"border-spacing:0px; empty-cells:hide; font-size:80%; padding:0px;\"><tr>";
		echo "<td style=\"background-color:Cornsilk; border:1px solid white; padding:4px;\">".$title_comment_mr."</td>";
		echo "<td style=\"background-color:Cornsilk; border:1px solid white; padding:4px;\">".$title_comment."</td>";
		echo "</tr></table>";
		echo "<ul style=\"margin-top:3px;\">";
		}
	else echo "<ul>";
	$query2 = "SELECT * FROM ".BASE.".classification WHERE semantic_class_title_prefix = \"".$semantic_class_title_prefix."\" ORDER BY semantic_class_id";
	$result2 = $bdd->query($query2);
	while($ligne2 = $result2->fetch()) {
		echo "<li>";
		$semantic_class_id = $ligne2['semantic_class_id'];
		if(is_super_admin($login)) {
			check_semantic_id("full",$semantic_class_id);
			}
		$class_comment = $ligne2['class_comment'];
		$class_comment_mr = $ligne2['class_comment_mr'];
		$comment = $class_comment_mr;
		if($comment <> '') $comment .= "<br />".$class_comment;
		else $comment = $class_comment;
		$comment = str_replace("<br />","\n",$comment);
		echo "<small>";
		if($comment <> '')
			echo "<a href=\"\" title=\"".$comment."\">".$semantic_class_id."</a>";
		else echo $semantic_class_id;
		echo " =</small> <span style=\"color:MediumTurquoise;\">".$ligne2['semantic_class']."</span> - <a target=\"_blank\" title=\"All songs in this class\" href=\"songs.php?semantic_class_id=".$ligne2['semantic_class_id']."\">".$ligne2['semantic_class_title']."</a>&nbsp;";
		$query3 = "SELECT song_id FROM ".BASE.".songs WHERE semantic_class_id = \"".$semantic_class_id."\" AND recording_DAT_index <> \"\"";
		$result3 = $bdd->query($query3);
		$number_recordings = $result3->rowCount();
		$result3->closeCursor();
		$query3 = "SELECT song_id FROM ".BASE.".songs WHERE semantic_class_id = \"".$semantic_class_id."\" AND translation_english <> \"\"";
		$result3 = $bdd->query($query3);
		$number_translations = $result3->rowCount();
		$result3->closeCursor();
		$query3 = "SELECT song_id FROM ".BASE.".songs WHERE semantic_class_id = \"".$semantic_class_id."\"";
		$result3 = $bdd->query($query3);
		$number_songs_subclass = $result3->rowCount();
		$result3->closeCursor();
		if($number_songs_subclass > 0) {
			echo  "<small>";
			if($number_recordings > 0) echo "<small>".$number_recordings."</small>&nbsp;".SOUND_ICON;
			if($number_recordings > 0) echo "&nbsp;";
			if($number_translations == $number_songs_subclass) $this_color = "red";
			else $this_color = "black";
			echo "<small><span style=\"color:".$this_color.";\">";
			if($this_color == "black") echo $number_translations."/";
			echo $number_songs_subclass."</span></small>&nbsp;".TRANSLATION_ICON;
			echo "</small>&nbsp;";
			if(is_admin($login) AND $this_color == "black") {
				echo "<form  id=\"print\" method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
				echo "<input type=\"hidden\" name=\"this_class\" value=\"".$ligne2['semantic_class']."\">";
				echo "<input type=\"hidden\" name=\"this_class_id\" value=\"".$semantic_class_id."\">";
				echo "&nbsp;<input class=\"button\" type=\"submit\" name=\"print_untranslated\" value=\"Print untranslated songs\">";
				echo "</form>";
				}
			}
		$list_cross_references = list_cross_references($ligne2['cross_references'],"MediumTurquoise",TRUE);
		if($list_cross_references <> '') echo "-> Cross-reference(s): <span style=\"color:MediumTurquoise;\">".$list_cross_references."</span>";
		echo "</li>";
		}
	$result2->closeCursor();
	echo "</ul>";
	return;
	}
?>