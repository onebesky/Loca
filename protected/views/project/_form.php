<div class="form wide">
<?php
echo CHtml::beginForm();
?>
    <?php echo CHtml::errorSummary($model); ?>
<div class="row">
    <?php
    echo CHtml::activeLabel($model, 'path');
    echo CHtml::activeTextField($model, 'path', array('class' => 'text-input'));
    echo "<div class='hint'>Absolute path on a filesystem to your project files.</div>";
    ?>
</div>
<div class="row">
    <?php
    echo CHtml::activeLabel($model, 'project_name');
    echo CHtml::activeTextField($model, 'project_name', array('class' => 'text-input'));
    echo "<div class='hint'>How the project is going to be displayed in " . Yii::app()->name . ".</div>";
    ?>
</div>
<div class="row">
    <?php
    echo CHtml::activeLabel($model, 'filename_filter');
    echo CHtml::activeTextField($model, 'filename_filter', array('class' => 'text-input'));
    echo "<div class='hint'>Ignore all the files with this name. For example, you can filter working files of versioning system. Use comma or space to define more types.</div>";
    ?>
</div>
<div class="row">
    <?php
    echo CHtml::activeLabel($model, 'index_only_extensions');
    echo CHtml::activeTextField($model, 'index_only_extensions', array('class' => 'text-input'));
    echo "<div class='hint'>Index only files with these extensions. Use comma or space to define more types.</div>";
    ?>
</div>
<div class="buttons">
    <?php
    echo CHtml::submitButton($model->isNewRecord ? 'Create' : 'Update', array('class' => 'submit-button'));
    ?>
</div>
<?php
echo CHtml::endForm();
?>
</div>