<?php

class SiteController extends Controller {

    /**
     * Declares class-based actions.
     */
    /*public function actions() {
        return array(
            // captcha action renders the CAPTCHA image displayed on the contact page
            'captcha' => array(
                'class' => 'CCaptchaAction',
                'backColor' => 0xFFFFFF,
            ),
            // page action renders "static" pages stored under 'protected/views/site/pages'
            // They can be accessed via: index.php?r=site/page&view=FileName
            'page' => array(
                'class' => 'CViewAction',
            ),
        );
    }*/

        public function filters() {
            return array('access- noAccess login index');
        }
        
    
    /**
     * This is the default 'index' action that is invoked
     * when an action is not explicitly requested by users.
     */
    public function actionIndex() {
        // renders the view file 'protected/views/site/index.php'
        // using the default layout 'protected/views/layouts/main.php'
        
        $res = Yii::app()->db->createCommand("SELECT name FROM sqlite_master WHERE type='table' AND name='file'")->queryScalar();
        if (!$res) {
            $this->redirect(array('/setup/start'));
        }else{
            $this->filterAccess(null);
        }

        $this->render('index');
    }

    /**
     * This is the action to handle external exceptions.
     */
    public function actionError() {
        if ($error = Yii::app()->errorHandler->error) {
            if (Yii::app()->request->isAjaxRequest)
                echo $error['message'];
            else
                $this->render('error', $error);
        }
    }

    /**
     * Displays the contact page
     */
    /*public function actionContact() {
        $model = new ContactForm;
        if (isset($_POST['ContactForm'])) {
            $model->attributes = $_POST['ContactForm'];
            if ($model->validate()) {
                $headers = "From: {$model->email}\r\nReply-To: {$model->email}";
                mail(Yii::app()->params['adminEmail'], $model->subject, $model->body, $headers);
                Yii::app()->user->setFlash('contact', 'Thank you for contacting us. We will respond to you as soon as possible.');
                $this->refresh();
            }
        }
        $this->render('contact', array('model' => $model));
    }*/

    public function actionNoAccess() {
        $this->render('access-denied');
    }
    
    /**
     * Displays the login page. Not the best approach for user login, but
     * let's keep it simple
     */
    public function actionLogin() {
        $error = false;
        if (isset($_POST['password'])){
            $pass = md5($_POST['password'] . $_SERVER['REMOTE_ADDR']);
            $system = md5(CoraDatabase::getConfigParam('access_password', '') . $_SERVER['REMOTE_ADDR']);
            if ($_POST['password'] == CoraDatabase::getConfigParam('access_password', '')){
                Yii::app()->session['login'] = md5($_POST['password'] . $_SERVER['REMOTE_ADDR']);
                $this->redirect(array('/site/index'));
            }else{
                $error = true;
            }
        }
        
        // display the login form
        $this->render('login', array('error' => $error));
    }

    /*public function actionTests1() {
        $mongoDb = Yii::app()->edmsMongoDB();

        $mongoDb->createCollection('test');
        
        $connection = new Mongo();
        $db = $connection->cora;
        $collection = $db->members;

        $collection->insert(array('name' => 'Jan', 'group' => '1'));
        
        $result = EDMSQuery::instance('members')->findArray();
        var_dump($result);
   

        $this->render('tests1');
    }*/
    
    /*public function actionListFiles(){
        $dataProvider = EDMSQuery::instance('files')->getModelDataProvider('File');
        $this->render('list-files',array('dataProvider'=>$dataProvider));
    }*/
    
    /**
     * Logs out the current user and redirect to homepage.
     */
    public function actionLogout() {
        Yii::app()->user->logout();
        $this->redirect(Yii::app()->homeUrl);
    }

}