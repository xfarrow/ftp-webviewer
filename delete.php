<?php
	session_start();
	require './includes/server_configs.php';

	$username = $_SESSION['username'];
	$password = $_SESSION['pass'];
	
	$ftp_connection = ftp_connect(FTP_SERVER,FTP_PORT,FTP_TIMEOUT) or die("Could not connect to " . FTP_SERVER);
	
	if(@ftp_login($ftp_connection,$username,$password)){
		
		//delete a file
		if(isset($_GET['name'])){
			$file_to_delete = $_GET['path']."/".$_GET['name'];
			if(@ftp_delete($ftp_connection,$file_to_delete)){
				header("Location: home.php?path=".$_GET['path']);
			}else{
				die("Could not delete file. Check your permissions");
			}
		}
		//else delete a folder
		else{
			$path = $_GET['path'];
			
			/*
			*	The path just before the one to delete, on oder to redirect there.
			*/
			$pos = strrpos($path,"/",-1);
			$previousPath = substr($path,0,$pos);
			
			ftp_rrmdir($ftp_connection,$path);
			
			header("Location: home.php?path=$previousPath");
		}
		ftp_close($ftp_connection);
	}
	
	/*
	*	Delete a folder, even if non-empty (ftp_rmdir() works only on empty directories)
	*/
	function ftp_rrmdir($ftp_connection, $directory){
	
		$documents = ftp_mlsd($ftp_connection,$directory);
		foreach($documents as $current_document){
			if($current_document['type'] == "file"){
				ftp_delete($ftp_connection,$directory."/".$current_document['name']);
			}else{
				ftp_rrmdir($ftp_connection,$directory."/".$current_document['name']);
			}
		}
		ftp_rmdir($ftp_connection, $directory);
	}

?>