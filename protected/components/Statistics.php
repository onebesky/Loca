<?php

/**
 * Get some code statistics from the database using MongoDB algorithms. 
 */
class Statistics {

    public static function getFolderFullStat($pathToFolder) {

        $connection = new Mongo();
        $db = $connection->cora;
        
        $map = new MongoCode("function() {
            if (this.filetype == 'dir'){
                emit(this.path + this.filename + '/', {total: this.lines_total, empty: this.lines_empty, code: this.lines_code, comment: this.lines_comment});
            }else{
                emit(this.path, {total: this.lines_total, empty: this.lines_empty, code: this.lines_code, comment: this.lines_comment});
            }
            
        }");
        /*$map = new MongoCode("function() {
var map_send = function(k,v){
    emit(k, {total: v.lines_total, empty: v.lines_empty, code: v.lines_code, comment: v.lines_comment});
}
            
var recursive_mapper = function(obj, level){
    level = level || 0;
    if (level > 5) { return false; }
    if (obj.filetype == 'file'){
        map_send(obj.path,obj);
    }else{
        recursive_mapper(obj.path + obj.filename + '/', level ++);
    }
}
recursive_mapper(this);
            }");*/
        
        // reduce has to sum all the counters
        $reduce = new MongoCode("function(k, values) {
            var stat = {total: 0, empty: 0, code: 0, comment: 0};
            for ( var i = 0; i < values.length; i ++ ) {
                stat.total += values[i].total;
                stat.empty += values[i].empty;
                stat.comment += values[i].comment;
                stat.code += values[i].code;
            }
            return stat;
}");

        $counts = $db->command(array(
            'mapreduce' => 'files',
            'map' => $map,
            'reduce' => $reduce,
            // find only files and directories in the root
            'query' => array("path" => new MongoRegex("/^" . str_replace('/', '\/', $pathToFolder) . '/')),
            'out' => 'file_statistics'
                ));
         print_r($counts);
         /* $records = $db->selectCollection($counts['result'])->find();
          foreach ($records as $record){
          //print_r($record);
          echo "{$record['_id']} is in {$record['value']}.<br />";
          }*/
        
        // get the output
        $result = EDMSQuery::instance('aggregated_numbers')->findOne(array('path' => $pathToFolder, 'filename' => '.'));
        return $result;
    }

}

?>
