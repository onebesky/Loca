<?php

class Cora {

    public static $activeProject = null;

    /**
     * Load project stored in session, so the home screen can show directly
     * the files from the project
     * @return boolean 
     */
    public static function getActiveProject() {
        if (self::$activeProject != null) return self::$activeProject;
        $project = Yii::app()->session['activeProject'];
        if ($project == null) return false;
        self::$activeProject = Project::model()->findByPk($project);
        if (is_object(self::$activeProject)){
            return self::$activeProject;
        }
        return false;
    }

}

?>
