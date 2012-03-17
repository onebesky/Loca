<div class="form">
<?php
echo CHtml::beginForm();
?>
<div class="row">
    <?php
    echo CHtml::label('From wich computers are you going to access this application?', 'access_filter');
    echo CHtml::textField('access_filter', CoraDatabase::getConfigParam('access_filter'), array('class' => 'text-input'));
    echo "<div class='hint'>Leave blank for non-restricted access.</div>";
    ?>
</div>
<div class="row">
    <?php
    echo CHtml::label('Would you like to create a password instead?', 'access_password');
    echo CHtml::textField('access_password', CoraDatabase::getConfigParam('access_password'), array('class' => 'text-input'));
    echo "<div class='hint'>Leave blank for no password.</div>";
    ?>
</div>
<div class="buttons">
    <?php
    echo CHtml::submitButton('Save', array('class' => 'submit-button'));
    ?>
</div>
<?php
echo CHtml::endForm();
?>
</div>
