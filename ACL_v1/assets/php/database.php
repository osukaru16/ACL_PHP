<?php
session_start();
ob_start();
$hasDB = false;
$server = 'localhost';
$user = 'root';
$pass = '';  //mysql
$db = 'acl_test';
$link = mysqli_connect($server,$user,$pass);

/*
if (!is_resource($link)) {   
	$hasDB = false;
	die("Could not connect to the MySQL server at localhost.");
} else {   
	$hasDB = true;*/
	mysqli_select_db($link, $db);
//}
?>