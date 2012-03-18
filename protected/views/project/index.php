<div class="intro">
    <h3>About Loca</h3>
    <p>
        Loca is simple to use lines of code analyzer. The tool is easy to install and
        gives you basic information about your code, such as number of lines of code,
        number of functions, number of lines of comments and other useful information.
    </p>
</div>

<div class="intro">
    <h3>Features</h3>
    <p>
    <ul>
        <li>
            Comparing projects between each other
        </li>
        <li>
            Comparing size of code you wrote
        </li>
        <li>
            Filter third party modules and extensions
        </li>
        <li>
            Browse to sub-directories
        </li>
    </ul>
</p>
</div>
<div class="clear"></div>
<div style="margin-left: 18px; margin-top: 12px;">
    <?php
// display list of projects
    $projects = Project::model()->findAll();
    ?>
    <h3 style="margin-left: 14px;">Projects</h3>
    <?php
    echo "<div>";
    foreach ($projects as $project) {
        echo "<div style='margin-left: 15px;'>";
        echo CHtml::link($project->project_name, Yii::app()->createUrl('/project/switchTo/' . $project->project_id), array('class' => 'project-link'));
        echo "<span style='color:#6d6d6d; font-size: 16px;'> (" . $project->path . ")</span>";
        echo "</div>";
    }
    echo "</div>";
    ?>
    <a href="<?php echo Yii::app()->createUrl('project/create'); ?>" class="main-button big-button" style="margin-top: 20px; margin-left: 20px; vertical-align: top;">
        <div id="create-button">
            Create Project
        </div>
    </a>
</div>