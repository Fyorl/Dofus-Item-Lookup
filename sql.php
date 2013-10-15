<?php

if (!defined('IN_APP')) {
	exit;
}

$mysqli = new mysqli(SQL_HOST, SQL_USER, SQL_PASS, SQL_DB);
$mysqli->set_charset('utf8');

?>
