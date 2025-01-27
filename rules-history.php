<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_tasks.php");
require_once("_edit_tasks.php");

if(!check_serious_attempt('browse')) die(); 

$name = "Rules history";
$canonic_url = SITE_URL."rules-history.php";

require_once("_header.php");

echo "<h2>Transliteration rules - history</h2>";

if(!identified()) {
	echo "<font color=red>You need to log in to read this page.</font>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

if(!is_admin($login)) {
	echo "<span style=\"color:red;\">Your status does not grant you access to this page.</span>";
	die();
	}

$max_years = 5;
$old_time = time() - (3600 * 24 * 365 * $max_years);
$old_date = date('Y-m-d H:i:s',$old_time);
$sql = "DELETE FROM ".BASE.".rules_history WHERE date < \"".$old_date."\"";
// echo $sql."<br />";
$result = $bdd->query($sql);
$result->closeCursor();
$old_date = date('Y-m-d',$old_time); 

echo "<blockquote>&nbsp;<br /><i>Over the past ".$max_years." years, starting </i>2019-07-21<i>, down to </i>".$old_date."<br /><br /><span style=\"color:red;\">➡</span> <a href=\"transliteration-rules.php?mode=recent\">Return to list of rules</a></blockquote>";

if(isset($_GET['rule']) AND $_GET['rule'] > 0)
	$rule = $_GET['rule'];
else $rule = 0;
if(isset($_GET['song']) AND $_GET['song'] > 0)
	$song = $_GET['song'];
else $song = 0;
if(isset($_GET['login']) AND $_GET['login'] <> '')
	$this_login = $_GET['login'];
else $this_login = '';

if($rule > 0 OR $song > 0 OR $this_login <> '') echo "<blockquote>[<a href=\"rules-history.php?rule=0&song=0&login=\">Display entire table</a>]</blockquote>";
echo "<table class=\"nice\">";
echo "<tr><th>rule</th><th>date</th><th>action</th><th>new rule</th><th>old rule</th><th colspan=\"2\">song example</th></tr>";
$query = "SELECT * FROM ".BASE.".rules_history WHERE id > 0";
if($rule > 0) $query .= " AND rule_id = \"".$rule."\"";
if($song > 0) $query .= " AND song_id = \"".$song."\"";
if($this_login <> '') $query .= " AND login = \"".$this_login."\"";
$query .= " ORDER BY date DESC";
$result = $bdd->query($query);
$i_ligne = 0;
while($ligne = $result->fetch()) {
	echo "<tr>";
	$song_id = $ligne['song_id'];
	$rule_id = $ligne['rule_id'];
	$date = $ligne['date'];
	$action = $ligne['action'];
	$old_leftarg = $ligne['old_leftarg'];
	$old_rightarg = $ligne['old_rightarg'];
	$new_leftarg = $ligne['new_leftarg'];
	$new_rightarg = $ligne['new_rightarg'];
	$song_id = $ligne['song_id'];
	$this_login = $ligne['login'];
	if($rule_id > 0) echo "<td class=\"nice\"><small><a title=\"Filter on this rule\" href=\"rules-history.php?rule=".$rule_id."\">#".$rule_id."</a></small></td>";
	else echo "<td class=\"nice\"><small></small></td>";
	echo "<td class=\"nice\"><small>".$date."</small></td>";
	echo "<td class=\"nice\"><span style=\"color:red;\">".$action."</span></td>";
	if($action <> "delete") echo "<td class=\"nice\">".$new_leftarg." —> ".$new_rightarg."</td>";
	else echo "<td></td>";
	if($action == "modify" OR $action == "delete") echo "<td class=\"nice\">".$old_leftarg." —> ".$old_rightarg."</td>";
	else echo "<td></td>";
	$bugsign = "☛ ";
	$bugs = bugs($song_id,$bugsign);
	if($song_id > 0) echo "<td class=\"nice\"><span style=\"color:red;\">".$bugs."</span>[".song($song_id,$song_id)."] [<a title=\"Filter on this song\" href=\"rules-history.php?song=".$song_id."\">select</a>]</td>";
	else echo "<td></td>";
	echo "<td class=\"nice\"><i><a title=\"Filter on this login\" href=\"rules-history.php?login=".$this_login."\">".$this_login."</a></i></td>";
	echo "</tr>";
	}
echo "</table>";
echo "</body>";
echo "</html>";
?>