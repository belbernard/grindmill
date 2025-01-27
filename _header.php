<?php
// session_start();
$this_page = substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1);
if($_SERVER['QUERY_STRING'] <> '') {
	$url_this_page = urldecode(substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1)."?".$_SERVER['QUERY_STRING']);
	$url_this_page = str_replace(' ','+',$url_this_page);
//	echo "url1 = ".$url_this_page."<br />";
	}
else {
	$url_this_page = urlencode(substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/")+1));
	// The following variables had been set by POST instructions...
//	echo "url2 = ".$url_this_page."<br />";
	if(isset($song_id) AND $song_id > 0) $url_this_page .= "?song_id=".$song_id;
	else if(isset($group_id) AND $group_id <> '') $url_this_page .= "?group_id=".$group_id;
	else if(isset($song_list) AND $song_list <> '') $url_this_page .= "?song_list=".$song_list;
//	else if(isset($semantic_class_title_prefix) AND $semantic_class_title_prefix <> '') $url_this_page .= "?class=".$semantic_class_title_prefix;
	else if(isset($recording_DAT_index) AND $recording_DAT_index <> '') $url_this_page .= "?recording_DAT_index=".$recording_DAT_index;
	else if(isset($translation_english) AND $translation_english <> '') $url_this_page .= "?translation_english=".$translation_english;
	else if(isset($translation_french) AND $translation_french <> '') $url_this_page .= "?translation_french=".$translation_french;
	else if(isset($devanagari) AND $devanagari <> '') $url_this_page .= "?devanagari=".$devanagari;
	else if(isset($roman_devanagari) AND $roman_devanagari <> '') $url_this_page .= "?roman_devanagari=".$roman_devanagari;
	else if(isset($location_id) AND $location_id > 0) $url_this_page .= "?location_id=".$location_id;
	else if(isset($village_english) AND $village_english <> '') $url_this_page .= "?village_english=".$village_english;
	else if(isset($village_devanagari) AND $village_devanagari <> '') $url_this_page .= "?village_devanagari=".$village_devanagari;
	else if(isset($taluka_english) AND $taluka_english <> '') $url_this_page .= "?taluka_english=".$taluka_english;
	else if(isset($taluka_devanagari) AND $taluka_devanagari <> '') $url_this_page .= "?taluka_devanagari=".$taluka_devanagari;
	else if(isset($district_english) AND $district_english <> '') $url_this_page .= "?district_english=".$district_english;
	else if(isset($district_devanagari) AND $district_devanagari <> '') $url_this_page .= "?district_devanagari=".$district_devanagari;
	else if(isset($performer_id) AND $performer_id > 0) $url_this_page .= "?performer_id=".$performer_id;
	else if(isset($performer_name_english) AND $performer_name_english <> '') $url_this_page .= "?performer_name_english=".$performer_name_english;
	else if(isset($performer_name_devanagari) AND $performer_name_devanagari <> '') $url_this_page .= "?performer_name_devanagari=".$performer_name_devanagari;
	}
if(isset($include_french) AND $include_french <> '' AND !is_integer(strpos($url_this_page,"include_french"))) {
	if(is_integer(strpos($url_this_page,'?')))
		$url_this_page .= "&include_french=".$include_french;
	else
		$url_this_page .= "?include_french=".$include_french;
	}
if(isset($_SESSION['login']) AND $_SESSION['login'] <> '') {
	$url_logout = "action.php?action=logout&url_return=".$url_this_page;
	$login = $_SESSION['login'];
	}
else {
	$url_login = "action.php?action=login&url_return=".$url_this_page;
	$login = '';
	}
	
// echo "<!DOCTYPE HTML>\n";
echo "<html lang=\"en\">\n";
echo "<head>\n";
echo "<meta charset=\"UTF-8\" />";
echo "<meta name=\"viewport\" content=\"width=device-width\" />\n";
echo "<link rel=\"stylesheet\" type=\"text/css\" href=\"grindmill.css\" />\n";
echo "<title>".$name." - Grindmill songs of Maharashtra — database</title>\n";
echo "<meta name=\"application-name\" content=\"सांगते बाई तुला  ♪ I tell you woman! ♪♪ on www.ccrss.org\" />\n";
echo "<meta name=\"msapplication-window\" content=\"width=device-width;height=device-height\" />\n";
echo "<meta name=\"title\" content=\"".$name." - Grindmill songs of Maharashtra — database\" />\n";
echo "<meta name=\"description\" content=\"Database on Grindmill songs of Maharashtra - Chants de la mouture au Maharashtra\" />\n";

$description = $name." - Grindmill songs of Maharashtra — database";
echo "<meta property=\"fb:app_id\" content=\"682792208773227\" />\n";
echo "<meta property=\"og:locale\" content=\"en_UK\" />\n";
echo "<meta property=\"og:type\" content=\"website\" />\n";
echo "<meta property=\"og:image:type\" content=\"image/png\" />\n";
echo "<meta property=\"og:image\" content=\"https://ccrss.org/database/images/meule-large.png\" />\n";
echo "<meta property=\"og:title\" content=\"".$name."\" />\n";
echo "<meta property=\"og:description\" content=\"".$description."\" />\n";
if($canonic_url <> '') echo "<meta property=\"og:url\" content=\"".$canonic_url."\" />\n";
echo "<meta property=\"og:site_name\" content=\"Grindmill songs of Maharashtra\" />\n";
echo "<meta name=\"twitter:description\" content=\"".$description."\" />\n";
echo "<meta name=\"twitter:title\" content=\"".$name."\" />\n";
echo "<meta name=\"twitter:image\" content=\"image/png\" />\n";

echo "<link rel=\"apple-touch-icon\" sizes=\"57x57\" href=\"images/apple-icon-57x57.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"60x60\" href=\"images/apple-icon-60x60.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"72x72\" href=\"images/apple-icon-72x72.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"76x76\" href=\"images/apple-icon-76x76.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"114x114\" href=\"images/apple-icon-114x114.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"120x120\" href=\"images/apple-icon-120x120.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"144x144\" href=\"images/apple-icon-144x144.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"152x152\" href=\"images/apple-icon-152x152.png\">";
echo "<link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"images/apple-icon-180x180.png\">";
echo "<link rel=\"icon\" type=\"image/png\" sizes=\"192x192\"  href=\"images/android-icon-192x192.png\">";
echo "<link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"images/favicon-32x32.png\">";
echo "<link rel=\"icon\" type=\"image/png\" sizes=\"96x96\" href=\"images/favicon-96x96.png\">";
echo "<link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"images/favicon-16x16.png\">";

if(isset($canonic_url) AND $canonic_url <> '') echo "<link rel=\"canonical\" href=\"".$canonic_url."\" />\n";
echo "<link href=\"https://fonts.googleapis.com/css?family=Playfair+Display+SC:700,900,400\" rel=\"stylesheet\" type=\"text/css\">\n";
echo "<link href=\"https://fonts.googleapis.com/css?family=Playfair+Display:700,900\" rel=\"stylesheet\" type=\"text/css\">\n";
echo "<link href=\"https://fonts.googleapis.com/css?family=Merriweather:400,300,700,700italic,300italic,400italic\" rel=\"stylesheet\" type=\"text/css\">\n";
echo "<link href=\"https://fonts.googleapis.com/css?family=Merriweather+Sans:400italic,700italic\" rel=\"stylesheet\" type=\"text/css\">\n";
echo "</head>\n";
echo "<body>\n";
echo "<div style=\"position:absolute; left:16px; top:8px;\">";
if($browser_name == "Safari")
	echo "</div><p><small><span style=\"color:red;\">➡&nbsp;Chrome or Firefox better<br />options to access OGG files!</span></small></p>";
else {
	if($bottom_of_page <> '') echo $bottom_of_page."<br />";
	echo "<small><small>Database design: <a target=\"_blank\" href=\"https://en.wikipedia.org/wiki/User:Belbernard\">Bernard Bel</a></small></small>";
	echo "</div>";
	}
if(!$hide_url) {
	echo "<div style=\"text-align:center; margin-left:2em; width:100%;\">";
	$url = SITE_URL.$url_this_page;
/*	$url = str_replace("%3F",'?',$url);
	$url = str_replace("%3D",'=',$url);
	$url = str_replace("%26",'&',$url);
	$url = str_replace("%2C",',',$url);
	$url = str_replace("%20",' ',$url);
	$url = str_replace("%2F",'/',$url);
	$url = str_replace("%25",'%',$url); */
	$url = str_replace('"','',$url);
	$link_this_page = "<a href=\"".$url."\">".str_replace('=',"<br />=&nbsp;",urldecode($url))."</a>";
	echo "<small>".$link_this_page."</small>";
//	echo "<br />".$this_page;
	echo "</div>";
	}
if($login <> '') {
	echo "<div style=\"text-align:right; margin-left:10px; position:absolute; right:16px; top:8px;\"><small>User: <i>".$login."</i></small> [<a href=\"".$url_logout."\">Logout</a>]<br /><small>";
	if(isset($user_role[$login]) AND ($user_role[$login] == "translate" OR $user_role[$login] == "admin" OR $user_role[$login] == "superadmin"))
		echo "➡ <a target=\"_blank\" href=\"workset.php\">My work sets</a><br />";
	if(isset($user_role[$login]) AND ($user_role[$login] == "translate" OR $user_role[$login] == "admin" OR $user_role[$login] == "superadmin"))
		echo "➡ <a target=\"_blank\" href=\"admin.php\">Admin page</a></small>";
	echo "</div>";
	}
else
	echo "<div style=\"text-align:right; margin-left:10px; position:absolute; right:16px; top:8px;\">[<a href=\"".$url_login."\">Login</a>]</div>";
echo "<div style=\"width:100%\"><h1><i>Project</i>&nbsp;&nbsp;<a target=\"_blank\" href=\"https://grindmill.org/\" title=\"सांगते बाई तुला - I tell you woman!\">♪♪ सांगते बाई तुला - Sāṅgatē bāī tulā - I tell you woman! ♪♪</a></h1></div>\n";
echo "<div style=\"float:right; background-color:Bisque; padding:10px; margin:10px;\">";
echo "<small>Read <a target=\"_blank\" title=\"https://grindmill.org/project/english/\" href=\"https://grindmill.org/project/english/\">project statement</a></small><br />\n";
echo "<small>With the support of <a target=\"_blank\" title=\"https://ruralindiaonline.org/\" href=\"https://ruralindiaonline.org/\"><i>People’s Archive of Rural India</i></a> (PARI)</small><br />\n";
echo "<small>Source documents produced by <a target=\"_blank\" title=\"https://gdspune.org\" href=\"https://gdspune.org\">GDS</a> / <a target=\"_blank\" title=\"https://ccrss.org\" href=\"https://ccrss.org\">CCRSS</a></small><br />\n";
echo "<small>Devanagari to Diacritic Roman <a target=\"_blank\" href=\"https://techwelkin.com/tools/transliteration/?from=devanagari&to=diacritic\">conversions</a> with <a target=\"_blank\" title=\"TechWelkin\" href=\"https://techwelkin.com/\">TechWelkin</a></small><br />\n";
echo "<div style=\"float:right;\">";
$link = "https://creativecommons.org/licenses/by-nc/4.0/";
$picture = "https://i.creativecommons.org/l/by/4.0/88x31.png";
echo "<p style=\"text-align:right\"><small><a href=\"".$link."\" target=\"_blank\" title=\"Credit: grindmill.org\">Creative Commons<br />";
echo "<img src=\"".$picture."\" width=\"88\" alt=\"CC license\"  style=\"text-align:right;\" /></a><br />";
echo "<a target=\"_blank\" href=\"https://grindmill.org/\" title=\"https://grindmill.org\">grindmill.org</a></small></p>";
echo "</div>";
if($this_page <> "recordings.php") echo "&nbsp;<small>".SOUND_ICON." Recordings: <a target=\"_blank\" href=\"recordings.php\">recordings.php</a></small><br />\n";
echo "&nbsp;• <small>Performers: <a target=\"_blank\" href=\"performer.php\">performer.php</a></small><br />\n";
if($this_page <> "villages.php") echo "&nbsp;• <small>Locations: <a target=\"_blank\" href=\"villages.php\">villages.php</a></small><br />\n";
if($this_page <> "classification.php") echo "&nbsp;• <small>Classification: <a target=\"_blank\" href=\"classification.php\">classification.php</a></small><br />\n";
if($this_page <> "groups.php") echo "&nbsp;• <small>Groups: <a target=\"_blank\" href=\"groups.php\">groups.php</a></small><br />\n";
echo "&nbsp;• <small>Glossary: <a target=\"_blank\" href=\"glossary.php\">glossary.php</a></small><br />\n";
if($this_page <> "search.php") echo "&nbsp;<small><span style=\"color:red;\"><b>⚲ Search:</b></span> <a target=\"_blank\" href=\"search.php\">search.php</a></small>\n";
echo "</div>";
?> 