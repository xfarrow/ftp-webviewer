<?php
	session_start();
	
	require './includes/server_configs.php';
	
	/* PHP Post/Redirect/Get (PRG) */
	if($_POST){
		$_SESSION['username'] = $_POST['username'];
		$_SESSION['pass'] = $_POST['pass'];
		header( "Location: {$_SERVER['REQUEST_URI']}", true, 303 );
		exit();
	}
	
	$username = $_SESSION['username'];
	$password = $_SESSION['pass'];
	
	$ftp_connection = ftp_connect(FTP_SERVER,FTP_PORT,FTP_TIMEOUT) or die("Could not connect to " . FTP_SERVER);
	
	if(@ftp_login($ftp_connection,$username,$password)){
		
		//Change the current directory if asked to
		if(isset($_GET['path'])){
			if(!@ftp_chdir($ftp_connection,$_GET['path'])) echo "Failed to open ".$_GET['path']."<br>";
		}
		$current_dir = ftp_pwd($ftp_connection);
		
		echo "<h3 style='display:inline'><b>Logged on as $username (".ftp_systype($ftp_connection).")</b></h3> [<a href='home.php'>homepage</a>]<br><br>";
		echo "<b>Current directory: $current_dir</b><br><a href='download.php?path=$current_dir'>[Download]</a><br><br>";
		
		$documents = ftp_mlsd($ftp_connection,$current_dir);
		
		/*
		*	Show files and folders
		*/
		foreach($documents as $current_document){
			
			if($current_document['type'] == "file"){
				
				//download file
				echo "<a href='download.php?path=$current_dir&name=";
				echo $current_document['name']."'>";
				
				//print file name
				echo $current_document['name'];
				echo "</a> [FILE]";
				
				//delete file
				echo "&nbsp<button onclick='confirmFileDeletion(\"$current_dir\", \"".$current_document['name']."\")'>Delete</button>";
			}
			else if($current_document['type'] == "dir"){
				
				//change directory
				echo "<a href='home.php?path=$current_dir";
				if($current_dir!='/')echo "/";
				echo $current_document['name']."'>";
				
				//print directory name
				echo $current_document['name'];
				echo "</a> [FOLDER]";
				
				//delete directory
				echo "&nbsp<button onclick='confirmFolderDeletion(\"$current_dir".$current_document['name']."\")'>Delete</button>";
			}
			
			//Rename
			echo "&nbsp<button onclick='renameFunction(\"$current_dir\", \"".$current_document['name']."\")' >Rename</button>";
			
			echo "<br>";
		}
		
	/*
	*	Upload file form
	*	The input file name must be an array in order to receive more than 1 file if needed.
	*/
	echo "	<br><b>Upload a file</b><br>
			<form action='upload.php?path=$current_dir' method='POST' enctype='multipart/form-data'>
			<input type='file' name='inputFile[]' multiple='multiple'>
			<br>
			<input type='submit' value='OK' style='margin-top:5px;'>
		</form>";
		
		
	/*
	*	Create folder form
	*/
	echo "	<br><b>Create a folder</b><br>
			<form action='createFolder.php?path=$current_dir' method='POST'>
			<input type='text' name='folder_name' placeholder='Folder Name'>
			<br>
			<input type='submit' value='OK' style='margin-top:5px;'>
			</form>";
	
	ftp_close($ftp_connection);
			
	}else{
		header("Location: login.php?err=1");
	}

?>

<script>

	function renameFunction(path,old_name) {
	var txt;
	var new_name = prompt("Please enter the new name:", "");
		if (new_name == null || new_name == "") {
			alert("No name submitted");
		} else {
		location.href = "rename.php?path="+path+"&old_name="+old_name+"&new_name="+new_name;
		}
	}
	
	function confirmFileDeletion(path, filename){
		if(confirm("Are you sure you want to delete "+filename+"?")){
			location.href = "delete.php?path="+path+"&name="+filename;
		}
	}
	
	function confirmFolderDeletion(path){
		if(confirm("Are you sure you want to delete "+path+"?")){
			location.href = "delete.php?path="+path;
		}
	}
	
</script>
