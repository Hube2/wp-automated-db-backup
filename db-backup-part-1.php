<?php 
	
	define('SITE_START', 0); // first site to backup
	define('SITE_END', 0);  // last site to backup
	define('TIMEZONE', 'America/Chicago'); // used when calling date_default_timezone_set()
	define('ARCHIVE_REMOVAL', '-30 days'); // backups before this time are deleted
	
	require(dirname(__FILE__).'/db-backup.php');
	
?>