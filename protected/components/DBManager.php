<?php

class DBManager
{
    private static $instance = null;

    private function __construct() {}

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new DBManager();
        }
        return self::$instance;
    }
	
	
	private function prepareQueryparams(array $query_params){
		$temp = array();
		$builderMethodKeys = array();
		
		foreach ($query_params as $key => $query_param) {
			$builder_method = $query_param[0];
			$param = $query_param[1];
			
			if($builder_method == 'where'){
			
				if(!isset($builderMethodKeys[$builder_method])) {
					$builderMethodKeys[$builder_method] = $key;
					$temp[$key] = $query_param;
				}
				else{
					switch ($builder_method) {
						case 'where':
							$temp[$builderMethodKeys['where']][1][0] = implode(' AND ', array($temp[$builderMethodKeys['where']][1][0], $param[0]));
							$temp[$builderMethodKeys['where']][1][1] = array_merge($temp[$builderMethodKeys['where']][1][1], $param[1]);
							break;
						
						default:
							
							break;
					}
					
					
					
				}
			
			}
			else{
				$temp[] = $query_param;
			}
		}
		//print_r($temp); exit;
		return $temp;
		
	}

    public function query(array $query_params, $use_pager = true, $index_by = null) {
	    $command = Yii::app()->db->createCommand();
		
		$query_params = $this->prepareQueryparams($query_params);
	    // a metódus nevek nem lehetnek a kulcsok a tömbben, mert a join-t pl. többször meg lehet hívni
	    foreach($query_params as $query_param) {
	        $builder_method = $query_param[0];
	        $param = $query_param[1];

	        if (!is_array($param)) {
	            $command->$builder_method($param);
	        } else {
	            call_user_func_array(array($command, $builder_method), $param);
	        }
	    }

	    if ($use_pager) {
	        $sql = $command->getText();
	        $params = $command->params;
	        $sql = preg_replace('/^SELECT/i', 'SELECT SQL_CALC_FOUND_ROWS ', $sql);
	        $command = Yii::app()->db->createCommand($sql);
	        $command->params = $params;
	    }
/*print_r($command->getText());
print_r($command->params);//exit;*/

	    $data = $command->queryAll();
//print_r($data); exit;
        $r = array();
        if (isset($index_by)) {
            if (count($data) > 0 && isset($data[0][$index_by])) {
                foreach ($data as $row) {
                    $r[$row[$index_by]] = $row;
                }

                $data = $r;
            }
        }

	    if ($use_pager) {
	        $command = Yii::app()->db->createCommand('SELECT FOUND_ROWS();');
	        $totalcount  = $command->queryScalar();
	    } else {
	        $totalcount = count($data);
	    }

	    return array('data' => $data, 'totalCount' => $totalcount);

    }

    public function logModelError($model) {

        $message = "";
        foreach($model->getErrors() as $field => $field_errors) {
            $message .= implode('<br/>', $field_errors)  . '(' . $model->getAttribute($field) . ')';
        }

        $message = 'Table name: ' . $model->tableName() . ', ' .$message;

        Yii::log($message, 'warning', 'sql');

        return $message;
    }

    public function getModelErrors($form) {
        $errors = array();
        foreach($form->getErrors() as $field => $field_errors) {
            $errors[] = implode('<br/>', $field_errors); // Yii a message-be beteszi a field labeljét is
        }

        return $errors;
    }

}
