#!/usr/bin/env php
<?php
/**
 * dst_analyze_tree.php
 * 
 * Manipulates the built raw tree to facilitate his analysis or parsing.
 * 
 * Data will be re-ordered in this tree structure: 
 * - multidim (multiple entry for file found)
 * - not found (file resulting not found)
 * - diff (file resulting modified)
 * - directory (where file are found)
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version 2020-06-23
 * @license MIT License
 * 
 * @example php dst_analyze_tree.php DataTree.json DataAnalyzeTree.json
 * 
 * Source JSON file should has the following structure:
 * [
 *   0,
 *   {
 *     "_root/": {
 *       "multidim": {
 *         "same path": [ "/drive/dst/same path", "/drive/src/same path 0" ]
 *       }
 *     }
 *   },
 *   {
 *     "_root/": {
 *       "multidim": {
 *         "same path": [ "/drive/dst/same path", "/drive/src/same path 1" ]
 *       }
 *     }
 *   },
 *   {
 *     "_root/": {
 *       "diff": {
 *         "file2.mp3": [ "/drive/src/file2.mp3" ],
 *         "file3.ext0": [ "/drive/src/file3.ext0", "/drive/src/another path/file3.ext0" ]
 *       }
 *     }
 *   },
 *   {
 *     "_root/": {
 *       "notfound": { "file1.mp3": "/drive/dst/file1.mp3", "file2.ext0": "/drive/dst/file2.ext0" }
 *     }
 *   }
 * ]
 * 
 * 
 * JSON destination file will have the following structure: 
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
 *     data : { "_root/": { "same path 0": "/drive/src/same path 1", "same path 1": "/drive/src/same path 1", "another path": "/drive/src/another path" } }
 *   }
 * }
 */

(isset($_SERVER["argv"]) && count($_SERVER["argv"]) === 2) || die("Usage: php dst_analyze_tree.php [source_json] [dest_json]\n");

$source_json = isset($_SERVER["ARGV"][1]) && file_exists($_SERVER["ARGV"][1]) ? $_SERVER["ARGV"][1] : exit(1);
$dest_json = empty($_SERVER["ARGV"][2]) ? exit(1) : $_SERVER["ARGV"][2];

$json = file_get_contents($source_json);
$json = str_replace("[0,", "[", $json);
$json = json_decode($json, true);

$data = [];

foreach ($json as $i => $tree) {
	if (! is_array($tree)) continue;

	foreach ($tree as $index => $subtree) {
		foreach ($subtree as $node => $dat) {
			if (! isset($data[$node]["length"])) $data[$node]["length"] = 0;
			if (! isset($data[$node]["data"][$index])) $data[$node]["data"][$index] = [];

			$data[$node]["data"][$index] += $dat;
			$data[$node]["length"] += count($dat);
		}
	}
}

var_dump($data, count($data));

file_put_contents($dest_json, json_encode($data));

