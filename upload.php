<?php
	/*
	*	Multiple files can be uploaded.
	*	References used: 
	*	https://www.studentstutorial.com/php/php-multiple-file-upload & 
	*	https://www.php.net/manual/en/features.file-upload.multiple.php
	*/
	
	session_start();
	
	require './includes/server_configs.php';
	
	if(!isset($_FILES['inputFile'])) die("No file sumbitted.");
	
	$uploaded_file_count = count($_FILES['inputFile']['name']);
	
	$username = $_SESSION['username'];
	$password = $_SESSION['pass'];
	
	$ftp_connection = ftp_connect(FTP_SERVER,FTP_PORT,FTP_TIMEOUT) or die("Could not connect to " . FTP_SERVER);
	
	if(@ftp_login($ftp_connection,$username,$password)){
		
		for($i=0; $i<$uploaded_file_count;$i++){
			$local_tmp_file = TMP_UPLOAD_FOLDER_PATH . $_FILES['inputFile']['name'][$i];
			move_uploaded_file($_FILES['inputFile']['tmp_name'][$i], $local_tmp_file);
		
			if(@ftp_put($ftp_connection,$_GET['path']."/".$_FILES['inputFile']['name'][$i],$local_tmp_file,FTP_BINARY)){
				unlink($local_tmp_file);
				header("Location: home.php?path=".$_GET['path']);
			}else{
				echo "Unable to save ".$_FILES['inputFile']['name'][$i]."on the FTP server. Are you authorized to write?";
			}
		}
		ftp_close($ftp_connection);		
	}
?>