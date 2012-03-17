<?php

/**
 * EDMSDataProvider.php
 *
 * Create a DataProvider from a MongoCursor
 * @link http://www.php.net/manual/en/class.mongocursor.php
 *
 * Support for sort and pagination.
 *
 * Usage:
  $cursor = $collection->find(); //MongoCursor

  $dataProvider = new EDMSDataProvider($cursor,
  			array(
					 'sort'=>array('create_time'=>-1,  //desc
					 'pagination'=>array(
					      'pageSize'=>20,
					    ),
					 ));

 * If the param $objectClassName is set, an array of the specified class instances will be fetched.
 * Set $objectClassName = 'stdClass' to return PHP standard objects
 *
 * You can use a model from yiimongodbsuite or a CFormModel with public properties as attributes.
 *
 *
 * PHP version 5.2+
 *
 * @author Joe Blocher <yii@myticket.at>
 * @copyright 2011 myticket it-solutions gmbh
 * @license New BSD License
 * @category Database
 * @package directmongosuite
 * @version 0.1.2
 * @since 0.1
 */
class EDMSDataProvider extends CDataProvider
{
	private $_cursor;
	private $_sort;

	private $_objectClassName;

	/**
	 * Assign the cursor and config
	 *
	 * @param MongoCursor $cursor
	 * @param array $config
	 */
	public function __construct($cursor, $config = array(), $objectClassName = null)
	{
		$this->_cursor = $cursor;
		$this->_objectClassName = $objectClassName;

		foreach($config as $key => $value)
			$this->$key = $value;
	}

	/**
	 * Fetches the data from the collection
	 * Uses the assigned cursor
	 *
	 * @return array list of data items
	 */
	protected function fetchData()
	{
		$data = array();

		if (empty($this->_cursor))
			return $data;

		if(($sort=$this->getSort())!== false && is_array($sort) && !empty($sort))
		    $this->_cursor->sort($sort);

		if (($pagination = $this->getPagination()) !== false)
		{
			$pagination->setItemCount($this->getTotalItemCount());

			$limit = $pagination->pageSize;
			$skip = $pagination->currentPage * $limit;
			$this->_cursor->limit($limit)->skip($skip);
		}


		$modelProperties = null;

		//get the array of public, non static propertyNames of the _objectClassName instance
		if (isset($this->_objectClassName) && $this->_objectClassName != 'stdClass')
		{
			$modelInstance = new $this->_objectClassName;
			if (!$modelInstance instanceof CModel) //CModel uses setAttributes in EDMSQuery::arrayToModel
			{
				$reflectionClass=new ReflectionClass($modelInstance);
				foreach($reflectionClass->getProperties() as $property)
				{
					if(	$property->isPublic() &&
						!$property->isStatic())
						$modelProperties[] = $property->getName();
				}
			}
		}


		// fetch data
		foreach ($this->_cursor as $id => $value)
		{
		   if (isset($this->_objectClassName)) //convert to a object
		   {
		   		if ($this->_objectClassName == 'stdClass')
		   		{
		   			$data[] = (object)$value; //add as stdClass
		   		}
		   	    else
			   	{
		   	    	$data[] = EDMSQuery::arrayToModel($value,$this->_objectClassName,$modelProperties);
			   	}
		   }
		   else
			  $data[] = $value;
		}

		return $data;
	}

	/**
	 * Fetches the data item keys from the collection.
	 *
	 * @return array list of data item keys.
	 */
	protected function fetchKeys()
	{
		$keys = array();
		foreach($this->getData() as $i => $data)
			$keys[$i] = $data['_id'];
		return $keys;
	}

	/**
	 * Calculates the total number of data items.
	 *
	 * @return integer the total number of data items.
	 */
	protected function calculateTotalItemCount()
	{
		return $this->_cursor->count();
	}


	/**
	 * Sets the sorting for this data provider.
	 * @param mixed $value the sorting to be used by this data provider. This could be a {@link CSort} object
	 * or an array used to configure the sorting object. If this is false, it means the sorting should be disabled.
	 */
	public function setSort($value)
	{
		if(is_array($value))
		   $this->_sort=$value;
	}

	/**
	 * Returns the sort object.
	 * @return CSort the sorting object. If this is false, it means the sorting is disabled.
	 */
	public function getSort()
	{
		return $this->_sort;
	}
}