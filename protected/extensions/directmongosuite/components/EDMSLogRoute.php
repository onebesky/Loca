<?php
/**
 * EDMSLogRoute.php
 *
 * Integrated version of the extension mongodblogroute by aoyagikouhei
 * @link http://www.yiiframework.com/extension/mongodblogroute/
 *
 *
 *  Auther aoyagikouhei
 * 2011/07/09 ver 1.1
 * Add capped collection : Thank you joblo
 *
 * 2011/06/23 ver 1.0
 * First release
 *
 * Install
 * Extract the release file under protected/extensions
 *
 * Changed config by joblo:
 * In config/main.php:
			 'log'=>array(
			 'class'=>'CLogRouter',
			 'routes'=>array(
			 		array(
						 'class'=>'ext.directmongosuite.components.EDMSLogRoute',
						 'levels'=>'trace, info, error, warning',
						 //'categories' => 'system.*',
			 ),
      ),
     ),
 *
 * Options
 * connectionId     : default null - (means 'edms' from the EDMSBehavior)
 * dbName                  : database name                  : default test
 * collectionName          : collaction name                : default yiilog
 * message                 : message column name            : default message
 * level                   : level column name              : default level
 * category                : category column name           : default category
 * timestamp               : timestamp column name          : default timestamp
 * timestampType           : float or date                  : default float
 * collectionSize          : capped collection size         : default 10000
 * collectionMax           : capped collection max          : default 100
 * installCappedCollection : capped collection install flag : default false
 *
 * Example
 'log'=>array(
	 'class'=>'CLogRouter',
				 'routes'=>array(
							 array(
							 'class'=>'ext.EMongoDbLogRoute',
							 'levels'=>'trace, info, error, warning',
							 'categories' => 'system.*',
							 //'connectionId' => 'edms1',
							 'collectionName' => 'yiilog',
							 'message' => 'message',
							 'level' => 'level',
							 'category' => 'category',
							 'timestamp' => 'timestamp',
							 'timestampType' => 'float',
							 ,'collectionSize' => 10000
							 ,'collectionMax' => 100
							 ,'installCappedCollection' => true
							 ),
						),
				 ),
 *
 * Capped colection
 * 1. set installCappedCollection true in main.php.
 * 2. run application and loged
 * 3. remove installCappedCollection in main.php.
 */
class EDMSLogRoute extends CLogRoute
{
	/**
	 * @var string the component id of the connection component
	 * @see EDMSConnection
	 * default is 'edms' if set to null
	 */
	public $connectionId=null;

	/**
	 * @var string the database name of the connection
	 * if you want to override the one from the connection component
	 * default is the dbName from the connection component if set to null
	 * @see EDMSConnection.php
	 */
	public $dbName=null;

	/**
	 * @var string Collection name
	 */
	public $collectionName='edms_log';

	/**
	 * @var string message column name
	 */
	public $message='message';

	/**
	 * @var string level column name
	 */
	public $level='level';

	/**
	 * @var string category column name
	 */
	public $category='category';

	/**
	 * @var string timestamp column name
	 */
	public $timestamp='timestamp';

	/**
	 * @var string timestamp type name float or date
	 */
	public $timestampType='float';

	/**
	 * @var integer capped collection size
	 */
	public $collectionSize=100000;

	/**
	 * @var integer capped collection max
	 */
	public $collectionMax=10000;

	/**
	 * @var boolean capped collection install flag
	 */
	public $installCappedCollection = false;

	/**
	 /**
	 * @var edmsMongo mongo Db collection
	 */
	private $_collection;


	/**
	 * EMongoDbLogRoute::getCollection()
	 *
	 * @return
	 */
	public function getCollection()
	{
		if ($this->installCappedCollection)
			return Yii::app()->edmsMongoDb($this->dbName,$this->connectionId)->createCollection
				                                                (
																  $this->collectionName,
																  true,
																  $this->collectionSize,
																  $this->collectionMax
																);
		else
		    return Yii::app()->edmsMongoCollection($this->collectionName,$this->dbName,$this->connectionId);
	}

	/**
	 * Initializes the route.
	 * This method is invoked after the route is created by the route manager.
	 */
	public function init()
	{
		parent::init();
		$this->_collection = $this->getCollection();
	}

	/**
	 * Saves log messages into mongodb.
	 * @param array list of log messages
	 */
	protected function processLogs($logs)
	{
		foreach($logs as $log) {
			$this->_collection->insert(array(
			  $this->message => $log[0]
			  ,$this->level => $log[1]
			  ,$this->category => $log[2]
			  ,$this->timestamp =>
			    'date' === $this->timestampType ? new MongoDate(round($log[3])) : $log[3]
			));
		}
	}

}
