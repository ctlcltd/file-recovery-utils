# file-recovery-utils

Recovery utils to check / backup / restore files.

These scripts help you to find missed files from other sources and made quasi-human-readable JSON to seek found files.

 

#### src_backup_ref-folder.php

Backup from a referring folder using one folder as source.
```
php src_backup_ref-folder.php SrcFolder DstFolder FindFolderSource fileextension
```


#### src_backup_ref-text.php

Backup from a referring folder using one text file as source.
```
php src_backup_ref-text.php SrcFolder DstFolder FindTextSource.text fileextension
```


#### dst_build_tree.php

Generates a JSON file with entries in a raw tree structure: 
- base path
  - path
    - multidim (multiple entry for file found)
    - not found (file resulting not found)
    - diff (file resulting modified)
- directory (where file are found)
```
php dst_build_tree.php SrcFolder DstFolder DataFileBase.csv DataTree.json exc,excludefileext
```


#### dst_analyze_tree.php

Manipulates the built raw tree to facilitate his analysis or parsing.

Data will be re-ordered in this tree structure: 
- multidim (multiple entry for file found)
- not found (file resulting not found)
- diff (file resulting modified)
- directory (where file are found)
```
php dst_analyze_tree.php DataTree.json DataAnalyzeTree.json
```


#### dst_check_tree__mp3.php

Checks MP3 files for integrity.

Example of using the built ordered tree.

```
php dst_check__mp3.php DstFolder DataAnalyzeTree.json DataCheckResults.json
```


#### dst_restore_tree.php

Tries to restore files using checked entries.
```
php dst_restore_tree.php SrcFolder DstFolder DataCheckResults.json ToCopyFolderDest
```

 

### Requirements

* PHP >= 7
* find
* diff
* mkdir

PHP context with `shell_exec` function enabled.


##### Local environments purpose

 

## License

[MIT License](LICENSE).

