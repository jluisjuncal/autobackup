<?php
// Load Dolibarr environment
$res=0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (! $res && ! empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) $res=@include($_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php");
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp=empty($_SERVER['SCRIPT_FILENAME'])?'':$_SERVER['SCRIPT_FILENAME'];$tmp2=realpath(__FILE__); $i=strlen($tmp)-1; $j=strlen($tmp2)-1;
while($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i]==$tmp2[$j]) { $i--; $j--; }
if (! $res && $i > 0 && file_exists(substr($tmp, 0, ($i+1))."/main.inc.php")) $res=@include(substr($tmp, 0, ($i+1))."/main.inc.php");
if (! $res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php")) $res=@include(dirname(substr($tmp, 0, ($i+1)))."/main.inc.php");
// Try main.inc.php using relative path
if (! $res && file_exists("../main.inc.php")) $res=@include("../main.inc.php");
if (! $res && file_exists("../../main.inc.php")) $res=@include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res=@include("../../../main.inc.php");
if (! $res) die("Include of main fails");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/utils.class.php';




$files = scandir($conf->admin->dir_output.'/backup', SCANDIR_SORT_DESCENDING);
$newest_file = $files[0];
echo "Last file is $newest_file<br>";
echo "Days interval is ".$conf->global->AUTOBACKUP_DAYS."<br>";

$timeold=time()-filemtime($conf->admin->dir_output.'/backup/'.$newest_file);
echo "File old is $timeold seconds<br>";
$intervalseconds=$conf->global->AUTOBACKUP_DAYS * 24 * 3600;
echo "Interval seconds is $intervalseconds seconds<br>";


if ($timeold > $intervalseconds or $newest_file=='..') {
	echo "Older than x days or not exist<br>";
	$companyname=str_replace(" ", "", $mysoc->name);
	$companyname=str_replace(".", "", $companyname);
	$companyname=$conf->admin->dir_output.'/backup/'.$companyname;
	$sqlcommand='C:/xampp/mysql/bin/mysqldump --opt -h '.$dolibarr_main_db_host.' -p'.$dolibarr_main_db_pass.' -u '.$dolibarr_main_db_user.'  '.$dolibarr_main_db_name.' > '.$companyname.'.sql';
	$result=exec($sqlcommand, $return);
	echo "<br>Result:";
	print_r($return);
	
	if ($conf->global->AUTOBACKUP_SENDTOFTP=="Yes") {
		$ftp_server=$conf->global->AUTOBACKUP_FTPSERVER;
		$ftp_user_name=$conf->global->AUTOBACKUP_FTPUSER;
		$ftp_user_pass=$conf->global->AUTOBACKUP_FTPPASSWORD;
		$remotepath=$conf->global->AUTOBACKUP_FTPREMOTEPATH;
	
		$conn_id = ftp_connect($ftp_server);
		//$conn_id = ftp_ssl_connect($ftp_server);
 

		$files = scandir($conf->admin->dir_output.'/backup', SCANDIR_SORT_DESCENDING);
		$newest_file = $files[0];
	
		$login_result = ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);
	
	
		if ((!$conn_id) || (!$login_result)) { 
			echo "FTP connection has failed!";
			echo "Attempted to connect to $ftp_server for user $ftp_user_name"; 
			exit; 
		} else {
			echo "Connected to $ftp_server, for user $ftp_user_name<br>";
		}
	

		if ($conf->global->AUTOBACKUP_FTPOVERWRITE=="No") $remote_file=$newest_file;
		else if ($conf->global->AUTOBACKUP_FTPOVERWRITE=="Yes") $remote_file=$dolibarr_main_db_name.".sql.bz2";
		else $remote_file=$conf->global->AUTOBACKUP_FTPOVERWRITE.".sql.bz2";
		
		//Necesary for some hostings
		ftp_pasv($conn_id, true);
		// upload a file
		if (ftp_put($conn_id, $remotepath.$remote_file, '../../../documents/admin/backup/'.$newest_file, FTP_BINARY)) {
			echo "successfully uploaded $file\n";
			exit;
		} else {
			echo "There was a problem while uploading ../../../documents/admin/backup/$newest_file<br>";
			echo "In $remotepath$remote_file";
			exit;
		}
		// close the connection
		ftp_close($conn_id);
	}
	
} else {
	echo "Newer than x days";
}

?>