<?php
require_once("_base_urls.php");
require_once("_relier_edit.php");
require_once("_users.php");
require_once("_tasks.php");
ini_set('memory_limit','300M');

$verbose = FALSE;
$trace = TRUE;
set_time_limit(300);

$backup_path = "DB_DUMP"; 
$base_path = "/home/ccrssovhrp/www/database/DB_DUMP";
chdir($base_path);
$olddir = getcwd();
if($verbose) echo $olddir."<br />";
$trace_file = FALSE;
$date = date("Y-m-d");
if(!file_exists($date)) {
	$result = mkdir($date,0705);
	if(!$result) die();
	chdir($date);
	if($trace) {
		$trace_file = fopen($olddir."/crontab-trace.txt",'w');
		if($trace_file) {
			fprintf($trace_file,"%s\r\n",date('Y-m-d H:i:s'));
			fprintf($trace_file,"%s\r\n","oldir = ".$olddir);
			fprintf($trace_file,"%s\r\n","datedir = ".getcwd());
			}
		}
	$tables = array();
	backup_tables($verbose,$tables,TRUE,"sql");
	$tables = array();
	backup_tables($verbose,$tables,FALSE,"sql");
	$tables = array();
	backup_tables($verbose,$tables,FALSE,"csv");
	backup_settings($verbose);
	chdir("../../");
	if($trace_file)
		fprintf($trace_file,"%s\r\n","newdir = ".getcwd());
	ClearBackups($verbose);
	if($trace_file)	fclose($trace_file);
	}
?>