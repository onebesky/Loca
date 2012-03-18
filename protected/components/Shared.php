<?php

class Shared {
    
    // file handler for debug file
    static $fh;
    
    /**
     * Set the flash message to show debug output on the next page
     * Widged ZienDebug is called for displaying the message.
     * @param <type> $object
     * @param <type> $message 
     */
    public static function debug($object, $message = '') {
        if (!YII_DEBUG) return true;
        $trace = debug_backtrace();
        $file = $trace[0]['file'];
        $line = $trace[0]['line'];
        $pos = strpos($file, 'protected');
        $file = substr($file, $pos);
        //$file = str_replace('C:\\wamp\\www\\zien\\protected\\', '', $file);
        $output = "";
        $output .= $file . " (" . $line . "): ";
        //$output .= print_r($trace, 1);
        if (strlen($message) > 0)
            $output .= " - $message - ";
        if (is_array($object)) {
            $output .= "<pre>" . print_r($object, 1) . "</pre>";
        }else if (is_object($object)){
            if (isset($object->attributes)){
                $output .= "<pre>" . print_r($object->attributes, 1) . "</pre>";
            }else{
                $output .= "<pre>" . print_r($object, 1) . "</pre>";
            }
        } else {
            $output .= $object;
        }
        if (!Yii::app()->params['cron']) {
            if (Yii::app()->user->hasFlash('debug')) {
                Yii::app()->user->setFlash('debug', Yii::app()->user->getFlash('debug') . "<br />" . $output);
            } else {
                Yii::app()->user->setFlash('debug', $output);
            }
        }

        // write it to the file
        $path = YiiBase::getPathOfAlias('application.runtime');
        $debugFile = $path . "/debug.txt";
        if (!self::$fh){
            self::$fh = fopen($debugFile, 'a');
        }
        
        if (self::$fh) {
            $output = self::toDatabase(time()) . ": " . $output . "\r\n";
            fwrite(self::$fh, $output);
            //fclose($fh);
        }
    }
    
    /**
     * Produces a little bit better passwords you can remember
     * The lenght is 8 characters, contains two digits and one capital letter
     * @return <type>
     */
    static function generateString($length = 6) {
        $numlen = 1;
        $output = '';
        $vowels = 'aeioe';
        $consonants = 'bcdfghklmnpqrstvwxzy';
        srand((double) microtime() * 1000000);

        for ($i = 0; $i < $length / 2; $i++) {
            $output .= $vowels[rand() % strlen($vowels)];
            $output .= $consonants[rand() % strlen($consonants)];
        }

        // put there one capital letter
        $rand = rand() % strlen($length - 1);
        $output[$rand] = strtoupper($output[$rand]);

        $pos = rand(2, $length - 2 - $numlen);
        return substr($output, 0, $pos) . rand(10, 99) . substr($output, $pos);
    }
    
    /**
     * Convert the time format to the database format
     */
    public static function toDatabase($date) {
        $time = self::toTimestamp($date);
        $formatted = date('Y-m-d H:i:s', $time);
        return $formatted;
    }
    
    /**
     * Converts everything into the timestamp
     * @param <mixed> $date
     * @return <timestamp>
     */
    public static function toTimestamp($date) {
        if (is_int($date)) {
            return $date;
        }
        if (($time = strtotime($date)) !== false) {
            return $time;
        }

        return false;
    }
}
?>
