<?php

class FileController extends Controller {

    public function filters() {
        return array('access');
    }

    /**
     * Go through files stores in database, check their updates and store it back to database
     * this function does not perform any calculations, only searches for new files 
     */
    public function actionCrawl() {
        $project = Cora::getActiveProject();
        // we should detect this before, so the project dashboard shows warning and goes to configuration
        if (is_object($project)) {
            // delete all the files, so the deleted files are not calculated ... better way is to go through all files and check if they exist
            //if (isset($_POST['restart']) && $_POST['restart']) {
            //Shared::debug("deleting files");
            //Yii::app()->db->createCommand("DELETE FROM file WHERE path LIKE '{$project->path}}%'")->execute();
            $db = Yii::app()->db;
            $command = $db->createCommand("SELECT file_id, filename, path FROM file WHERE path LIKE '{$project->path}%'");
            Shared::debug("SELECT file_id, filename, path FROM file WHERE path LIKE '{$project->path}%'");
            $dataReader = $command->query();
            while (($row = $dataReader->read()) !== false) {
                if (!file_exists($row['path'] . '/' . $row['filename'])) {
                    Shared::debug("deleting " . $row['path'] . '/' . $row['filename']);
                    $db->createCommand("DELETE FROM file WHERE file_id = " . $row['file_id'])->execute();
                } else {
                    Shared::debug($row['filename'] . ' exists');
                }
            }
            //}
            echo FileIndexer::crawl($project->path);
        } else {
            $this->redirect('/site/index');
        }
    }

    /**
     * Load unprocessed files from database and load their metrics 
     */
    public function actionProcess() {
        $limit = 100;
        $project = Cora::getActiveProject();
        if (is_object($project)) {
            $count = FileIndexer::process($project, $limit);
            if ($count < $limit) {
                echo "done";
            } else {
                echo $count;
            }
        } else {
            return false;
        }
    }

    /**
     * Sum comnts for files in directories and apply given filters.
     * Run it after all the file stats are calculated 
     */
    public function actionRecalculateStats() {
        $project = Cora::getActiveProject();
        if (is_object($project)) {
            FileIndexer::calculateDirs($project->path);
            echo "true";
        } else {
            return false;
        }
    }

    /**
     * Load files and directories inside one directory.
     * @param type $directory 
     */
    public function actionGetDir() {
        $project = Cora::getActiveProject();
        if (is_object($project)) {
            $path = $project->path;
            if (isset($_POST['path'])) {
                // make sure this is part of the project
                $path = urldecode($_POST['path']);
                Shared::debug($path);
                if (!strstr($path, $project->path)) {
                    Shared::debug("not in the path");
                    Yii::app()->end();
                }
            }
            if (substr_compare($path, '/', -1, 1) != 0)
                $path .= "/";
            Shared::debug($path);
            // need to include exclusion for the file
            $files = File::model()->findAllBySql("SELECT * FROM file WHERE path = '$path' ORDER BY filetype='file', filename");
            Shared::debug("SELECT * FROM file WHERE path = '$path' ORDER BY filetype='file', filename");
            $result = array();

            $db = Yii::app()->db;

            foreach ($files as $file) {
                // detect icon and merge it with file attributes
                $file->loadFileTypeIcon();
                $attributes = $file->attributes;
                $attributes['icon'] = $file->icon;
                // has it any childs?
                $attributes['has_children'] = 0;
                if ($file->filetype == 'dir') {
                    $count = $db->createCommand("SELECT COUNT(*) FROM file WHERE path = '{$file->path}{$file->filename}/'")->queryScalar();
                    if ($count)
                        $attributes['has_children'] = 1;
                }

                $result[] = $attributes;
            }
            Shared::debug($result);
            echo json_encode($result);
            Yii::app()->end();
        } else {
            return false;
        }
    }

    /**
     * Filter this file / dir, so it does not count in the project statistics 
     */
    public function actionAddFilter() {
        $project = Cora::getActiveProject();
        //$project->createRootPath();
        if (is_object($project)) {
            if (isset($_POST['file_id'])) {
                Shared::debug("add filter for " . $_POST['file_id']);
                $file = File::model()->findByPk($_POST['file_id']);
                $updated = $file->addFilter();
                Shared::debug($updated);
                echo json_encode($updated);
                Yii::app()->end();
            }
        }
    }

    public function actionRemoveFilter() {
        $project = Cora::getActiveProject();
        if (is_object($project)) {
            if (isset($_POST['file_id'])) {
                Shared::debug("add filter for " . $_POST['file_id']);
                $file = File::model()->findByPk($_POST['file_id']);
                $updated = $file->removeFilter();
                Shared::debug($updated);
                echo json_encode($updated);
                Yii::app()->end();
            }
        }
    }

    /**
     * Return json decoded stats for the file
     */
    public function actionGetInfo() {

        if (isset($_POST['file_id'])) {
            // TODO: redo it for Linux, stupid!
            $file = File::model()->findByPk($_POST['file_id']);
            if (is_object($file)) {
                /* /foreach (File::$track as $attr){
                  $stats[$attr] = $file->$attr;
                  } */
                Shared::debug($file->attributes);
                echo json_encode($file->attributes);
                Yii::app()->end();
            }
        }
        $file = new File;
        echo json_encode($file->attributes);
        Yii::app()->end();
    }

}

?>
