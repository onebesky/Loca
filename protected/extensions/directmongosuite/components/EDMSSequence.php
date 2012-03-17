<?php

/**
 * EDMSSequence.php
 *
 * Autogenerate unique integer values
 * Simulate the sequence/autoincrement feature
 *
 * PHP version 5.2+
 *
 * @author Joe Blocher <yii@myticket.at>
 * @copyright 2011 myticket it-solutions gmbh
 * @license New BSD License
 * @category Database
 * @package directmongosuite
 * @version 0.2
 * @since 0.2
 */


class EDMSSequence
{
	/**
	 * Return the next value incremented by $incrementBy
	 * Returns $incrementBy if the sequence not exists
	 *
	 * Uses the configured EDMSConnection from config/main.php
	 * if $dbName/$connectionId = null
	 *
	 * @param string $sequenceName
	 * @param integer $incrementBy
	 * @param string $collectionName
	 * @param mixed $dbName
	 * @param mixed $connectionId
	 * @return integer
	 */
	public static function nextVal($sequenceName = 'default', $incrementBy = 1, $collectionName = 'edms_sequences', $dbName = null, $connectionId = null)
	{
		if (!is_int($incrementBy))
			throw new CException('incrementBy must be an integer value');

		$result = Yii::app()->edmsMongoDb($dbName,$connectionId)->command(
						      array(
								    'findAndModify' => $collectionName,
								    'query' => array('sequence' => $sequenceName),
								    'update' => array( '$inc' => array( 'value' => $incrementBy)),
								    'new' => true,  //if upsert return the value
								    'upsert' => true,
								    //'fields' => array(),
								));

		return isset($result['ok']) &&
			   isset($result['value']['value']) &&
			   $result['ok'] == 1 ? $result['value']['value'] : null;
	}

	/**
	 * Return the current, not incremented value
	 *
	 * @param string $sequenceName
	 * @param string $collectionName
	 * @param mixed $dbName
	 * @param mixed $connectionId
	 * @return integer or null if not exists
	 */
	public static function currentVal($sequenceName = 'default', $collectionName = 'edms_sequences', $dbName = null, $connectionId = null)
	{
		$query = new EDMSQuery($collectionName,$dbName,$connectionId);
		$cursor = $query->findOne(array('sequence' => $sequenceName),array('value'));

		return $cursor !== false && isset($cursor['value']) ? $cursor['value'] : null;
	}


	/**
	 * Set the current value
	 * The sequence will be generated if not exists
	 * Use with care because of the consistency of your db
	 *
	 * @param mixed $value
	 * @param string $sequenceName
	 * @param string $collectionName
	 * @param mixed $dbName
	 * @param mixed $connectionId
	 * @param array $options for the update method
	 * @return mixed boolean or array, depends on optionss['safe']
	 */
	public static function setVal($value,$sequenceName = 'default', $collectionName = 'edms_sequences', $dbName = null, $connectionId = null,$options=array())
	{
		if (!is_int($value))
			throw new CException('value must be an integer value');

	   $query = new EDMSQuery($collectionName,$dbName,$connectionId);
	   return $query->upsert(array('sequence' => $sequenceName),array('sequence' => $sequenceName,'value' => $value),$options);
	}


	/**
	 * Remove the sequence
	 *
	 * @param string $sequenceName
	 * @param string $collectionName
	 * @param mixed $dbName
	 * @param mixed $connectionId
	 * @param array $options for the remove method
	 * @return mixed boolean or array, depends on optionss['safe']
	 */
	public static function remove($sequenceName = 'default', $collectionName = 'edms_sequences', $dbName = null, $connectionId = null,$options=array())
	{
		$query = new EDMSQuery($collectionName,$dbName,$connectionId);
		return $query->remove(array('sequence' => $sequenceName),true,$options);
	}

	/**
	 * Create an index for the sequence
	 * Call this once if you have a lot of different sequences to handle
	 */
	public static function ensureIndex($collectionName = 'edms_sequences', $dbName = null, $connectionId = null)
	{
	   $query = new EDMSQuery($collectionName,$dbName,$connectionId);
	   $query->ensureIndexes('sequence');
	}

}