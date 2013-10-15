#!/usr/bin/env php-cli
<?php

function add_to_tree ($res) {
	global $tree, $langs, $mysqli;

	while ($row = $res->fetch_assoc()) {
		$name = mb_strtolower($row['name']);
		echo "Adding $name...\n";

		$sql = $mysqli->query("
			SELECT *
			FROM `translate`
			WHERE `en` = '" . $mysqli->escape_string($name) . "'
		");

		$translate = array('en' => $row['name']);
		if ($sql->num_rows > 0) {
			$translate = $sql->fetch_assoc();
		}

		foreach ($langs as $lang) {
			if (!isset($translate[$lang])) {
				continue;
			}

			$str = mb_strtolower($translate[$lang]);
			$current = &$tree[$lang];
			for ($i = 0; $i < mb_strlen($str); $i++) {
				$c = mb_substr($str, $i, 1);
				if (!isset($current[$c])) {
					$current[$c] = array();
				}

				$current = &$current[$c];
			}
		}
	}
}

if (count($_SERVER['argv']) < 1) {
	exit;
}

define('IN_APP', 1);
require_once 'sql.php';

ini_set('memory_limit', -1);
mb_internal_encoding('UTF-8');

$langs = array('en', 'de', 'es', 'fr', 'it', 'jp', 'nl', 'pt');
$tree = array(
	'en' => array()
	, 'de' => array()
	, 'es' => array()
	, 'fr' => array()
	, 'it' => array()
	, 'jp' => array()
	, 'nl' => array()
	, 'pt' => array()
);

$res = $mysqli->query("
	SELECT `id`, `name`
	FROM `items`
	ORDER BY `name` ASC
");
add_to_tree($res);

$res = $mysqli->query("
	SELECT `id`, `name`
	FROM `sets`
	ORDER BY `name` ASC
");
add_to_tree($res);

foreach ($langs as $lang) {
	if ($lang === 'en') {
		$treefile = 'tree.json';
	} else {
		$treefile = "tree.$lang.json";
	}

	file_put_contents($treefile, json_encode($tree[$lang]));
}

?>