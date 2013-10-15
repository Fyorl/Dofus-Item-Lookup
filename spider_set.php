#!/usr/bin/env php-cli
<?php

function clean ($str) {
	$str = htmlspecialchars_decode($str, ENT_QUOTES);
	$str = preg_replace('#<[^>]+>#', '', $str);
	$str = trim($str);

	return $str;
}

function process_category ($category, $full_link = false) {
	global $wiki;

	echo "Processing category: $category...\n";
	$url = "{$wiki}/wiki/Category:{$category}";
	if ($full_link) {
		$url = $wiki . $category;
	}
	$contents = file_get_contents($url);

	$start = strpos($contents, '<div id="mw-pages">');
	$end = strpos($contents, '<div class="printfooter">');
	$contents = substr($contents, $start, ($end - $start));

	$matches = array();
	$skip = strpos($contents, '<h3>*</h3>');

	preg_match_all(
		'#<li><a href="([^"]+)" title="([^"]+)">([^<]+)</a></li>#'
		, $contents
		, $matches
		, PREG_SET_ORDER
	);

	foreach ($matches as $k => $match) {
		if ($skip && $k < 2) {
			continue;
		}

		echo "Processing item: $match[3]...\n";
		process_item($match[1]);
	}

	$next = preg_match(
		'#<a href="([^"]+)" title="[^"]+">next 200</a>#'
		, $contents
		, $matches
	);

	if ($next) {
		process_category($matches[1], true);
	}
}

function process_item ($link) {
	global $wiki, $mysqli, $chars;

	$matches = array();
	$contents = file_get_contents($wiki . $link);
	$full_contents = $contents;

	$snip = snip(
		$full_contents
		, '<header id="WikiaPageHeader" class="WikiaPageHeader">'
		, '<nav class="wikia-menu-button" >'
	);

	preg_match('#<h1>([^>]+)</h1>#', $snip, $matches);
	$name = $matches[1];
	$name = preg_replace('#\([^)]+\)#', '', $name);
	$name = htmlspecialchars_decode($name, ENT_QUOTES);
	$name = strtr($name, $chars);

	$items = array();
	$bonus = array();
	$characteristics = array();
	$attack = array();
	$defense = array();
	$misc = array();

	$lookup = array('characteristics', 'attack', 'defense', 'misc');

	$snip = snip($full_contents, '<table class="wikitable"', '</table>');
	$start = strpos($full_contents, '<table class="wikitable"');
	$end = strpos($full_contents, '</table>', $start);
	preg_match_all(
		'#<th>(.*)$#m'
		, $snip
		, $matches
		, PREG_SET_ORDER
	);

	foreach ($matches as $match) {
		$inner = array();
		if (!preg_match('#<br /><a href="([^"]+)"#', $match[1], $inner)) {
			continue;
		}
		$items[] = $inner[1];
	}

	$snip = snip($full_contents, '<table class="wikitable"', '</table>', $end);
	$end = strpos($full_contents, '</table>', $end + strlen('</table>'));
	preg_match_all(
		'#<tr>.<th>(.*?)</td></tr>#s'
		, $snip
		, $matches
		, PREG_SET_ORDER
	);

	foreach ($matches as $match) {
		$inner = array();
		preg_match('#<td>(.*)$#m', $match[1], $inner);
		$bonus[] = clean($inner[1]);
	}

	$snip = snip($full_contents, '<table class="wikitable"', '</table>', $end);
	preg_match_all('#<td>(.*?)</td>#s', $snip, $matches, PREG_SET_ORDER);

	foreach ($matches as $k => $match) {
		if (trim($match[1]) === '') {
			continue;
		}

		$var = $lookup[$k];
		$ar = &$$var;
		$inner = array();
		preg_match_all('#<li>(.*)$#m', $match[1], $inner, PREG_SET_ORDER);

		foreach ($inner as $row) {
			$ar[] = clean($row[1]);
		}
	}

	$mysqli->query("
		REPLACE INTO `sets` (
			`link`
			, `name`
			, `items`
			, `bonus`
			, `characteristics`
			, `attack`
			, `defense`
			, `misc`
		)
		VALUES (
			'" . $mysqli->escape_string($link) . "'
			, '" . $mysqli->escape_string($name) . "'
			, '" . $mysqli->escape_string(serialize($items)) . "'
			, '" . $mysqli->escape_string(serialize($bonus)) . "'
			, '" . $mysqli->escape_string(serialize($characteristics)) . "'
			, '" . $mysqli->escape_string(serialize($attack)) . "'
			, '" . $mysqli->escape_string(serialize($defense)) . "'
			, '" . $mysqli->escape_string(serialize($misc)) . "'
		)
	");
}

function snip ($contents, $start, $end, $offset = 0) {
	$s = strpos($contents, $start, $offset);
	if ($s !== false) {
		$e = strpos($contents, $end, $s);
		if ($e !== false) {
			return substr($contents, $s, ($e - $s));
		}
	}

	return false;
}

if (count($_SERVER['argv']) < 1) {
	exit;
}

define('IN_APP', 1);
require_once 'sql.php';

$wiki = 'http://dofuswiki.wikia.com';
$categories = array(
	'Set'
);

require_once 'chars.php';

foreach ($categories as $category) {
	process_category($category);
}

?>
