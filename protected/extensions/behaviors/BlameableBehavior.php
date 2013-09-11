<?php
/**
 * Logs to every row who created and who updated it. This may interests you when working in a group of
 * more people sharing same privileges.
 *
 * @copyright mintao GmbH & Co. KG
 * @author Florian Fackler <florian.fackler@mintao.com>
 * @license MIT <http://www.opensource.org/licenses/mit-license.php>
 * @package Yii framework
 * @subpackage db-behavior
 * @version 0.1 Beta
 */

class BlameableBehavior extends CActiveRecordBehavior
{
    /**
     * @param string $createdByColumn Name of the column in the table where to write the creater user name
     */
    public $createdByColumn = 'created_by';

    /**
     * @param string $updatedByColumn Name of the column in the table where to write the updater user name
     */
    public $updatedByColumn = 'updated_by';

    public $createdAtColumn = 'created_at';
    public $updatedAtColumn = 'updated_at';

    public function beforeValidate($event)
    {
        try {
            $user_id = Yii::app()->user->id;
        } catch (Exception $e) {
            $user_id = 0;
        }

        $availableColumns = array_keys($this->owner->tableSchema->columns);
        if($this->owner->isNewRecord){

            if(in_array($this->createdByColumn, $availableColumns)){
                if(empty($this->owner->{$this->createdByColumn})){
                    $this->owner->{$this->createdByColumn} = $user_id;
                }
            }

            if(in_array($this->createdAtColumn, $availableColumns)){
                if(empty($this->owner->{$this->createdAtColumn})){
                    $this->owner->{$this->createdAtColumn} = sfDate::getInstance()->formatDbDateTime();
                }
            }

        }
        else {
            if(in_array($this->updatedByColumn, $availableColumns)){
                $this->owner->{$this->updatedByColumn} = $user_id;
            }

            if(in_array($this->updatedAtColumn, $availableColumns)){
                $this->owner->{$this->updatedAtColumn} = sfDate::getInstance()->formatDbDateTime();
            }
        }

        return parent::beforeValidate($event);
    }
}
