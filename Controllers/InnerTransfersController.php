<?php

/**
 * Class GoodsTransferController
 *
 * @const TRANSFER_CSS string
 */
class InnerTransfersController extends BaseController
{
    const TRANSFER_CSS = 'transfer_grid.css';

    /**
     * Display transfers
     *
     * @return void
     */
    public function actionIndex()
    {
        if (!\CurrentUser::isKontragent())
            throw new CHttpException(404, 'Недостаточно прав для доступа');

        //active transfers
        $balancesTransfer = new OstatkiModel('innerTransfers');

        $balancesTransfer->kontr_id = Yii::app()->session['kontr_id'];
        //if is set - load in model
        if (isset($_GET['OstatkiModel']))
            $balancesTransfer->setAttributes($_GET['OstatkiModel']);

        $balancesTransfer->validate();

        $renderArr = [
            'model' => $balancesTransfer,
            'dataProvider' => $balancesTransfer->getCurrentTransfersWithFilter(),
        ];

        if (Yii::app()->request->isAjaxRequest) {
            $this->renderPartial('inner_transfers', $renderArr);
        } else {
            $this->registerInnerSource();
            $this->render('inner_transfers', $renderArr);
        }
    }

    /**
     * Get GridView, used in view.
     *
     * @param $dataProvider
     * @return object
     */
    public function getInnerTransfers($dataProvider)
    {

        $dataDataProvider = $dataProvider->getData();

        $columns = [
            [
                'name' => '№',
                'type' => 'raw',
                'value' => '$row+1+$this->grid->dataProvider->getPagination()->offset',
            ],
            [
                'name' => 'g_id',
                'type' => 'raw',
                'value' => '$data->g_id',
            ],
            [
                'name' => 'good.g_name',
                'type' => 'raw',
                'value' => 'isset($data->good->g_name) ? $data->good->g_name:""',
                'footer' => 'Итог:'
            ],
            [
                'name' => 'qty',
                'type' => 'raw',
                'value' => '$data->qty',
                'footer' => OstatkiModel::model()->countSumQty($dataDataProvider),
                'htmlOptions' => ['class' => 'qty'],
            ],
            [
                'name' => 'good.price',
                'type' => 'raw',
                'value' => 'isset($data->good->price) && isset($data->good->price) ? $data->good->price : ""',
                'footer' => OstatkiModel::model()->countSumPrice($dataDataProvider),
                'htmlOptions' => ['class' => 'price'],
            ],
            [
                'name' => 'good.price_opt',
                'type' => 'raw',
                'value' => 'isset($data->good->price_opt) && isset($data->good->price_opt) ? $data->good->price_opt : ""',
                'footer' => OstatkiModel::model()->countSumOptPrice($dataDataProvider),
            ],
            [
                'name' => 'good.price_sum',
                'type' => 'raw',
                'value' => '(isset($data->good->price_opt) && isset($data->qty)) ? $data->good->price_opt*$data->qty : ""',
                'htmlOptions' => ['class' => 'sum_opt_price'],
            ],
            [
                'name' => 'shop.name',
                'type' => 'raw',
                'value' => 'isset($data->shop->shop_name) ? $data->shop->shop_name : ""',
                'htmlOptions' => ['class' => 'shop_name'],
            ],
            [
                'name' => '',
                'type' => 'raw',
                'value' => '$data->getImg()',
            ],
            [
                'name' => '',
                'type' => 'raw',
                'value' => '$data->getLinkMoveTransfer()',
            ],
        ];

        $this->getGridView($dataProvider, $columns, '', 'inner_transfer_table');
    }

    /**
     * Move good from one shop in other shop.
     *
     * @return string of json object.
     */
    public function actionMoveGood()
    {
        if (!Yii::app()->request->isAjaxRequest)
            throw new CHttpException(400, 'Недостаточно прав для доступа');

        //$model have single area of responsibility, repsonds behind reception $balancesFromModel and $balancesTo
        $model = new OstatkiModel(OstatkiModel::SCENARIO_MOVE_GOODS_FROM);

        $model->attributes = Yii::app()->request->getPost('OstatkiModel');

        if($model->shop_from == $model->shop_to)
            throw new CHttpException(400, 'Не верно созданно перемещение.');

        if ($model->validate()) {
            /*
             * Редактирование теку. перемещ.
             * Получение перемещение.
             * Оределение колл-во запрошенного товара.
             * Если есть достаточное колличество, уменьшаем значение в б.д. для этого товара. если 0 удалить.
             * Если не достаточно, возвращаем ошибку.
             * Если уменьшили значение, делаем перемещение товара в таблице.
            */
            $balancesFromModel = $model->getBalancesFrom();

            $balancesQntFrom = $balancesFromModel->qty - $model->move_qty;

            //if changing qunatity more then 0
            if ($balancesQntFrom < 0) {
                echo CJSON::encode(['status' => 400, 'error' => 'Указанно не верное колличество товара']);
                return;
            }

            $balancesTo = $model->getBalancesTo();

            /*
             * if good is fined, change quntity of the good to current quantity
             * else insert into database new row.
             */
            $transaction = Yii::app()->db->beginTransaction();
            if ($balancesTo) {
                $balancesTo->qty += $model->move_qty;
                $balancesTo->save();
            } else {
                $ostatkiModel = new OstatkiModel;
                $ostatkiModel->g_id = $model->g_id;
                $ostatkiModel->qty = $model->move_qty;
                $ostatkiModel->shop_id = $model->shop_to;
                $ostatkiModel->datum = OstatkiModel::getCurrentTimestamp();
                $ostatkiModel->save();
            }

            /*
             *
             * if hasn't errors, decrease quantity of good from model $balancesFromModel.
             * if save $balancesFromModel model, commit changes
             */
            if ($balancesTo && !$balancesTo->hasErrors() || Yii::app()->db->getLastInsertID()) {
                $balancesFromModel->qty = $balancesQntFrom;
                if ($balancesFromModel->save()) {
                    $transaction->commit();
                    echo CJSON::encode(['status' => 200]);
                    return;
                } else {
                    echo CJSON::encode(['status' => 400, 'error' => 'Произошла ошибка при перемещении товара.']);
                    return;
                }
            }
        }
    }

    /**
     * Return criteria in view, for filter
     *
     * @return CDbCriteria
     */
    public function getCriteriaBrandList()
    {
        return OstatkiModel::getCriteriaBrandList(intval(Yii::app()->session['kontr_id']));
    }

    /**
     * Registration script on inner treansfers page
     *
     * @return void
     *
     */
    protected function registerInnerSource()
    {
        Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl . '/css/inner_transfers_page.css');
        Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/inner_transfers.js', CClientScript::POS_END);
    }
    /**
     * Reception CGridview.
     *
     * @param $dataProvider - object
     * @param $columns - array
     * @param string $rowCssClassExpression - string
     * @param string $cssClass - string
     * @param string $summary - string
     * @return object
     */
    //protected function getGridView($dataProvider, $columns, $rowCssClassExpression = '', $cssClass = '', $summary = '', $model)
    protected function getGridView($dataProvider, $columns, $rowCssClassExpression = '', $cssClass = '', $summary = '')
    {
        return $this->widget('zii.widgets.grid.CGridView', [
            'dataProvider' => $dataProvider,
            'columns' => $columns,
            'rowCssClassExpression' => $rowCssClassExpression,
            'emptyText' => 'Перемещения не найденны.',
            'htmlOptions' => ['class' => 'grid-view transfer_table ' . $cssClass],
            'cssFile' => Yii::app()->request->baseUrl . '/css/' . self::TRANSFER_CSS,
            'summaryText' => $summary,
        ]);
    }
}