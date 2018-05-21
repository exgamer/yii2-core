<?php

namespace core\forms\v2;

use Yii;
use \yii\base\Model;

/**
 * Форма смены статуса
 * 
 * @property integer $status    - статус
 * @property integer $reason_id - причина смены статуса (0 означает что вводится кастомная причина - $comment)
 * @property string $comment    - комментарий
 * 
 * @author Kamaelkz <kamaelkz@yandex.kz>
 */
class ChangeStatusForm extends Model
{
    public $status;
    public $reason_id;
    public $comment;
    
    public function rules() 
    {
        return [
                    [
                        [
                            'status'
                        ],
                        'required'
                    ],
                    [
                        [
                            'status',
                            'reason_id',
                        ],
                        'integer'
                    ],
                    [
                        [
                            'comment'
                        ],
                        'string',
                        'max' => 512
                    ],
                    [
                        [
                            'reason_id'
                        ],
                        'validateReason'
                    ]
        ];
    }
    
    public function validateReason($attribute, $params)
    {
        if( $this->{$attribute} == 0 && ! $this->comment ){
            $this->addError($attribute, Yii::t('common', 'Необходимо заполнить "{label}".', [
                'label' => $this->getAttributeLabel('comment')
            ]));
        }
        if( $this->{$attribute} > 0 && $this->comment ) {
            $this->addError($attribute, Yii::t('common', 'Невозможно оставить комментарий совместно с причиной.'));
        }
    }
    
    public function attributeLabels() 
    {
        return [
            STATUS_FIELD => Yii::t('common', 'Статус'),
            'reason_id' => Yii::t('common', 'Причина'),
            'comment' => Yii::t('common', 'Комментарий'),
        ];
    }
}