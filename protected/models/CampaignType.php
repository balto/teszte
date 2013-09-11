<?php

/**
 * This is the model class for table "campaign_type".
 *
 * The followings are the available columns in table 'campaign_type':
 * @property integer $id
 * @property string $name
 * @property integer $dance_type_id
 * @property integer $created_by
 * @property string $created_at
 * @property integer $updated_by
 * @property string $updated_at
 *
 * The followings are the available model relations:
 * @property DanceType $danceType
 * @property CampaignTypeDetail[] $campaignTypeDetails
 * @property CampaignTypePermission[] $campaignTypePermissions
 * @property CampaignTypePermission[] $campaignTypePermissions1
 * @property CampaignTypeRequire[] $campaignTypeRequires
 * @property CampaignTypeRequire[] $campaignTypeRequires1
 * @property MemberCampaignType[] $memberCampaignTypes
 * @property TicketTypeCampaignType[] $ticketTypeCampaignTypes
 */
class CampaignType extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CampaignType the static model class
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
		return 'campaign_type';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, dance_type_id, created_by, created_at', 'required'),
			array('dance_type_id, created_by, updated_by', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>200),
			array('updated_at', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, dance_type_id, created_by, created_at, updated_by, updated_at', 'safe', 'on'=>'search'),
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
			'danceType' => array(self::BELONGS_TO, 'DanceType', 'dance_type_id'),
			'campaignTypeDetails' => array(self::HAS_MANY, 'CampaignTypeDetail', 'campaign_type_id'),
			'campaignTypePermissions' => array(self::HAS_MANY, 'CampaignTypePermission', 'campaign_type_id'),
			'campaignTypePermissions1' => array(self::HAS_MANY, 'CampaignTypePermission', 'permission_campaign_type_id'),
			'campaignTypeRequires' => array(self::HAS_MANY, 'CampaignTypeRequire', 'campaign_type_id'),
			'campaignTypeRequires1' => array(self::HAS_MANY, 'CampaignTypeRequire', 'require_campaign_type_id'),
			'memberCampaignTypes' => array(self::HAS_MANY, 'MemberCampaignType', 'campaign_type_id'),
			'ticketTypeCampaignTypes' => array(self::HAS_MANY, 'TicketTypeCampaignType', 'campaign_type_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => Yii::t('msg', 'Név'),
			'dance_type_id' => Yii::t('msg', 'Tánc típus'),
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('dance_type_id',$this->dance_type_id);
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