<h2>
    You don't have access to this page
</h2>
<div>
    You see this page probably because you are accessing <?php Yii::app()->name ?> 
    from an <b>IP address</b> which is not on white list. <br />You can fix this by deleting
    the database file <i>/protected/data/loca.db</i>.
</div>
<?php
$pass = CoraDatabase::getConfigParam('access_password', '');
if (strlen($pass)){
    ?>
<div style="margin-top: 15px;">
    However, you can also try to access this page using a <?php echo CHtml::link('login password', Yii::app()->createUrl('/site/login'));?>.
</div>
<?php
}
?>
