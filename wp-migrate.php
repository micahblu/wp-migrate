#!/usr/bin/php
<?php
/* 
 * WP-Migrate
 *
 * @version 0.8
 * @author micah blu
 *
 */

/** The name of the database for WordPress */
define('DB_NAME', 'dev_wp_readytowear');

/** MySQL database username */
define('DB_USER', 'rtwadmin');

/** MySQL database password */
define('DB_PASSWORD', 'ACI950thirdave');

/** MySQL hostname */
define('DB_HOST', 'localhost');

/**
 * IMPORTANT - Set new site url i.e if new site is mynewsite.com enter: http://mynewsite.com
 */
$newSiteURL = "http://dev.readytowearbeauty.com";

/**
 * Connect to our database using the same credentials as the wordpress installation
 */
$cn = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD) or die("Could not connect");
mysql_select_db(DB_NAME);

/** 
 * Grab the old Site URL
 */
$sql = "SELECT * FROM wp_options WHERE option_name='siteurl'";
$result = mysql_query($sql) or die(mysql_error());
$row = mysql_fetch_assoc($result);

$oldSiteURL = $row["option_value"];

function mysql_primary_column_name($table, $cxn){
	$sql = "show index from $table where Key_name = 'PRIMARY'";
	$result= mysql_query($sql, $cxn) or die('Bad Query: ' . $sql);

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
				if(preg_match('#' . preg_quote($oldSiteURL) . '#', $value)){

					$newvalue = str_replace($oldSiteURL, $newSiteURL, $value);
					$sql = "UPDATE $table SET $field = '$newvalue' WHERE $primary_field = " . $row[$primary_field];
					$update_result = mysql_query($sql);
					
					echo "Found $oldSiteURL in " . $table . "." . $field . "\n";
					echo "New value:\n";
					echo $newvalue . "\n";
					echo "----------------------------------------------------\n\n";
				}
			}
		}
	}
}
echo "all done :)";
mysql_close($cn);