#!/usr/bin/env php
<?php
/* 
 * WP-Migrate
 *
 * @version 0.8.4
 * @author micah blu
 */

$config = array();

$shortopts  = "";
$shortopts .= "d:"; // Database
$shortopts .= "h:"; // Hostname
$shortopts .= "u:"; // Username
$shortopts .= "p:"; // Password
$shortopts .= "s:"; // New site / server url

$longopts  = array(
    "database:",
    "hostname:",
    "username:",
    "password:",
    "site:"
);

$options = getopt($shortopts, $longopts);

if(count($options) > 0){
	$config['username'] = $options["u"];
	$config['password'] = $options["p"];
	$config['hostname'] = $options["h"];
	$config['database'] = $options["d"];
	$config['siteurl'] = trim($options["s"]);

}

if(file_exists("config.php")){
	include "config.php";
}else{

	// Find wp config and extract credentials
	$path = '';	
	$wp_config = '';

	for( $i=0, $dirs = explode("/", dirname(__FILE__)), $j = count($dirs); $i < $j; $i++ ){
		$path .= DIRECTORY_SEPARATOR . $dirs[$i];
		
		if(file_exists($path . DIRECTORY_SEPARATOR . "wp-config.php")){
			$wp_config = file_get_contents($path . DIRECTORY_SEPARATOR . "wp-config.php");
			break;
		}
	}

	if($wp_config !== ''){
		preg_match_all("/define\((.+)\)/", $wp_config, $matches);

		echo "Found matches: \n";

		if(isset($matches[1])){
			foreach($matches[1] as $line){
				if(preg_match("/DB_NAME/", $line)){
					$config['database'] = trim(explode(',', str_replace("'", "", $line))[1]);
				}elseif(preg_match("/DB_USER/", $line)){
					$config['username'] = trim(explode(',', str_replace("'", "", $line))[1]);
				}elseif(preg_match("/DB_PASSWORD/", $line)){
					$config['password'] = trim(explode(',', str_replace("'", "", $line))[1]);
				}elseif(preg_match("/DB_HOST/", $line)){
					$config['hostname'] = trim(explode(',', str_replace("'", "", $line))[1]);
				}
			}
			
		}
	}
}

if(count($config) > 0){
	foreach($config as $field => $value){
		echo $field . " = " . $value . "\n";
		if(!$value){
			echo "Sorry but $field is missing\n";
			exit();
		}
	}
}else{
	echo "It apprears you have a broken config.php file\n";
}

if(!isset($config['siteurl'])){
	echo 'Missing new site url';
	exit();
}

/**
 * Connect to our database using the same credentials as the wordpress installation
 */
$cn = mysql_connect($config['hostname'], $config['username'], $config['password']) or die("Could not connect");
mysql_select_db($config['database']);

/** 
 * Grab the old Site URL
 */
$sql = "SELECT * FROM wp_options WHERE option_name='siteurl'";
$result = mysql_query($sql) or die(mysql_error());
$row = mysql_fetch_assoc($result);

$oldSiteURL = $row["option_value"];
echo "Old site url: " . $oldSiteURL . "\n";

// update siteurl and home
$sql = "UPDATE wp_options SET option_value='" . $config['siteurl'] . "' WHERE option_name='siteurl'";
$result = mysql_query($sql) or die(mysql_error());

$sql = "UPDATE wp_options SET option_value='" . $config['siteurl'] . "' WHERE option_name='home'";
$result = mysql_query($sql) or die(mysql_error());


/**
 * Returns primary key column name
 * @param  String $table database table name
 * @param  Resource $cxn Mysql connection resource
 * @return String Column name
 */
function mysql_primary_column_name($table, $cn){
	$sql = "show index from $table where Key_name = 'PRIMARY'";
	$result= mysql_query($sql) or die('Bad Query: ' . $sql);

	$arr = mysql_fetch_array($result);
	return $arr['Column_name'];
}

$sql = "SHOW TABLES";
$result = mysql_query($sql) or die('Bad Query: ' . $sql);

while($tables = mysql_fetch_assoc($result)){
	foreach($tables as $table){

		// Find primary key
		$primary_field = mysql_primary_column_name($table, $cn);

		$sql = "SELECT * FROM $table";
		$subresult= mysql_query($sql) or die('Bad Query: ' . $sql);

		while($row = mysql_fetch_assoc($subresult)){

			foreach($row as $field => $value){
				if(preg_match('%' . preg_quote($oldSiteURL) . '%', $value)){

					$newvalue = str_replace($oldSiteURL, $config['siteurl'], $value);

					$sql = "UPDATE $table SET $field = '$newvalue' WHERE $primary_field = " . $row[$primary_field];
					$update_result = mysql_query($sql) or die(mysql_error());

					echo "Matched: \"" . $value . "\"\n";
					echo "in " . $table . "." . $field . "\n";
					echo "New value:\n";
					echo $newvalue . "\n";
					echo "----------------------------------------------------\n\n";
				}
			}
		}
	}
}
echo "all done :)";
mysql_close();