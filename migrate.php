#!/usr/bin/php -q
<?php
	include "Migrations.php";

	$migrate = new Migrations();

	switch ($argv[1]) {
		case 'install':
			$migrate->install();
		break;
		
		case 'generate':
			if(!isset($argv[2])){
				die("\nPlease specify a name for the migration\n");
			}else{
				$migrate->generate($argv[2]);
			}
		break;

		case 'g':
			if(!isset($argv[2])){
				die("\nPlease specify a name for the migration\n");
			}else{
				$migrate->generate($argv[2]);
			}
		break;

		case 'run':
			if(isset($argv[2])){
				$migrate->run($argv[2]);
			}else{
				$migrate->run();
			}
		break;

		case 'rm':
			if(isset($argv[2])){
				$migrate->run_down($argv[2]);
			}else{
				die("\nPlease specify the id of the migration you want to delete\n");
			}
		break; 

		default:
			
		break;
	}	 

	echo $migrate->output; 
?> 