Php Standalone Migrations Plugin v0.0.1 
================================

This plugin enables to migrate your db tables as in Ruby on Rails. 


Installation : 
-------------------------

To install the plugin download it and put it in a sub directory in your project 

Make sure you have the rights to execute the migrate.php 
Move the config.php.sample to config.php and update the contents with your db credentials.
        
        ./migrate.php install 

Generating Migrations : 
-------------------------

To generate a migration you have to run the command below. With specifying the class name with underscores.

		
		./migrate.php generate the_name_of_the_migration 

This will generate a php file under versions directory with the unix timestamp. 

Coding migration files : 
-------------------------

Open the migration file you generated and in the up method add the change you want to add. In the down methid add the reverse functions for rolling back the change if something goes wrong. 

* Adding a table (when using "create_table", Migrations will automatically add an auto-incrementing primary key column called "id")

	 
		class CreateUsersTable extends Migrations{
			public function up(){
				$this->create_table('users', array(
					array('username', 'string'),
					array('password', array('type' => 'string', 'length' => 252)),
					array('sign_in_count', 'integer')
				));
			}

			// We have to code the reverse of it for being able to rollback
			public function down(){
				$this->drop_table('users');
			}
		}

  you can either choose to specify the field type with string by choosing defaults or an array if you want to customize.

* Adding a field 
		
		class AddAStrangeFieldToUsers extends Migrations{
			public function up(){
				$this->add_field('users', 'strange_field', array('type' => 'string', 'length' => 12));
				$this->add_field('users', 'strange_two', 'integer');
				$this->add_field('users', 'strange_three', 'string');
			}

			public function down(){
				$this->remove_field('users', 'strange_field');
				$this->remove_field('users', 'strange_two');
				$this->remove_field('users', 'strange_three');
			}
		}

* Removing a field

        class RemoveVeryImportantField extends Migrations{
	        public function up(){
		        $this->remove_field('users', 'strange_two');
	        }

	        public function down(){
	        	$this->add_field('users', 'strange_two', 'string');
	    	}
        }

* Removing a table 

		class CreateUsersTable extends Migrations{
			public function up(){
				$this->drop_table('users');
			}

			public function down(){
				$this->create_table('users', array(
					array('username', 'string'),
					array('password', array('type' => 'string', 'length' => 252)),
					array('sign_in_count', 'integer')
				));
			}
		}

* Running migrations

        ./migrate.php run 

Field types and options 
-------------------------
* String 
	length
* Integer 
	length 
* Enum 
	options
* Datetime 
	null 
	options

For existing projects
-------------------------

Install the plugin, export the latest version of your database and generate a new migration ie: database.sql 
		
		./migrate.php generate initial_version 

Then open the migration file and in the up method please insert 

		$this->runsql('database.sql');

The database sql file path is relative to the path you installed the plugin.

Todos 
-------------------------
* Add more datatypes 
* Make output messages smarter 
* Add schema dump and schema create.
* Add availability to handle migrations with the same class name. 