#!/usr/bin/env php
<?php
/**
 * src_backup_ref-folder.php
 * 
 * Backup from a referring folder using one text file as source.
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version 2020-06-23
 * @license MIT License
 * 
 * @example php src_backup_ref-text.php SrcFolder DstFolder FindTextSource.text fileextension
 * 
 * 
 * Source text file should has the following structure:
 * 
 * /path/file0.ext
 * /path/file1.ext
 * ...
 */

(isset($_SERVER["argv"]) && count($_SERVER["argv"]) === 4) || die("Usage: php src_backup_ref-text.php [base_src] [base_dst] [find_text_source] [find_type]\n");

$base_src = isset($_SERVER["argv"][1]) && file_exists($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : exit(1);
$base_dst = isset($_SERVER["argv"][2]) && file_exists($_SERVER["argv"][2]) ? $_SERVER["argv"][2] : exit(1);
$find_text_source = isset($_SERVER["argv"][3]) && file_exists($_SERVER["argv"][3]) ? $_SERVER["argv"][3] : exit(1);
$find_type = empty($_SERVER["argv"][4]) ? exit(1) : $_SERVER["argv"][4];

function escapebtb($str) {
	return str_replace(["`", "$"], ["\`", "\\$"], $str);
}

$missing = fopen($find_text_source, "r");

$files = [];

while (($buffer = fgets($missing)) !== false) {
	$buffer = str_replace("\n", "", $buffer);

	$file = basename($buffer);
	$file = substr($file, 0, -4);

	$files[$buffer] = null;

	$fa = shell_exec("find \"" . escapebtb($base_src) . "\" -name \"" . escapebtb($file) . "." . escapeshellcmd($find_type) . "\" -type f -print");

	if ($fa) {
		$files[$buffer] = explode("\n", $fa)[0];
	} else {
		$file = strpos($file, "(") ? substr($file, 0, strpos($file, "(")) : $file;
		$file = strpos($file, "[") ? substr($file, 0, strpos($file, "[")) : $file;
		$file = str_replace([" ", "_", "-", "&"], "*", $file);
		$file = str_replace(["**", "**"], "*", $file);

		$sa = shell_exec("find \"" . escapebtb($base_src) . "\" -name \"" . escapebtb($file) . " ." . escapeshellcmd($find_type) . "\" -type f -print");

		if ($sa) {
			$files[$buffer] = explode("\n", $sa)[0];
		}
	}
}

var_dump($files, count($files));


echo "\n\n\n\n";

foreach($files as $file => $found) {
	$filepath = substr(dirname($file), 2) . "/";
	$filename = basename($file);

	shell_exec("mkdir -p \"" . escapebtb($base_dst . $filepath) . "\"");

	if ($found) {
		$foundname = basename($found);

		shell_exec("cp \"" . escapebtb($found) . "\" \"" . escapebtb($base_dst . $filepath . $filename) . "\"");
	} else {
		echo $file . "\n";
	}
}

