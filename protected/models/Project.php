<?php

class Project extends CActiveRecord {
    
    private $fileConfig = null;
    
    /*public $_id;
    public $project_id;
    public $project_name;
    public $path;*/
    
    /** filters indexing files */
    //public $filename_filter = '.svn, CSV';
    
    /** what files it should read */
    //public $index_only_extensions = "php";
    
    
    /**
     * Returns the static model of the specified AR class.
     * @return Offer the static model class
     */
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'project';
    }
    
    public function beforeValidate() {
        // get rid of windows back slashes ... PHP works fine with linux path as well
        if (strpos($this->path, '\\')){
            $this->path = str_replace('\\', '/', $this->path);
        }
        
        // remove the last slash
        $lastSlash = substr($this->path, strlen($this->path) - 1);
        if ($lastSlash == '/'){
            $this->path = substr($this->path, 0, strlen($this->path) - 1);
        }
        
        // check if something is there
        if (!is_dir($this->path)){
            $this->addError('path', 'Cannot find the root directory for this project. Make sure the path is correct.');
            return false;
        }
        return true;
    }
    
    public function afterValidate(){
        $root = $this->getRootFile();
        if (!is_object($root) && $this->project_id > 0){
            // create a root file ... it is the one above project
            $this->createRootFile();
            Shared::debug("root file created");
        }
    }
    
    /**
     * Creates directory record for the project 
     */
    public function createRootFile(){
        $lastSlash = strrpos($this->path, "/", -2);
            $projectPath = substr($this->path, 0, $lastSlash) . "/";
            $projectName = trim(substr($this->path, $lastSlash), "/");
            $root = new File;
            $root->path = $projectPath;
            $root->filename = $projectName;
            $root->filetype = 'dir';
            $root->project_id = $this->project_id;
            $root-> save();
    }
    
    public function getFilenameFilter(){
        $filter = array ('.', '..');
        $additional = explode($this->filename_filter, ',');
        foreach ($additional as $f){
            $filter[] = trim($f);
        }
        return $filter;
    }
    
    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('project_name', 'required'),
            array('path, filename_filter, index_only_extensions', 'safe')
        );
    }
    
   /* public function update() {
        $attributes = $this->attributes;
        if ($this->project_id == null){
            $this->project_id = EDMSSequence::nextVal('project_id'); 
        }
        unset($attributes['_id']); // auto assigned by mongo db
        EDMSQuery::instance('projects')->upsert(array($this->project_id), $attributes);
    }*/
    
    /**
     * Returns project found in a session, the one we are working with 
     */
   /* public static function getActiveProject(){
        $data = EDMSQuery::instance('project')->findOne();
        if (is_array($data)){
            $project = new Project;
            $project->attributes = $data;
            return $project;
        }
        return null;
    }*/
    
    // TODO: replaced by create root file
   /* public function createRootPath(){
        $lastSlash = strrpos($this->path, "/", -2);
        $projectPath = substr($this->path, 0, $lastSlash) . "/";
        $projectName = trim(substr($this->path, $lastSlash), "/");

        $file = File::model()->findByAttributes(array('path' => $projectPath, 'filename' => $projectName));

        if (!is_object($file)){
            $file = new File();
            $file->filename = $projectName;
            $file->path = $projectPath;
            $file->filetype = "dir";
            $file->save();
        }
    }*/
    
    /**
     * Returns root file associated with the project
     * @return type 
     */
    public function getRootFile(){
        $lastSlash = strrpos($this->path, "/", -2);
        $projectPath = substr($this->path, 0, $lastSlash) . "/";
        $projectName = trim(substr($this->path, $lastSlash), "/");

        $file = File::model()->findByAttributes(array('path' => $projectPath, 'filename' => $projectName));
        return $file;
    }
    
    public function loadFileConfig(){
        // just to simulate stored settings
        $stored = array("PhpConfig", "JavascriptConfig");
        $this->fileConfig = array();
        foreach ($stored as $fileConfig){
            Yii::import('application.components.filetypes.' . $fileConfig);
            $config = new $fileConfig();
            foreach ($config->extensions as $extension){
                $this->fileConfig[$extension] = $config->commentRules;
            }
        }
    }
    
    public function getFileConfig($extension = null){
        if ($this->fileConfig == null){
            $this->loadFileConfig();
        }
        if ($extension == null){
            return $this->fileConfig;
        }else{
            if (isset($this->fileConfig[$extension])){
                return $this->fileConfig[$extension];
            }else{
                throw new CException("Cannot find config for $extension");
            }
        }
    }
}
?>
