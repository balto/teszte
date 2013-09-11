<?php

/**
 * This is the model class for table "ticket_type".
 *
 * The followings are the available columns in table 'ticket_type':
 * @property integer $id
 * @property integer $moment_count
 * @property integer $is_daily
 * @property integer $valid_days
 * @property integer $default_price
 * @property integer $created_by
 * @property string $created_at
 * @property integer $updated_by
 * @property string $updated_at
 *
 * The followings are the available model relations:
 * @property Ticket[] $tickets
 * @property TicketTypeCampaignType[] $ticketTypeCampaignTypes
 */
class TicketType extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return TicketType the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ticket_type';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('created_by, created_at', 'required'),
			array('moment_count, valid_days, default_price, created_by, updated_by', 'numerical', 'integerOnly'=>true),
			array('updated_at', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, moment_count, is_daily, valid_days, default_price, created_by, created_at, updated_by, updated_at', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'tickets' => array(self::HAS_MANY, 'Ticket', 'ticket_type_id'),
			'ticketTypeCampaignTypes' => array(self::HAS_MANY, 'TicketTypeCampaignType', 'ticket_type_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'moment_count' => Yii::t('msg', 'Bérlet alkalmak száma'),
			'is_daily' => Yii::t('msg', 'Napi jegy'),
			'valid_days' => Yii::t('msg', 'Érvényes (nap)'),
			'default_price' => Yii::t('msg', 'Alapár'),
			'created_by' => 'Created By',
			'created_at' => 'Created At',
			'updated_by' => 'Updated By',
			'updated_at' => 'Updated At',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('moment_count',$this->moment_count);
		$criteria->compare('is_daily',$this->is_daily);
		$criteria->compare('valid_days',$this->valid_days);
		$criteria->compare('default_price',$this->default_price);
		$criteria->compare('created_by',$this->created_by);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('updated_by',$this->updated_by);
		$criteria->compare('updated_at',$this->updated_at,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

    public function behaviors(){
        return array(
            'Blameable' => array(
                'class'=>'ext.behaviors.BlameableBehavior',
            ),
        );
    }
}