#!/usr/bin/env php
<?php
/**
 * dst_check_tree__mp3.php
 * 
 * Checks MP3 files for integrity.
 * 
 * Example of using the built ordered tree.
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version 2020-06-23
 * @license MIT License
 * 
 * @example php dst_check__mp3.php DstFolder DataAnalyzeTree.json DataCheckResults.json
 * 
 * 
 * Source JSON file should has the following structure: 
 * {
 *   multidim: {
 *     length: 2,
 *     data : { "_root/": { "same path 0": "/drive/src/same path 0", "same path 1": "/drive/src/same path 1" } }
 *   },
 *   notfound: {
 *     length: 2,
 *     data : { "_root/": { "file1.mp3": "/drive/dst/file1.mp3", "file2.ext0": "/drive/dst/file2.ext0" } }
 *   },
 *   diff: {
 *     length: 3,
 *     data : { "_root/": { "file2.mp3": [ "/drive/src/file2.mp3" ], "file3.ext0": [ "/drive/src/file3.ext0", "/drive/src/another path/file3.ext0" ] } }
 *   },
 *   directory: {
 *     length: 2,
 *     data : { "_root/": { "same path 0": "/drive/src/same path 1", "same path 1": "/drive/src/same path 1" } }
 *   }
 * }
 * 
 * 
 * JSON destination file will have the following structure: 
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

(isset($_SERVER["argv"]) && count($_SERVER["argv"]) === 3) || die("Usage: php dst_check_tree_mp3.php [base_dst] [source_json] [dest_json]\n");

$base_dst = isset($_SERVER["argv"][1]) && file_exists($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : exit(1);
$source_json = isset($_SERVER["argv"][2]) && file_exists($_SERVER["argv"][2]) ? $_SERVER["argv"][2] : exit(1);
$dest_json = empty($_SERVER["argv"][3]) ? exit(1) : $_SERVER["argv"][3];

// env var MP3CHECK_PIPELINE
$MP3CHECK_PIPELINE = "--only-mp3 --anomaly-check --error-check --any-crc --any-bitrate --any-sampling --any-mode --ign-junk-start --ign-junk-end --ign-bitrate-sw --show-valid";
$MP3CHECK_PIPELINE = isset($_SERVER["MP3CHECK_PIPELINE"]) ? $_SERVER["MP3CHECK_PIPELINE"] : $MP3CHECK_PIPELINE;

$source = file_get_contents($source_json);
$source = json_decode($source, true);
$source = $source["notfound"]["data"];

$files = ["ok" => ["length" => 0, "data" => []], "nok" => ["length" => 0, "data" => []], "misc" => ["length" => 0, "data" => []]];

function escapebtb($str) {
	return str_replace(["`", "$"], ["\`", "\\$"], $str);
}

foreach ($source as $dir => $dat) {
	$dest_dir = rtrim(str_replace("_root/", $base_dst, $dir), "/") . "/";
	$dirname = str_replace(__DIR__, ".", $dest_dir); 

	echo ". directory    {$dirname}\n\n";

	foreach ($dat as $filename => $file) {
		$ext = strtolower(substr($filename, strrpos($filename, ".") + 1));

		if ($ext !== "mp3") {
			echo ". skipping    {$ext}  {$filename}\n\n";

			$files["misc"]["length"] += 1;
			$files["misc"]["data"][$dir][$filename] = ["src" => $file];

			continue;
		}

		echo ". checking    {$filename}\n\n";

		$check = shell_exec("mp3check " . $MP3CHECK_PIPELINE . " --dummy \"" . escapebtb($file) . "\"");

		if ($check) {
			$check = explode("\n", rtrim($check, "\n"));
			array_shift($check);

			$status = false;
			if ($check[0] === "valid audio mpeg stream") $status = true;
			else if (count($check) === 1 && strpos($check[0], "valid id3 tag trailer") === 0) $status = true;

			echo "\t" . implode("\n\t", $check) . "\n\n";

			$status = $status ? "ok" : "nok";

			$files[$status]["length"] += 1;
			$files[$status]["data"][$dir][$filename] = ["src" => $file, "check" => $check];
		} else {
			throw new Error("Missing library: mp3check.");
		}

		echo "\n";
	}
	
}

var_dump($files, count($files));

file_put_contents($dest_json, json_encode($files));

