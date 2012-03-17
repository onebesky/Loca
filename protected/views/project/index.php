This is an index screen ... should have some welcome stuff and usage

<?php
// display list of projects
$projects = Project::model()->findAll();
?>
<h2>Select a project:</h2>
<?php
echo "<div>";
foreach ($projects as $project) {
    echo "<div style='margin-left: 15px;'>";
    echo CHtml::link($project->project_name, Yii::app()->createUrl('/project/switchTo/' . $project->project_id), array('class' => 'project-link'));
    echo "<span style='color:#939188; font-size: 20px;'> (" . $project->path . ")</span>";
    echo "</div>";
}
echo "</div>";
?>
<a href="<?php echo Yii::app()->createUrl('project/create'); ?>" class="main-button big-button" style="margin-top: 20px; margin-left: 20px; vertical-align: top;">
    <div id="create-button">
        Create Project
    </div>
</a>
