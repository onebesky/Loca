<?php

class TestController extends Controller {
    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex() {
        // renders the view file 'protected/views/site/index.php'
        // using the default layout 'protected/views/layouts/main.php'
        $this->render('index');
    }
    
        public function actionCreateData(){
        $connection = new Mongo();
        $db = $connection->cora;
        
        $members = $db->members;
        $groups = $db->groups;
        
        $numbers = $db->numbers;
        
        // for faster searching
        $groups->ensureIndex('group_id');
        
        // create 20 groups
        for ($i = 0; $i < 20; $i++){
            $groups->insert(array('name' => 'group ' . $i + 1, 'group_id' => $i + 1));
        }

        for ($i = 0; $i < 100; $i++){
            $groupId = rand(1,20);
            $members->insert(array('name' => Shared::generateString(), 'group_id' => $groupId));
        }
        
        for ($i = 0; $i < 1000; $i ++){
            $number = rand(0,99);
            $numbers->insert(array('number' => $number, 'name' => Shared::generateString()));
        }
        
        Yii::app()->user->setFlash('members','The test data was created.');
        $this->redirect('/');
    }
    
    public function actionDeleteData(){
        $connection = new Mongo();
        $db = $connection->cora;
        
        // delete all
        $db->members->remove();
        Yii::app()->user->setFlash('members','The test data was deleted!');
        $this->redirect('/site/index');
    }
    
    public function actionDetectComments(){
        $test = array('/* First comment', 
 'first comment—line two*/',
 'line of regular code',
'/* Second comment **/',
'another line',
'// single comment');
        $commentOpen;
        foreach ($test as $line){
            $line = trim($line);
            if (preg_match('/^\/\//', $line)){
                echo "single comment<br />";
            }
            else if (preg_match('/^#/', $line)){
                echo "single comment<br />";
            }
            else if (preg_match('/^\/\*.*\*\//', $line)){
                echo "full tag<br />";
            }
            else if (preg_match('/^\/\*/', $line)){
                echo "open tag<br />";
            }
            else if (preg_match('/\*\//', $line)){
                echo "close tag<br />";
            }else{
                echo "code<br />";
            }
        }
    }
    
    public function actionNumFunctions(){
        $data = '
// this is some test referring this function
/*and this is comment for the function*/
function doSomething(){

}

    function somethingElse() // comment after the function
    {
    
    }
/** This function is useless */    
public function another1(){

}
';
        $lines = explode("\n", $data);
        $functions = 0;
        $openComment = false;
        
        $file = new File;
        
        foreach ($lines as $line) {
            $line = trim($line); 
             
            if ($openComment && !preg_match('/\*\//', $line)) {
                // open and no closing tag
                $file->lines_comment++;
            } else if (strlen($line) == 0) {
                // nothing
                $file->lines_empty++;
            } else if (preg_match('/^\/\//', $line)) {
                // double slash
                $file->lines_comment++;
            } else if (preg_match('/^#/', $line)) {
                // another single line comment
                $file->lines_comment++;
            } else if (preg_match('/^\/\*.*\*\//', $line)) {
                // multiline on single line
                $file->lines_comment++;
            } else if (preg_match('/^\/\*/', $line)) {
                // opening multiline
                $file->lines_comment++;
                $openComment = true;
            } else if (preg_match('/\*\//', $line)) {
                // closing multiline
                $file->lines_comment++;
                $openComment = false;
            } else {
                // everything else ... suppose regular code
                $file->lines_code++;
                if (strstr($line, 'function')){
                    $functions ++;
                }
            }
        }
        echo "Found $functions functions";
        
    }
}
?>
