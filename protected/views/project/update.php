<div><div class="main-title" style="margin-left: 150px; display: inline-block;">Update <?php echo $model->project_name ?> </div>
    <?php
    echo CHtml::link('<div class="main-button"><div class="delete-button">Delete</div></div>', $this->createUrl('project/delete', array('id' => $model->project_id)), array('confirm' => 'Do you really want to delete this project?'));
    echo CHtml::link('<div class="main-button"><div class="up-button">Back to Projects</div></div>', Yii::app()->createUrl('/project/switchTo'));
    ?>
</div>
<?php

echo "<div class='clear'></div>";

?>
<?php
$this->renderPartial('_form', array('model' => $model));
?>
