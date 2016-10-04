<?php 
	
	// version 5.0.0
	// 20111212
	// rebuilt to use mysqli functions, renamed file and class
	// this is not compatible with previous versions
	// 		added method connected() to test if connection succeeded
	//		added optional parameter $charset to __construct to use a charset other than utf8
	//		rewrote isTable method to return true/false/NULL (NULL means an error)
	//		rewrote isColumn method to return true/false/NULL (NULL means an error)
	
	// version 4.0.1
	// 20110928
	// removed auto creation of default object
	// removed config file
	
	// version 4.0.0
	// 20100925
	// altered file so that it does not create a default connection
	//			moved this and the function to get the default connection to file blunt.mysql.connect.php
	//			include that file in setup, that file will include this file
	// altered class so that it does not store db connection info as class variables
	
	// version 3.0.2
	// 20091001
	// corrected a minor bug that caused an unknown index error in the get1() function

	// version 3.0.1
	// 20090417
	/*		changed coding to align better with POBS
					Modified findbluntMysql() so that this class name can be obfuscated
					renamed function select() to get() to avoid impropper obfuscation of regex in getCount()
	*/
	
	// version 3.0.0
	// finished on 20090416
	/*
	
		USAGE
		
		Instantiate:
			$db = new bluntMysql($host, $user, $password, $database);
				Attempts to creates a connection to a database with the connection information provided. The class will 
				make multiple attempts at connection depending on the value of $bluntMysqlMaxConnectAttempts (see blunt.mysql.config.php)
				Each instance of bluntMysql can only be used to connect to a single database
				
				returns true on succcess and false on failure
				
		Queries:
			With few exceptions, it is up to the user of this class to properly construct MySQL querries. Is is not the entent of 
			this class to develope a new language or way to communicate with a database. The goal of this class is to streamline 
			common functions that are generally performed on data that is returned by MySQL. For instance, it is common proactice 
			to convert a MySQL resource returned by a SELECT query into an array containing the rows and columns returned.
			
		Errors
			Any error reported during the last operation performed is stored
			
			$error = $db->errno()		returns the error number, if any, of the last operation
			$error = $db->error()		retruns the error text, if any, from the last operation
			
			for more on error see php documentation on mysqli_error() and mysqli_errno()			
			
			
		getTables()
			Returns a list of all tables in the database associated with the connection.
			
			returns and array on success where each value is the name of a table (an empty array is returned if no tables are found)
			returns false on failure
			
			The table list is stored so that if this function is called again the stored data is used rather than making another
			query for the same date
			
			**** eventually this wil be expanded to get additional table attribues
			
		isTable($table_name)
			Returns true if $table_name exists in the current database
			returns false if the table does not exist or if an error occurs, check error() to determine
			
			if the funciton getTables() has not been called, then this fucntion calls getTables()
		
		getColumns($table)
			Returns a list of all columns in $table
			returns an array of column names on success
			returns false on failure
			
			the column names are stored for this table so repeat calls do not re-query the db
			if tables have not been retrieved then this will also retrieve and store the list of tables
			
			**** This should be expanded in the future to get column attributes as well
			
		getAllColumns()
			gets lists of all columns in all tables
			returns true on success, false on failure
			data is stored
			I don't know why I included this function, except that if I plan to need all the column names for
			some reason then I can do all the queries at once, but it would be a waste of resources to call this
			if there is no reason to have a list of all columns in all tables
			
		isColumn($table, $column)
			check to see if $column exists in $table
			returns true if column exits 
			returns false if column does not exist or if an error occurs, check error() to determine
			
		get($query)
			run a select query. As stated previously, most query functions expect a valid MySQL query statement
			what the functions do is what happens after the query is run
			
			returns false on error			
			returns and array if successful. Each member of the array is an array in the form (column_name=>column_value)
			also sets record count
			
			as an example:
				$result = $db->select($query);
				
				if you have a table with 3 columns and 2 rows are returned the array would look something like
						$db => Array (
										[0] => Array (
														[column_1_name] => column 1 value,
														[column_2_name] => column 2 value,
														[column_3_name] => column 3 value,
													)
										[1] =>  Array (
														[column_1_name] => column 1 value,
														[column_2_name] => column 2 value,
														[column_3_name] => column 3 value,
													)
				 								 )
			get1($query)
				This is the same as get() except that it returns only a single record. Use this when it is imposible to get more than
				a single record (when selecting on a unique field or index)
				check to see if multiple records where returned by checking ->redcord_count
				could also be used if you want to select mutilpe records but only return the first
				This returns the same values as get except that the array will be in the form:
										Array (
														[column_1] => column 1 value,
														[column_2] => column 2 value,
														[column_3] => column 3 value,
													)
													
			getLast()
				Returns the last result array (all records) returned by last get() or get1()
			
			update($query)
				perform an update query
				returns true/false
				also sets affect rows									
			
			insert($query)
				perform an insert query
				returns true/false
				also sets affected rows
				also sets inserted id
			
			delete($query)
				perform a delete query
				also sets affected rows
			
			getCount($query)
				perform a count query
				This function will add COUNT() to a query that does not contain it.
				By passing something like "SELECT id FROM table WHERE..."
					the function will convert this to "SELECT COUNT(id) FROM table WHERE..."
					rules, there can only be a single field selected.
					If the query is more complicated than this or the word "count" appears anywhere in the query
						you must add COUNT() yourself 
				returns count or false on failure
				also sets record count
			
			query($query)
				This is a generic query that can be used where the others do not fit
				returns false on failure
				on success, if a record set is returned by the query then the record set is returned, else it returns true
				if a record was inserted then inserted id is set
				if rows were affected the affected rows is set
				if a record set is returned then record count is set
			
			begin()
				begin a transaction (InnoDB Tables only)
			
			commit()
				commit transaction (InnoDB Tables only)
			
			rollback()
				rollback transaction (InnoDB Tables only)
			
			clean($dirty)
				This function will perform a mysqli_real_escape_string on the values passed to it.
				Values sould be passed as an array
				for example: $_POST = $db->clean($_POST);
				handles multidimesional arrays (recursive function)
			
			insertColumn($table, $column, $specs)
				insert $column into $table where the column properties are in $specs
				This was added becasue I find it useful
				query($query) would also work for all Alter Table functions
			
			insertedId()
				returns last inserted id
			
			affectedRows()
				returns affected rows
			
			recordCount();
				returns record count of last action of select or count
	
	*/
	
	// removed configration and included in class
	//require_once(dirname(str_replace('\\', '/', str_replace('\\\\', '/', __FILE__))).'/blunt.mysql.config.php');
	
	/*
		Removed default connection as this will no longer be used
		see the new file blunt.mysql.connect.php
	
	$defaultBluntDbConnection = new bluntMysql(CMS_DB_HOST, CMS_DB_USER, CMS_DB_PASS, CMS_DB_DBNM);
	if (!$defaultDbConnection->connection) {
		die('Unable to connect to MySQL. Please check connection information and try again.');
	}
	
	function getBluntMysql() {
		global $defaultBluntDbConnection;
		return  $defaultBluntDbConnection;
	} // end function getW3tMysql
	 
	*/
	
	class bluntMysqli {
		
		private $connected;
		private $connection;						// holds MySQL link identifier for this connection or false
		private $db;										// holds true or false indicating a db was selected
		private $results;								// holds mysql identifier of last query performed
		private $return_array;					// holds the results of the last query in an array
		private $tables;								// holds a list of all tables in the db for this connection
		private $columns;								// holds and array of all columns in all tables of the db for this connection
																		//		array form: $columns[<TABLE NAME>][x] = column name
		private $column_data;						// for future expansion
																		// will hold an array of all column types in the db
																		//		array form: $column_data[<TABLE NAME>][<COLUMN NAME>]['type'] = type
																		//		this will be expanded in the future to include other column informations
																		// 		such as column length or enum choices
		public $inserted_id;						// holds the last inserted id from the last insert action
		public $record_count;					// holds the count of records returned in last select operation
		public $affected_rows;					// holds the number of rows affected by the last INSERT, UPDATE, REPLACE or DELETE
		public $error;									// holds the error text from the last error reported
		public $errno;									// holds the error value from the last error reported
		
		private $transaction;						// will be set to true if a transaction has already been started
		private $transaction_count;			// will contain the number of transactions started and will only complete the transaction
																		//			when the last on has been completed
		private $charset;
		
		static $instance = false;
		
		
		public function __construct($host, $user, $password, $database, $charset='utf8') {
			
			self::$instance = $this;
			
			$this->connected = false;
			// this varaible sets the max number of times that an attempt will be made to connect to a database
			// bluntMysql will make multiple attemts to connect, once connected it will also try to choose the
			// database selected more than once. The total number of connect/select attempts will not acceed the
			// value set here
			$maxAttempts = 5;
			
			// set the character set and colattion to use in connections to the database
			$this->charset = $charset;
			
			$this->connected = false;
			$this->connection = false;
			$this->results = false;
			$this->return_array = array();
			$this->tables = false;
			$this->columns = array();
			$this->column_data = array();
			$this->inserted_id = false;
			$this->record_count = 0;
			$this->affected_rows = 0;
			$this->error = '';
			$this->errno = 0;
			$this->transaction = false;
			$this->transaction_count = 0;
			
			$attempts = 0;
			while(!$this->connected) {
				$this->connection = @mysqli_connect($host, $user, $password, $database);
				if (mysqli_connect_error()) {
					$this->errno = mysqli_connect_errno($this->connection);
					$this->error = mysqli_connect_error($this->connection);
					$this->connection = false;
					$attempts++;
					if ($attempts > $maxAttempts) {
						break;
					}
				} else {
					$this->connected = true;
				} // end if error else
			} // end while not connected
			
			// set connection charset and collation
			if ($this->connected) {
				if (!mysqli_set_charset($this->connection, $this->charset)) {
					$this->setError();
					$this->db = false;
					$this->connection = false;
					$this->connected = false;
				} // end not set charset
			} // end if connected
			
			// this class does not support persistant connections
			// connections will be closed on script completion
			register_shutdown_function(array($this, 'close'), false);
			
		} // end public function __construct
		
		public static function construct($host, $user, $password, $database, $charset='utf8') {
			// this function can be called using the function call_user_func_array();
			// example $db = call_user_func_array(array('bluntMysqli', 'construct'), $array);
			return new self($host, $user, $password, $database, $charset);
		} // end public static function construct
		
		public function connected() {
			// one of the few getters
			// check to see if connected
			return $this->connected;
		} // end public function connected
		
		public function close($report=true) {
			// this function will be closed on shutdown
			// but can also be called manually
			$success = false;
			if ($this->checkConnection()) {
				if (mysqli_close($this->connection)) {
					$this->connected = false;
					$success = true;
				} elseif($report) {
					$this->setError();
				}
			}
			if ($report) {
				return $success;
			}
		} // end public function close
		
		public function clean($dirty, $clean_keys=false) {
			// this function will take a varaible or array
			// perform mysqli_real_escape_string on the value or all values if an array
			// also escaptes % and _
			// and return the cleaned values
			// recursive function
			$clean = false;
			if ($this->checkConnection()) {
				$clean = '';
				if (is_array($dirty)) {
					$clean = array();
					if (count($dirty) > 0) {
						foreach ($dirty as $key => $value) {
							if ($clean_keys) {
								$key = $this->clean($key);
							}
							$clean[$key] = $this->clean($value);
						}
					}
				} else {
					$clean = mysqli_real_escape_string($this->connection, $dirty);
					//$clean = str_replace('%', '\\%', $clean);
					//$clean = str_replace('_', '\\_', $clean);
				}
			}
			return $clean;
		} // end public function clean
		
		public function transaction_started() {
			return $this->transaction;
		} // end publuc function transaction
		
		public function begin() {
			// START TRANSACTION 
			/*
					This class allows multiple nested calls to begin
					unlike the default action where a new transaction start will
					commit a previous transaction and start a new one this class will not
					this removes the need to remember if you allready started a transaction
					this allows you to have multiple functions/methods that may need to 
					start a transaction but that may not always be used together without 
					needing to test to see if a transaction has already been started
					although the function method $this->transaction_started() will return this information
					the method $this->transaction_started() is still included for backwards compatibility
					from a time before this method was modified
			*/
			$success = false;
			if ($this->checkConnection()) {
				$this->transaction_count++;
				if (!$this->transaction) {
					// only start if a transactions has not already been started
					if (mysqli_query($this->connection, 'START TRANSACTION')) {
						$success = true;
						$this->transaction = true; 
					} else {
						$this->setError();
					}
				}
			}
			return $success;
		} // end public function begin
		
		public function commit() {
			$success = false;
			if ($this->checkConnection()) {
				if ($this->checkTransaction()) {
					$success = true;
					$this->transaction_count--;
					if ($this->transaction_count == 0) {
						if (mysqli_query($this->connection, 'COMMIT')) {
							$this->transaction = false;
						} else {
							$success = false;
							$this->setError();
						} // end if commit else
					} // end if transaction count == 0
				}
			}
			return $success;
		} // end public function commit
		
		public function rollback() {
			$success = false;
			if ($this->checkConnection()) {
				if ($this->checkTransaction()) {
					if (mysqli_query($this->connection, 'ROLLBACK')) {
						$success = true;
						$this->transaction_count = 0;
						$this->transaction = false;
					} else {
						$this->setError();
					} // end if rollback else
				}
			}
			return $success;
		} // end public function rollback
		
		public function get($query) {
			$results = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (($this->results = mysqli_query($this->connection, $query)) !== false) {
					$this->fetchRows();
					$results = $this->return_array;
					$this->record_count = count($this->return_array);
				} else {
					$this->setError();
				}
			}
			return $results;
		} // end public function get
		
		public function get1($query) {
			//echo $query;
			$results = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (($this->results = mysqli_query($query, $this->connection)) !== false) {
					$this->fetchRows();
					$results = array();
					$this->record_count = count($this->return_array);
					if ($this->record_count > 0) {
						$results = $this->return_array[0];
					}
				} else {
					$this->setError();
				}
			}
			return $results;
		} // end public function get1
		
		public function query($query) {
			$results = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (($this->results = mysqli_query($this->connection, $query)) !== false) {
					$results = true;
					$this->inserted_id = @mysqli_insert_id($this->connection);
					$this->affected_rows = @mysqli_affected_rows($this->connection);
					if (!is_bool($this->results)) {
						// not a boolian value so something must have been returned
						// treat it as a get
						$this->fetchRows();
						$this->record_count = count($this->return_array);
						$results = $this->return_array;
					}
				} else {
					$this->setError();
				}
			}
			return $results;
		} // end public function query
		
		public function getCount($query) {
			// do not include the MySQL COUNT in query, this will be added
			// including this will cause query to fail
			$results = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (!preg_match('/\s+COUNT\s*\(/is', $query)) {
					$query = preg_replace('/(SELECT\s+)(.*?)(\s+FROM)/is', '\1COUNT(\2) as count\3', $query);
					if (($this->results = mysqli_query($this->connection, $query)) !== false) {
						$this->fetchRows();
						$this->record_count = $this->return_array[0]['count'];
						$this->return_array = array();
						$results = $this->record_count;
					} else {
						$this->setError();
					}
				} // else sent query already contains "COUNT" and I have no idea what to return
			}
			return $results;
		} // end public function getCount
		
		public function getMax($query) {
			// do not include the MySQL MAX in query, this will be added
			// including this will cause query to fail
			$results = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (!preg_match('/\s+MAX\s*\(/is', $query)) {
					$query = preg_replace('/(SELECT\s+)(.*?)(\s+FROM)/is', '\1MAX(\2) AS max\3', $query);
					if (($this->results = mysqli_query($this->connection, $query)) !== false) {
						$results = 0;
						if (mysqli_num_rows($this->results) > 0) {
							$row = mysqli_fetch_assoc($this->results);
							$results = $row['max'];
						} // else if it is < 1 then there should have been an error
					} else {
						$this->setError();
					}
				} // else sent query already contains "MAX" and I have no idea what to return
			}
			return $results;
		} // end public function getMax
		
		public function getMin($query) {
			// do not include the MySQL MIN in query, this will be added
			// including this will cause query to fail
			$results = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (!preg_match('/\s+MIN\s*\(/is', $query)) {
					$query = preg_replace('/(SELECT\s+)(.*?)(\s+FROM)/is', '\MIN(\2) AS min\3', $query);
					if (($this->results = mysqli_query($this->connection, $query)) !== false) {
						$results = 0;
						if (mysqli_num_rows($this->results) > 0) {
							$row = mysqli_fetch_assoc($this->results);
							$results = $row['min'];
						}
					} else {
						$this->setError();
					}
				} // else sent query already contains "MIN" and I have no idea what to return
			}
			return $results;
		} // end public function getMin
		
		public function insert($query) {
			$success = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (mysqli_query($this->connection, $query)) {
					$this->inserted_id = mysqli_insert_id($this->connection);
					$this->affected_rows = mysqli_affected_rows($this->connection);
					$success = true;
				} else {
					$this->setError();
				}
			}
			return $success;
		} // end public function insert
		
		public function update($query) {
			$success = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (mysqli_query($this->connection, $query)) {
					$this->affected_rows = mysqli_affected_rows($this->connection);
					$success = true;
				} else {
					$this->setError();
				}
			}
			return $success;
		} // end public function update
		
		public function delete($query) {
			$success = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (mysqli_query($this->connection, $query)) {
					$this->affected_rows = mysqli_affected_rows($this->connection);
					$success = true;
				} else {
					$this->setError();
				}
			}
			return $success;
		} // end public function delete
		
		public function isTable($table) {
			// this function checks to see if $table is a table in this db
			$exists = NULL;
			if ($this->checkConnection()) {
				$exists = false;
				if (($this->tables !== false && !in_array($table, $this->tables)) || $this->tables === false) {
					// $this->tables == false, we have not gotten tables yet
					// if the table is not listed, check again
					$this->getTables(false);
				}
				if (in_array($table, $this->tables)) {
					$exists = true;
				}
			}
			return $exists;
		} // end public function isTable
		
		public function getTables($return=true) {
			// this function returns an array holding a list of all the table names in this db
			// returns an empty array if there are no tables
			// returns false if there is an error
			// this function also detects if tables were already retrieved and if so returns stored values
			$tables = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if (($results = mysqli_query($this->connection, 'SHOW TABLES'))!== false) {
					$tables = array();
					if (mysqli_num_rows($results) > 0) {
						while ($row = mysqli_fetch_row($results)) {
							$tables[] = $row[0];
						}
					}
					$this->tables = $tables;
				} else {
					$this->setError();
				}
			}
			if ($return) {
				return $tables;
			}
		} // end public function getTables
		
		public function isColumn($table, $column) {
			$exists = NULL;
			if ($this->checkConnection()) {
				if ($this->isTable($table)) {
					$exists = false;
					if (!isset($this->columns[$table]) || (isset($this->columns[$table]) && !in_array($column, $this->columns[$table]))) {
						$this->getColumns($table, false);
					}
					if (in_array($column, $this->columns[$table])) {
						$exists = true;
					}
				} else {
					$this->errno = 0;
					$this->error = 'The table `'.$table.'` does not exist';
				}
			} // end if connection
			return $exists;
		} // end public function isColumn
		
		public function getColumns($table, $return=true) {
			// this function returns and array holding a list of the columns in $table
			// returns an empty array if there are no culumns in table
			// returns false on error
			// this function also stores results so that query is not reperformed if called
			// 		again for the same table
			$columns = false;
			if ($this->checkConnection()) {
				$this->clearResults();
				if ($this->isTable($table)) {
					if (($results = mysqli_query($this->connection, $query)) !== false) {
						$this->columns[$table] = array();
						if (mysqli_num_rows($results) > 0) {
							while($row = mysqli_fetch_row($results)) {
								$this->columns[$table][] = $row[0];
							}
							$columns = $this->columns[$table];
						}
					} else {
						$this->setError();
					}
				} else {
					$this->errno = 0;
					$this->error = 'The table `'.$table.'` does not exist';
				}
			}
			if ($return) {			
				return $columns;
			}
		} // end public function getColumns
		
		public function insertColumn($table, $column, $specs) {
			$tables = $this->getTables();
			//print_r($this->tables);
			$success = false;
			if ($this->checkConnection()) {
				if ($this->isTable($table)) {
					if (!$this->isColumn($table, $column)) {
						$query = 'ALTER TABLE '.$table.' ADD COLUMN '.$column.' '.$specs;
						//print_r($this->columns);die;
						if (mysqli_query($this->connection, $query)) {
							if ($this->isColumn($table, $column)) {
								$success = true;
							}
						} else {
							$this->setError();
						}
					} else {
						// column already exists
						$this->errno = 0;
						$this->error = 'The column `'.$column.'` already exists in table `'.$table.'`';
					}
				} else {
					// table does not exist
					$this->errno = 0;
					$this->error = 'The table `'.$table.'` does not exist';
				}
			}
			return $success;
		} // end public function insertColumn
		
		public function dropColumn($table, $column) {
			$success = false;
			if ($this->checkConnection()) {
				if ($this->isTable($table)) {
					if ($this->isColumn($table, $column)) {
						$query = 'ALTER TABLE '.$table.' DROP COLUMN '.$column;
						if (mysqli_query($this->connection, $query)) {
							unset($this->columns[$table]);
							if (!isColumn($table, $column)) {
								$success = true;
							}
						} else {
							$this->setError();
						}
					} else {
						// column does not exist
						$this->errno = 0;
						$this->error = 'The column `'.$column.'` does not exist in table `'.$table.'`';
					}
				} else {
					// table does not exist
					$this->errno = 0;
					$this->error = 'The table `'.$table.'` does not exist';
				}
			}
			return $success;
		} // end public function dropColumn
		
		
		private function fetchRows() {
			$this->return_array = array();
			if (mysqli_num_rows($this->results) > 0) {
				while ($row = mysqli_fetch_assoc($this->results)) {
					$this->return_array[] = $row;
				}
			}
		} // end private function MysqlFetchRows
		
		private function clearResults() {
			$this->return_array = array();
			$this->inserted_id = false;
			$this->affected_rows = 0;
			$this->errno = 0;
			$this->error = '';
		} // end private function ClearResults
		
		private function setError() {
			$this->errno = mysqli_errno($this->connection);
			$this->error = mysqli_error($this->connection);
		} // end private function error
		
		private function checkConnection() {
			if (!$this->connected) {
				$this->errno = 0;
				$this->error = 'There is no active database connection';
			}
			return $this->connected;
		} // end private function checkConnection
		
		private function checkTransaction() {
			if (!$this->transaction) {
				$this->errno = 0;
				$this->error = 'There is no active transaction';
			}
			return $this->transaction;
		} // end private function checkTransaction
		
	} // end class bluntMysql
	
?>