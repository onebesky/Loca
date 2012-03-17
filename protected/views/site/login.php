<div class="form">
<?php
if ($error){
    ?>
    <div style="padding-left: 100px; color: red">Wrong password, try it again.</div>
    <?php
}
echo CHtml::beginForm();
?>
<div class="row">
    <?php
    echo CHtml::label('Password', 'password');
    echo CHtml::textField('password', '', array('class' => 'text-input'));
    ?>
</div>
<div class="buttons">
    <?php
    echo CHtml::submitButton('Login', array('class' => 'submit-button'));
    ?>
</div>
<?php
echo CHtml::endForm();
?>
</div>