<?php

/**
 * This class represents model stored to mongo db 
 */
class File extends CActiveRecord {
    /* public $_id;
      public $filename;
      public $filetype;
      public $extension; // file extension without the dot
      public $path; // full path to this file
      public $fullpath;
      public $last_modification; //by user
      public $last_indexed; // by cora
      public $processed = false; // is it indexed? lines are probably 0;
      public $lines_total = 0;
      public $lines_empty = 0;
      public $lines_code = 0;
      public $lines_comment = 0; */

    public $icon;
    
    /**
     * Array of all possible attributes we might track whithin the file
     * @var type 
     */
    public static $track = array('lines_total', 'lines_empty', 'lines_code', 'lines_comment', 'num_functions', 'code_size', 'filesize');
    
    /**
     * Returns the static model of the specified AR class.
     * @return Offer the static model class
     */
    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'file';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('filename', 'required'),
            array('file_id, filesize, project_id, lines_comment, last_indexed, lines_total, lines_empty, lines_code, lines_code, last_modification, processed, num_functions, code_size, filtered', 'numerical', 'integerOnly' => true),
            array('path, filetype, extension, icon', 'safe')
        );
    }
    
    /**
     * get the right css class depending on file extension. Use it only when the
     * file is being rendered.
     */
    public function loadFileTypeIcon() {
        parent::afterFind();
        if ($this->filetype == 'dir'){
            $this->icon = 'dir-icon';
        }else{
            // TODO: load confing
            $this->icon = 'file-icon';
        }
        //$this->attributes['icon'] = $this->icon;
        //Shared::debug($this->icon);
    }
    
    /*public function inDatabase() {
        $result = EDMSQuery::instance('files')->findArray(array('path' => $this->path, 'filename' => $this->filename, 'last_modification' => array('$lte' => $this->last_modification)), array('last_modification'));
        if (count($result))
            return true;
        return false;
    }*/


    /**
     * whats wrong with this one? 
     */
    /*public function update() {
        Shared::debug("updating {$this->path}{$this->filename}");
        $attributes = $this->attributes;
        unset($attributes['_id']);
        EDMSQuery::instance('files')->upsert(array('path' => $this->path, 'filename' => $this->filename), $attributes);
        //EDMSQuery::instance('files')->insert($this->attributes);
        //Shared::debug($res);
    }*/

    /**
     * Reads the content of file and parse it. This might be overwritten for different types of code 
     * TODO: should be a behavior based on file extension
     */
    public function process() {
        $lines = file($this->path . $this->filename);
        $openComment = false;
        $this->lines_total = count($lines);

        foreach ($lines as $line) {
            $this->filesize += strlen($line);
            $line = trim($line); // trim tabs, spaces and line breaks from the file
            
            if ($openComment && !preg_match('/\*\//', $line)) {
                // open and no closing tag
                $this->lines_comment++;
            } else if (strlen($line) == 0) {
                // nothing
                $this->lines_empty++;
            } else if (preg_match('/^\/\//', $line)) {
                // double slash
                $this->lines_comment++;
            } else if (preg_match('/^#/', $line)) {
                // another single line comment
                $this->lines_comment++;
            } else if (preg_match('/^\/\*.*\*\//', $line)) {
                // multiline on single line
                $this->lines_comment++;
            } else if (preg_match('/^\/\*/', $line)) {
                // opening multiline
                $this->lines_comment++;
                $openComment = true;
            } else if (preg_match('/\*\//', $line)) {
                // closing multiline
                $this->lines_comment++;
                $openComment = false;
            } else {
                // everything else ... suppose regular code
                $this->lines_code++;
                if (strstr($line, 'function')){
                    $this->num_functions ++;
                }
                $slashes = strpos($line, "//");
                if ($slashes) $line = substr($line, 0, $slashes);
                $this->code_size += strlen($line);
            }
        }
        $this->processed = true;
        $this->save();
    }

    /**
     * Exclude statistics of this file / folder in the global stats 
     */
    function addFilter(){
        Shared::debug("adding filter to {$this->path}{$this->filename}");
        $this->filtered = 1;
        $this->save();
        Shared::debug($this->getErrors());
        // TODO: recursively find parents and recalulate stats there
        return $this->updateParent();
    }
    
    /**
     * Include statistics of this file / folder in the global stats 
     */
    function removeFilter(){
        Shared::debug("removing filter from {$this->path}{$this->filename}");
        $this->filtered = 0;
        $this->save();
        Shared::debug($this->getErrors());
        return $this->updateParent();
    }
    
    /**
     * Recalculate stats of parent folders after changing filter settings
     * of this file. This function recurively removes stats of this file
     * in all the parents.
     * @param array $updatedDirs
     * @return type 
     */
    private function updateParent($updatedDirs = array()){
        $lastSlash = strrpos($this->path, "/", -2);
        $parentPath = substr($this->path, 0, $lastSlash) . "/";
        $parentName = trim(substr($this->path, $lastSlash), "/");
        Shared::debug($parentPath . "-" . $parentName);
        $parent = File::model()->findByAttributes(array('path' => $parentPath, 'filename' => $parentName));
        if (is_object($parent)){
            Shared::debug("found parent");
            
            // it is enough to recalculate only one dir, not whole sub structure ...
            FileIndexer::calculateDirs(array('path' => $parent->path, 'filename' => $parent->filename), false);
            
            // load again, FileIndexer update stats for parent
            $parent = File::model()->findByPk($parent->file_id);
            $updatedDirs[] = $parent->attributes;
            $updatedDirs = $parent->updateParent($updatedDirs);
        }
        return $updatedDirs;
    }
}

?>
