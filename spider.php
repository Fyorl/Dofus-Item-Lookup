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
		if ($skip && $k === 0) {
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

function process_craftable ($link, $first_run = false) {
	global $wiki, $mysqli;
	$matches = array();

	if (!$first_run) {
		$res = $mysqli->query("SELECT * FROM `craft` WHERE `link` = '$link'");
		if ($res->num_rows > 0) {
			return false;
		}

		echo "Processing craftable: $link...\n";

		$contents = file_get_contents($wiki . $link);
		if (strpos($contents, '<dt> Crafted by') === false) {
			return false;
		}
	} else {
		$contents = $first_run;
	}

	$start = strpos($contents, '<dt> Crafted by');
	$end = strpos($contents, '</ul>', $start);
	$invalid = strpos($contents, '<dt> Used in the craft of');
	
	if ($invalid !== false && $end > $invalid) {
		return false;
	}

	$snip = substr($contents, $start, ($end - $start));
	if ($snip) {
		preg_match_all('#<li>(.*)$#m', $snip, $matches, PREG_SET_ORDER);
		$craft = array();

		foreach ($matches as $match) {
			$link = array();
			preg_match(
				'/<a href="([^"]+)" title="([^"]+)"/'
				, $match[1]
				, $link
			);

			$sub = process_craftable($link[1]);
			if (!$sub) {
				$sub = array();
			}

			$mysqli->query("
				INSERT INTO `craft`
				(`link`, `name`, `craft`)
				VALUES (
					'" . $mysqli->escape_string($link[1]) . "'
					, '" . $mysqli->escape_string($link[2]) . "'
					, '" . $mysqli->escape_string(serialize($sub)) . "'
				)
			");

			$craft[] = array('str' => clean($match[1]), 'link' => $link[1]);
		}

		return $craft;
	}

	return false;
}

function process_item ($link) {
	global $wiki, $mysqli, $chars;

	$matches = array();
	$contents = file_get_contents($wiki . $link);
	$full_contents = $contents;

	$start = strpos($contents, '<div class="NavHead"');
	$end = strpos($contents, 'Official description');
	$contents = substr($contents, $start, ($end - $start));

	preg_match('#</span>([^<]+)</div>#', $contents, $matches);
	$name = $matches[1];
	$name = htmlspecialchars_decode(preg_replace('#\([^)]+\)#', '', $name), ENT_QUOTES);
	$name = strtr($name, $chars);

	$damage = array();
	$effects = array();
	$conditions = array();
	$craft = array();
	$fname = '';

	$snip = snip($contents, 'Damage</a> :', '</table>');
	if ($snip) {
		preg_match_all('#<li>(.*)$#m', $snip, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$damage[] = clean($match[1]);
		}
	}

	$snip = snip($contents, '<dt>Effects :</dt>', '</table>');
	if ($snip) {
		preg_match_all('#<li>(.*)$#m', $snip, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$effects[] = clean($match[1]);
		}
	}

	$snip = snip($contents, '<dt>Conditions :</dt>', '</td>');
	if ($snip) {
		preg_match_all('#<li>(.*)$#m', $snip, $matches, PREG_SET_ORDER);

		foreach ($matches as $match) {
			$conditions[] = clean($match[1]);
		}
	}

	preg_match(
		'#<table style="background:none;width:11em;" cellspacing="0" cellpadding="0">(.*?)</table>#s'
		, $contents
		, $matches
	);
	$features = $matches[1];

	$img = array();
	
	if (strpos($features, '<noscript>') === false) {
		$found = preg_match(
			'#<img alt="[^"]+" src="([^"]+)"#'
			, $features
			, $img
		);
	} else {
		$found = preg_match(
			'#<img alt="[^"]+" src="[^"]+" width="\d+" height="\d+" data-src="([^"]+)"#'
			, $features
			, $img
		);
	}

	if ($found) {
		$paths = explode('/', $img[1]);
		$fname = urldecode(array_pop($paths));
		echo "Caching $fname...\n";

		$data = file_get_contents($img[1]);
		file_put_contents('cache/' . $fname, $data);
	}

	$craft = process_craftable(false, $full_contents);
	if (!$craft) {
		$craft = array();
	}

	$mysqli->query("
		REPLACE INTO `items` (
			`link`
			, `name`
			, `damage`
			, `effects`
			, `conditions`
			, `features`
			, `craft`
			, `image`
		) VALUES (
			'" . $mysqli->escape_string($link) . "'
			, '" . $mysqli->escape_string($name) . "'
			, '" . $mysqli->escape_string(serialize($damage)) . "'
			, '" . $mysqli->escape_string(serialize($effects)) . "'
			, '" . $mysqli->escape_string(serialize($conditions)) . "'
			, '" . $mysqli->escape_string($features) . "'
			, '" . $mysqli->escape_string(serialize($craft)) . "'
			, '" . $mysqli->escape_string($fname) . "'
		)
	");
}

function snip ($contents, $start, $end) {
	$s = strpos($contents, $start);
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
	'Amulet'
	, 'Backpack'
	, 'Belt'
	, 'Boots'
	, 'Cloak'
	, 'Dofus'
	, 'Hat'
	, 'Ring'
	, 'Shield'
	, 'Trophy'
	, 'Axe'
	, 'Bow'
	, 'Dagger'
	, 'Hammer'
	, 'Hunting_Weapon'
	, 'Pickaxe'
	, 'Scythe'
	, 'Shovel'
	, 'Staff'
	, 'Sword'
	, 'Tool'
	, 'Wand'
);

require_once 'chars.php';

foreach ($categories as $category) {
	process_category($category);
}

?>