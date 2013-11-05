<?php
	class Migrations {
		// database credentials
		const DBCONFIG = "config.ini";
		protected $db_host; 
		protected $db_user;
		protected $db_password;
		protected $db_database;
		public $output = "";
		public $resultset = array();
		var $connection; 

		public function __construct(){
			require_once "config.php";
			$conf = new DB_CONFIG();
			$this->db_host = $conf->host;
			$this->db_user = $conf->user;
			$this->db_password = $conf->pass;
			$this->db_database = $conf->db;
				
			$this->connection  = $this->connect($this->db_host, $this->db_user, $this->db_password, $this->db_database);
		
			if($this->connection->connect_error){
				die("\nThere was a problem connecting to your database \n");	
			}		

		}
		
		/*
		* Connects to the db
		* @param {string} $host 
		* @param {string} $user 
		* @param {string} $pass
		* @param {string} $db
		* @return {connection}
		*/
		public function connect($host, $user, $pass, $db){
			return $mysqli = new mysqli($host, $user, $pass, $db);	
		}

		/*
		* generates a migration with the given name
		* @param {string} $name -> the name of the migration with underscores. 
		* @return {bool}
 		*/ 
		public function generate($name){
			$file_path = "versions/" . time() . "_" . $name . ".php" ;
			$migrate = fopen($file_path , 'w') or die("Cannot generate the migration check file permissions.");
			$name = $this->camelize($name);
// @TODO fix this.
$content = "<?php 
	class " . $name . " extends Migrations{
		public function up(){

		}

		public function down(){

		}
	}
?>
";
	 		file_put_contents($file_path, $content);
	 		$klass = $this->camelize($name);
	 		$this->output .= "\nMigration $name generated\n";
		}

		/*
		* Creates table with the given credentials.
		* fields structure
		* array( 
		* 	array('name', array('type' => 'string')), 
		*   array('name', 'string')
		* )
		* @param {string} $name -> the name of the table 
		* @param {array} $fields -> the list of fields to build the table
		* @return {bool}
		*/ 
		public function create_table($name, $fields){
			$qr = "CREATE TABLE IF NOT EXISTS " . $name . " (";
			// @TODO add more options for the key 
			$qr .= "`id` int(11) NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`), ";	
			$last_el = end($fields);
			foreach ($fields as $f) {
				$qr .= "`" . $f[0] . "` " . $this->dataType($f[1]);
				if($last_el != $f){
					$qr .= ","; 
				}		
			}	
			$qr .= ");";
			$q = $this->connection->query($qr); 
			if(!$q){
				$this->output .= "There was a problem creating table $name\n";
				$this->resultset[] = false; 
			}else{
				$this->output .= "Table $name created successfully\n";
				$this->resultset[] = true;
			}
			// check if there is a mysql error 
		}

		/*
		* Adds a field to the given table 
		* @param {string} => the name of the table 
		* @param {string} => the name of the field 
		* @param {mixed} => options for the field
		* @param {array} => the options required for that field
		* @return VOID
		*/
		public function add_field($table, $field, $options){
			$qr = "ALTER TABLE `$table` ADD $field " . $this->datatype($options);
			$q = $this->connection->query($qr);
			if($q){	
				$this->output .= "Added field $field to table $table. \n";
				$this->resultset[] = true;
			}else{
				$this->output .= "There was a problem adding field: $field .\n";
				$this->resultset[] = false;
			}
		}

		/*
		* Remove a field from table  
		* @param {string} -> the name of the table 
		* @param {string} -> the name of the field
		* @return VOID 
		*/
		public function remove_field($table, $field){
			$qr = "ALTER TABLE `$table` DROP $field"; 
			$q = $this->connection->query($qr);

			if($q){	
				$this->output .= "Removed field $field to table $table. \n";
				$this->resultset[] = true;
			}else{
				$this->output .= "There was a problem removing field: $field .\n";
				$this->resultset[] = false;
			}
		}

		/*
		* Removes a table from the db
		* @params {string} <- The name of the table 
		* @return VOID 
		*/ 
		public function drop_table($table){
			$qr = "DROP TABLE IF EXISTS `$table`"; 
			$q = $this->connection->query($qr);

			if($q){	
				$this->output .= "Dropped table $table\n";
				$this->resultset[] = true;
			}else{
				$this->output .= "There was a problem dropping table : $table .\n";
				$this->resultset[] = false;
			}
		}
		/*
		* Creates the schema_migrations db table for keeping track of the files needed 
		* @return {bool}
		*/ 
		public function install(){
			$exists = $this->connection->query('SELECT 1 FROM schema_migrations');

			if($exists){
				die("\nMigrations plugin is already installed\n");
			}

			$var = $this->create_table("schema_migrations",
				array(
					array('version', 'string')
				)
			);

			if(!file_exists('./versions')){
				mkdir('./versions', 0777, true);
			}
		}

		/*
		* Runs the migrations in their order.
		* @param {until} -> the id of the migraion you want run until. 
		* @return {bool} 
		* @TODO error handling : Does not record the migration if all the migrations run.
		* @TODO rollback if the run is not successful. 
		*/
		public function run($until = Null){
			$this->check_installation();
			$this->output .= "\nStarting migration...\n"; 
			$migration_successful = true; 
			foreach(glob('./versions/*.php') as $filename){
			    require_once($filename);
			    $klass = $this->get_class_name($filename); 
			    $version = $this->get_version_number($filename);
			    // check if we already ran this version 
			    $version_exists = $this->connection->query("SELECT * FROM schema_migrations WHERE version = $version");
			  	
			  	if($version_exists->num_rows == 0){
			  		$this->output .= "----- Running migration $klass \n"; 
			    	$migration = new $klass;
			    	$migration->up(); 
			    	$this->output .= $migration->output;
			    	$migration_ran = true; 
			    	foreach($migration->resultset as $r){
			    		if(!$r){
			    			$migration_ran = false; 
			    		}
			    	}
			    	if($migration_ran){
			    		$this->connection->query("INSERT INTO `schema_migrations` (`version`) VALUES ('$version');");
			    	}else{
			    		$migration->down();
			    	}

			  	} 
			} 

			$this->output .= "Done...\n";
		}

		/*
		* This methos is for being able to use the migrations plugin in existing projects. 
		* Simply get the latest version of your database as sql in versions directory and run that for all your team 
		* to have a common ground. 
		* @param {string} -> filename 
		* @return {bool} 
		*/
		public function runsql($filename){
			$this->SplitSQL('versions/' . $filename);
			$this->resultset[] = true; 
		}

		public function SplitSQL($file, $delimiter = ';'){
    		set_time_limit(0);
    		if (is_file($file) === true){
        		$file = fopen($file, 'r');
        		if (is_resource($file) === true){
            		$query = array();
					while (feof($file) === false){
                		$query[] = fgets($file);
                		if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1){
                    		$query = trim(implode('', $query));
							if ($this->connection->query($query) === false){
                        		$this->output .= 'ERROR: ' . $query . "\n";
                    		}else{
                        		$this->output .= 'SUCCESS: ' . $query . "\n";
                    		}
                    		
                    		while (ob_get_level() > 0){
                        		ob_end_flush();
                    		}
							flush();
                		}

                		if (is_string($query) === true){
                    		$query = array();
                		}
            		}
					return fclose($file);
        		}		
    		}
    		return false;
    	}

		/*
		* The datatype returns the datatype from the given string. 
		* @param {mixed} -> $type
		* @return {string}
		*/
		public function dataType($options){
			// @TODO improve data types
			if(is_array($options)){
				$type = $options['type']; 
			}else{
				$type = $options;
			}

			switch ($type) {
				case 'string':
					if(is_array($options) && isset($options['length'])){
						return "VARCHAR(" . $options['length'] . ")";
					}else{
						return "VARCHAR(255)";
					}	
				break;
					
				case 'integer': 
					if(is_array($options) && isset($options['length'])){
						return "INT(" . $options['length'] . ")";
					}else{
						return "INT(11)";
					}
				break;

				case 'datetime':
					if(is_array($options)){
						$datetime_ret = "datetime ";
						if(isset($options['null']) && !$options['null']){
							$datetime_ret .= "NOT NULL ";
						}
						
						if(isset($options['default'])){
							$datetime_ret .= "DEFAULT '" . $options['default'] . "'";
						}else{
							if($options['null'] && !$options['null']){
								$datetime_ret .= "NOT NULL DEFAULT '0000-00-00 00:00:00'";
							}else{
								$datetime_ret .= "DEFAULT NULL";
							}
						}

						return $datetime_ret;
					}else{
						return "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'";
					}
				break;

				case 'enum':
					if(is_array($options) && isset($options['options'])){
						$last_el = end($options['options']);
						$opt = "";
						foreach ($options['options'] as $o) {
							$opt .= "'$o'";
							if($o != $last_el){
								$opt .= ",";
							}
						}
						return "enum(".$opt.")"; 
					}
					else{
						return $options;
					}
				break;
 
				default:
					return $options; 
				break;
			}
		}

		public function check_installation(){
			$exists = $this->connection->query('SELECT 1 FROM schema_migrations');

			if(!$exists){
				die("\nMigrations plugin is not installed properly.\n");
			}
		}

		public function camelize($name){
			return preg_replace('/(?:^|_)(.?)/e',"strtoupper('$1')",$name); 
		}

		/*
		* Gets the class name of the file you've included.
		* @param {string} <- File with path ie : ./versions/1382641175_create_users_table
		* @return {string}
		*/
		public function get_class_name($filename){
			$class_arr = explode('/', $filename);
			$cl_name_array = explode('_', $class_arr[2]);
			$class_name = str_replace($cl_name_array[0] . '_', '', $class_arr[2]);
			$class_name = $this->camelize($class_name);
			$class_name = str_replace(".php", '', $class_name);
			return $class_name; 
		}

		/*
		* Gets the version number of the given migration file 
		* @params {string} <- migration file name .
		* @return {string}
		*/ 
		public function get_version_number($filename){
			$class_arr = explode('/', $filename);
			$cl_name_array = explode('_', $class_arr[2]);
			return($cl_name_array[0]);
		}
	}	
?>