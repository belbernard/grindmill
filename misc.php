<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier_edit.php"); // Mandatory to write in check_serious_attempt()
require_once("_tasks.php");
require_once("_edit_tasks.php");

if(!check_serious_attempt('browse')) die();

$name = "Misc";
	
require_once("_header.php");

echo "<h2>Misc</h2>";

if(!identified()) {
	echo "<span style=\"color:red;\">You logged out, or your edit session expired.<br />You need to log in or return to the “edit start” page.</span>";
	die();
	}
$login = $_SESSION['login'];
$_SESSION['try'] = 0;

$old_time = time() - 3600;
$sql = "DELETE FROM ".BASE.".t_access WHERE acce_time < \"".$old_time."\"";
$result = $bdd->query($sql);
$result->closeCursor();

$date = date('Y-m-d H:i:s');

if(!is_editor($login)) {
	echo "<span style=\"color:red;\">Your status does not grant you access to this page.</span>";
	die();
	}

/* $query = "SELECT * FROM ".BASE.".recordings WHERE recording_DAT_index LIKE \"".$tape_code."%\" ORDER BY time_code_start";
$result = $bdd->query($query);
while($ligne = $result->fetch()) {
	$DAT_index = $ligne['recording_DAT_index'];
	$time_code_start = $ligne['time_code_start'];
	$time_code_end = $ligne['time_code_end'];
	$table1 = explode(':',$time_code_start);
	$table2 = explode(':',$time_code_end);
	if((count($table1) > 2 AND strlen($table1[0]) > 1) OR (count($table2) > 2 AND strlen($table2[0]) > 1))
		echo $DAT_index." “".$time_code_start."” “".$time_code_end."”<br />";
	}
$result->closeCursor(); */

/* $query = "SELECT song_id, recording_DAT_index, time_code_start FROM ".BASE.".songs WHERE time_code_start <> \"\"";
$result = $bdd->query($query);
$n = $result->rowCount();
while($ligne = $result->fetch()) {
	$song_id = $ligne['song_id'];
	$recording_DAT_index = $ligne['recording_DAT_index'];
	$new_DAT_index = guess_DAT_index(FALSE,$song_id);
	$time_code_start = $ligne['time_code_start'];
	if($recording_DAT_index <> $new_DAT_index)
		echo "#".song($song_id,$song_id)." ".$time_code_start." ".$recording_DAT_index." -> ".$new_DAT_index."<br />";
	}
$result->closeCursor(); */

/* $query = "SELECT * FROM ".BASE.".recordings ORDER BY recording_DAT_index";
$result = $bdd->query($query);
$oldtape = '';
while($ligne = $result->fetch()) {
	$recording_DAT_index = $ligne['recording_DAT_index'];
	$table = explode('-',$recording_DAT_index);
	$tape = $table[0]."-".$table[1];
	if($tape <> $oldtape) {
		$oldtape = $tape;
		$old_end = 0;
		}
	$time_code_start = $ligne['time_code_start'];
	$time_code_end = $ligne['time_code_end'];
	$seconds_start = time_code_to_seconds($time_code_start);
	$seconds_end = time_code_to_seconds($time_code_end);
	if($seconds_start < $old_end) {
		echo $recording_DAT_index." ".$time_code_start." < ".$old_time_code_end."<br />";
		}
	$old_end = $seconds_end;
	$old_time_code_end = $time_code_end;
	}
$result->closeCursor();  */

$song_id = 2607;

$query = "SELECT * FROM ".BASE.".songs WHERE song_id = \"".$song_id."\"";
$result = $bdd->query($query);
$ligne = $result->fetch();
$result->closeCursor();
$devanagari = $ligne['devanagari'];
echo $devanagari."<br />";
$roman = $ligne['roman_devanagari'];
echo $roman."<br /><br />";

$devanagari = str_replace("<br />","\n",$devanagari);
$roman = str_replace("<br />","\n",$roman);
/* for($i = 0; $i < strlen($devanagari); $i++) {
	$c = mb_substr($devanagari,$i,1,'UTF-8');
	if($c == ' ') echo " — ";
	else echo $c.' ';
	}
echo "<br /><br />"; */

/* $query = "SELECT * FROM ".BASE.".dev_roman ORDER BY roman DESC LIMIT 10";
$result = $bdd->query($query);
while($ligne = $result->fetch()) {
	$devanagari = $ligne['devanagari'];
	$roman = $ligne['roman'];
	echo $devanagari." —> ".$roman."<br />";
	}
$result->closeCursor(); */

if(isset($_POST['transliteration']) AND $_POST['transliteration'] <> '') {
	$transliteration = $_POST['transliteration'];
	$found = FALSE;
	$source = '';
	for($i = 0; $i < strlen($devanagari); $i++) {
		$c = mb_substr($devanagari,$i,1,'UTF-8');
		$code = uniord($c);
		$this_char = "char_".$i;
		if(isset($_POST[$this_char])) {
			echo $code.' '; $found = TRUE;
			$source .= $c;
			}
		}
	if($found) echo "<br />".$source." —> ".$transliteration;
	}

$source = '';
for($i = 0; $i < strlen($devanagari); $i++) {
	$c = mb_substr($devanagari,$i,1,'UTF-8');
	if($c == ' ' OR $c == "\n") {
		$transliteration .= $c;
		$source = '';
		continue;
		}
	$source .= $c;
	$query = "SELECT * FROM ".BASE.".dev_roman WHERE devanagari = \"".$source."\" ORDER BY roman DESC LIMIT 1";
	$result = $bdd->query($query);
	$n = $result->rowCount();
	if($n > 0) {
		$ligne = $result->fetch();
		$transliteration .= $ligne['roman'];
		$source = '';
		}
	$result->closeCursor();
	}
echo "==> ".$transliteration."<br />";

echo "<form method=\"post\" action=\"".$url_this_page."\" enctype=\"multipart/form-data\">";
echo "<table><tr>";
for($i = 0; $i < strlen($devanagari); $i++) {
	$c = mb_substr($devanagari,$i,1,'UTF-8');
	$code = uniord($c);
	if($c == "\n") {
		echo "</tr><tr>";
		continue;
		}
	if($c == '') break;
	echo "<td>";
	if($c <> ' ') {
		echo $c."<br />";
		echo "<small>".$code."</small><br />";
		echo "<input type=\"checkbox\" name=\"char_".$i."\" value=\"ok\" />";
		}
	echo "</td>";
	}
echo "</tr><td colspan=\"20\">";
echo "Transliterate as:&nbsp;<input type=\"text\" name=\"transliteration\" size=\"15\" value=\"\" />";
echo "&nbsp;<input class=\"button\" type=\"submit\" value=\"SAVE\">";
echo "</td><tr>";
echo "</tr><table>";
echo "</form>";





$text1 = $text2 = '';
for($i = 0; $i < strlen($devanagari); $i++) {
	$c = mb_substr($devanagari,$i,1,'UTF-8');
	$d = mb_substr($roman,$i,1,'UTF-8');
	if($c == '' AND $d == '') break;
	$c_code = uniord($c);
	$d_code = uniord($d);
	if($c == ' ') $c = "(space)";
	if($d == ' ') $d = "(space)";
	echo "<small>".$i."</small> — ".$c_code.' '.$c." — ".$d_code.' '.$d."<br />";
	if($c <> '') $text1 .= uchr($c_code);
	if($d <> '') $text2 .= uchr($d_code);
	}
echo "<br />";
echo str_replace("\n","<br />",$text1)."<br />".str_replace("\n","<br />",$text2);
echo "<br />";
echo "<p>length devanagari = ".mb_strlen($devanagari)."</p>";
echo "<p>length text1 = ".mb_strlen($text1)."</p>";
echo "<p>length roman = ".mb_strlen($roman)."</p>";
echo "<p>length text2 = ".mb_strlen($text2)."</p>";
$offset = 0;
$string = '';
while($offset >= 0) {
	$result = ordutf8($devanagari,$offset);
	$code = $result['code'];
	$sequ = $result['sequ'];
	$string .= $sequ." ";
    echo $offset.": ".$code." [".$sequ."]<br />";
	}
/*
$table = explode(' ',trim($string));
$text = '';
for($i = 0; $i < count($table); $i++)
	$text .= uchr($table[$i] + 128);
echo $text; */

echo "</body>";
echo "</html>";

function uchr ($code) {
    $str = html_entity_decode('&#'.$code.';',ENT_NOQUOTES,'UTF-8');
    return $str;
}

function unichr($u) {
    return mb_convert_encoding('&#' . intval($u) . ';', 'UTF-8', 'HTML-ENTITIES');
}

function ordutf8($string, &$offset) {
    $sequ = $code = ord(substr($string, $offset,1));
    if ($code >= 128) {        //otherwise 0xxxxxxx
        if ($code < 224) $bytesnumber = 2;                //110xxxxx
        else if ($code < 240) $bytesnumber = 3;        //1110xxxx
        else if ($code < 248) $bytesnumber = 4;    //11110xxx
        $codetemp = $code - 192 - ($bytesnumber > 2 ? 32 : 0) - ($bytesnumber > 3 ? 16 : 0);
        for ($i = 2; $i <= $bytesnumber; $i++) {
            $offset ++;
            $code2 = ord(substr($string, $offset, 1)) - 128;        //10xxxxxx
            $sequ .= " ".$code2;
            $codetemp = $codetemp*64 + $code2;
        	}
        $code = $codetemp;
    	}
    $offset += 1;
    if ($offset >= strlen($string)) $offset = -1;
    $result['code'] =  $code;
    $result['sequ'] =  $sequ;
    return $result;
	}

function uniord($u) {
    $k = mb_convert_encoding($u, 'UCS-2LE', 'UTF-8');
    $k1 = ord(substr($k, 0, 1));
    $k2 = ord(substr($k, 1, 1));
    return $k2 * 256 + $k1; 
	}
?>