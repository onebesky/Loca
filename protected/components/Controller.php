<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var string the default layout for the controller view. Defaults to '//layouts/column1',
	 * meaning using a single column layout. See 'protected/views/layouts/column1.php'.
	 */
	public $layout='//layouts/column1';
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();
	/**
	 * @var array the breadcrumbs of the current page. The value of this property will
	 * be assigned to {@link CBreadcrumbs::links}. Please refer to {@link CBreadcrumbs::links}
	 * for more details on how to specify this property.
	 */
	public $breadcrumbs=array();
        
        /**
         * IP based or cookie based access filter
         * @param type $chain 
         */
        public function filterAccess($chain){
            Shared::debug($_SERVER['REMOTE_ADDR']);
            $filter = CoraDatabase::getConfigParam('access_filter', '');
            $pass = CoraDatabase::getConfigParam('access_password', '');
            
            if (strlen($pass)){
                Shared::debug("checking pass ..");
                $pass = md5($pass . $_SERVER['REMOTE_ADDR']);
                if (Yii::app()->session['login'] != $pass){
                    Shared::debug("wrong pass");
                    $this->redirect(array('site/login'));
                }
            }
            
            if (strlen($filter)){
                Shared::debug("checking filter ..");
                // get an array of allowed ips
                $filter = str_replace(" ", ",", $filter);
                $ip = explode(",", $filter);
                if (!in_array($_SERVER['REMOTE_ADDR'], $ip)){
                    Shared::debug("wrong ip");
                    $this->redirect(array('site/noAccess'));
                }
            }
            if ($chain != null) $chain->run();
        }
}