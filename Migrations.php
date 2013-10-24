<?php
	class Migrations {
		// database credentials
		const DBCONFIG = "config.ini";
		protected $db_host; 
		protected $db_user;
		protected $db_password;
		protected $db_database;
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
		public function change(){

		}
	}
?>
";
	 
	 		file_put_contents($file_path, $content);
		}

		/*
		* Creates table with the given credentials.
		* fields structure
		* array( 
		* 	array('name', 'type', 'options'), 
		*   array('name', 'type', 'options')
		* )
		* @param {string} $name -> the name of the table 
		* @param {array} $fields -> the list of fields to build the table
		* @return {bool}
		*/ 
		public function create_table($name, $fields){
			$qr = "CREATE TABLE IF NOT EXISTS " . $name . " (";
			$last_el = end($fields);
			foreach ($fields as $f) {
				$qr = $qr . "`" . $f[0] . "` " . $this->dataType($f[1]) . " " . $f[2] ;
				if($last_el != $f){
					$qr = $qr . ","; 
				}		
			}		
			$qr = $qr . ");";
			return $qr;
		}

		/*
		* Adds a field to the given table 
		* @param {string} => the name of the table 
		* @param {string} => the name of the field 
		* @param {string} => the type of the field
		* @param {array} => the options required for that field
		* @return {bool}
		*/
		public function add_field($table, $field, $type, $options){

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

			$var = $this->connection->query($this->create_table("schema_migrations",
				array(
					array('id', 'integer', 'NOT NULL'), 
					array('version', 'string', 'NULL')
				)
			));

			if(!file_exists('./versions')){
				mkdir('./versions', 0777, true);
			}
		}

		/*
		* Runs the migrations in their order.
		* @param {until} -> the id of the migraion you want run until. 
		* @return {bool} 
		*/
		public function run($until = Null){
			$this->check_installation();
			
			foreach(glob('./versions/*.*') as $filename){
			    require_once($filename);
			    $klass = $this->get_class_name($filename); 
			    echo $klass . "\n"; 
			} 
		}

		/*
		* The datatype returns the datatype from the given string. 
		* @param {string} -> $type
		* @return {string}
		*/
		public function dataType($type){
			// @TODO improve data types
			switch ($type) {
				case 'string':
					return 'VARCHAR(255)';	
				break;
					
				case 'integer': 
					return "INT(11)";
				break;

				default:
					return false; 
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
	}	
?>