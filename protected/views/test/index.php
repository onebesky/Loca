<?php 
$this->pageTitle = Yii::app()->name; 
Yii::app()->clientScript->registerCoreScript('jquery');
?>

<h1>Welcome to <i><?php echo CHtml::encode(Yii::app()->name); ?></i></h1>

<p>You can count your lines of code now!</p>
<ul>
    <li><?php echo CHtml::link('create data', Yii::app()->createUrl('site/createData')); ?></li>
    <li><?php echo CHtml::link('delete data', Yii::app()->createUrl('site/deleteData')); ?></li>
    <li><?php echo CHtml::link('list data', Yii::app()->createUrl('site/listData')); ?></li>
</ul>

<ul>
    <li><?php echo CHtml::link('Create Project', Yii::app()->createUrl('project/create')); ?></li>
</ul>
Database: <br />

<?php
$connection = new Mongo();
$db = $connection->cora;

$members = $db->members;
$groups = $db->groups;

echo "There are <b>" . $members->count() . "</b> members in the database and " . $groups->count() . " groups.<br />";
print_r ($members->find()->limit(1));
?>


Get the number of random records using map reduce

<?php
$numbers = $db->numbers;
$map = new MongoCode("function() {emit(this.number, this.name);}");
$reduce = new MongoCode("function(k, values) {
 return values.length;
}");

$counts = $db->command(array(
    'mapreduce' => 'numbers',
    'map' => $map,
    'reduce' => $reduce,
    'out' => 'aggregated_numbers'
    ));
/*print_r($counts);
$records = $db->selectCollection($counts['result'])->find();
foreach ($records as $record){
    //print_r($record);
    echo "{$record['_id']} is in {$record['value']}.<br />";
}*/

echo "<p>we have " . $db->aggregated_numbers->count() . " numbers</p>";
/*
$fav = $db->aggregated_numbers;
$res = $fav->find(array('limit' => 10));
foreach ($res as $rec){
    print_r($rec);
}*/

$result = EDMSQuery::instance('aggregated_numbers')->findArray(array(),array('_id', 'value'),array('value' => -1), 10);
foreach ($result as $record){
    echo "{$record['_id']} is in {$record['value']} categories.<br />";
}
?>