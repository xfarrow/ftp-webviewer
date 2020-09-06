<?php

	session_start();
	
	require './includes/server_configs.php';
	
	if(!isset($_GET['path'])){
		echo "Nothing to download(?)";
		die();
	}
	
	$remote_file_path = $_GET['path']; 
	
	if(!isset($_SESSION['username']) or !isset($_SESSION['pass'])){
		echo "Login first";
		die();
	}
	
	$username = $_SESSION['username'];
	$password = $_SESSION['pass'];
	
	$ftp_connection = ftp_connect(FTP_SERVER,FTP_PORT,FTP_TIMEOUT) or die("Could not connect to" . FTP_SERVER);

	if(@ftp_login($ftp_connection,$username,$password)){
		
		/*
		*	if name is set, it has to download one single file,
		*	else it has to download a folder.
		*/
		if(isset($_GET['name'])){
			
			$remote_file_name = $_GET['name'];
			getFile($remote_file_name,$remote_file_path,$ftp_connection,TMP_DOWNLOAD_FOLDER_PATH);
			downloadFile(TMP_DOWNLOAD_FOLDER_PATH.$remote_file_name);
			
			//deletes file from the temp directory
			unlink(TMP_DOWNLOAD_FOLDER_PATH.$remote_file_name);
				
			
		}else
		{ 
			//the name of the folder where the downloaded folder is stored.
			$folder_name = getDirectory($remote_file_path,$ftp_connection,TMP_DOWNLOAD_FOLDER_PATH);
			
			zipFolder(TMP_DOWNLOAD_FOLDER_PATH.$folder_name);
			downloadFile(TMP_DOWNLOAD_FOLDER_PATH.$folder_name.".zip");
			
			//Deletes folder and the zipped folder from the temp directory
			rrmdir(TMP_DOWNLOAD_FOLDER_PATH.$folder_name);
			unlink(TMP_DOWNLOAD_FOLDER_PATH.$folder_name.".zip");
		}
	}

	/*
	*	Downloads a single file on the HTTP server.
	*
	*	1st argument: remote file name;
	*	2nd argument: remote file path;
	*	3rd argument: result of ftp_connect();
	*	4th argument: local folder path where the file has to be saved. If it's -1, it
	*	gets saved in the root directory of the local file system.
	*/
	function getFile($remote_file_name,$remote_file_path,$ftp_connection,$local_folder_path){
		ftp_get($ftp_connection,$local_folder_path."/".$remote_file_name,$remote_file_path."/".$remote_file_name,FTP_BINARY);	
	}
	
	/*
	*	Downloads a directory on the HTTP server, its files (using getFile) and its sub-directories, recursively.
	*
	*	1st argument: remote path to download (it's a directory);
	*	2nd argument: result of ftp_connect();
	*	3rd argument: where to save this folder on the local file system.
	*/
	function getDirectory($remote_path,$ftp_connection,$local_folder_path){
		
		/*
		*	the folder's name in the local file system is the
        *	string after the last '/'
		*	e.g. if the folder is is /home/user/myFolder, it will be 
		*	saved as "myFolder".
		*	If it's the root folder, it will be saved as "root_directory", since
		*	a folder whose name is nothing can't be created.
		*/
		
		if($remote_path!="/"){
			$local_folder_name = basename($remote_path);
		}else{
			$local_folder_name = "root_directory";
		}
		
		/*
		* crate folder on the HTTP server
		*/
		mkdir($local_folder_path.$local_folder_name,0777);
		
		
		
		/*
		* get a list of all the documents and folders in the current folder
		*/		
		$documents = ftp_mlsd($ftp_connection,$remote_path);
			
		foreach($documents as $current_document){
			
			/*
			* if it's a file, download the file in the local_folder_name
			*/
			if($current_document['type'] == "file"){
				if($local_folder_path=="/"){
					getFile($current_document['name'],$remote_path,$ftp_connection,$local_folder_path.$local_folder_name);
				}else{
					getFile($current_document['name'],$remote_path,$ftp_connection,$local_folder_path."/".$local_folder_name);
				}
				
			}
			/*
			* if it's a folder, recursively call this function
			*/
			else if($current_document['type'] == "dir"){
				if($local_folder_path=="/"){
					getDirectory($remote_path."/".$current_document['name'],$ftp_connection,$local_folder_path.$local_folder_name);
				}else{
					getDirectory($remote_path."/".$current_document['name'],$ftp_connection,$local_folder_path."/".$local_folder_name);
				}
			}
		}
		return $local_folder_name;
	}
	
	/*
	*	This function sends to the client the file it requested via HTTP
	*/
	function downloadFile($filepath){
		if(file_exists($filepath)){
			$finfo = finfo_open(FILEINFO_MIME_TYPE);
			header('Content-Type: ' . finfo_file($finfo, $filepath));
			finfo_close($finfo);
			header('Content-Disposition: attachment; filename='.basename($filepath));

			//No cache
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');

			//Define file size
			header('Content-Length: ' . filesize($filepath));

			ob_clean();
			flush();
			readfile($filepath);
		}else{
			echo "File not found.";
		}
	}

	/*
	*	Create a zip file if a folder is asked to be downloaded
	*	(a folder can't be download directly via HTTP, so either it downloads
	*	a compressed archive, or a single file each time needs to be sent). Opted for
	*	the 1st.
	*/
	function zipFolder($path){
		$path = realpath($path);
		
		echo "<h1><b>TRYING TO ZIP $path</b></h1>";
		
		// Initialize archive object
		$zip = new ZipArchive();
		
		/*
		*	$path.".zip" specifies the name and the position of the newely
		*	created zip file. 
		*	It is saved in the last but one path specified in the path, its name
		*	is the last path + ".zip".
		*	e.g. path: "/myFolder/NewFolder". It'll be saved in /myFolder and called NewFolder.zip
		*/
		
		$zip->open($path.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);

		// Create recursive directory iterator
		/** @var SplFileInfo[] $files */
		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($path),
			RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $name => $file)
		{
			// Skip directories (they would be added automatically)
			if (!$file->isDir())
			{
				// Get real and relative path for current file
				$filePath = $file->getRealPath();
				
				$pos = strrpos($path,"/",-1);
				/*
				*	Since we're handling real paths, the webserver could run on a Windows
				*	machine. If we have something like C:\Programs\Folder, the previous line could not work.
				*/
				if(!$pos) $pos = strrpos($path,"\\",-1); //[Windows path]
				$relativePath = substr($filePath, $pos + 1);
				
				// For maximum portability, it is recommended to always use forward slashes (/)
				$relativePath = str_replace("\\","/",$relativePath);
				
				// Add current file to archive
				$zip->addFile($filePath, $relativePath);
			}
		}

	// Zip archive will be created only after closing object
	$zip->close();
	}
	
	function rrmdir($dir) { 
		if (is_dir($dir)) { 
			$objects = scandir($dir);
			foreach ($objects as $object) { 
				if ($object != "." && $object != "..") { 
					if (is_dir($dir. DIRECTORY_SEPARATOR .$object) && !is_link($dir."/".$object))
						rrmdir($dir. DIRECTORY_SEPARATOR .$object);
					else
						unlink($dir. DIRECTORY_SEPARATOR .$object); 
				} 
			}
		rmdir($dir); 
   } 
 }
?>