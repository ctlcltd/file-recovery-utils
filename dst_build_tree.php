#!/usr/bin/env php
<?php
/**
 * dst_build_tree.php
 * 
 * Generates a JSON file with entries in a raw tree structure: 
 * - base path
 *   - path
 *     - multidim (multiple entry for file found)
 *     - not found (file resulting not found)
 *     - diff (file resulting modified)
 * - directory (where file are found)
 * 
 * @author Leonardo Laureti <https://loltgt.ga>
 * @version 2020-06-23
 * @license MIT License
 * 
 * @example php dst_build_tree.php SrcFolder DstFolder DataFileBase.csv DataTree.json exc,excludefileext
 * 
 * 
 * JSON destination file will have the following structure: 
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
 */

(isset($_SERVER["argv"]) && count($_SERVER["argv"]) === 4) || die("Usage: php dst_build_tree.php [base_src] [base_dst] [dest_csv] [dest_json] [exclude_filetypes]\n");

global $base_src, $base_dst, $exclude_filetypes, $data;

$base_src = isset($_SERVER["argv"][1]) && file_exists($_SERVER["argv"][1]) ? $_SERVER["argv"][1] : exit(1);
$base_dst = isset($_SERVER["argv"][2]) && file_exists($_SERVER["argv"][2]) ? $_SERVER["argv"][2] : exit(1);
$dest_csv = empty($_SERVER["ARGV"][3]) ? exit(1) : $_SERVER["ARGV"][3];
$dest_json = empty($_SERVER["ARGV"][4]) ? exit(1) : $_SERVER["ARGV"][4];
$exclude_filetypes = empty($_SERVER["ARGV"][5]) ? [] : explode(",", $_SERVER["ARGV"][5]);

data(true);

checkFiles($base_src);


function data($arg) {
	global $data;

	if (is_array($arg) && ! empty($arg)) {
		fwrite($data[1], "," . json_encode($arg) . "]");
		fseek($data[1], -1, SEEK_END);
	} else if ($arg === true) {
		$data = [];
		$data[2] = csv_to_arr(file($dest_csv));
		$data[0] = fopen($dest_csv, "c+");
		$data[1] = fopen($dest_json, "c+");

		! fread($data[0], 1) && fwrite($data[0], "i,n\n");
		! fread($data[1], 1) && fwrite($data[1], "[0]");

		fseek($data[0], 0, SEEK_END);
		fseek($data[1], -1, SEEK_END);
	} else if ($arg === false) {
		fclose($data[0]);
		fclose($data[1]);
	}
}

function check($i = null, $n = null) {
	global $data;

	fwrite($data[0], str_replace(",", "\\\\,", $i) . "," . str_replace(",", "\\\\,", $n) . "\n");
}

function passed($i = null, $n = null) {
	global $data;

	return isset($data[2][$i][$n]);
}

function debug($fn, $ret = null, $args = null) {
	echo ($fn[0] === "." ? "\t" : "\n"), $fn,
		(is_null($ret) ? "" : "    " . (is_array($ret) ? arr_to_ssa_str($ret) : (is_string($ret) ? "\"{$ret}\"" : var_export($ret, true)))),
		(is_null($args) ? "" : "    " . (is_array($args) ? arr_to_ssa_str($args) : (is_string($args) ? "\"{$args}\"" : var_export($args, true)))),
		"\n\n";
}

function arr_to_ssa_str($arr = array()) {
	$len = count($arr) -1;
	$i = 0;
	$r = "";

	$r .= "[ ";
	foreach ($arr as $k => $v) {
		$r .= "{$k} => " . (is_string($v) ? "\"{$v}\"" : var_export($v, true));
		if ($i++ != $len) $r .= ", ";
	}
	$r .= " ]";

	return $r;
}

function csv_to_arr($csv) {
	if (! is_array($csv)) return [];

	$csv = array_map("str_getcsv", $csv);
	$arr = [];

	next($csv);
	foreach($csv as $in) $arr[$in[0]][$in[1]] = true;

	return $arr;
}

function escapebtb($str) {
	return str_replace(["`", "$"], ["\`", "\\$"], $str);
}

function findDstDirectory($src_dir, $root = null) {
	global $base_dst;

	$root = $root ? rtrim($root, "/") . "/" : $base_dst;
	$dst_dir = null;

	$dir = basename($src_dir);

	$fa = shell_exec("find \"" . $root . "\" -name \"" . $dir . "\" -type d -print");

	if ($fa) {
		$dst_dir = explode("\n", rtrim($fa, "\n"));
	} else {
		$dir = strpos($dir, "(") ? substr($dir, 0, strpos($dir, "(")) : $dir;
		$dir = str_replace([" ", "**", "**"], "*", $dir);

		$sa = shell_exec("find \"" . escapebtb($root) . "\" -name \"" . escapebtb($dir) . "\" -type d -print");

		if ($sa) {
			$dst_dir = explode("\n", rtrim($sa, "\n"));
		}
	}

	debug('findDstDirectory', $dst_dir, [ '$src_dir' => $src_dir, '$root' => $root ]);

	return $dst_dir;
}

function findDstFile($src_file, $root = null) {
	global $base_dst, $exclude_filetypes;

	$root = $root ? rtrim($root, "/") . "/" : $base_dst;
	$dst_file = null;

	$file = basename($src_file);

	if (file_exists($root . $file)) {
		$dst_file = [ $root . $file ];
	} else if (! empty($exclude_filetypes) && ! in_array(substr($file, strrpos($file, ".") + 1), $exclude_filetypes)) {
		$fa = shell_exec("find \"" . $root . "\" -name \"" . $file . "\" -type f -print");

		if ($fa) {
			$dst_file = explode("\n", rtrim($fa, "\n"));
		} else {
			$file = strpos($file, "(") ? substr($file, 0, strpos($file, "(")) : $file;
			$file = strpos($file, "[") ? substr($file, 0, strpos($file, "[")) : $file;
			$file = str_replace([" ", "_", "-", "&", "."], "*", $file);
			$file = str_replace(["**", "**"], "*", $file);

			$sa = shell_exec("find \"" . escapebtb($root) . "\" -name \"" . escapebtb($file) . "\" -type f -print");

			if ($sa) {
				$dst_file = explode("\n", rtrim($sa, "\n"));
			}
		}
	}

	debug('findDstFile', $dst_file, [ '$src_file' => $src_file, '$root' => $root ]);

	return $dst_file;
}

function checkFiles($src_dir, $root = null, $data = []) {
	global $base_src;

	$i = basename($src_dir);
	$idx = str_replace($base_src, "_root/", $src_dir);
	$dir = opendir($src_dir);

	debug('checkFiles', $idx, [ '$src_dir' => $src_dir, '$root' => $root ]);

	while (($n = readdir($dir)) !== false) {
		if ($n[0] === ".")
			continue;

		if (passed($i, $n)) {
			debug('. skip', $i, $n);

			continue;
		}

		$src = realpath(rtrim($src_dir, "/") . "/" . $n);

		if (is_dir($src)) {
			$dst_dir = findDstDirectory($src, $root);

			if (! empty($dst_dir)) {
				if (count($dst_dir) > 1) {
					$data[$idx]["multidim"][$n] = $dst_dir;

					debug('. multidim', $dst_dir);
					check($i, $n);
					data($data);

					$data = [];

					continue;
				} else {
					$data[$idx]["directory"][$n] = $dst_dir[0];
				}
			}

			$data = checkFiles($src, $dst_dir[0], $data);

			check($i, $n);
			data($data);

			$data = [];

			continue;
		}

		$dst_file = findDstFile($src, $root);

		if (empty($dst_file)) {
			$data[$idx]["notfound"][$n] = $src;

			debug('. notfound', $src);
			check($i, $n);

			continue;
		}

		$diff = [];

		foreach ($dst_file as $dst) {
			$d = shell_exec("diff -q \"" . escapebtb($dst) . "\" \"" . escapebtb($src) . "\"");

			debug('. diff', ! empty($d), $dst);

			if ($d) $diff[] = $dst;
		}

		if (! empty($diff)) {
			$data[$idx]["diff"][$n] = $diff;
		}

		check($i, $n);
	}

	return $data;
}

data(false);

