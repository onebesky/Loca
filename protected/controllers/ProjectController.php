<?php

class ProjectController extends Controller {

    public function filters() {
        return array('access');
    }

    public function actionCreate() {
        $project = new Project;

        // set default values
        $path = YiiBase::getPathOfAlias('webroot');
        $project->path = substr($path, 0, strrpos($path, '/'));
        $project->filename_filter = '.svn, .git';

        // have to be replaced by supported file type selector
        $project->index_only_extensions = 'php, css, js';

        if (isset($_POST['Project'])) {
            $project->attributes = $_POST['Project'];
            if ($project->save()) {
                $project->createRootFile();
                Yii::app()->user->setFlash('project', 'The project was created.');
                $this->redirect(array('/project/switchTo/' . $project->project_id));
            }
        }
        $this->render('create', array('model' => $project));
    }

    public function actionUpdate($id) {

        $project = $this->loadModel($id);

        if (isset($_POST['Project'])) {
            $project->attributes = $_POST['Project'];
            if ($project->save()) {
                $this->redirect(array('/site/index'));
            } else {
                Shared::debug($project->getErrors());
            }
        }
        $this->render('update', array('model' => $project));
    }

    /**
     * Just testing screen for jqcharts 
     */
    public function actionChart() {
        $this->layout = 'empty';
        $this->render('chart');
    }

    /**
     * Set the active project and redirects back to main screen, so project dashboard can be displayed
     * @param type $id 
     */
    public function actionSwitchTo($id = null) {
        if ($id == null) {
            Yii::app()->session['activeProject'] = null;
        } else {
            $this->loadModel($id);
            Yii::app()->session['activeProject'] = $id;
        }
        $this->redirect(array('/site/index'));
    }

    /**
     * Delete project from database and redirect home
     * @param type $id project id
     */
    public function actionDelete($id) {
        $project = $this->loadModel($id);
        if (is_object($project)) {
            Yii::app()->db->createCommand("
    DELETE FROM file WHERE project_id = {$project->project_id}
    ")->execute();

            $project->delete();
            Yii::app()->session['activeProject'] = null;
            $this->redirect(array('/site/index'));
        }
    }

    /**
     * Returns the data model based on the primary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer the ID of the model to be loaded
     */
    public function loadModel($id) {
        $model = Project::model()->findByPk((int) $id);
        if ($model === null)
            throw new CHttpException(404, 'The requested project does not exist.');
        return $model;
    }

    /**
     * Return json decoded stats for the root file of the project 
     */
    public function actionGetInfo() {
        $project = Cora::getActiveProject();
        if (is_object($project)) {
            // TODO: redo it for Linux, stupid!
            $file = $project->getRootFile();
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
