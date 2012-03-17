<?php

class SetupController extends Controller {

    /**
     * Recreate database and set basic settings for the system 
     */
    public function actionStart() {

        $res = Yii::app()->db->createCommand("SELECT name FROM sqlite_master WHERE type='table' AND name='file'")->queryScalar();
        if (!$res) {
            CoraDatabase::createDatabase();
        }
        $this->filterAccess(null);
        
        $project = Project::model()->find();
        if (isset($_POST['access_filter'])) {
            // TODO: should validate if it is IP address
            CoraDatabase::setConfigParam('access_filter', $_POST['access_filter']);
            CoraDatabase::setConfigParam('access_password', $_POST['access_password']);


            if (!is_object($project)) {
                Shared::debug("no project");
                $this->redirect(array('/project/create'));
            } else {
                $this->redirect(array('/site/index'));
            }
        }
        if (!is_object($project)) {
            $this->render('start');
        } else {
            $this->render('access');
        }
    }

}

?>
