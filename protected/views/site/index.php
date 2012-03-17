<?php 
$this->pageTitle = Yii::app()->name; 
Yii::app()->clientScript->registerCoreScript('jquery');
?>

<?php
$project = Cora::getActiveProject();
if (is_object($project)){
    $this->renderPartial('/project/dashboard', array('model' => $project));
}else{
    // display list of projects
    $this->renderPartial('/project/index');
}
?>