<?php
	session_start();
	require './includes/server_configs.php';
	
	if(!isset($_GET['path']) or !isset($_GET['old_name']) or !isset($_GET['new_name'])) die("Not enough arguments provided.");
	
	$old_name = $_GET['path']."/".$_GET['old_name'];
	$new_name = $_GET['path']."/".$_GET['new_name'];	
	
	$username = $_SESSION['username'];
	$password = $_SESSION['pass'];
	
	$ftp_connection = ftp_connect(FTP_SERVER,FTP_PORT,FTP_TIMEOUT) or die("Could not connect to " . FTP_SERVER);

	if(@ftp_login($ftp_connection,$username,$password)){
		if(@ftp_rename($ftp_connection,$old_name,$new_name)) header("Location: home.php?path=".$_GET['path']);
		else{
			ftp_close($ftp_connection);
			echo "Could not rename. Check permissions";
		}
	}
?>