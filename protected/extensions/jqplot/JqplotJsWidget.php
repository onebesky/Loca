<?php
Yii::import('application.extensions.jqplot.JqplotWidget');
class JqplotJsWidget extends JqplotWidget{
	/**
	 * @var string the name of the container element that contains the progress bar. Defaults to 'div'.
	 */
	public $tagName = 'div';

	/**
	 * Run this widget.
	 * This method will render nothing, just register the js classes
	 */
	public function run(){
		$id=$this->getId();
		$this->htmlOptions['id']=$id;        

	}

}