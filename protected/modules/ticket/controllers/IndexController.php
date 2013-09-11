<?php

class IndexController extends Controller
{
	public function actionIndex()
	{
		$this->render('index');
	}
	
	public function actionGetTicketTypes()
	{
		$isCombo = $this->getParameter('isCombo', false, false);
		
		$extra_params = array();
		$this->addPagerParams($extra_params);
		$this->addOrderParams($extra_params);
		$this->addFilterParams($extra_params);
	
		$response = TicketManager::getInstance()->getTicketTypes($extra_params, $isCombo);
	
		$this->renderText(json_encode($response));
	}

	// Uncomment the following methods and override them if needed
	/*
	public function filters()
	{
		// return the filter configuration for this controller, e.g.:
		return array(
			'inlineFilterName',
			array(
				'class'=>'path.to.FilterClass',
				'propertyName'=>'propertyValue',
			),
		);
	}

	public function actions()
	{
		// return external action classes, e.g.:
		return array(
			'action1'=>'path.to.ActionClass',
			'action2'=>array(
				'class'=>'path.to.AnotherActionClass',
				'propertyName'=>'propertyValue',
			),
		);
	}
	*/
}