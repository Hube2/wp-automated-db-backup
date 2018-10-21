<?php 
	
	define('SITE_START', 0); // first site to backup
	define('SITE_END', 0);  // last site to backup
	define('TIMEZONE', 'America/Chicago'); // used when calling date_default_timezone_set()
	define('ARCHIVE_REMOVAL', '-30 days'); // backups before this time are deleted
	
	/* Cron Completion Message 
			If you would like the script to output a completion message after running
			define that message here. Some hosting solutions only send you an email
			if there is an error. If you need to know that the cron ran succesfully
			in these cases then you must create some output
	*/
	//define('BACKUP_COMPLETION_MESSAGE',  basename(__FILE__).' Completed');
	
	require(dirname(__FILE__).'/db-backup.php');
	
?>