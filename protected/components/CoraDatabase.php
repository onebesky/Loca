<?php

class CoraDatabase {

    public static function createDatabase() {
        $db = Yii::app()->db;

        $db->createCommand("DROP TABLE IF EXISTS file")->execute();
        $db->createCommand("
CREATE TABLE file(
     file_id INTEGER PRIMARY KEY ASC,
     project_id INTEGER,
     filename TEXT,
     filetype TEXT,
     path TEXT,
     extension TEXT,
     processed INTEGER DEFAULT 0,
     last_modification INTEGER,
     last_indexed INTEGER,
     lines_total INTEGER DEFAULT 0,
     lines_empty INTEGER DEFAULT 0,
     lines_code INTEGER DEFAULT 0,
     lines_comment INTEGER DEFAULT 0,
     num_functions INTEGER DEFAULT 0,
     filesize INTEGER DEFAULT 0,
     code_size INTEGER DEFAULT 0,
     filtered INTEGER DEFAULT 0
)")->execute();

        $db->createCommand("
    CREATE INDEX idx_project ON file (project_id)
    ")->execute();

        $db->createCommand("
    CREATE INDEX idx_path ON file (path)
    ")->execute();

        $db->createCommand("DROP TABLE IF EXISTS project")->execute();
        $db->createCommand("
CREATE TABLE project(
     project_id INTEGER PRIMARY KEY ASC,
     project_name TEXT,
     path TEXT,
     last_scan INTEGER,
     filename_filter TEXT,
     index_only_extensions TEXT
)")->execute();

        $db->createCommand("DROP TABLE IF EXISTS project_filter")->execute();
        $db->createCommand("
CREATE TABLE project_filter(
     project_filter_id INTEGER PRIMARY KEY ASC,
     project_id INTEGER,
     excluded_path TEXT
)")->execute();

        // global configuration
        $db->createCommand("DROP TABLE IF EXISTS config")->execute();
        $db->createCommand("
CREATE TABLE config(
     param TEXT PRIMARY KEY,
     val TEXT
)")->execute();

        $ip = $_SERVER['REMOTE_ADDR'];
        Shared::debug($ip);
        if ($ip != '127.0.0.1'){
            $ip = '127.0.0.1,' . $ip;
        }
        
        if ($_SERVER['REMOTE_ADDR'] != "::1"){
            $ip .= ",::1";
        }
        Shared::debug($ip);
        self::setConfigParam('access_filter', $ip);
        //$db->createCommand("INSERT INTO config VALUES('access_filter', '127.0.0.1')")->execute();
    }

    /**
     * Load config value directly from database
     * @param type $param What do you want to return
     * @param type $default What is returned if nothing is found
     * @return type 
     */
    public static function getConfigParam($param, $default = null) {
        $res = Yii::app()->db->createCommand("SELECT val FROM config WHERE param = :param")->queryScalar(array(':param' => $param));
        if ($res) {
            return $res;
        } else {
            return $default;
        }
    }

    /**
     * Store config value in database
     * @param type $param
     * @param type $value 
     */
    public static function setConfigParam($param, $value) {
        $origValue = self::getConfigParam($param, null);
        Shared::debug("String $param => $value, orig is $origValue");
        //if ($origValue == '_undefined') {
        //    Yii::app()->db->createCommand("INSERT INTO config (param, val) VALUES(:param, :value)")->execute(array(':param' => $param, ':value' => $value));
        //} else {
            if ($origValue !== $value) {
                Yii::app()->db->createCommand("REPLACE INTO config (param, val) VALUES(:param, :value)")->execute(array(':param' => $param, ':value' => $value));
            }
        //}
    }

}

?>
