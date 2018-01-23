<?php


	class ACL
	{
		var $perms = array();		//Array : Stores the permissions for the user
		var $userID = 0;			//Integer : Stores the ID of the current user
		var $userRoles = array();	//Array : Stores the roles of the current user
		var $conect;

		
		function __construct($userID = '')
		{
			if ($userID != '')
			{
				$this->userID = floatval($userID);
			} else {
				$this->userID = floatval($_SESSION['userID']);
			}
			$conect = new mysqli('localhost', 'root', '', 'acl_test');

			$this->userRoles = $this->getUserRoles('ids');
			//echo "permisos".var_dump($this->userRoles);
			$this->buildACL();


/*
			try{

				$opciones = array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8");
				$conect = new PDO('mysql:host=localhost;dbname=acl_test', 'root', '', $opciones);
				
			}
			catch (PDOException $p){

				echo "<h1> Error ".$p->getMessage()."</h1><br />";
			}
*/

			






		}
		/*
		function ACL($userID = '')
		{
			$this->__constructor($userID);
			//crutch for PHP4 setups
		}
		*/
		function buildACL()
		{
			//first, get the rules for the user's role
			if (count($this->userRoles) > 0)
			{
				$this->perms = array_merge($this->perms,$this->getRolePerms($this->userRoles));
			}
			//then, get the individual user permissions
			$this->perms = array_merge($this->perms,$this->getUserPerms($this->userID));
		}
		
		function getPermKeyFromID($permID)
		{
			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //temporal


			$strSQL = "SELECT `permKey` FROM `permissions` WHERE `ID` = " . floatval($permID) . " LIMIT 1";
			$data = mysqli_query($conect, $strSQL);
			$row = mysqli_fetch_array($data);
			return $row[0];
		}
		
		function getPermNameFromID($permID)
		{

			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //temporal

			$strSQL = "SELECT `permName` FROM `permissions` WHERE `ID` = " . floatval($permID) . " LIMIT 1";
			$data = mysqli_query($conect, $strSQL);
			$row = mysqli_fetch_array($data);
			return $row[0];
		}
		
		function getRoleNameFromID($roleID)
		{
			$strSQL = "SELECT `roleName` FROM `roles` WHERE `ID` = " . floatval($roleID) . " LIMIT 1";
			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //Temporal
			$data = mysqli_query($conect, $strSQL);
			$row = mysqli_fetch_array($data);
			return $row[0];
		}
		
		function getUserRoles()
		{
			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //temporal


			$strSQL = "SELECT * FROM `user_roles` WHERE `userID` = " . floatval($this->userID) . " ORDER BY `addDate` ASC";
			$data = mysqli_query($conect, $strSQL);
			$resp = array();
			while($row = mysqli_fetch_array($data))
			{
				$resp[] = $row['roleID'];
			}
			return $resp;
		}
		
		function getAllRoles($format='ids')
		{
			$format = strtolower($format);
			$strSQL = "SELECT * FROM `roles` ORDER BY `roleName` ASC";
			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //Temporal
			$data = mysqli_query($conect, $strSQL);
			$resp = array();
			while($row = mysqli_fetch_array($data))
			{
				if ($format == 'full')
				{
					$resp[] = array("ID" => $row['ID'],"Name" => $row['roleName']);
				} else {
					$resp[] = $row['ID'];
				}
			}
			return $resp;
		}
		
		function getAllPerms($format='ids')
		{
			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //temporal

			$format = strtolower($format);
			$strSQL = "SELECT * FROM `permissions` ORDER BY `permName` ASC";
			$data = mysqli_query($conect, $strSQL);
			$resp = array();
			while($row = mysqli_fetch_assoc($data))
			{
				if ($format == 'full')
				{
					$resp[$row['permKey']] = array('ID' => $row['ID'], 'Name' => $row['permName'], 'Key' => $row['permKey']);
				} else {
					$resp[] = $row['ID'];
				}
			}
			return $resp;
		}

		function getRolePerms($role)
		{
			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //temporal


			if (is_array($role))
			{
				$roleSQL = "SELECT * FROM `role_perms` WHERE `roleID` IN (" . implode(",",$role) . ") ORDER BY `ID` ASC";
			} else {
				$roleSQL = "SELECT * FROM `role_perms` WHERE `roleID` = " . floatval($role) . " ORDER BY `ID` ASC";
			}
			$data = mysqli_query($conect, $roleSQL);
			$perms = array();
			while($row = mysqli_fetch_assoc($data))
			{
				$pK = strtolower($this->getPermKeyFromID($row['permID']));
				if ($pK == '') { continue; }
				if ($row['value'] === '1') {
					$hP = true;
				} else {
					$hP = false;
				}
				$perms[$pK] = array('perm' => $pK,'inheritted' => true,'value' => $hP,'Name' => $this->getPermNameFromID($row['permID']),'ID' => $row['permID']);
			}
			return $perms;
		}
		
		function getUserPerms($userID)
		{
			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //Temporal


			$strSQL = "SELECT * FROM `user_perms` WHERE `userID` = " . floatval($userID) . " ORDER BY `addDate` ASC";
			$data = mysqli_query($conect, $strSQL);
			$perms = array();
			while($row = mysqli_fetch_assoc($data))
			{
				$pK = strtolower($this->getPermKeyFromID($row['permID']));
				if ($pK == '') { continue; }
				if ($row['value'] == '1') {
					$hP = true;
				} else {
					$hP = false;
				}
				$perms[$pK] = array('perm' => $pK,'inheritted' => false,'value' => $hP,'Name' => $this->getPermNameFromID($row['permID']),'ID' => $row['permID']);
			}
			return $perms;
		}
		
		function userHasRole($roleID)
		{
			foreach($this->userRoles as $k => $v)
			{
				if (floatval($v) === floatval($roleID))
				{
					return true;
				}
			}
			return false;
		}
		
		function hasPermission($permKey)
		{
			$permKey = strtolower($permKey);
			if (array_key_exists($permKey,$this->perms))
			{
				if ($this->perms[$permKey]['value'] === '1' || $this->perms[$permKey]['value'] === true)
				{
					return true;
				} else {
					return false;
				}
			} else {
				return false;
			}
		}
		
		function getUsername($userID)
		{

			$conect = new mysqli('localhost', 'root', '', 'acl_test'); //temporal


			$strSQL = "SELECT `username` FROM `users` WHERE `ID` = " . floatval($userID) . " LIMIT 1";
			$data = mysqli_query($conect, $strSQL);
			$row = mysqli_fetch_array($data);
			return $row[0];
		}
	}

?>