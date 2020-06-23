#!/usr/bin/env php
<?php
/**
 * src_backup_ref-folder.php
 * 
 * Backup from a referring folder using one folder as source.
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version 2020-06-23
 * @license MIT License
 * 
 * @example php src_backup_ref-folder.php SrcFolder DstFolder FindFolderSource fileextension
 */

(isset($_SERVER["argv"]) && count($_SERVER["argv"]) === 4) || die("Usage: php src_backup_ref-folder.php [base_src] [base_dst] [find_folder_source] [find_type]\n");

$base_src = isset($_SERVER["argv"][1]) && file_exists($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : exit(1);
$base_dst = isset($_SERVER["argv"][2]) && file_exists($_SERVER["argv"][2]) ? $_SERVER["argv"][2] : exit(1);
$find_src_folder = isset($_SERVER["argv"][3]) && file_exists($_SERVER["argv"][3]) ? $_SERVER["argv"][3] : exit(1);
$find_type = empty($_SERVER["argv"][4]) ? exit(1) : $_SERVER["argv"][4];


function escapebtb($str) {
	return str_replace(["`", "$"], ["\`", "\\$"], $str);
}

$dir = opendir($find_src_folder);

$files = [];

while (($cdir = readdir($dir)) !== false) {
	if ($cdir[0] === '.')
		continue;

	$filename = substr($cdir, 0, -4);

	$files[$filename] = null;

	$file = $filename;

	$fa = shell_exec("find \"" . escapebtb($base_src) . "\" -name \"" . escapebtb($file) . "." . escapeshellcmd($find_type) . "\" -type f -print");

	if ($fa) {
		$files[$filename] = explode("\n", $fa)[0];
	} else {
		$file = strpos($file, "(") ? substr($file, 0, strpos($file, "(")) : $file;
		$file = strpos($file, "[") ? substr($file, 0, strpos($file, "[")) : $file;
		$file = str_replace([" ", "_", "-", "&"], "*", $file);
		$file = str_replace(["**", "**"], "*", $file);

		$sa = shell_exec("find \"" . escapebtb($base_src) . "\" -name \"" . escapebtb($file) . " ." . escapeshellcmd($find_type) . "\" -type f -print");

		if ($sa) {
			$files[$filename] = explode("\n", $sa)[0];
		}
	}
}

var_dump($files, count($files));


echo "\n\n\n\n";

foreach($files as $file => $found) {
	$found = str_replace($base_src . "/", $base_dst, $found);

	if ($found) {
		$foundname = basename($found);

		shell_exec("cp \"" . escapebtb($found) . "\" \"" . escapebtb($base_dst . $foundname) . "\"");
	} else {
		echo $file . "." . $find_type . "\n";
	}
}

