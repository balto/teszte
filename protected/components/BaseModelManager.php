<?php

class BaseModelManager
{
    /**
    *
    * Visszaadja a model_name alapján a manager osztályt
    * Ha a model_name null, vagy nincs saját manager osztálya, akkor az ős CodetableManager-t adja vissza
    * @param string $model_name
    * @return class
    */
    public static function getManagerClass($model_name) {

        // ki kell kapcsolni a Yii változót, hogy ne dobjon errort a class_exists, ha nem létezik a fájl
        Yii::$enableIncludePath = false;

        if (!is_null($model_name) && class_exists($model_name.'Manager')) {
            $manager_class_name = $model_name.'Manager';
            $manager_class = $manager_class_name::getInstance();
        } else {
            //$manager_class = CodetableManager::getInstance();
        }

        Yii::$enableIncludePath = true;

        return $manager_class;
    }

    /**
    *
    * Visszaadja a model_name alapján a form osztályt
    * Ha a model_name null, vagy nincs saját form osztálya, akkor az ős CodetableForm-ot adja vissza
    * @param string $model_name
    * @return class
    */
    public static function getFormClass($model_name) {

        // ki kell kapcsolni a Yii változót, hogy ne dobjon errort a class_exists, ha nem létezik a fájl
        Yii::$enableIncludePath = false;

        if (!is_null($model_name) && class_exists($model_name.'Form')) {
            $form_class_name = $model_name.'Form';
            $form_class = new $form_class_name();
        } else {
            //$form_class = new CodetableForm();
        }

        Yii::$enableIncludePath = true;

        return $form_class;
    }

    public function getFormData($model_name, $id, $form_name) {
        if ($form_name) {
            $form = new $form_name();
        } else {
            $form = self::getFormClass($model_name);
        }

        $attr_names = $form->attributeNames();
        unset($attr_names[$form->getCSRFFieldname()]);

        $record = $model_name::model()->findByPk($id);

        $data = array();
        foreach($attr_names as $attr_name) {
            // a csrf_tokent kihagyjuk
            if ($attr_name != $form->getCSRFFieldname()) $data[$form->generateName($attr_name)] = $record->$attr_name;
        }

        return $data;
    }

    public function isEdit($params){
    	if (isset($params['id']) && $params['id']) {
    		return true;
    	}
    	
    	return false;
    }
    
    public function save($model_name, $params, $form_name) {
    	//print_r(func_get_args()); exit;
    	
    	$edit = false;
        if (isset($params['id']) && $params['id']) {
        	$edit = true;
            $record = $model_name::model()->findByPk($params['id']);
        } else {
            $record = new $model_name();
        }

        if ($form_name) {
            $form = new $form_name();
        } else {
            $form = self::getFormClass($model_name);
        }

        $form->bindActiveRecord($record);
        $form->bind($params);

        if ($form->validate()) {
            $form->save();
        }

        $errors = $form->getErrors();
        if (empty($errors)) {
            $response = array(
                    'success'=>true,
                    'message'=>'Az adatok sikeresen rögzítésre kerültek.',
            		'error' => 0,
                    'id' => $form->id,
            		'is_edit' => $edit,
            );
        } else {
            $response = array(
                    'success'=>false,
            		'error' => 1,
                    'message'=>'Az adatok módosítása nem sikerült az alábbi hibák miatt:',
                    'errors'=>Arrays::array_flatten($errors),
            );
        }
        return $response;
    }

    /**
     *
     * kitörli az adott model adott azonosítójú recordját, ha nem hivatkozik rá semmi.
     * Ha igen, akkor visszaadja a hivatkozó modeleket és a hivatkozó recordok azonosítóit
     * @param unknown_type $model_name
     * @param unknown_type $id
     * @param unknown_type $message_prefix
     */
    public function delete($model_name, $id, $message_prefix = 'A record')
    {
        //törlés előtt le kell ellenőrizni a függőségeket
        $errors = $this->checkIntegrityConstraint($model_name, $id);

        $response_success_true = array(
            'success'=>true,
            'message'=>$message_prefix.' sikeresen törölve.',
        );
        $response_success_false = array(
            'success'=>false,
            'message'=>$message_prefix.' törlése sikertelen!',
            'errors'=> (empty($errors)?'':$errors),
        );

        $rows_deleted = 0;
        if (empty($errors)) {
            // FIGYELEM: direkt nem a deleteByPk van hívva, mert akkor nem futnak le az ActiveRecord behavior-ei, pl a Versionable és nem tenné be törléskor a verziót
            $rows_deleted =$model_name::model()->findByPk($id)->delete();//$model_name::model()->deleteByPk($id);
        }

        return $rows_deleted == 1 ? $response_success_true : $response_success_false;
    }

    /**
     * Leellenőrzi, hogy az adott id-jű elemre hivatkozik-e valaki, és hibaüzenetben felsorolja a hivatkozásokat
     *
     * @param integer $id   azonosító
     * @return array        hibák
     */
    protected function checkIntegrityConstraint($model_name, $id) {
        return $this->getIntegrityErrors($this->getIntegrityReferences($model_name, $id));
    }

    /**
     * Visszadja egy asszociatív tömbben azokat, akik az adott id-jű elemre hivatkoznak
     *
     * @param integer $id
     * @return array
     */
     protected function getIntegrityReferences($model_name, $id) {
        $references = array();

        $relations = $model_name::model()->getMetaData()->relations;

        foreach($relations as $name => $relation) {
            if ($relation instanceof CHasManyRelation) { // TODO: lehet, hogy még a BELONGTO-t is kellene figyelni!!!

                $related_model_name = $relation->className;
                $foreign_field_name = $relation->foreignKey;

                $related_references = $this->getForeignKeyReferences($related_model_name, $foreign_field_name, $id);

                if (count($related_references) > 0) $references[$related_model_name] = $related_references;
            }
        }

        return $references;
    }

    /**
    * Viszaadja az adott model osztálynak azokat a rekordjait, amelyek hivatkoznak az adott foreign key mezőben az adott értékre
    *
    * @param $model_name
    * @param $foreignFieldName
    * @param $foreign_key
    * @return array
    */
    protected function getForeignKeyReferences($model_name, $foreignFieldName, $foreign_key) {
        $command = Yii::app()->db->createCommand()
            ->select('id')
            ->from(sfInflector::underscore($model_name))
            ->where($foreignFieldName.' = :foreign_key', array(':foreign_key' => $foreign_key));

        return $command->queryAll();
    }

    /**
    * Visszaadja a hivatkozások alapján a hibaüzenetet, ami tartalmazza a hivatkozó model osztályt és a foreign key-eket
    *
    * @param $references
    * @return array
    */
    protected function getIntegrityErrors(array $references) {
        $errors = array();
        foreach($references as $related_model_name => $related_references) {
            // TODO: ha minden modelhez megadnánk a magyar nevét, akkor a hibaüzenet szebb is lehetne
            $errors[] = $related_model_name .' hivatkozik rá: '.json_encode($related_references);
        }
        return $errors;
    }

    /**
     * A hierarchikus adathalmazt rekurzívan bejárja, és a levél elemeket megjelelöli egy LEAF property-vel
     * hogy az ExtJS ColumnTree tudjon erről, és ennek megfelelően renderelje a fát
     *
     * @param array $data Referencia szerint átadott hierarchikus adathalmaz, a Besorolási fa node-jaival
     * @return none
     */
    protected function markLeaves(&$node)
    {
        // ha vannak gyermekei a vizsgált node-nak, rekurzívan mindegyikre meghívjuk a fv-t
        if (isset($node['data']) && count($node['data'])) {
            foreach ($node['data'] as &$child) $this->markLeaves($child);
        // ha nincs, akkor beállítjuk, hogy ő egy levél
        } else {
            $node['leaf'] = 'true';
            $node['iconCls'] = 'x-tree-icon-parent';
        }

    }

    public function sendCustomerServiceMail($subject, $message)
    {
        return Mail::sendHtmlMail(
            array(Yii::app()->params['email']['senderEmail'] => Yii::app()->params['email']['senderName']),
            array(Yii::app()->params['customer_service_email']['recipientEmail'] => Yii::app()->params['customer_service_email']['recipientName']),
            $subject,
            $message.Yii::app()->params['customer_service_email']['message-footer']
        );
    }

    /**
     *
     * Összemergeli a where paramétereket az extra_params-ban levő where paraméterekkel,
     * és egyúttal kiveszi az extra_paramból a where paramétereket
     *
     * @param unknown_type $where_params
     * @param unknown_type $extra_params
     */
    public function mergeWhereAndExtraParams($where_params, &$extra_params) {
        $extra_where_str = '';
        $extra_where_params = array();


        $where_keys_in_extra_params = array();
        foreach($extra_params as $key => $extra_param) {
            $builder_method = $extra_param[0];
            if ($builder_method == 'where') {
                $extra_where_str .= ($extra_where_str?' AND ':'').$extra_param[1][0];
                $extra_where_params = array_merge($extra_where_params, $extra_param[1][1]);

                $where_keys_in_extra_params[] = $key;
            }
        }

        // fordított sorrendben kiszedjük a where-eket a tömbből
        array_reverse($where_keys_in_extra_params);
        foreach($where_keys_in_extra_params as $key) {
            unset($extra_params[$key]);
        }

        $str = $extra_where_str . ($extra_where_str?' AND ':' ') . $where_params['str'];
        $params = array_merge($where_params['params'], $extra_where_params);

        return array('str' => $str , 'params' => $params);
    }

	protected function getSelectColumnsForGrid(array $items) {
		$result = $this->getColsArray($items);
		
		return implode(', ', $result);
	}
	
	protected function getSelectColumnsForCombo(array $items, $id) {
		$result = array();
		$result = $this->getColsArray($items, false);
		
		$withSeparator = implode(",' ',", $result);
		
		return $id.", CONCAT(".$withSeparator.") AS name";
	}
	
	private function getColsArray(array $items, $withAs = true){
		$result = array();
		
		foreach ($items AS $column => $as){
			$col = $as;
			$isAs = is_string($column) ? true : false ;
				
			if ($isAs){
				if($withAs){
					$col = $column.' AS '.$as;
				}
				else{
					$col = $column;
				}
			}
			
			$result[] = $col;
		}
		
		return $result;
	}

}