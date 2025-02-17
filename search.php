<?php
// session_start();
require_once("_base_urls.php");
require_once("_relier.php");
require_once("_tasks.php");
$name = "Search";
$canonic_url = '';
require_once("_header.php");

echo "<h2>Search Grindmill songs database</h2>";

/* echo "page session status = " . session_status();
echo "<br />page session_id = " . session_id();
echo "<br />login = ";
var_dump($_SESSION['login']);
echo "<br />color1 = ";
var_dump($_SESSION['favcolor']);
$_SESSION['favcolor'] = 'green';
echo "<br />color2 = ";
var_dump($_SESSION['favcolor']);
echo "<br />"; */

echo "<blockquote>";
if(is_translator($login)) {
	echo "<p style=\"padding-top:24px;\" ><span style=\"color:red;\">➡ </span><a href=\"replace.php\" target=\"_blank\">Search and replace procedures</a></p>";
	}
else echo "<p style=\"padding-top:24px;\" ><span style=\"color:red;\">➡ </span>You need to <a href=\"action.php?action=login&url_return=".$url_this_page."\">log in</a> to access more features.</p>";

echo "</blockquote>";
echo "<table>";
echo "<tr>";

echo "<td>";
echo "<table border=1>";
echo "<tr>";
echo "<td>Serial number of song</td>";
echo "<td><form name=\"search\" method=\"post\" action=\"songs.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='song_id' size='12' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">31632</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Songs in group…</td>";
echo "<td><form name=\"search\" method=\"post\" action=\"songs.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='group_id' size='12' value=\"\"></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Singer name (English)<br /><i>starts with…</i></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"performer.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='performer_name_english' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">Ambo</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Singer name (Marathi)<br /><i>starts with…</i></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"performer.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='performer_name_devanagari' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">रांज</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Village name (English)<br /><i>starts with…</i><br /><a href=\"villages.php\" target=\"_blank\">Link to all villages</a></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"location.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='village_english' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">Raj</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Village name (Marathi)<br /><i>starts with…</i><br /><a href=\"villages.php\" target=\"_blank\">Link to all villages</a></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"location.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='village_devanagari' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">आंबे</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "</table>";
echo "</td>";

echo "<td>";
echo "<table border=1>";
echo "<tr>";
echo "<td>Taluka name (English)<br /><i>starts with…</i></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"location.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='taluka_english' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">Mul</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Taluka name (Marathi)<br /><i>starts with…</i></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"location.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='taluka_devanagari' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">राहु</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>District name (English)<br /><i>starts with…</i></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"location.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='district_english' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">Usm</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>District name (Marathi)<br /><i>starts with…</i></td>";
echo "<td><form name=\"search\" method=\"post\" action=\"location.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='district_devanagari' size='25' value=\"\"><br /><small>e.g. <span style=\"color:blue;\">अमरा</span></small></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Word(s) in English translation of song</td>";
echo "<td colspan=\"2\"><form name=\"search\" method=\"post\" action=\"songs.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='translation_english' size='50' value=\"\"><br /><small>e.g. “<span style=\"color:blue;\">(wives OR cat) AND brother</span>”</small><span style=\"color:red;\">&nbsp;(*)</span></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Word(s) in French translation of song</td>";
echo "<td colspan=\"2\"><form name=\"search\" method=\"post\" action=\"songs.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='translation_french' size='50' value=\"\"><br /><small>e.g. “<span style=\"color:blue;\">stylo, 'or' and (Ambedkar, Bhim, Baba, Babasaheb)</span>”</small><span style=\"color:red;\">&nbsp;(*)</span></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Word(s) in Devanagari transcription of song</td>";
echo "<td colspan=\"2\"><form name=\"search\" method=\"post\" action=\"songs.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='devanagari' size='50' value=\"\"><br /><small>e.g. “<span style=\"color:blue;\">पाताळाला OR (सुद AND NOT तशी)</span>”</small><span style=\"color:red;\">&nbsp;(*)</span></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "<tr>";
echo "<td>Word(s) in Roman transcription of song</td>";
echo "<td colspan=\"2\"><form name=\"search\" method=\"post\" action=\"songs.php\" enctype=\"multipart/form-data\" target=\"_blank\">";
echo "<input type='text' name='roman_devanagari' size='50' value=\"\"><br /><small>e.g. “<span style=\"color:blue;\">(kavatīkyā OR nī) AND (mājhā OR aṅga) AND NOT gēla</span>”</small><span style=\"color:red;\">&nbsp;(*)</span></td>";
echo "<td><input type=\"submit\" class=\"button\" value=\"SEARCH\"></td>";
echo "</form>";
echo "</tr>";
echo "</table>";
echo "</td>";

echo "</tr>";
echo "</table>";

echo "<blockquote><span style=\"color:red;\">(*)</span> Operators in logical expressions:<ul>";
echo "<li>Common format ‘OR’, ‘AND’, ‘NOT’</li>";
echo "<li>Compact format: ‘+’, ‘.’, ‘!’</li>";
echo "<li>Alternate format: ‘||’, ‘or’, comma, ‘and’, ‘!’…</li>";
echo "<li>Equivalent examples:<ul>";
echo "<li><a href=\"".SITE_URL."songs.php?roman_devanagari=(kavatīkyā OR nī) AND (mājhā OR aṅga) AND NOT gēla\" target=\"_blank\">(kavatīkyā OR nī) AND (mājhā OR aṅga) AND NOT gēla</a></li>";
echo "<li><a href=\"".SITE_URL."songs.php?roman_devanagari=(kavatīkyā, nī) AND (mājhā, aṅga) and not gēla\" target=\"_blank\">(kavatīkyā, nī) AND (mājhā, aṅga) and not gēla</a> <small>[Comma = OR]</small></li>";
echo "<li><a href=\"".SITE_URL."songs.php?roman_devanagari=(kavatīkyā+nī).(mājhā+aṅga).!gēla\" target=\"_blank\">(kavatīkyā+nī).(mājhā+aṅga).!gēla</a></li>";
echo "<li><a href=\"".SITE_URL."songs.php?roman_devanagari=(kavatīkyā || nī) and (mājhā || aṅga) and !gēla\" target=\"_blank\">(kavatīkyā || nī) and (mājhā || aṅga) and !gēla</a></li>";
echo "<li><a href=\"".SITE_URL."songs.php?translation_french=stylo, 'or' and (Ambedkar, Bhim, Bhimrao, Bhimraja, Bhimraya, Baba, Babasaheb)\" target=\"_blank\">stylo, 'or' and (Ambedkar, Bhim, Bhimrao, Bhimraja, Bhimraya, Baba, Babasaheb)</a><br /><small>[use single quotes to qualify a word avoiding confusion with an operator]</small></li>";
echo "</ul>";
echo "</ul></blockquote>";

echo "</body>";
echo "</html>";
?>