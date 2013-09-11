<?php

/**
 * This is the model class for table "user".
 *
 * The followings are the available columns in table 'user':
 * @property integer $id
 * @property string $username
 * @property string $loginname
 * @property string $tokenname
 * @property string $algorithm
 * @property string $salt
 * @property string $password
 * @property integer $is_first_password
 * @property integer $is_active
 * @property integer $is_super_admin
 * @property string $last_login
 * @property integer $failed_logins
 * @property string $session_id
 * @property integer $created_by
 * @property string $created_at
 * @property string $updated_at
 *
 * The followings are the available model relations:
 * @property CampaignMomentTeachers[] $campaignMomentTeachers
 * @property CampaignTeachers[] $campaignTeachers
 * @property Country[] $countries
 * @property Country[] $countries1
 * @property County[] $counties
 * @property County[] $counties1
 * @property FileDescriptor[] $fileDescriptors
 * @property Permission[] $permissions
 * @property Permission[] $permissions1
 * @property Settlement[] $settlements
 * @property Settlement[] $settlements1
 * @property User $createdBy
 * @property User[] $users
 * @property UserGroup[] $userGroups
 * @property UserProfile[] $userProfiles
 * @property UserProfile[] $userProfiles1
 * @property UserProfile[] $userProfiles2
 */
class User extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('username, loginname, created_by, created_at', 'required'),
			array('is_first_password, is_active, is_super_admin, failed_logins, created_by', 'numerical', 'integerOnly'=>true),
			array('username, loginname, tokenname, algorithm, salt, password', 'length', 'max'=>128),
			array('session_id', 'length', 'max'=>64),
			array('last_login, updated_at', 'safe'),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, username, loginname, tokenname, algorithm, salt, password, is_first_password, is_active, is_super_admin, last_login, failed_logins, session_id, created_by, created_at, updated_at', 'safe', 'on'=>'search'),
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
			'campaignMomentTeachers' => array(self::HAS_MANY, 'CampaignMomentTeachers', 'user_id'),
			'campaignTeachers' => array(self::HAS_MANY, 'CampaignTeachers', 'user_id'),
			'countries' => array(self::HAS_MANY, 'Country', 'created_by'),
			'countries1' => array(self::HAS_MANY, 'Country', 'updated_by'),
			'counties' => array(self::HAS_MANY, 'County', 'created_by'),
			'counties1' => array(self::HAS_MANY, 'County', 'updated_by'),
			'fileDescriptors' => array(self::HAS_MANY, 'FileDescriptor', 'created_by'),
			'permissions' => array(self::MANY_MANY, 'Permission', 'user_permission(user_id, permission_id)'),
			'permissions1' => array(self::HAS_MANY, 'Permission', 'updated_by'),
			'settlements' => array(self::HAS_MANY, 'Settlement', 'created_by'),
			'settlements1' => array(self::HAS_MANY, 'Settlement', 'updated_by'),
			'createdBy' => array(self::BELONGS_TO, 'User', 'created_by'),
			'users' => array(self::HAS_MANY, 'User', 'created_by'),
			'userGroups' => array(self::MANY_MANY, 'UserGroup', 'user_user_group(user_id, user_group_id)'),
			'userProfiles' => array(self::HAS_MANY, 'UserProfile', 'user_id'),
			'userProfiles1' => array(self::HAS_MANY, 'UserProfile', 'created_by'),
			'userProfiles2' => array(self::HAS_MANY, 'UserProfile', 'updated_by'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'username' => 'Username',
			'loginname' => 'Loginname',
			'tokenname' => 'Tokenname',
			'algorithm' => 'Algorithm',
			'salt' => 'Salt',
			'password' => 'Password',
			'is_first_password' => 'Is First Password',
			'is_active' => 'Is Active',
			'is_super_admin' => 'Is Super Admin',
			'last_login' => 'Last Login',
			'failed_logins' => 'Failed Logins',
			'session_id' => 'Session',
			'created_by' => 'Created By',
			'created_at' => 'Created At',
			'updated_at' => 'Updated At',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('loginname',$this->loginname,true);
		$criteria->compare('tokenname',$this->tokenname,true);
		$criteria->compare('algorithm',$this->algorithm,true);
		$criteria->compare('salt',$this->salt,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('is_first_password',$this->is_first_password);
		$criteria->compare('is_active',$this->is_active);
		$criteria->compare('is_super_admin',$this->is_super_admin);
		$criteria->compare('last_login',$this->last_login,true);
		$criteria->compare('failed_logins',$this->failed_logins);
		$criteria->compare('session_id',$this->session_id,true);
		$criteria->compare('created_by',$this->created_by);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('updated_at',$this->updated_at,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return User the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
	
	public function behaviors(){
		return array(
			'Blameable' => array(
					'class'=>'ext.behaviors.BlameableBehavior',
			),
		);
	}
}
