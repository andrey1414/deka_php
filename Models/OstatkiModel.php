<?php

class OstatkiModel extends CActiveRecord
{
    public $kontr_id;
    public $shop_from;
    public $shop_to;
    public $move_qty;
    public $shop_filter_id;

    //for filter
    public $sort_shop;
    public $sort_brand;
    public $sort_on;
    public $sort_order;
    public $sort_img;

    public static $sortOn = [
        'good_name' => 'По наименованию',
        'balances' => 'По количеству',
        'price_opt' => 'По цене',
        'shop_name' => 'По магазину',
    ];
    public static $sortOrder = [
        'asc' => 'Возрастанию',
        'desc' => 'По убыванию',
    ];

    protected $per_on_page = 100;

    const SCENARIO_INNER_TRANS = 'innerTransfers';
    const SCENARIO_MOVE_GOODS_FROM = 'moveGoodsFrom';

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return [
            ['sort_shop, sort_brand, sort_img', 'filter', 'filter' => 'intval', 'on' => self::SCENARIO_INNER_TRANS],
            ['sort_on, sort_order', 'filter', 'filter' => 'htmlspecialchars', 'on' => self::SCENARIO_INNER_TRANS],
            ['sort_on, sort_order', 'filter', 'filter' => 'trim', 'on' => self::SCENARIO_INNER_TRANS],
            ['move_qty, g_id, shop_to, shop_from, kontr_id', 'filter', 'filter' => 'intval', 'on' => self::SCENARIO_MOVE_GOODS_FROM],
            ['move_qty', 'filter', 'filter' => 'abs', 'on' => self::SCENARIO_MOVE_GOODS_FROM],
            ['g_id, shop_to, shop_from', 'filter', 'filter' => 'intval', 'on' => self::SCENARIO_MOVE_GOODS_FROM],
            ['g_id', 'exists', 'className' => 'GoodsModel', 'attributeName' => 'g_id', 'on' => self::SCENARIO_MOVE_GOODS_FROM],
            ['shop_id, ', 'exists', 'className' => 'Shops', 'attributeName' => 'shop_id', 'on' => self::SCENARIO_MOVE_GOODS_FROM],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'g_id' => 'Код',
            'good.g_name' => 'Наим-ние',
            'qty' => 'Кол-во',
            'good.price' => 'Цена <br>(рек) грн.',
            'good.price_opt' => 'Цена <br>(опт) USD.',
            'good.price_sum' => 'Сумма <br>(опт) USD.', //значение колличество умноженное на цену USD
            'shop.name' => 'Отдел', //shop.name получение по связи.
            'good.img' => 'Изображение', //берется динамически, из бренад, и товара,
            'shop.to' => 'В магазин',
            'sort_shop' => 'Отдел'
        ];
    }

    /**
     * @return string
     */
    public function tableName()
    {
        return 'ostatki';
    }

    /**
     * @return array
     */
    public function relations()
    {
        return [
            'goodsTransfer' => [self::HAS_ONE, 'GoodsTransferModel', ['g_id' => 'g_id']],
            'shop' => [self::HAS_ONE, 'Shops', ['shop_id' => 'shop_id']],
            'good' => [self::HAS_ONE, 'GoodsModel', ['g_id' => 'g_id']],
            'photo' => [self::HAS_ONE, 'Photo', ['tovar_id' => 'g_id']],
            'brands' => [self::HAS_ONE, 'BrandModel', ['id' => 'brand_id']],
        ];
    }

    /**
     * Receipt DataProvider of current transfers
     *
     * @return CActiveDataProvider
     */
    public function getCurrentTransfers()
    {
        return $this->getCActiveDataProvider($this->getCurrentTransfersCriteria());
    }

    /**
     * Receipt DataProvider with filtered params
     *
     * @return CActiveDataProvider
     */
    public function getCurrentTransfersWithFilter()
    {
        //merge with criteria of filtration
        $criteria = $this->getCurrentTransfersCriteria();
        $criteria->mergeWith($this->getFilterCriteria());
        return $this->getCActiveDataProvider($criteria);
    }

    /**
     * Display img
     *
     * @return string
     */
    public function getImg()
    {
        if (isset($this->brand_id) && isset($this->photo->source))
            return '<img src="' . new \GoodsPhotoSrc ($this->brand_id, 'big', $this->photo->source) . '">';
        return '';
    }

    /**
     * Reception string for grid view.
     *
     * @return string
     */
    public function getLinkMoveTransfer()
    {
        return '<div class="move_transfer" data-g_id="' . $this->g_id . '" data-shop_id="' . $this->shop_id . '">Переместить</div>';
    }


    public function countSumQty($dataDataProvider)
    {
        $totalQty = 0;
        foreach ($dataDataProvider as $dataProviderColumn)
            $totalQty += $dataProviderColumn->qty;
        return $totalQty;
    }

    public function countSumPrice($dataDataProvider)
    {
        $totalPrice = 0;
        foreach ($dataDataProvider as $dataProviderColumn)
            if (isset($dataProviderColumn->good->price)) {
                $totalPrice = $dataProviderColumn->good->price * $dataProviderColumn->qty;
            }
        return $totalPrice;
    }

    public function countSumOptPrice($dataDataProvider)
    {
        $totalPrice = 0;
        foreach ($dataDataProvider as $dataProviderColumn)
            if (isset($dataProviderColumn->good->price_opt)) {
                $totalPrice += $dataProviderColumn->good->price_opt * $dataProviderColumn->qty;
            }
        return $totalPrice;
    }

    /**
     * Criteria
     *
     * @return CDbCriteria
     */
    public static function getCriteriaBrandList($kontrId)
    {
        $criteria = new CDbCriteria;
        $criteria->with = ['brands', 'shop'];
        $criteria->condition = 'shop.kontr_id = ' . $kontrId;
        $criteria->condition .= ' AND t.shop_id=shop.shop_id';
        $criteria->condition .= ' AND brands.id IS NOT NULL';
        $criteria->order = 'brands.brand ASC';
        return $criteria;
    }

    /**
     *  Get AR model to balances for shop from doing transfer.
     *
     * @return AR model
     */
    public function getBalancesFrom()
    {
        $criteria = new CDbCriteria;
        $criteria->condition = 'g_id = ' . $this->g_id;
        $criteria->condition .= ' AND shop_id = ' . $this->shop_from;
        $criteria->condition .= ' AND qty > 0';
        return $this->find($criteria);
    }

    /**
     *  if good and shop have been existing in database. UPD dont have unique keys in database.
     *
     * @return object - AR model
     */
    public function getBalancesTo()
    {
        $criteria = new CDbCriteria;
        $criteria->condition = 'g_id = ' . $this->g_id;
        $criteria->condition .= ' AND shop_id = ' . $this->shop_to;
        $criteria->condition .= ' AND qty > 0';
        return $this->find($criteria);
    }

    /**
     * Time for insert in DB.
     *
     * @return CDbExpression
     */
    public static function getCurrentTimestamp()
    {
        return new CDbExpression('NOW()');
    }

    public function primaryKey()
    {
        return 'g_id';
    }

    public function getId()
    {
        return $this->g_id;
    }

    /**
     * Returns criteria for current transfers
     *
     * @return CDbCriteria
     */
    protected function getCurrentTransfersCriteria()
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'shop.kontr_id=' . $this->kontr_id;
        //more then 0
        $criteria->condition .= ' AND (qty > 0 AND good.price >= 0)';
        $criteria->group = 't.shop_id, good.g_id';
        $criteria->with = ['good', 'shop', 'photo'];
        return $criteria;
    }

    /**
     * Criteria for filtration on attributes.
     *
     * @return CDbCriteria
     */
    protected function getFilterCriteria()
    {
        $criteria = new CDbCriteria();
        if ($this->sort_shop)
            $criteria->condition .= 't.shop_id=' . $this->sort_shop;
        if ($this->sort_brand)
            $criteria->condition .= ' AND t.brand_id=' . $this->sort_brand;
        if ((bool)$this->sort_img)
            $criteria->condition .= ' AND photo.source IS NOT NULL';
        $criteria->mergeWith($this->getSortOn());
        return $criteria;
    }

    /**
     * Return sort type ASC|DESC
     *
     * @return string
     */
    protected function getSortType()
    {
        return $this->sort_order == 'asc' ? 'ASC' : 'DESC';
    }

    /**
     * Choose order for sort on an attribute
     *
     * @return CDbCriteria
     */
    protected function getSortOn()
    {
        $criteria = new CDbCriteria();

        switch ($this->sort_on) {
            case 'good_name' :
                $criteria->order = $this->getOrderOnGoodName();
                break;
            case 'balances' :
                $criteria->order = $this->getOrderOnBalances();
                break;
            case 'price_opt' :
                $criteria->order = $this->getOrderOnPriceWholesale();
                break;
            case 'shop_name' :
                $criteria->order = $this->getOrderOnShopName();
                break;
        }
        return $criteria;
    }

    protected function getOrderOnGoodName()
    {
        return 'good.price ' . $this->getSortType();
    }

    protected function getOrderOnBalances()
    {
        return 't.qty ' . $this->getSortType();
    }

    protected function getOrderOnPriceWholesale()
    {
        return 'good.price_opt ' . $this->getSortType();
    }

    protected function getOrderOnShopName()
    {
        return 'good.price_opt ' . $this->getSortType();
    }

    /**
     * DataProvider
     *
     * @param $criteria
     * @return CActiveDataProvider
     */
    protected function getCActiveDataProvider($criteria)
    {
        return new CActiveDataProvider('OstatkiModel', [

            'criteria' => $criteria,
            'pagination' => [
                'pageSize' => $this->per_on_page
            ],
            'sort' => [
                'attributes' => [
                    'g_id',
                    'good.g_name' => [
                        'asc' => 'good.g_name ASC',
                        'desc' => 'good.g_name DESC',
                    ],
                    'qty',
                    'req_qty',
                    //reducing to int
                    'good.price' => [
                        'asc' => 'good.price*1 ASC',
                        'desc' => 'good.price*1 DESC',
                    ],
                    'good.price_opt' => [
                        'asc' => 'good.price_opt*1 ASC',
                        'desc' => 'good.price_opt*1 DESC',
                    ],
                    'good.price_sum' => [
                        'asc' => 'good.price_opt*t.qty ASC',
                        'desc' => 'good.price_opt*t.qty DESC',
                    ],

                    'shop.name' => [
                        'asc' => 'shop.shop_name ASC',
                        'desc' => 'shop.shop_name DESC',
                    ]
                ],
                'defaultOrder' => 'good.g_name ASC',
            ],
        ]);
    }
}