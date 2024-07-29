<?php
/**
 * @link https://cms.skeeks.com/
 * @copyright Copyright (c) 2010 SkeekS
 * @license https://cms.skeeks.com/license/
 * @author Semenov Alexander <semenov@skeeks.com>
 */

namespace skeeks\cms\callcheck\ucaller;

use skeeks\cms\callcheck\CallcheckHandler;
use skeeks\cms\models\CmsCallcheckMessage;
use skeeks\yii2\form\fields\FieldSet;
use yii\base\Exception;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

/**
 *
 * @see https://smsimple.ru/api-http/
 *
 * @author Semenov Alexander <semenov@skeeks.com>
 */
class UcallerCallcheckHandler extends CallcheckHandler
{
    public $api_key = "";
    public $service_id = "";

    /**
     * @return array
     */
    static public function descriptorConfig()
    {
        return array_merge(parent::descriptorConfig(), [
            'name' => \Yii::t('skeeks/cms', 'ucaller.ru'),
        ]);
    }


    public function rules()
    {
        return ArrayHelper::merge(parent::rules(), [
            [['api_key'], 'required'],
            [['service_id'], 'required'],
            [['api_key'], 'string'],
            [['service_id'], 'string'],
        ]);
    }

    public function attributeLabels()
    {
        return ArrayHelper::merge(parent::attributeLabels(), [
            'api_key' => "Секретный ключ сервиса",
            'service_id' => "ID сервиса",

        ]);
    }

    public function attributeHints()
    {
        return ArrayHelper::merge(parent::attributeHints(), [

        ]);
    }


    /**
     * @return array
     */
    public function getConfigFormFields()
    {
        return [
            'main' => [
                'class'  => FieldSet::class,
                'name'   => 'Основные',
                'fields' => [
                    'service_id',
                    'api_key',
                ],
            ],
        ];
    }


    /**
     * @see https://developer.ucaller.ru/
     *
     * @param $phone
     * @return array
     * @throws Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\httpclient\Exception
     */
    public function callcheck($phone)
    {
        $queryString = http_build_query([
            'key'     => $this->api_key,
            'service_id'      => $this->service_id,
            'phone'      => $phone,
            'ip'         => \Yii::$app->request->userIP,
            'mix' => 1,
        ]);

        $url = 'https://api.ucaller.ru/v1.0/initCall?'.$queryString;


        $client = new Client();
        $response = $client
            ->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->setUrl($url)
            ->send();

        if (!$response->isOk) {
            throw new Exception($response->content);
        }

        return $response->data;
    }

    /**
     * @param CmsCallcheckMessage $callcheckMessage
     * @return bool
     * @throws Exception
     */
    public function callcheckMessage(CmsCallcheckMessage $callcheckMessage)
    {
        $data = $this->callcheck($callcheckMessage->phone);

        $callcheckMessage->provider_response_data = (array)$data;
        $callcheckMessage->provider_status = (string)ArrayHelper::getValue($data, 'status');
        $callcheckMessage->provider_call_id = (string)ArrayHelper::getValue($data, 'call_id');

        if (ArrayHelper::getValue($data, 'status') == "OK") {
            $callcheckMessage->status = CmsCallcheckMessage::STATUS_OK;
            $callcheckMessage->code = (string) ArrayHelper::getValue($data, 'code');
        } else {
            $callcheckMessage->status = CmsCallcheckMessage::STATUS_ERROR;
            $callcheckMessage->error_message = ArrayHelper::getValue($data, 'status_text');
        }

        return true;
    }

}