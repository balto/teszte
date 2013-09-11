<?php
class TicketManager extends BaseModelManager
{
    private static $instance = null;

    private function __construct() {

    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new TicketManager();
        }
        return self::$instance;
    }
    
    /**
     * Torzsadatok / Jegy tipusok - jegy tipusok lista
     * 
     * @param unknown_type $extra_params
     * @param unknown_type $isCombo
     */
    public function getTicketTypes($extra_params = array(), $isCombo = false)
    {//,"#",ttct.is_main,"#",ttct.is_free
    	$selectArray = array(
    		'tt.id',
    		'tt.moment_count',
    		'tt.is_daily',
    		'tt.valid_days',
    		'tt.default_price',
    		'COALESCE(GROUP_CONCAT(CONCAT(dt.name," ",ct.name,";",CAST(ttct.is_main AS CHAR),";",CAST(ttct.is_free AS CHAR)) SEPARATOR ", "), "...nincs kampány típushoz rendelve") as joined_campaign_types',
    	);
    	
    	if ($isCombo) {
    		$selectArray = array(	
    			'dt.name' => 'dance_type_name',
    			'ct.name',
    		);
    		
    		$select = $this->getSelectColumnsForCombo($selectArray, "ct.id");
    	}
    	else{
    		$select = $this->getSelectColumnsForGrid($selectArray);
    	}
    	
        $query_params = array(
            array('select', $select),
            array('from', TicketType::model()->tableName().' tt'),
            array('join', array(TicketTypeCampaignType::model()->tableName().' ttct', 'ttct.ticket_type_id = tt.id')),
            array('join', array(CampaignType::model()->tableName() . ' ct', 'ttct.campaign_type_id = ct.id')),
			array('join', array(DanceType::model()->tableName() . ' dt', 'ct.dance_type_id = dt.id')),
			array('group', 'tt.id'),
        );

        $query_params = array_merge($query_params, $extra_params);
		
		$results = DBManager::getInstance()->query($query_params);

		foreach($results['data'] AS &$result){
			$jct_key = 'joined_campaign_types';
			
			$joined_types = array();
			$permissioned_types = array();
			$temp_jcts = explode(',', $result[$jct_key]);
			
			foreach($temp_jcts AS $tct){
				$tct_temp = explode(';', $tct);
				$ct_name = $tct_temp[0];
				$ct_is_main = $tct_temp[1];
				$ct_is_free = $tct_temp[2];
				
				if($ct_is_main){
					$joined_types[] = $ct_name;
				}
				else{
					$container = $ct_name;
					if($ct_is_free) $container.=' ingyenes';
					$permissioned_types[] = $container;
				}
			}
			
			$result[$jct_key] = implode(', ', $joined_types);
			$result['permissioned_campaign_types'] = implode(', ', $permissioned_types);
			
		}

        return $results;
    }
	
	/**
	 * Torzsadatok / Jegy tipusok - jegy tipus szerkesztese ablakban levo kotelezo kampany tipus gridnek a listaja
	 * 
	 * @param unknown_type $ticketTypeId
	 * @param unknown_type $extra_params
	 */
    public function getJoinCampaignTypes($ticketTypeId = 0, $isMain = 0, $extra_params = array())
    {
    	$query_params = array(
    			//array('select', 'ct.id, ct.name, dt.name AS dance_type_name, moment_count, required_moment_count, ttct.is_main, ttct.is_free'),
    			array('select', 'ct.id, ct.name, dt.name AS dance_type_name, ttct.is_main, ttct.is_free'),
    			array('from', TicketTypeCampaignType::model()->tableName().' ttct'),
    			array('join', array(CampaignType::model()->tableName().' ct', 'ttct.campaign_type_id = ct.id')),
    			array('join', array('dance_type dt', 'ct.dance_type_id = dt.id')),
    			array('where', array('ttct.ticket_type_id=:ticket_type_id AND ttct.is_main=:is_main', array(':ticket_type_id' => $ticketTypeId, ':is_main' => $isMain))),
    			/* array('join', array('settlement n_s', 'c.notify_settlement_id = n_s.id')),
    			 array('join', array('member_status ms', 'ms.id = c.member_status_id')),*/
    	);
    
    	$query_params = array_merge($query_params, $extra_params);
    
    	return DBManager::getInstance()->query($query_params);
    }
	
	/**
     * Torzsadatok / jegy tipusok - jegy tipus mentese
     * 
     * @param unknown_type $model_name
     * @param unknown_type $params
     * @param unknown_type $form_name
     * @param unknown_type $JoinCampaignTypes
     * @return Ambigous <multitype:boolean string NULL , multitype:boolean string Ambigous <multitype:, multitype:multitype:string unknown  > >
     */
    public function saveTicketType($model_name, $params, $form_name, $JoinCampaignTypes = array(), $permissionedCampaignTypes = array()) {
    	$response = parent::save($model_name, $params, $form_name);
    	
    	if($response['success']){
    		
    		
    		//kötelező meglévő tanfolyamok
    		if(isset($params['id']) && !empty($params['id'])){
				TicketTypeCampaignType::model()->deleteAll("ticket_type_id =:ticket_type_id", array(':ticket_type_id' => $params['id']));
			}

			foreach ($JoinCampaignTypes AS $ctId){
				$ctr = new TicketTypeCampaignType();
				$ctr->ticket_type_id = $response['id'];
				$ctr->campaign_type_id = $ctId;
				$ctr->is_main = 1;
				$ctr->is_free = 0;
				$ctr->save();
			}
			
			foreach ($permissionedCampaignTypes AS $pctData){
				$tempData = explode(',', $pctData);
				$pctId = $tempData[0];
				
				if(is_bool($tempData[1])){
					$is_free = $tempData[1] ? 1 : 0 ;
				}
				else{
					if($tempData[1] == 'false'){
						$is_free = 0;
					}
					elseif ($tempData[1] == 'true'){
						$is_free = 1;
					}
					else{
						$is_free = $tempData[1];
					}
				}
				
				$ctr = new TicketTypeCampaignType();
				$ctr->ticket_type_id = $response['id'];
				$ctr->campaign_type_id = $pctId;
				$ctr->is_main = 0;
				$ctr->is_free = $is_free;
				$ctr->save();
			}
    	}
    	
    	return $response;
    }

    public function save($params) {
        if (isset($params['id']) && $params['id']) {
            $record = Ticket::model()->findByPk($params['id']);
        } else {
            $record = new Ticket();
        }

        if (isset($params['ticket_type_id']) && $params['ticket_type_id']) {
        	$ticket_type = TicketType::model()->findByPk($params['ticket_type_id']);
        	$params['moment_left'] = $ticket_type->moment_count;
        }
        
        $form = new TicketForm();

        $form->bindActiveRecord($record);
        $form->bind($params);

        if ($form->validate()) {
            if($form->save()){
                
            }
        }

        $errors = $form->getErrors();
        if (empty($errors)) {
            $response = array(
                        'success'=>true,
                        'message'=>'Az adatok sikeresen rögzítve.',
            			'id' => $form->id,
            );
        } else {
            $response = array(
                        'success'=>false,
                        'message'=>'Az adatok módosítása nem sikerült az alábbi hibák miatt:',
                        'errors'=>Arrays::array_flatten($errors),
            );
        }

        return $response;
    }
    
	public function delete($id) {
    	$errors = array();
    	
    	if(Ticket::model()->count('ticket_type_id=:ttid', array(':ttid' => $id))){
    		$errors[] = 'Addig nem törölhető amíg tartozik alá bérlet!';
    	}
    	
    	if (!empty($errors)) {
            return array(
                'success'=>true,
            	'error' => 1,
                'message'=>Yii::t('msg' ,'Bérlet típus törlése sikertelen!'),
                'errors'=>$errors
            );
        }
        
        $response_success_true = array(
        		'success'=>true,
        		'error' => 0,
        		'message'=>Yii::t('msg' ,'Bérlet típus sikeresen törölve.')
        );
        $response_success_false = array(
        		'success'=>false,
        		'error' => 1,
        		'message'=>Yii::t('msg' ,'Bérlet típus törlése sikertelen!'),
        		'errors'=>array()
        );
        
        $rows_deleted = TicketType::model()->deleteByPk($id);
        
        return $rows_deleted == 1 ? $response_success_true : $response_success_false;
    }
/*
    public function setStatus($client_id, $status_column){
        $id = StatusManager::getInstance()->getId('MemberStatus', $status_column);

        $client = Client::model()->findByPk($client_id);
        $client->member_status_id = $id;
       return $client->save();
    }

    public function generateIdentifier(){
        $id = new MemberIdentifier();
        $id->save();

        return $id->id;
    }

    public function setRightStatus($client_id, $status_column){
        $status = RightStatus::model()->find('`'.$status_column.'`=1');

        Right::model()->updateAll(array('status_id' => $status->id, 'active_to' => sfDate::getInstance()->formatDbDate()), 'member_id=:member_id AND del_reason_id IS NOT NULL AND active_to IS NOT NULL', array(':member_id' => $client_id));
    }

    
    public function setRightsLogout($client_id, $active_to){
        Yii::import('application.modules.right.components.RightManager');
        $rm = RightManager::getInstance();
        $last_day = sfDate::getInstance(date('Y').'-12-31')->formatDbDate();

        $rights = Right::model()->findAll('member_id=:client_id AND del_reason_id IS NULL', array(':client_id' => $client_id));
        $logout_status = $this->getRightLogoutStatus();
        $active_to = sfDate::getInstance($active_to)->formatDbDate();
        $del_status = StatusManager::getInstance()->getId('RightStatus','deleted');

        foreach($rights AS $right){
            $right->del_reason_id = $logout_status;
            $right->active_to = $active_to;
            $right->status_id = $del_status;

            if($right->save()){
                Allocation::model()->deleteAll('`right_id`=:right_id AND `from`>:active_to', array(':right_id' => $right->id, ':active_to' => $last_day));
                $rm->holidayDelete($client_id, 'client_id', $last_day);
            }
        }
    }

    private function getRightLogoutStatus(){
        return StatusManager::getInstance()->getId('RightBuyDelReason','logout');
    }*/
}

?>