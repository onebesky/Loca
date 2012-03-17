<?php

/**
 * EDMSLogAction.php
 *
 * Usage:
 * Add this action to a controller of your choice

public function actions()
{
	return array(
	    ...

		'showlog'=>array(
			'class'=>'EDMSLogAction',
		),
	);
}

 * Now you can view the logs by calling the url 'controllerId/showlog'
 *
 *
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

class EDMSLogAction extends CAction
{
	/**
	 * Run the action
	 */
	public function run()
	{
		//capture the widget output
		$output = $this->controller->widget('EDMSLogViewer',array(),true);
		//render the widget into the controllers layout
		$this->controller->renderText($output);
	}
}