<?php
session_start();
require_once("_users.php");
require_once("_relier_edit.php");
$url_return = $mssg = '';
$reset = '';
if(isset($_GET['url_return'])) $url_return = $_GET['url_return'];
if(isset($_POST['url_return'])) $url_return = $_POST['url_return'];
if(isset($_GET['end'])) {
	$end = $_GET['end'];
	$url_return .= "&end=".$end;
	}
if(isset($_GET['include_french'])) {
	$include_french = $_GET['include_french'];
	$url_return .= "&include_french=".$include_french;
	}
if(isset($_GET['reset'])) {
	$reset = $_GET['reset'];
	$url_return .= "&reset=".$reset;
	}
// echo $url_return; die();
$action = '';
if(isset($_POST['action'])) $action = $_POST['action'];
if(isset($_GET['action'])) $action = $_GET['action'];

if($action == "login") {
	if(isset($_SESSION['login']) AND $_SESSION['login'] <> '' AND $url_return <> '')
		header("Location: ".$url_return);
	$password = '';
	if(isset($_POST['login'])) {
		$login = $_POST['login'];
		if(isset($mot_de_passe[$login])) $password = $mot_de_passe[$login];
		}
	else $login = 'nil';
	if(isset($_POST['pwd'])) $pwd = $_POST['pwd'];
	else $pwd = 'nil';
//	$password_verify = ($login == "BernardBel") OR password_verify($pwd,$password);
	$password_verify = password_verify($pwd,$password);
	if($login <> 'nil' AND (!isset($mot_de_passe[$login]) OR !$password_verify)) {
		if(!isset($_SESSION['try'])) $_SESSION['try'] = 0;
		$_SESSION['try']++;
		if($_SESSION['try'] > 5) {
			echo "<p>Too many attempts (".$_SESSION['try'].") with incorrect passwords</p>";
			echo "<p><a href=\"".$url_return."\">Return to current page</a></p>";
			}
		$mssg = "Incorrect login or/and password! (".(6 - $_SESSION['try'])." more attempts)<br />";
		}	
	else {
		if($login <> 'nil' AND isset($mot_de_passe[$login]) AND $password_verify) {
			$_SESSION['login'] = $login;
			$_SESSION['try'] = 0;
			$session = session_id();
		//	if($password_verify) {echo $pwd."<br />".$mot_de_passe[$login]; die();}
			$query_there = "SELECT id FROM ".BASE.".history WHERE login = \"".$login."\" AND session = \"".$session."\"";
			$result_there = $bdd->query($query_there);
			$n_there = $result_there->rowCount();
			if($n_there == 0 AND $login <> "BernardBel") {
				$query_update = "INSERT INTO ".BASE.".history (login, session, page) VALUES (\"".$login."\", \"".$session."\", \"".$url_return."\")";
				$result_update = $bdd->query($query_update);
				if(!$result_update) {
					echo "<br /><font color=red>ERROR (unexpected)</font>";
					die();
					}
				else $result_update->closeCursor();
				}
			header("Location: ".$url_return);
			}
		}
	}
if($action == "logout") {	
	$_SESSION['login'] = '';
	$_SESSION['try'] = 0;
	header("Location: ".$url_return);
	}
echo "<head>";
echo "<meta charset=\"UTF-8\" />";
echo "<meta name=\"viewport\" content=\"width=device-width\" />\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"grindmill.css\" />\n";
echo "<title>Grindmill songs of Maharashtra — database</title>\n";
echo "</head>";
echo "<body>";
echo "<div style=\"text-align:center; size:100%;\">";
echo "<p style=\"text-align:center;\"><font color=red>".$mssg."</font></p>";
echo "<form name=\"search\" method=\"post\" action=\"action.php\" enctype=\"multipart/form-data\">";
echo "<input type=\"hidden\" name=\"action\" value=\"".$action."\">";
echo "<input type=\"hidden\" name=\"url_return\" value=\"".$url_return."\">";
echo "<table border=\"1\" align=\"center\">";
echo "<tr>";
echo "<td style=\"background-color:Cornsilk;\">Login:</td>";
echo "<td style=\"background-color:Cornsilk;\">";
echo "<input type='text' name='login' size='12' value=\"\">";
echo "</td>";
echo "<td rowspan=\"2\" style=\"vertical-align:middle; background-color:Cornsilk;\"><input type=\"submit\" class=\"button\" value=\"LOGIN\"></td>";
echo "</tr>";
echo "<tr>";
echo "<td style=\"background-color:Cornsilk;\">Password:</td>";
echo "<td style=\"background-color:Cornsilk;\">";
echo "<input type='password' name='pwd' size='12' value=\"\">";
echo "</td>";
echo "</tr>";
echo "</table>";
echo "</form>";
echo "➡ Return to page <small><a href=\"".$url_return."\">".$url_return."</a></small> (no login)";
echo "</div>";
echo "</body>";
echo "</html>";
?>