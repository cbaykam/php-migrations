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
				die("\nPlease see the help page for command usage\n");
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

		default:
			
		break;
	}
?> 