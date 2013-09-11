<?php
/**
 * Controller is the customized base controller class.
 * All controller classes for this application should extend from this base class.
 */
class Controller extends CController
{
	/**
	 * @var array context menu items. This property will be assigned to {@link CMenu::items}.
	 */
	public $menu=array();
	
	public $import = array();

	/**
	* Initializes the controller.
	* This method is called by the application before the controller starts to execute.
	* You may override this method to perform the needed initialization for the controller.
	* @since 1.0.1
	*/
	public function init()
	{
	    // A Site module-on kívül minden modulhoz autentikált user férhet csak hozzá
        $is_module_secure = false;
	    if ($this->module) {
	        $is_module_secure = $this->module->secure;
	    }
	    if ($is_module_secure && !Yii::app()->user->isAuthenticated()) {
            header('HTTP/1.0 401 Unauthorized');

            // betesszük a válaszba a status_code-ot is, mert a fájl feltöltésnél az ext nem teszi bele az általa készített fake XMLHttpRequest objektumba
            $this->layout = false;
            $response = json_encode(array("redirect"=>"", "status"=>401));
            $this->renderText($response);
            Yii::app()->end();
	    }

	    // Menü kiválasztása esetén a session-be bekerül az utolsó url
	    $param_name = Yii::app()->params['last_visited_url_param_name'];
	    if ($this->hasParameter($param_name) ) {
	        Yii::app()->user->setAttribute($param_name, $this->getParameter($param_name));

	        $jx_param_name = Yii::app()->params['last_visited_url_jx_params_name'];
	        if ($this->hasParameter($jx_param_name)) {
	            Yii::app()->user->setAttribute($jx_param_name, $this->getParameter($jx_param_name));
	        }
	    }

	    Yii::app()->params['system_base_url'] = Yii::app()->params['no_script_name'] ? '/': $_SERVER['SCRIPT_NAME'];
	    
	    if(is_array($this->import)){
	    	foreach ($this->import as $path) {
	    		Yii::import($path);
	    	}
	    }
	}

    /**
     * Ellenőrzi egy request paraméter meglétét, ill. alapértelmezett értékkel tér vissza ha kötelező és nem jött ilyen paraméter
     *
     *
     * @param string $name A paraméter neve
     * @param $default_value Az alapértelmezett érték
     * @param $mandatory Közetelező-e a paraméter
     * @return value
     */
    protected function getParameter($name, $default_value = null, $mandatory = true, $post_method = true)
    {
        // az action sfWebRequest objektuma
        $request = Yii::app()->request;
        $method = $post_method ? 'getPost' : 'getQuery';

        // ha nem szükséges, a normál módszerrel visszaadjuk a paraméter értékét, vagy ha nincs ilyen paraméter, akkor a default értéket
        if (!$mandatory) return $request->$method($name, $default_value);

        // ha szükséges, akkor ellenőrizzük van-e ilyen paraméter, és van-e értéket
        $param = $request->$method($name, null);
        if (!isset($param)) {
            throw new Exception(get_class($this).': missing parameter '. $name .' from request');
        }
        return $param;
    }

    protected function hasParameter($name, $post_method = true) {
        $params = $post_method ? $_POST : $_GET;

        return isset($params[$name]);
    }

    public function getPagerParameters() {
        $pager = array();
        if ($this->hasParameter('limit')) $pager['limit'] = $this->getParameter('limit');
        if ($this->hasParameter('start')) $pager['offset'] = $this->getParameter('start');

        return $pager;
    }

    public function getOrderParameters($post = true) {
        $sorter = array();
        if ($this->hasParameter('sort', $post)) {
            $params = json_decode($this->getParameter('sort', null, true, $post), true);
            foreach($params as $param) {
                $sorter[] = $param['property']. ' '. $param['direction'];
            }
        }

        return $sorter;
    }

    public function getFilterParameters() {
        $filter = array();
        if ($this->hasParameter('filter')) {
            $params = json_decode($this->getParameter('filter'), true);

            foreach($params as $param) {
                $f = array("column" => $param['property'],
                    "value" =>$param['value']);
                if(isset($param['like'])) $f['like'] = $param['like'];

                $filter[] = $f;
            }

        }
        return $filter;
    }

    protected function getPagerQueryParams($pager = null) {
        $result = array();
        if (!empty($pager)) {
            $result[] = array('limit', $pager['limit']);
            $result[] = array('offset', $pager['offset']);
        }

        return $result;
    }

    protected function getOrderQueryParams($order = null) {
        $result = array();
        if (!empty($order)){
            $result[] = array('order', array($order));
        }

        return $result;
    }

    protected function getFilterQueryParams($filter = null, $where = null, $where_mode = 'AND') {
        $result = array();
        if (!empty($filter)) {
            $where_arr = array();
            $where_vals = array();
//print_r($filter); exit;
            foreach ($filter as $f) {
                $col_str = "`".$f['column']."`";
                $col_name = $f['column'];

                $column_is_funtion = (strpos($col_name, '(')>-1) && (strpos($col_name, ')')>-1);

                if (!$column_is_funtion && strpos($f['column'], '.')){
                    $col_arr = explode('.', $f['column']);
                    $col_str = $col_arr[0].".`".$col_arr[1]."`";
                    $col_name = $col_arr[1];
                }

                //id figyelése
                $col_name_arr = explode('_', $col_name);
                $last_slice = $col_name_arr[count($col_name_arr)-1];

                if (($last_slice == 'id' && strtolower($f['value'])!='is null' && strtolower($f['value'])!='is not null') || (isset($f['like']) && !$f['like'])) {
                    $where_arr[] = $col_str." =:".$col_name;
                    $where_vals[':'.$col_name] = $f['value'];
                }
                elseif(strtolower($f['value'])=='is null' || strtolower($f['value'])=='is not null'){
                    $where_arr[] = $col_str." ".$f['value'];
                    //$where_vals[':'.$col_name] = null;
                }
                else{
                    if ($column_is_funtion) {
                        // ( kiiktatása a névből
                        $col_name = str_replace('(', '', $col_name);
                        // ) kiiktatása a névből
                        $col_name = str_replace(')', '', $col_name);
                        // , kiiktatása a névből
                        $col_name = str_replace(',', '', $col_name);
                        // space kiiktatása a névből
                        $col_name = str_replace(' ', '', $col_name);
                        // . kiiktatása a névből
                        $col_name = str_replace('.', '', $col_name);

                        $col_str = $f['column'];
                    }

                    $where_arr[] = $col_str." LIKE :".$col_name;
                    $where_vals[':'.$col_name] = "%".$f['value']."%";
                }

            }
/*
            if (!is_null($where)){
                if (is_array($where)) {
                    $where_arr[] = $where[0];
                    $where_vals = array_merge($where_vals, $where[1]);
                }elseif(is_string($where)){
                    $where_arr[] = $where;
                }
            }*/

            if (!is_null($where)){
                if (is_array($where)) {
                    $where_arr[] = $where[0];
                    if(isset($where[1])){
                        $where_vals = array_merge($where_vals, $where[1]);
                    }
                }
                elseif(is_string($where)){
                    $where_arr[] = $where;
                }
            }

            $where_str = (strtolower($where_mode)=='or')?"(".implode(" ".$where_mode." ", $where_arr).")":implode(" ".$where_mode." ", $where_arr);

            $result[] = array('where', array($where_str, $where_vals));
        }

        return $result;
    }

    protected function addPagerParams(&$params) {
        $params = array_merge($params, $this->getPagerQueryParams($this->getPagerParameters()));
    }

    protected function addOrderParams(&$params) {
        $params = array_merge($params, $this->getOrderQueryParams($this->getOrderParameters()));
    }

    protected function unsetFilterGet($filter_name) {
        $location_search = null;

        if(isset($_POST['filter'])){
            $filter_post = json_decode($_POST['filter'], true);
            $new_post_filter = array();
            foreach ($filter_post AS $filter){
                if($filter['property'] != $filter_name){
                    $new_post_filter[] = $filter;
                }
                else{
                    $location_search = $filter['value'];
                }
            }

            if(count($new_post_filter)){
                $_POST['filter'] = json_encode($new_post_filter, true);
            }
            else{
                unset($_POST['filter']);
            }
        }

        return $location_search;
    }

    public function isSetFilter($filter_name){

    	if($this->hasParameter('filter')){
    		$filter_post = json_decode($_POST['filter'], true);
    		print_r($filter_post); exit;
    		foreach ($filter_post AS $filter){
    			if($filter['property'] == $filter_name){
    				return $filter['value'];
    			}
    		}
    	}
    	
    	return false;
    }
    
    public function addFilter($name, $value, $like = true){
        $filter = array();
        if($this->hasParameter('filter')){
            $filter = json_decode($_POST['filter'], true);
        }

        $filter[] = array('property' => $name, 'value' => $value, 'like' => $like);

        $_POST['filter'] = json_encode($filter, true);
    }

    public function addFilterParams(&$params, $where_mode = 'AND') {
        $where = null;
        foreach ($params as $where_key => $param) {
            if ($param[0] == 'where'){
                $where = $param[1];
            }
        }
        $params = array_merge($params, $this->getFilterQueryParams($this->getFilterParameters(), $where, $where_mode));
    }

    protected function handleFirstEmpty(&$data) {
        $first_empty = $this->getParameter('first_empty', false, false);
        $first_empty_value = $this->getParameter('first_empty_value', null, false);
        $first_empty_display = $this->getParameter('first_empty_display', null, false);

        if ($first_empty){
            $empty_arr = array('id'=>(is_null($first_empty_value))?'':$first_empty_value,'name'=>(is_null($first_empty_value))?'':$first_empty_display);

            array_unshift($data, $empty_arr);
        }
    }

    protected function getUserHasCredential($module_name, $controller_name, $action_name) {
        $has = true;
        if ($module_name != 'site') {
            $module_class = sfInflector::camelize($module_name).'Module';

            Yii::import('application.modules.'.$module_name.'.'.$module_class, true);

            if (is_callable($module_class.'::getModuleCredentials')) {
                $module_credentials = $module_class::getModuleCredentials();
                if (isset($module_credentials[$controller_name][$action_name])) {
                    $action_credentials = $module_credentials[$controller_name][$action_name];

                    $useAnd = true;
                    if (is_array($action_credentials) && is_array($action_credentials[0])) {
                        $useAnd = false;
                    }
                    $has = Yii::app()->user->hasCredential($action_credentials, $useAnd);
                }
            }
        }

        return $has;
    }

    protected function beforeAction($action)
    {

	    if ($this->module) {
	        if (  !$this->getUserHasCredential($this->module->id, $this->getId(), $this->action->id)) {
	            $this->layout = false;
	            header('HTTP/1.1 403 Forbidden');
	            $response = json_encode(array("error"=>array("message"=>Yii::t('msg',"Ön nem rendelkezik az adott művelet elvégzéséhez szükséges jogosultsággal!")), "status"=>403));
	            $this->renderText($response);
	            Yii::app()->end();

	        }
	    }
	    return true;

    }

    /**
     * Renders a static text string.
     * The string will be inserted in the current controller layout and returned back.
     * @param string $text the static text string
     * @param boolean $return whether the rendering result should be returned instead of being displayed to end users.
     * @return string the rendering result. Null if the rendering result is not required.
     * @see getLayoutFile
     */
    public function renderText($text,$return=false)
    {
        if ($this->hasParameter('credentials')) {
            $decoded_text = json_decode($text, true);

            $credential_response = array();
            $asked_credentials = $this->getParameter('credentials');
            foreach ($asked_credentials as $credential) {
                if ($credential == 'isSuperAdmin') {
                    $credential_response[$credential] = (int)Yii::app()->user->getDbUser()->is_super_admin;
                } else {
                    $credential_response[$credential] = (int)Yii::app()->user->hasCredential($credential);
                }
            }

            $decoded_text['credentials'] = $credential_response;

            $text = json_encode($decoded_text);
        }

        parent::renderText($text, $return);
    }

    /**
     *
     * Szűrő combo store-okhoz használt field definíciók
     */
    public function getBasicSelectFieldDefinitions($display_col_name = 'name') {
        $fields = array();

        $fields[] = array(
            'name' => 'id',
            'type' => '',
            'header' => 'Azonosító',
            'xtype' => '',
            'sortDir' => '',
            'gridColumn' => false,
        );

        $fields[] = array(
            'name' => $display_col_name,
            'type' => '',
            'header' => 'Megnevezés',
            'xtype' => '',
            'sortDir' => 'asc',
            'gridColumn' => true,
            'flex' => 1,
        );


        return $fields;
    }
	
	/**
     *
     * Szűrő combo store-okhoz használt field definíciók
     */
    public function getBasicTreeFieldDefinitions($display_col_name = 'text') {
        $fields = array();

        $fields[] = array(
            'name' => 'id',
            'type' => '',
            'header' => 'Azonosító',
            'xtype' => '',
            'sortDir' => '',
            'gridColumn' => false,
        );

        $fields[] = array(
            'name' => $display_col_name,
            'header' => 'Megnevezés',
            'xtype' => 'treecolumn',
            //'sortDir' => 'asc',
           // 'gridColumn' => true,
            'flex' => 1,
        );

        return $fields;
    }

	public function actionGetComboList()
	{
		$results = $this->getComboListData();

		$this->handleFirstEmpty($results['data']);

		$this->renderText(json_encode($results, true));
	}

	protected function getComboListData()
	{
		$model = $this->getParameter('model');
		$selection_field_name = $this->getParameter('selection_field_name', 'name', false);
		$extra_where_params = $this->getParameter('extra_where_params', '', false);

		$query = trim($this->getParameter('query', null, false));

		if ($query) {
			$_POST['filter'] = json_encode(array(
				array('property' => $selection_field_name, 'value' => $query)
			));
		}

		$params = array();
		$params[] = array('select', "id, {$selection_field_name} AS name");
		$params[] = array('from', $model::model()->tableName());
		$params[] = array('order', $selection_field_name);

		if (!empty($extra_where_params)) {
			$array = json_decode($extra_where_params, true);
			foreach($array as $name => $value) {
				$this->addFilter($name, $value, false);
			}
		}

		$this->addPagerParams($params);
		$this->addFilterParams($params);

		if ($model::model()->hasAttribute('is_active')) {
			$active_only = $this->getParameter('active_only', 1, false);
			if ($active_only == 1) {
				$found = false;
				foreach($params AS &$param){
					if($param[0] == 'where'){
						$and = ($param[1][0] == '')?'':' AND ';
						$param[1][0].= $and.'is_active = 1';
						$found = true;
						break;
					}
				}

				if (!$found) $params[] = array('where', 'is_active = 1');
			}
		}

		return DBManager::getInstance()->query($params);
	}

    public function actionGetClientComboList(){
        $query = $this->getParameter('query', null, false);
        $active_only = $this->getParameter('active_only', true, false);
        $members_only = $this->getParameter('members_only', false, false);
        $query = trim($query);

        $this->addFilter('c.identifier', $query, false);
        $this->addFilter('c.name', $query);

        $params = array();

        $select = array(
            'c.id',
            'CONCAT(IF(c.identifier IS NULL, "-", c.identifier ), " | ", c.name, " | ", CONCAT(c.zip, " ", s.name, ", ", c.street))  AS name',
        );

        $params[] = array('select', implode(', ', $select));
        $params[] = array('from', Client::model()->tableName().' c');
        $params[] = array('join', array('settlement s', 'c.settlement_id = s.id'));
        $params[] = array('order', 'c.id');

        $this->addFilterParams($params, 'OR');

        if ($active_only) {
            foreach($params AS &$param){
                if($param[0] == 'where'){
                    $and = ($param[1][0] == '')?'':' AND ';
                    $param[1][0].= $and.'member_leave_reason_id IS NULL';
                    break;
                }
            }
        }

        if ($members_only) {
            foreach($params AS &$param){
                if($param[0] == 'where'){
                    $and = ($param[1][0] == '')?'':' AND ';
                    $param[1][0].= $and.'member = 1';
                    break;
                }
            }
        }

//print_r($params); exit;
        $results = DBManager::getInstance()->query($params);

        $this->renderText(json_encode($results, true));
    }

    public function actionGetRightComboList(){
        $active_only = $this->getParameter('active_only', true, false);
        $client_id = $this->getParameter('client_id', false, false);

        $select = array(
            'id',
            'id AS name',
        );

        $params = array();
        $params[] = array('select', implode(', ', $select));
        $params[] = array('from', Right::model()->tableName());
        $params[] = array('order', 'id');

        $where_str = '';
        $where_params = array();

        if ($active_only) {
            $where_str .= '(active_to >= SYSDATE() OR active_to IS NULL)';
        }

        if ($client_id) {
            $where_str .= ($where_str?' AND ':''). 'member_id = :client_id';
            $where_params = array_merge($where_params, array(':client_id' => $client_id));
        }

        if ($where_str) $params[] = array('where', array($where_str, $where_params));

        $results = DBManager::getInstance()->query($params);

        $this->renderText(json_encode($results, true));
    }

    public function actionGetStatusList(){
        $model = $this->getParameter('model');
        $column = $this->getParameter('column');

        $results = StatusManager::getInstance()->getStatus($model, $column);

        $this->renderText(json_encode($results, true));
    }
}