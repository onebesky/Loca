<?php

/**
 * EDMSCache.php
 *
 * Integrates the extension mongodbcache
 * @link http://www.yiiframework.com/extension/mongodbcache/
 *
 * Usage: add the 'cache' component to config/main.php

  'components' => array(
   ....
     'cache' => array(
		 'class'=>'EDMSCache',
		 //set to false after first use of the cache to increase performance
		 'ensureIndex' => true,
		 //$connectionId = 'edms' //default - see EDMSConnection
		 //$dbName = 'cacheDB'
      ),
   ...

 * PHP version 5.2+
 *
 * @author Joe Blocher <yii@myticket.at>
 * @copyright 2011 myticket it-solutions gmbh
 * @license New BSD License
 * @category Database
 * @package directmongosuite
 * @version 0.1
 * @since 0.1
 */
class EDMSCache extends CCache
{
	/**
	 * @var string the component id of the connection component
	 * @see EDMSConnection
	 * default is 'edms' if set to null
	 */
	public $connectionId = null;

	/**
	 * @var string the database name of the connection
	 * if you want to override the one from the connection component
	 * default is the dbName from the connection component if set to null
	 * @see EDMSConnection.php
	 */
	public $dbName = null;

	/**
	 * @var string name of the collection to store cache content. Defaults to 'mongodb_cache'.
	 * The items are stored withe following structure:
	 * <pre>
	 *  key: string,
	 *  value: string
	 *  expire: integer
	 * </pre>
	 */
	public $collectionName = 'edms_cache';

	public $ensureIndex = true;  //set to false after first use of the cache

	private $_gcProbability=100;
	private $_gced=false;

	/**
	 * Initializes this application component.
	 * ensureIndex 'key' if $ensureIndex = true
	 * Set $ensureIndex to false after first use to increase performance
	 *
	 * This method is required by the {@link IApplicationComponent} interface.
	 * It ensures the existence of the cache DB table.
	 * It also removes expired data items from the cache.
	 */
	public function init()
	{
		parent::init();

		$collection = $this->getCollection();

		if ($this->ensureIndex)
			$collection->ensureIndex( array('key' => 1));  // create index on "key"
	}

	/**
	 * Returns current edmsMongoCollection object
	 *
	 * @return edmsMongoCollection
	 */
	public function getCollection()
	{
		return Yii::app()->edmsMongoCollection($this->collectionName,$this->dbName,$this->connectionId);
	}

	/**
	 * @return integer the probability (parts per million) that garbage collection (GC) should be performed
	 * when storing a piece of data in the cache. Defaults to 100, meaning 0.01% chance.
	 * @since 1.0.9
	 */
	public function getGCProbability()
	{
		return $this->_gcProbability;
	}

	/**
	 * @param integer $value the probability (parts per million) that garbage collection (GC) should be performed
	 * when storing a piece of data in the cache. Defaults to 100, meaning 0.01% chance.
	 * This number should be between 0 and 1000000. A value 0 meaning no GC will be performed at all.
	 * @since 1.0.9
	 */
	public function setGCProbability($value)
	{
		$value=(int)$value;
		if($value<0)
			$value=0;
		if($value>1000000)
			$value=1000000;
		$this->_gcProbability=$value;
	}

	/**
	 * Retrieves a value from cache with a specified key.
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key a unique key identifying the cached value
	 * @return string the value stored in cache, false if the value is not in the cache or expired.
	 */
	protected function getValue($key)
	{
		$time=time();
		$criteria = array(
	                  'key' => (string)$key,
	                  '$or' =>  array(
	                  	              array('expire' => 0),
	                                  array('expire' => array('$gt'=> $time)),
									  ),
	                );

		$cursor = $this->getCollection()->findOne($criteria);

		if (!empty($cursor))
			return $cursor['value'];

		return null;
	}

	/**
	 * Retrieves multiple values from cache with the specified keys.
	 * @param array $keys a list of keys identifying the cached values
	 * @return array a list of cached values indexed by the keys
	 */
	protected function getValues($keys)
	{
		if(empty($keys))
			return array();

		$time=time();
		$results=array();
		$criteria = array(
		              'key' => array('$in' => $keys),
					  '$or' =>  array(
					  	              array('expire' => 0),
							          array('expire' => array('$gt'=> $time)),
						              ),
		            );

		$cursor = $this->getCollection()->find($criteria);
		if (!empty($cursor) && $cursor->count())
		{
			foreach ($cursor as $id => $value)
				$results[$value['key']] =  $value['value'];

		}

		return $results;
	}

	/**
	 * Stores a value identified by a key in cache.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function setValue($key,$value,$expire)
	{
		return $this->addValue($key,$value,$expire);
	}

	/**
	 * Stores a value identified by a key into cache if the cache does not contain this key.
	 * This is the implementation of the method declared in the parent class.
	 *
	 * If the key exists the value will be updated, otherwise inserted
	 *
	 * @param string $key the key identifying the value to be cached
	 * @param string $value the value to be cached
	 * @param integer $expire the number of seconds in which the cached value will expire. 0 means never expire.
	 * @return boolean true if the value is successfully stored into cache, false otherwise
	 */
	protected function addValue($key,$value,$expire)
	{
		if(!$this->_gced && mt_rand(0,1000000)<$this->_gcProbability)
		{
			$this->gc();
			$this->_gced=true;
		}

		if($expire>0)
			$expire+=time();
		else
			$expire=0;

		$criteria = array('key' => (string)$key);

		$data = array(
	                  'key' => (string)$key,
	                  'value' => (string)$value,
	                  'expire' => (int)$expire,
	                );

		$options = array('upsert'=>true);

		try
		{
			$result =  $this->getCollection()->update($criteria,$data,$options);
			return $result;
		}
		catch(Exception $e)
		{
			return false;
		}
	}

	/**
	 * Deletes a value with the specified key from cache
	 * This is the implementation of the method declared in the parent class.
	 * @param string $key the key of the value to be deleted
	 * @return boolean if no error happens during deletion
	 */
	protected function deleteValue($key)
	{
		$criteria = array(
			'key' => (string)$key,
		);

		$this->getCollection()->remove($criteria);
		return true;
	}

	/**
	 * Removes the expired data values.
	 */
	protected function gc()
	{
		//delete expired entries
		$criteria = array(
			'expired' => array('$gt' => 0),
			'expired' => array('$lt' => time()),
		);

		$this->getCollection()->remove($criteria);
	}

	/**
	 * Deletes all values from cache.
	 * This is the implementation of the method declared in the parent class.
	 * @return boolean whether the flush operation was successful.
	 */
	protected function flushValues()
	{
		$this->getCollection()->remove();
		return true;
	}

}