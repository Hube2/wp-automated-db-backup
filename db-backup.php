<?php 
	
	/*
			This program is distributed in the hope that it will be useful,
			but WITHOUT ANY WARRANTY; without even the implied warranty of
			MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
			GNU General Public License for more details.
	*/
	
	set_time_limit(0);
	
	date_default_timezone_set('America/Chicago');
	$remove_date = strtotime('-30 days');

	require(dirname(__FILE__).'/blunt.mysqli.class.php');
	require(dirname(__FILE__).'/Mysqldump.php');
	
	$config_file = dirname(dirname(__FILE__)).'/wp-config.php';
	$file_content = file_get_contents($config_file);
	if (!$file_content) {
		exit;
	}
	
	preg_match_all('/define\s*?\(\s*?([\'"])(DB_NAME|DB_USER|DB_PASSWORD|DB_HOST|DB_CHARSET)\1\s*?,\s*?([\'"])([^\3]*?)\3\s*?\)\s*?;/si', $file_content, $defines);
	
	if ((isset($defines[2]) && !empty($defines[2])) && 
	    (isset($defines[4]) && !empty($defines[4]))) {
		foreach( $defines[ 2 ] as $key => $define ) {
			switch($define) {
				case 'DB_NAME':
					$name = $defines[ 4 ][ $key ];
					break;
				case 'DB_USER':
					$user = $defines[ 4 ][ $key ];
					break;
				case 'DB_PASSWORD':
					$pass = $defines[ 4 ][ $key ];
					break;
				case 'DB_HOST':
					$host = $defines[ 4 ][ $key ];
					break;
				case 'DB_CHARSET':
					$char = $defines[ 4 ][ $key ];
					break;
			}
		}
	}
	
	if (preg_match('/\$table_prefix\s*?=\s*?[\'"]([^\'"]+)[\'"]/is', $file_content, $matches)) {
		$prefix = $matches[1];
	}
	
	if (!$host || !$name || !$pass || !$char || !$pass || !$prefix) {
		exit;
	}
	
	$db = new bluntMysqli($host, $user, $pass, $name, $char);
	
	$tables = $db->getTables();
	$sites = array();
	if (in_array($prefix.'blogs', $tables)) {
		$query = 'SELECT blog_id FROM '.$prefix.'blogs ORDER BY blog_id';
		$results = $db->get($query);
		$sites = array();
		foreach ($results as $result) {
			$sites[intval($result['blog_id'])] = array();
		}
	}
	
	foreach ($tables as $index => $table) {
		//preg_match('/^'.preg_quote($prefix).'([0-9]+)_/', $table, $matches);
		//echo '<pre>'; print_r($matches); echo '</pre>';
		if (preg_match('/^'.preg_quote($prefix).'([0-9]+)_/', $table, $matches)) {
			if (isset($sites[intval($matches[1])])) {
				$sites[intval($matches[1])][] = $table;
			}
			unset($tables[$index]);
		}
	}
	// whatever's left is site 1
	// could be all tables if not multisite
	$sites[1] = $tables;
	
	// make sure folder exists for dumps
	$base = dirname(__FILE__).'/sites';
	if (!is_dir($base)) {
		mkdir($base);
	}
	
	// backups for each site
	foreach ($sites as $site => $tables) {
		$path = $base.'/'.$site;
		// make sure the site page exists
		if (!is_dir($path)) {
			mkdir($path);
		}
		// first delete old files older that setting
		if (($handle = opendir($path)) !== false) {
			while(($file = readdir($handle)) !== false) {
				if (!is_dir($path.'/'.$file) && 
						preg_match('/([0-9]{4}-[0-9]{2}-[0-9]{2})_([0-9]{2}-[0-9]{2}-[0-9]{2})/', $file, $matches)) {
					$file_time = strtotime($matches[1].' '.str_replace('-', ':', $matches[2]));
					if ($file_time < $remove_date) {
						unlink($path.'/'.$file);
					}
				}
			}
		}
		// now dump the new backup
		$file = $path.'/'.date('Y-m-d_H-i-s').'.sql';
		$settings = array(
			'include-tables' => $tables,
			'add-drop-table' => true
		);
		$dump = new Ifsnop\Mysqldump\Mysqldump('mysql:host='.$host.';dbname='.$name, $user, $pass, $settings);
		$dump->start($file);
	} // end foreach $site
	
?>