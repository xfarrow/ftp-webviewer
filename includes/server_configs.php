<?php
	const FTP_SERVER = "127.0.0.1";
	const FTP_PORT = 21;
	const FTP_TIMEOUT = 90;
	
	/*
	*	The path where files and folders will be temporaily stored on the HTTP server 
	*	in order to be sent to the client.
	*	(NEEDS TO ALREADY EXIST)
	*/
	const TMP_DOWNLOAD_FOLDER_PATH = "tmpDownloads/";
	
	/*
	*	Local tmp folder where the file needs to temporaily be stored on the HTTP server
	*	in order to be sent to the FTP server.
	*	NOTICE: this folder is not necessary since the file gets already stored in array
	*	$_FILES['inputFile']['tmp_name'], so you can adjust this as you wish. We used another
	*	folder to make things clearer and consistent.
	*	(NEEDS TO ALREADY EXIST)
	*/
	
	const TMP_UPLOAD_FOLDER_PATH = "tmpUploads/";
?>