<?php
// https://perishablepress.com/protect-post-requests/
// https://www.askapache.com/online-tools/http-headers-tool/
$remote_ip = $remote_host = $user_agent = $method = $protocol = $referer = $post_vars = $request_uri = '';
if(isset($_GET['from'])) $from = "Attempt to read “".$_GET['from']."”";
else {
	$from = '';
	if(isset($_SERVER['REQUEST_URI'])) $request_uri = $_SERVER['REQUEST_URI'];
	}
if(is_integer(strpos($request_uri,"/database/error-handler.php"))) die();
// if($request_uri == "/database/error-handler.php") die();
if(isset($_SERVER['REMOTE_ADDR'])) $remote_ip = $_SERVER['REMOTE_ADDR'];
if(isset($_SERVER["REMOTE_HOST"])) $remote_host = $_SERVER["REMOTE_HOST"];
if(isset($_SERVER['HTTP_USER_AGENT'])) $user_agent = $_SERVER['HTTP_USER_AGENT'];
if(is_integer(strpos($user_agent,"//www.google.com"))) die();
if(isset($_SERVER['SERVER_PROTOCOL'])) $protocol = $_SERVER['SERVER_PROTOCOL'];
if(isset($_SERVER['HTTP_REFERER'])) $referer = $_SERVER['HTTP_REFERER'];

if($from <> '') $subject = "[Grindmill database] Illicit request (".$remote_ip.")";
else $subject = "[Grindmill database] Illicit POST request (".$remote_ip.")";
$post_vars = file_get_contents('php://input');
$url = "http://www.traceip.net/?query=".$remote_ip;
$link = "<a href=\"".$url."\">".$remote_ip."</a>";
$message = 'IP: ' . $link . "<br />";
$message .= 'HOST: ' . $remote_host . "<br />";
$message .= 'User Agent: ' . $user_agent . "<br />";
$message .= 'Protocol: ' . $protocol . "<br />";
$message .= 'Referer: ' . $referer . "<br />";
if($from == '') $message .= 'Request URI: ' . $request_uri . "<br />";
if($from <> '') $message .= $from."<br />";
else $message .= 'POST Vars: ' . $post_vars . "<br />";
// $mailfrom = 'Grindmill database';
$mailfrom = "bernarbel@gmail.com";
$mailto = "bernarbel@gmail.com";
// echo $message;
send_mail($mailfrom,"Superadmin",$mailto,$subject,$message);
echo "Error 403: you don't have permission to access pages on this server.";
exit();

function send_mail($mailfrom,$user,$mailto,$subject,$texte) {
	$name = str_replace('_',' ',$user);
	$to = "\"".$name."\" <".$mailto.">";
	$from = $mailfrom;
	ini_set("SMTP","smtp.ccrss.org");
//	ini_set("SMTP","mail-fr.securemail.pro");
	$JOUR  = date("Y-m-d");
	$HEURE = date("H:i");
	$headers = "From: ".$from." \n";
	$headers .= "Reply-to: ".$from." \n";
	$headers .= "MIME-Version: 1.0 \n";
	$headers .= "X-Priority: 1  \n";
	$headers .= "X-MSMail-Priority: High \n";
	$headers .= "Content-Type: text/html; charset=utf-8 \n";
	$mail_Data = "<html> \n";
	$mail_Data .= "<head> \n";
	$mail_Data .= "<title>".$subject."</title> \n";
	$mail_Data .= "</head> \n";
	$mail_Data .= "<body> \n";
	$mail_Data .= $texte."<br><br> \n";
	$mail_Data .= "</body> \n";
	$mail_Data .= "</html> \n";
	$CR_Mail = mail($to,$subject,$mail_Data,$headers);
	if($CR_Mail === FALSE)
   		echo "<p style=\"width:100%; text-align:center; color:red;\">Error sending email</p>";
	return $CR_Mail;
	}
?>