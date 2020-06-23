#!/usr/bin/env php
<?php
/**
 * dst_restore_tree.php
 * 
 * Tries to restore files using checked entries.
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version 2020-06-23
 * @license MIT License
 * 
 * @example php dst_restore_tree.php SrcFolder DstFolder DataCheckResults.json ToCopyFolderDest
 * 
 * 
 * Source JSON should has the following structure: 
 * {
 *   ok: {
 *     length: 1,
 *     data : { "_root/path": { "file.mp3": { src: "/drive/dst/path/file.mp3", check: [ "valid audio mpeg stream" ] } } }
 *   },
 *   nok: {
 *     length: 1,
 *     data : { "_root/path": { "file0.mp3": { src: "/drive/dst/path/file0.mp3", check: [ "can't stat file (dangling symbolic link?)" ] } } }
 *   },
 *   misc: {
 *     length: 1,
 *     data : { "_root/path": { "file1.flac": { src: "/drive/dst/path/file1.flac" } } }
 *   }
 * }
 */

(isset($_SERVER["argv"]) && count($_SERVER["argv"]) === 4) || die("Usage: php src_restore_tree.php [base_src] [base_dst] [source_json] [dest_folder]\n");

$base_src = isset($_SERVER["argv"][1]) && file_exists($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : exit(1);
$base_dst = isset($_SERVER["argv"][2]) && file_exists($_SERVER["argv"][2]) ? $_SERVER["argv"][2] : exit(1);
$source_json = isset($_SERVER["argv"][3]) && file_exists($_SERVER["argv"][3]) ? $_SERVER["argv"][3] : exit(1);
$dest_folder = empty($_SERVER["argv"][4]) ? exit(1) : $_SERVER["argv"][4];

$source = file_get_contents($source_json);
$source = json_decode($source, true);
$source = $source["diff"]["data"];

function escapebtb($str) {
	return str_replace(["`", "$"], ["\`", "\\$"], $str);
}

foreach ($source as $dir => $dat) {
	$dst_dir = rtrim(str_replace("_root/", $dest_folder, $dir), "/") . "/";
	$dirname = str_replace(__DIR__, ".", $dst_dir); 

	echo ". directory    {$dirname}\n\n";

	shell_exec("mkdir -p \"" . escapebtb($dst_dir) . "\"");

	foreach ($dat as $filename => $choice) {
		if (count($choice) > 1) {
			echo "Choice for {$filename} : \n\n";
			foreach ($choice as $i => $file) {
				$file = str_replace(__DIR__, ".", $file);
				echo "[{$i}] {$file}\n";
			}
			echo "[n] for skip ...\n\n";
			echo " ?\n\n";

			$input = rtrim(fgets(STDIN));

			if (strtolower($input) === "n") {
				echo "\n. skipping    {$filename}\n";
			} else if (is_numeric($input)) {
				$file = str_replace(__DIR__, ".", $choice[$input]);
				echo "\n. copying    {$filename}  selected {$input}  {$file}\n";

				shell_exec("cp \"" . escapebtb($choice[$input]) . "\" \"" . escapebtb($dst_dir . $filename) . "\"");
			} else {
				throw new Error("Bad input.");
			}
		} else {
			$found = str_replace(__DIR__, ".", $choice[0]);
			echo ". copying    {$filename}  {$found}\n";

			shell_exec("cp \"" . escapebtb($choice[0]) . "\" \"" . escapebtb($dst_dir . $filename) . "\"");
		}

		echo "\n";
	}
	
}

