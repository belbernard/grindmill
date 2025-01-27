<?php
$mot_de_passe['Bernard'] = '$2y$12$ADaQuDCjHS/R6u8e8m9X.eKWQoAhv1rerHbLrVDXtQyeaa2TxXaiq';
$mot_de_passe['Namita'] = '$2y$12$I/64P5nJJOQlIFokoDMaseyzQuUS1nByoYWanrudRAhB87bymt.0u';
$mot_de_passe['Sneha'] = '$2y$12$ubloaOqqWAR/v.Bsua2oCeS9QscXA7tQ3.8FI2NZlzlqswLZ9sSMq';
$mot_de_passe['Siddharth'] = '$2y$12$qWto304AbJu7tkmSAZi7junxKPNowkU92A7vkIYMZmq9CcQFUalsC';
$mot_de_passe['Asha'] = '$2y$12$tFaYIEwO59FhgCswf.gjq.kK.TwkuYBUq/FGZ4M370SrqeCGq7nQ2';
$mot_de_passe['Jyoti'] = '$2y$12$Ede0Vq39Ix2LTwUYIH/mxu17tQkjjYyYnRRehtlGbxvJWn/ww3Tsy';
$mot_de_passe['Rajani'] = '$2y$12$oE0rYsK3Ewgxmm7Jw3LsXuzMLtGruVY8d3KVJ4IJrC6nZ1gj5wKH.';
$mot_de_passe['TestEditor'] = '$2y$12$BLbYLX4.WuUbhKinapF2CepZWewY5IxM/nvKeMDxZUT58tejrIcg.';

$user_role['Bernard'] = "superadmin";
$user_role['Namita'] = "superadmin";
$user_role['Sneha'] = "admin";
$user_role['Siddharth'] = "admin";
$user_role['Asha'] = "translate";
$user_role['Rajani'] = "translate";
$user_role['Jyoti'] = "edit";
$user_role['TestEditor'] = "mapping";

$user_mail['Bernard'] = "bernarbel@gmail.com";
$user_mail['Namita'] = "namita.waikar@gmail.com";
$user_mail['Sneha'] = "9sneha.n@gmail.com";
$user_mail['Siddharth'] = "siddharth@ruralindiaonline.org";
$user_mail['Asha'] = "ashaogale36@gmail.com";
$user_mail['Rajani'] = "";
$user_mail['Jyoti'] = "";
$user_mail['TestEditor'] = "bernarbel@gmail.com";

// Below is a procedure used to temporarily read the id and encrypted password of a newly created user
$user_file = @fopen("_editor_list.txt","r");
if($user_file) {
	while(!feof($user_file)) {
		$line = fgets($user_file);
		$line = trim($line);
		if($line == '') continue;
		$table = explode(' ',$line);
		$name = $table[0];
		$user_role[$name] = $table[1];
		$mot_de_passe[$name] = $table[2];
		}
	fclose($user_file);
	}
?>