<?php

/**
 * This piece of shit is used to crawl on disk and collect information about files
 */
class FileIndexer {

    // gonna be loaded from config; assoc array is faster
    public static $indexFilters = array('.' => true, '..' => true, '.svn' => true, 'CSV' => true);
    //public static $forceReload = false;

    // what about file types ... we don't need to index all of them

    public static function crawl($startPath, $limit = 8) {
        if ($limit == 0)
            return;
        $unprocessed = 0;
        $project = Cora::getActiveProject();
        $fileConfig = $project->getFileConfig();

        // delete all the files, so the deleted files are not calculated
        /*if (self::$forceReload) {
            Shared::debug("deleting files");
            Yii::app()->db->createCommand("DELETE FROM file WHERE path LIKE '$startPath%'")->execute();
            
            // delete files only once
            self::$forceReload = false;
        }*/

        $startPath = str_replace("\\", "/", $startPath);
        if (substr_compare($startPath, '/', -1, 1) != 0)
            $startPath .= "/";

        if (is_dir($startPath)) {

            // asoc array containing all the project files
            // used to find updates in files while reading filesystem
            $files = array();
            $db = Yii::app()->db;
            $dataReader = $db->createCommand("SELECT * FROM file WHERE path LIKE '$startPath%'")->query();
            foreach ($dataReader as $row) {
                $files[$row['path'] . $row['filename']] = $row;
            }

            $dh = opendir($startPath);
            Shared::debug("opening $startPath");
            if ($dh) {
                //Shared::debug("opening dir");
                while (($filename = readdir($dh)) !== false) {
                    //Shared::debug("found $filename");
                    // todo: load exceptions from config
                    if (!isset(self::$indexFilters[$filename])) {
                        $fullPath = $startPath . $filename;
                        $extension = substr($filename, strrpos($filename, '.') + 1);
                        $type = filetype($fullPath);

                        if ($type != 'dir' && !isset($fileConfig[$extension])) {
                            Shared::debug("$extension is not supported.");
                        } else {
                            $file = new File;

                            $file->last_modification = filemtime($fullPath);

                            //Shared::debug("processing $filename");

                            // update the file in the database?
                            //$update = true;
                            if (isset($files[$fullPath])) {
                                if ($file->last_modification <= $files[$fullPath]['last_modification']) {
                                    // already there, skip it
                                    //$update = false;
                                } else {
                                    $file->attributes = $files[$fullPath];
                                    // we need to update this field, not crete new one
                                    $file->isNewRecord = false;
                                    $file->last_indexed = time();
                                    $file->processed = false;
                                    $file->save();
                                    $unprocessed++;
                                }
                            } else {
                                // new record
                                $file->path = $startPath;
                                $file->filename = $filename;
                                $file->last_indexed = time();
                                $file->project_id = $project->project_id;
                                
                                if ($type == 'file') {
                                    $file->filetype = 'file';
                                } else if ($type == 'dir') {
                                    // save the directory
                                    $file->filetype = 'dir';
                                }
                                if ($type == 'file') {
                                    $file->extension = $extension;
                                }
                                $file->last_indexed = time();
                                $file->save();
                                $unprocessed++;
                            }

                            if ($type == 'dir') {
                                Shared::debug("GO inside " . $fullPath . " ($limit)");
                                self::crawl($fullPath, $limit - 1);
                            }
                        }
                    }else{
                        Shared::debug("$filename is filtered");
                    }
                }
            }
        } else {
            Shared::debug("cannot find the project path");
        }

        return $unprocessed;
    }

    /**
     * Load unprocessed files from database and analyze them
     * @param type $limit 
     */
    public static function process($project, $limit = 100) {
        // load list of unprocessed files
        $files = File::model()->findAllBySql("SELECT * FROM file WHERE processed = 0 AND filetype = 'file' AND project_id = {$project->project_id} LIMIT 0,$limit");

        foreach ($files as $file) {
            /* $file = new File;
              $file->path = $_file['path'];
              $file->filename = $_file['filename']; */
            $file->process();
        }
        return count($files);
    }

    public static function calculateDirs($path, $recurse = true) {
        //$files = File::model()->findAllBySql("SELECT file_id, filename, path FROM file WHERE processed = 0 AND filetype = 'dir' AND path LIKE '{$project->path}%' LIMIT 0,$limit");
        // asoc array containing all the project files
        // used to find updates in files while reading filesystem
        $files = array();
        $dirs = array();

        $db = Yii::app()->db;
        // TODO: include filters ... doh. Any of the parents can be filtered
        if ($recurse) {
            $parentPath = substr($path, 0, strrpos($path, "/", -2)) . "/";
            $dataReader = $db->createCommand("SELECT * FROM file WHERE path LIKE '{$path}%' OR path = '$parentPath'")->query();
        } else {
            // combine filename and path together to get single level of the tree
            if (is_array($path)) {
                $dataReader = $db->createCommand("SELECT * FROM file WHERE path = '" . $path['path'] . "' OR path = '" . $path['path'] . $path['filename'] . "/'")->query();
                //Shared::debug("SELECT * FROM file WHERE path = '" . $path['path'] . "' OR path = '" . $path['path'] . $path['filename'] . "/'");
                $path = $path['path'] . $path['filename'] . "/";
            } else {
                // load only one object, it is not really useful
                $dataReader = $db->createCommand("SELECT * FROM file WHERE path = '{$path}' OR path = '{$path}'")->query();
            }
        }

        foreach ($dataReader as $row) {
            if ($row['filetype'] == 'dir') {
                // reset all dirs when indexing whole filesystem
                if ($recurse) {
                    // not sure
                    foreach (File::$track as $attribute) {
                        $row[$attribute] = 0;
                    }
                }
                $dirs[$row['path'] . $row['filename'] . '/'] = $row;
                $files[$row['path'] . $row['filename'] . '/'] = $row;
                // reset the attributes
            } else {
                $files[$row['path'] . $row['filename']] = $row;
            }
        }

        // reset counts only for updated directory
        if (!$recurse) {
            Shared::debug("reset $path");
            foreach (File::$track as $attribute) {
                $dirs[$path][$attribute] = 0;
            }
        }

        //Shared::debug($files);
        // step 2: find parents of each file and start counting
        foreach ($files as $file) {
            if (!$file['filtered']) {
                //Shared::debug("file " . $file['filename'] . ' has ' . $file['lines_total'] . ' lines of code.');
                $filename = $file['path'] . $file['filename'];
                $parts = explode("/", $filename);
                //Shared::debug(count($parts) - 1);
                //Shared::debug($parts);
                //Shared::debug($filename);
                // we want to update all the directories above
                // absolute path ends by slash
                for ($i = count($parts) - 1; $i > 0; $i--) {
                    $parentPath = '';
                    for ($j = 0; $j < $i; $j++) {
                        $parentPath .= $parts[$j] . "/";
                    }

                    Shared::debug("dir path " . $parentPath);
                    if (isset($dirs[$parentPath])) {
                        //Shared::debug("update from " . $file['filename'] . " - $parentPath");
                        $parent = &$dirs[$parentPath];
                        foreach (File::$track as $attr){
                            $parent[$attr] += $file[$attr];
                        }
                    } else {
                        //Shared::debug($file['filename'] . " has no parent - $parentPath");
                        // reached the top level, lets move to another file
                        $i = 0;
                    }
                }
            } else {
                //Shared::debug("file " . $file['filename'] . ' is filtered and has ' . $file['lines_total'] . ' lines of code.');
            }
        }

        // step 3: save the directories
        // TODO: save only if there is a change
        foreach ($dirs as $path => $_dir) {
            if ($files[$path]['lines_total'] != $_dir['lines_total']) {
                $dir = new File();
                $dir->setAttributes($_dir);
                $dir->processed = true;
                $dir->isNewRecord = false;
                $dir->save();
            }
        }
    }

}

?>
