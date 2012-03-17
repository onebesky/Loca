<?php

$this->widget('zii.widgets.grid.CGridView', array(
    'dataProvider' => $dataProvider,
    'enableSorting' => false,
    'columns' => array(
        'path',
        'filename',
        'last_modification',
        array(
            'name' => 'processed',
            'value' => '$data->processed ? "Yes" : "No"'
        ),
        'lines_code',
        'lines_empty',
        'lines_comment'
    ),
));
?>
