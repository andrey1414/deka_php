<?php

/**
 * This is the model class for table "goods_transfer".
 *
 * The followings are the available columns in table 'goods_transfer':
 * @property string $id
 * @property string $g_id
 * @property string $datum
 * @property string $datum_sobral
 * @property string $datum_otpravil
 * @property string $datum_poluchil
 * @property integer $qty
 * @property integer $req_qty
 * @property string $user_who_save
 * @property string $ko_from
 * @property string $ko_to
 * @property string $ko_for
 * @property string $reason
 * @property string $description
 * @property string $status
 * @property string $trans_id
 * @property string $deka_user
 * @property integer $delivery_service
 * @property string $declaration_number
 * @property string $order_id
 *
 * The followings are the available model relations:
 * @property DekaUsers $dekaUser
 * @property Goods $good
 * @property OtgruzkiTransId $trans
 * @property Kontragent $koFrom
 * @property Kontragent $koTo
 * @$per_on_page int
 *
 *
 * @const STATUS_COLLETED intager - стутус собранно
 * @const STATUS_SENT integer
 * @const STATUS_RECD integer
 * @const STATUS_NOT_REACHED integer
 * @const STATUS_CANCELED integer
 * @const STATUS_NOT_SENT integer
 * @const STATUS_NEW integer
 * @const SCENARIO_CREATE string
 */
class GoodsTransferModel extends CActiveRecord
{
    //amount of the values, need in footer of cgridview
    public $executed_ko_from;
    public $executed_ko_to;

    public $from_date;
    public $to_date;
    public $ko_from_sess;

    //columns for gridView
    public $checkbox_column;
    public $column_index;

    //test, не работает без свойства = атрибуты
    public $koFrom_kontr_name_from;
    public $price_recommend;
    public $price_recommend_opt;
    public $ko_to;
    public $status_collected;
    public $status_sent;
    public $good_g_name;
    public $delivery_service;
    public $declaration_number;
    public $check;

    public $searchAttributes = [];
    //titles, using in class TitleColumn
    public $titles = [];

    protected $printPages = [];
    protected $per_on_page = 10;

    const STATUS_COLLETED = 1;
    const STATUS_SENT = 2;
    const STATUS_RECD = 3; //полученно
    const STATUS_NOT_REACHED = 4;
    const STATUS_CANCELED = 5;
    const STATUS_NOT_SENT = 6;
    const STATUS_NEW = 7;

    const ACTIVE_TRANSFERS = 'active';
    const EXECUTED_TRANSFERS = 'executed';

    const SCENARIO_CREATE = 'create';
    const SCENARIO_DELIVERY = 'delivery';
    const SCENARIO_CHANGE_STATUS = 'change_status';
    const SCENARIO_DELETE = 'delete';
    const SCENARIO_CHANGE_COLLECTED = 'change_collected';

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'goods_transfer';
    }

    /**
     * Sum of values for gridView.
     *
     * @param $dataDataProvider
     * @return int
     */
    public function countSummRegQty($dataDataProvider)
    {
        $totalQty = 0;
        foreach ($dataDataProvider as $dataProviderColumn)
            $totalQty += $dataProviderColumn->req_qty;
        return $totalQty;
    }

    /**
     * Sum of values for gridView.
     *
     * @param $dataDataProvider
     * @return int
     */
    public function countSummQty($dataDataProvider)
    {
        $totalQty = 0;
        foreach ($dataDataProvider as $dataProviderColumn)
            $totalQty += $dataProviderColumn->qty;
        return $totalQty;
    }

    /**
     * Sum of values for gridView.
     *
     * @param $dataDataProvider
     * @return int
     */
    public function countPriceRecommend($dataDataProvider)
    {
        $totalPriceRecommend = 0;
        foreach ($dataDataProvider as $dataProviderColumn) {
            if (isset($dataProviderColumn->qty) && isset($dataProviderColumn->good->price)) {
                $totalPriceRecommend += $dataProviderColumn->qty * $dataProviderColumn->good->price;
            }
        }
        return $totalPriceRecommend;
    }

    /**
     * Sum of values for gridView.
     *
     * @param $dataDataProvider
     * @return int
     */
    public function countOptPriceRecommend($dataDataProvider)
    {
        $totalPrice = 0;
        foreach ($dataDataProvider as $dataProviderColumn) {
            if (isset($dataProviderColumn->req_qty) && isset($dataProviderColumn->good->price_opt)) {
                $totalPrice += $dataProviderColumn->req_qty * $dataProviderColumn->good->price_opt;
            }
        }
        return $totalPrice;
    }

    /**
     * rules to search
     * @return array
     */
    public function rules()
    {
        return [
            ['ko_from, ko_to, g_id, qty, trans_id', 'required', 'on' => self::SCENARIO_CREATE],
            ['ko_from, ko_to, g_id, qty, trans_id', 'filter', 'filter' => 'intval'],
            ['description', 'filter', 'filter' => 'htmlspecialchars'],
            ['description', 'filter', 'filter' => 'trim'],
            ['ko_from, ko_to, ko_for, user_who_save', 'exists', 'className' => 'KontragentModel', 'attributeName' => 'kontr_id'],
            ['g_id', 'exists', 'className' => 'GoodsModel', 'attributeName' => 'g_id'],
            ['deka_user', 'exists', 'className' => 'DekaUsers', 'attributeName' => 'user_id'],
            ['delivery_service', 'exists', 'className' => 'DekaOrdersOtpravkaType', 'attributeName' => 'type_id'],
            ['trans_id', 'exists', 'className' => 'OtgruzkiTransId', 'attributeName' => 'trans_id'],
            ['delivery_service', 'filter', 'filter' => 'intval', 'on' => self::SCENARIO_DELIVERY],
            ['declaration_number', 'filter', 'filter' => 'trim', 'on' => self::SCENARIO_DELIVERY],
            ['declaration_number', 'filter', 'filter' => 'htmlspecialchars', 'on' => self::SCENARIO_DELIVERY],
            ['check', 'filterCheckedRow', 'on' => self::SCENARIO_DELIVERY],
            ['check', 'filterCheckedRow', 'on' => self::SCENARIO_CHANGE_STATUS],
            ['status', 'filter', 'filter' => 'intval', 'on' => self::SCENARIO_CHANGE_STATUS],
            ['check', 'filterCheckedRow', 'on' => self::SCENARIO_DELETE],
            ['id, g_id, datum, datum_sobral, datum_otpravil, datum_poluchil, qty, req_qty, user_who_save, ko_from, ko_to, ko_for, reason, description, status, trans_id, deka_user, delivery_service, declaration_number, order_id, good_g_name, ko_to, koFrom_kontr_name_from, status_sent', 'safe', 'on' => 'search'],
            ['status, qty, trans_id, user_who_save', 'required', 'on' => self::SCENARIO_CHANGE_COLLECTED],
            ['ko_from, trans_id, user_who_save', 'filter', 'filter' => 'intval', 'on' => self::SCENARIO_CHANGE_COLLECTED],
        ];
    }

    /**
     * Update deilvery service on chooice rows.
     *
     * @return boolean
     */
    public function updateDeliveryService()
    {
        if ($this->validate()) {
            return $this->updateAll([
                'status' => self::STATUS_SENT,
                'delivery_service' => $this->delivery_service,
                'datum_otpravil' => GoodsTransferModel::getCurrentTimestamp(),
                'declaration_number' => $this->declaration_number,
            ], $this->receptCheckedCodntition());

        }
        return false;
    }

    /**
     * Change status of a transfers.
     *
     * @return bool
     */
    public function changeStatus()
    {
        //columns witch will be updated
        $updateColumns = ['status' => $this->status];

        //if status is set, add date of action. Else throw exception
        if ($this->status == GoodsTransferModel::STATUS_RECD)
            $updateColumns['datum_poluchil'] = GoodsTransferModel::getCurrentTimestamp();

        //update checked rows
        if ($this->updateAll($updateColumns, $this->receptCheckedCodntition()))
            return true;
        return false;

    }

    public function relations()
    {
        return [
            'dekaUser' => [self::HAS_MANY, 'DekaUsers', ['user_id' => 'deka_user']],
            'good' => [self::HAS_ONE, 'GoodsModel', ['g_id' => 'g_id']],
            'trans' => [self::BELONGS_TO, 'OtgruzkiTransId', 'trans_id'],
            'koFrom' => [self::HAS_ONE, 'KontragentModel', ['kontr_id' => 'ko_from']],
            'koTo' => [self::HAS_ONE, 'KontragentModel', ['kontr_id' => 'ko_to']],
            'otpravkaType' => [self::HAS_ONE, 'DekaOrdersOtpravkaType', ['type_id' => 'delivery_service']],
            'transferStatus' => [self::HAS_MANY, 'GoodsTransferStatus', ['id' => 'status']],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'g_id' => 'Код',
            'datum' => 'Дата',
            'datum_sobral' => 'Собранно',
            'datum_otpravil' => 'Отправка',
            'datum_poluchil' => 'Полученно',
            'qty' => 'Кол-во отправленное',
            'req_qty' => 'Кол-во запрошенное',
            'ko_from' => 'От кого',
            'ko_to' => 'Куда',
            'reason' => 'Reason',
            'description' => 'Примечание',
            'status' => 'Статус',
            'trans_id' => 'Проводка',
            'delivery_service' => 'Служба доставки',
            'declaration_number' => 'Номер декларации',
            'good.g_name' => 'Наим-ние',
            'koFrom.kontr_name' => 'Контрагент',
            'status_collected' => 'Собранно',
            'status_sent' => 'Отправлено',
            'price_recommend' => 'Цена <br /> (рек) ГРН',
            'price_recommend_opt' => 'Цена <br /> (опт) USD',
            'executed_ko_from' => 'Откуда',
            'executed_ko_to' => 'Куда',
            'koFrom_kontr_name_from' => 'Откуда',
            'good_g_name' => 'Наим-ние',
            'checkbox_column' => '',
            'column_index' => '',
        ];
    }

    /**
     * Reception Active transfer considering user role.
     * @return CActiveDataProvider
     */
    public function getActiveTransfers()
    {
        //$criteria = new CDbCriteria();
        $criteria = $this->getActiveTransfersCriteria();
        $criteria->mergeWith($this->getSearchCriteria());
        return $criteria;
    }

    /**
     * Get dataProvider to print page
     *
     * @return CActiveDataProvider
     */

    public function getPrintPageTransfers()
    {
        return $this->getDataProvider($this->getPrintPageTransfersCriteria());
    }

    /**
     * status to CGridView
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }


    /**
     * Return description of transfer
     *
     * @return string
     */
    public function getDescrStatusSent()
    {
        $text = '';
        //Если статус указан, сохраненение значений которые указанны
        if ($this->status == self::STATUS_SENT) {
            $text .= isset($this->datum_otpravil) ? $this->getDateSent() . '<br>' : '';
            $text .= isset($this->otpravkaType->type_name) ? $this->otpravkaType->type_name . ' ' : '';
            $text .= isset($this->declaration_number) ? $this->declaration_number : ' ';
        }
        return $text;
    }


    /**
     * Return date to CGridView
     * @return DateTime|string
     */
    public function getDatumPoluchil()
    {
        return $this->getDateInEuropeFormat($this->datum_poluchil);
    }

    /**
     * Return date to CGridView
     * @return DateTime|string
     */
    public function getDateColumn()
    {
        return $this->getDateInEuropeFormat($this->datum);
    }

    /**
     * Return date to CGridView
     * @return DateTime|string
     */
    protected function getDateSent()
    {
        return $this->getDateInEuropeFormat($this->datum_otpravil);
    }

    /**
     * Date to CGridView
     *
     * @return DateTime|string
     */

    public function getDescrStatusCollected()
    {
        return $this->getDateInEuropeFormat($this->datum_sobral);
    }

    protected function getDateInEuropeFormat($dateUSAFormat)
    {
        return (new DateTime($dateUSAFormat))->format('d.m.Y');
    }


    /**
     * Define primary key, need to ActiveRecord
     * @return string
     */
    public function primaryKey()
    {
        return 'id';
    }

    /**
     * Define value of id, need to ActiveRecord
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search($dataProviderType)
    {
        //choose criteria type
        $criteria = '';
        switch ($dataProviderType) {
            case self::ACTIVE_TRANSFERS:
                $criteria = $this->getActiveTransfers();
                break;
            case self::EXECUTED_TRANSFERS:
                $criteria = $this->getExecutedTransfers();
                break;
        }

        return $this->getDataProvider($criteria);
    }

    public function activeTransfersExspression()
    {
        return "gts" . $this->getStatus() . " trans_id_" . $this->trans_id . " ko_from" . $this->ko_from;
    }

    /*
     * List of values, based on data form dataProvider.
     *
     * @return array
     */

    public static function getKoFromList($dataDataProvider)
    {
        $koFromNames = [];
        //Формирование списка отделов.
        //Правильнее вынести в отдельный абстрактный класс и расширить его. Или использовать декоратор.
        //if ($dataDataProvider = $dataProvider->getData()) {
        foreach ($dataDataProvider as $record) {
            $koFromNames[$record->koFrom->kontr_id] = $record->koFrom->kontr_name;
        }
        //}
        return $koFromNames;
    }

    public static function getCurrentTimestamp()
    {
        return new CDbExpression('NOW()');
    }

    /*
     * Filter for rules
     *
     * @return array
     * */
    public function filterCheckedRow()
    {
        return $this->check = array_filter($this->check, 'intval');
    }

    /**
     * reception criteria for update, delete methods
     *
     * @return CDbCriteria
     */
    public function receptCheckedCodntition()
    {
        $criteria = new CDbCriteria;
        //add array values in condition
        $criteria->addInCondition('id', $this->check);
        return $criteria;
    }


    /**
     * Current error, can have error when done validation, save, AR errors, etc.
     *
     * @return string
     */
    public function getLastError()
    {
        return current(current($this->getErrors()));
    }

    /**
     * Send mail with delivery type
     *
     */
    public function sendMailWithDelivry()
    {
        $deliveryTypeName = DekaOrdersOtpravkaType::model()->getDeliveryType($this->delivery_service);

        $mail = new \Deka\Mail();
        $mail->from(CurrentUser::getEmail(), CurrentUser::getDisplayName());
        $mail->to(TESTSERVER ? 'laa@deka.ua' : 'kba@deka.ua');
        $mail->message = 'Перемещение отправлено<br/>
                           Дата: ' . (new \Deka\DateTime())->formatDateTime() . '<br/>
                           Служба доставки: ' . ($deliveryTypeName->type_name ? $deliveryTypeName->type_name : '') . '<br/>
                           Номер декларации: ' . $this->declaration_number;
        $mail->sendToCron();
    }

    /**
     * Update collection quantity
     *
     * @return bool
     */
    public function changeCollectQuantity()
    {
        if (!$this->validate())
            return false;

        $this->status = GoodsTransferModel::STATUS_COLLETED;
        $this->qty = $this->qty ?: $this->req_qty;
        $this->datum_sobral = GoodsTransferModel::getCurrentTimestamp();

        if ($this->save())
            return true;
        return false;
    }

    /**
     * Kontr names list, for html element - select
     *
     * @return array
     */
    public static function getKontrNames()
    {
        //Вынесено было т.к. dinstinct не работал в по связанным таблицам AR.
        $sql = 'SELECT DISTINCT `k`.`kontr_id`, `k`.`kontr_name`
        FROM `goods_transfer` AS `gt`
        INNER JOIN `kontragent` AS `k` ON `gt`.`ko_to`=`k`.`kontr_id`
        WHERE `gt`.`status` IN(3)';
        return Yii::app()->db->createCommand($sql)->queryAll();
    }

    /**
     * Doing validation attributes witch using in build criteria for search.
     *
     * @param $goodsTransferModel
     */
    public function validationSearchAttrs($goodsTransferModel)
    {
        $goodName = isset($goodsTransferModel['good_g_name']) ? trim(htmlspecialchars($goodsTransferModel['good_g_name'])) : '';
        $koTo = isset($goodsTransferModel['ko_to']) ? trim(htmlspecialchars($goodsTransferModel['ko_to'])) : '';
        $koFrom = isset($goodsTransferModel['koFrom_kontr_name_from']) ? trim(htmlspecialchars($goodsTransferModel['koFrom_kontr_name_from'])) : '';
        $sendedText = isset($goodsTransferModel['status_sent']) ? trim(htmlspecialchars($goodsTransferModel['status_sent'])) : '';

        $this->searchAttributes = [
            'goodName' => $goodName,
            'koFrom' => $koFrom,
            'koTo' => $koTo,
            'sentText' => $sendedText,
        ];
    }

    protected function getSearchCriteria()
    {
        $this->datum = !empty($this->datum) ? (new \Deka\DateTime($this->datum))->formatDBDate() : NULL;
        $this->datum_sobral = !empty($this->datum_sobral) ? (new \Deka\DateTime($this->datum_sobral))->formatDBDate() : NULL;
        //using query string as date.
        $this->status_sent = !empty($this->status_sent) ? (new \Deka\DateTime($this->status_sent))->formatDBDate() : NULL;
        $this->datum_poluchil = !empty($this->datum_poluchil) ? (new \Deka\DateTime($this->datum_poluchil))->formatDBDate() : NULL;

        $criteria = new CDbCriteria;
        $criteria->compare('id', $this->id, true);
        $criteria->compare('t.g_id', $this->g_id, true);
        $criteria->compare('datum', $this->datum, true);
        $criteria->compare('datum_sobral', $this->datum_sobral, true);
        $criteria->compare('datum_poluchil', $this->datum_poluchil, true);
        $criteria->compare('qty', $this->qty);
        $criteria->compare('req_qty', $this->req_qty);
        $criteria->compare('user_who_save', $this->user_who_save, true);
        $criteria->compare('ko_for', $this->ko_for, true);
        $criteria->compare('reason', $this->reason, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('trans_id', $this->trans_id, true);
        $criteria->compare('deka_user', $this->deka_user, true);
        $criteria->compare('delivery_service', $this->delivery_service);
        $criteria->compare('declaration_number', $this->declaration_number, true);
        $criteria->compare('order_id', $this->order_id, true);
        $criteria->compare('good.g_name', $this->good_g_name, true);
        $criteria->compare('koFrom.kontr_name', $this->koFrom_kontr_name_from, true);
        $criteria->compare('koTo.kontr_name', $this->ko_to, true);
        $criteria->compare('ko_from', $this->ko_from, true);
        //$criteria->compare('ko_to', $this->ko_to, true);
        //$criteria->compare('status', $this->status, true);
        //used date in USA format.
        $criteria->compare('datum_otpravil', $this->status_sent, true);

        return $criteria;
    }

    /**
     * get criteria for kontr_agent
     *
     * @return string
     */
    protected function getKoFromCriteria()
    {
        return 'ko_from=' . $this->ko_from_sess . ' OR ko_to=' . $this->ko_from_sess;
    }

    /**
     *
     * Get pages for print, wich set in cookie.
     *
     * @return CDbCriteria
     */
    protected function getPrintPageTransfersCriteria()
    {
        $criteria = new CDbCriteria();
        $criteria->with = ['good', 'koFrom', 'koTo', 'otpravkaType'];
        $criteria->addInCondition('id', $this->check);
        $criteria->order = 'koFrom.kontr_name ASC';
        return $criteria;
    }

    protected function getActiveTransfersCriteria()
    {
        $criteria = new CDbCriteria();
        $criteria->with = ['good', 'koFrom', 'koTo', 'otpravkaType'];
        if ($this->ko_from_sess)
            $criteria->condition .= $this->getKoFromCriteria() . ' AND';
        $criteria->condition .= ' status <> ' . self::STATUS_RECD;
        if ($this->status)
            $criteria->condition .= ' AND status =' . $this->status;
        if ($this->ko_from)
            $criteria->condition .= ' AND ko_from =' . $this->ko_from;
        return $criteria;
    }

    protected function getExecutedTransfersCriteria()
    {
        $criteria = new CDbCriteria();
        $criteria->with = ['good', 'koFrom', 'koTo', 'otpravkaType'];
        if ($this->ko_from_sess)
            $criteria->condition .= $this->getKoFromCriteria() . ' AND';

        $criteria->condition .= ' status=' . self::STATUS_RECD;

        //add params to condition.
        if ($this->executed_ko_from)
            $criteria->condition .= ' AND ko_from =' . $this->executed_ko_from;
        if ($this->executed_ko_to)
            $criteria->condition .= ' AND ko_to =' . $this->executed_ko_to;
        if ($this->from_date)
            $criteria->condition .= " AND DATE(datum) >= '" . (new Deka\DateTime($this->from_date))->formatDBDateTime() . "'";
        if ($this->to_date)
            $criteria->condition .= " AND DATE(datum) <= '" . (new Deka\DateTime($this->to_date))->formatDBDateTime() . "'";

        return $criteria;
    }

    protected function getExecutedTransfers()
    {
        $criteria = $this->getExecutedTransfersCriteria();
        $criteria->mergeWith($this->getSearchCriteria());
        return $criteria;
    }

    protected function getDataProvider($criteria)
    {
        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
            'pagination' => ['pageSize' => $this->per_on_page],
            'sort' => [
                'attributes' => [
                    'datum' => [
                        'asc' => 'datum ASC',
                        'desc' => 'datum DESC',
                    ],
                    'g_id',
                    'trans_id',
                    'good_g_name' => [
                        'asc' => 'good.g_name ASC',
                        'desc' => 'good.g_name DESC',
                    ],
                    'qty',
                    'req_qty',
                    'price_recommend' => [
                        'asc' => 'good.price*t.qty ASC',
                        'desc' => 'good.price*t.qty DESC',
                    ],
                    'price_recommend_opt' => [
                        'asc' => 'good.price_opt*t.req_qty ASC',
                        'desc' => 'good.price_opt*t.req_qty DESC',
                    ],
                    'ko_to' => [
                        'asc' => 'koTo.kontr_name ASC',
                        'desc' => 'koTo.kontr_name DESC',
                    ],
                    'status_collected' => [
                        'asc' => 'datum_sobral ASC',
                        'desc' => 'datum_sobral DESC',
                    ],
                    'status_sent' => [
                        'asc' => 'datum_otpravil ASC',
                        'desc' => 'datum_otpravil DESC',
                    ],
                    'datum_poluchil' => [
                        'asc' => 'datum_poluchil ASC',
                        'desc' => 'datum_poluchil DESC',
                    ],
                    'description' => [
                        'asc' => 'description ASC',
                        'desc' => 'description DESC',
                    ],
                    'koFrom_kontr_name_from' => [
                        'asc' => 'koFrom.kontr_name ASC',
                        'desc' => 'koFrom.kontr_name DESC',
                    ],
                ],
                'defaultOrder' => 'koFrom.kontr_name ASC',
            ],
        ));
    }
}