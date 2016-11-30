<?php

/**
 * Class GoodsTransferController
 *
 * @const TRANSFER_CSS string
 */
class GoodsTransferController extends BaseController
{
    const TRANSFER_CSS = 'transfer_grid.css';

    //html select element values.
    //public $koFromNames = [];

    /**
     * Display transfers
     *
     * @return void
     */
    public function actionIndex()
    {
        if (!(\CurrentUser::isRegionalManager() || \CurrentUser::isDekaUser()))
            throw new CHttpException(404, 'Недостаточно прав для доступа');

        $isSetPrintPages = !!($this->getPrintPagesId()) ? true : false;

        $renderArr = [
            'model' => $this->getActiveTransfersModel(),
            'modelExecuted' => $this->getExecutedTransfersModel(),
            'isSetPrintPages' => $isSetPrintPages,
        ];

        //if ajax request, render only changing gridview html
        if (Yii::app()->request->isAjaxRequest) {
            $this->renderPartial('index', $renderArr);
        } else {
            $this->registerCssJsFileS();
            $this->render('index', $renderArr);
        }
    }

    /**
     * Reception goodsTransfers for using activeDataProvider.
     *
     * @return GoodsTransferModel
     */
    public function getActiveTransfersModel()
    {
        //active transfers
        $model = new GoodsTransferModel('search');

        $model->unsetAttributes();  // clear any default values
        if (isset($_GET['GoodsTransferModel']))
            $model->attributes = $_GET['GoodsTransferModel'];

        //Params should be add when value is set, because model filtering on the attributes in search.
        if (intval(Yii::app()->request->getQuery('status', '')))
            $model->status = intval(Yii::app()->request->getQuery('status', ''));

        if (intval(Yii::app()->request->getQuery('ko_from', '')))
            $model->ko_from = intval(Yii::app()->request->getQuery('ko_from', ''));

        if (intval(Yii::app()->request->getQuery('exec_ko_from')))
            $model->executed_ko_from = intval(Yii::app()->request->getQuery('exec_ko_from', 0));

        if (intval(Yii::app()->request->getQuery('exec_ko_to', 0)))
            $model->executed_ko_to = intval(Yii::app()->request->getQuery('exec_ko_to', 0));

        if (CHtml::encode(Yii::app()->request->getQuery('from_date', '')))
            $model->from_date = CHtml::encode(Yii::app()->request->getQuery('from_date', ''));

        if (CHtml::encode(Yii::app()->request->getQuery('to_date', '')))
            $model->to_date = CHtml::encode(Yii::app()->request->getQuery('to_date', ''));

        if (CHtml::encode(Yii::app()->request->getQuery('to_date', '')))
            $model->to_date = CHtml::encode(Yii::app()->request->getQuery('to_date', ''));

        $model->ko_from_sess = isset(Yii::app()->session['kontr_id']) ? intval(Yii::app()->session['kontr_id']) : NULL;

        return $model;
    }

    /**
     * Reception executed goodsTransfers for using activeDataProvider.
     *
     * @return GoodsTransferModel
     */
    public function getExecutedTransfersModel()
    {
        $modelExecuted = new GoodsTransferModel('search');
        $modelExecuted->unsetAttributes();  // clear any default values
        if (isset($_GET['GoodsTransferModel']))
            $modelExecuted->attributes = $_GET['GoodsTransferModel'];

        //Executed transfers. Because property names different from default property names, dont need check is set them, but if wish you add new attribute, add it in model as new propery.
        $modelExecuted->ko_from = intval(Yii::app()->request->getQuery('ko_from', ''));
        //executed transfers
        $modelExecuted->executed_ko_from = intval(Yii::app()->request->getQuery('exec_ko_from', 0));
        $modelExecuted->executed_ko_to = intval(Yii::app()->request->getQuery('exec_ko_to', 0));
        $modelExecuted->from_date = CHtml::encode(Yii::app()->request->getQuery('from_date', ''));
        $modelExecuted->to_date = CHtml::encode(Yii::app()->request->getQuery('to_date', ''));
        //get kontr_id
        $modelExecuted->ko_from_sess = isset(Yii::app()->session['kontr_id']) ? intval(Yii::app()->session['kontr_id']) : NULL;

        return $modelExecuted;
    }

    /**
     * Dispalay in js, json object.
     *
     * @return string
     */
    public function actionCreate()
    {
        if (!Yii::app()->request->isAjaxRequest)
            throw new CHttpException(404, 'Недостаточно прав для доступа');

        $model = new GoodsTransferModel();

        $model->scenario = GoodsTransferModel::SCENARIO_CREATE;
        if (isset($_POST['GoodsTransferModel'])) {
            $model->attributes = $_POST['GoodsTransferModel'];
            //id of user
            $model->deka_user = \CurrentUser::userId();
            $model->datum = GoodsTransferModel::getCurrentTimestamp();
            //if save values, return json object with status.
            if ($model->save()) {
                echo CJSON::encode(['status' => 200]);
                return;
            } else {
                echo CJSON::encode(['status' => 400, 'error' => $model->getLastError()]);
                return;
            }
        }

        $createForm = $this->renderPartial('create', ['model' => $model], true);
        echo CJSON::encode(['html' => $createForm, 'status' => 200]);
    }

    /**
     * Changing of the chooce transfers
     *
     * @return string - json object
     */
    public function actionChangeCollectedQuantity()
    {

        if (!Yii::app()->request->isAjaxRequest)
            throw new CHttpException(404, 'Недостаточно прав для доступа');

        $checked = [];
        $haveError = false;

        $userId = CurrentUser::userId();
        $koFrom = intval(Yii::app()->session['kontr_id']);

        $transaction = Yii::app()->db->beginTransaction();
        //validate each checked value as one model.
        foreach (Yii::app()->request->getPost('GoodsTransferModel') as $goodTransfer) {
            $model = GoodsTransferModel::model()->findByPk(intval($goodTransfer['id']));
            $model->attributes = $goodTransfer;
            $model->scenario = GoodsTransferModel::SCENARIO_CHANGE_COLLECTED;
            $model->ko_from = $koFrom;
            $model->user_who_save = $userId;
            //if saved, then add value to printPage.
            if ($model->changeCollectQuantity()) {
                $checked[] = $goodTransfer['id'];
            } else {
                $haveError = true;
            }
        }

        if (!$haveError) {
            $this->addPrintPagesId($checked);
            $transaction->commit();
            echo CJSON::encode(['status' => 200]);
        } else {
            $transaction->rollBack();
            echo CJSON::encode(['status' => 400, 'error' => 'Прозошла ошибка при сохранении перемещения.']);
        }
    }

    /**
     * Change status and delivery information in checked row.
     *
     */
    public function actionDeliveryService()
    {
        if (!Yii::app()->request->isAjaxRequest || \CurrentUser::isRegionalManager())
            throw new CHttpException(404, 'Недостаточно прав для доступа');

        $model = new GoodsTransferModel;
        $model->scenario = GoodsTransferModel::SCENARIO_DELIVERY;
        $model->delivery_service = $_POST['GoodsTransferModel']['delivery_service'];
        $model->declaration_number = $_POST['GoodsTransferModel']['declaration_number'];
        $model->check = $_POST['check'];

        //if updated rows
        if ($model->updateDeliveryService()) {
            $model->sendMailWithDelivry();
            $this->addPrintPagesId($_POST['check']);
            echo CJSON::encode(['status' => 200]);
            return;
        } else {
            if ($model->getErrors())
                echo CJSON::encode(['status' => 400, 'error' => $model->getLastError()]);
            else
                echo CJSON::encode(['status' => 400, 'error' => 'Значения уже обновлены.']);
        }
    }

    /**
     * Update status of the selected transfers.
     * @param $status int
     * @return string
     *
     */
    public function actionChangeStatus($status)
    {
        //if status not shouldn't changing, throw exception
        if (!($status == GoodsTransferModel::STATUS_RECD || $status == GoodsTransferModel::STATUS_NOT_SENT))
            throw new CHttpException(404, 'Не правильный статус');

        if (!Yii::app()->request->isAjaxRequest)
            throw new CHttpException(404, 'Недостаточно прав для доступа');

        $model = new GoodsTransferModel();
        $model->scenario = GoodsTransferModel::SCENARIO_CHANGE_STATUS;
        $model->status = $status;
        $model->check = $_POST['check'];

        if ($model->validate() && $model->changeStatus()) {
            $this->addPrintPagesId($_POST['check']);
            echo CJSON::encode(['status' => 200]);
        } else {
            echo CJSON::encode(['status' => 400, 'error' => $model->getLastError()]);
        }
    }

    /**
     * Delete selected transfers.
     *
     * @return string - json object
     */
    public function actionDelete()
    {
        if (!Yii::app()->request->isAjaxRequest || \CurrentUser::isRegionalManager())
            throw new CHttpException(404, 'Недостаточно прав для доступа');
        if (empty($_POST['check'])) {
            echo CJSON::encode(['status' => 400, 'error' => 'Необходимо указать перемещения.']);
            return;
        }

        $model = new GoodsTransferModel();

        $model->scenario = GoodsTransferModel::SCENARIO_DELETE;
        $model->check = $_POST['check'];

        //if doing delete
        if ($model->deleteAll($model->receptCheckedCodntition()))
            echo CJSON::encode(['status' => 200]);
        else
            echo CJSON::encode(['status' => 400, 'error' => $model->getLastError()]);
    }

    /**
     * Display changed transfers of user
     *
     * @return void
     */
    public function actionPrintPage()
    {
        //получение страниц для отображения и их вывод на странице.
        $printPages = $this->getPrintPagesId();
        if (empty($printPages))
            throw new CHttpException(400, 'Не правильный запрос');

        $model = new GoodsTransferModel;
        //set checked rows, and filtration
        $model->check = $this->getPrintPagesId();
        $model->filterCheckedRow();
        $dataProvider = $model->getPrintPageTransfers();

        $renderArr = [
            'model' => $model,
            'dataProvider' => $dataProvider,
        ];

        //if ajax request, render only changing gridview html
        if (Yii::app()->request->isAjaxRequest) {
            $this->renderPartial('print_page', $renderArr);
        } else {
            $this->registerCssJsFileS();
            $this->render('print_page', $renderArr);
        }
    }

    /*
     * Set array in cookie with pages for print, would contain array with index => id of transfer.
     *
     * @param $checkedArr
     *
     * @return void
     * */
    public function addPrintPagesId($checkedArr)
    {
        Yii::app()->request->cookies['checkedTransfers'] = new CHttpCookie('checkedTransfers', serialize($checkedArr));
    }

    /**
     * Get array of print pages
     *
     * @return array
     */
    public function getPrintPagesId()
    {
        return isset(Yii::app()->request->cookies['checkedTransfers']) ? unserialize(Yii::app()->request->cookies['checkedTransfers']) : [];
    }

    /**
     * Get gridView.
     *
     * @param $dataProvider object
     * @return object
     */
    public function getActiveTransfers($model)
    {

        $dataDataProvider = $model->search(GoodsTransferModel::ACTIVE_TRANSFERS)->getData();

        $columns = [
            [
                'name' => 'checkbox_column',
                'header' => '<input type="checkbox">',
                'type' => 'raw',
                'value' => 'CHtml::checkBox("check[]",false,["value"=>$data->id])',
                'htmlOptions' => ['class' => 'goods_transfer_id'],
                'filter' => false,
            ],
            [
                'name' => 'column_index',
                'header' => '№',
                'type' => 'raw',
                'value' => '$row+1+$this->grid->dataProvider->getPagination()->offset',
                'filter' => false,
            ],
            [
                'name' => 'koFrom_kontr_name_from',
                'type' => 'raw',
                'value' => 'isset($data->koFrom->kontr_name) ? $data->koFrom->kontr_name : ""',
                //Класс для отображения ko_from(Откуда).
                'class' => 'TitleColumn',
                'footer' => 'Итог:'
            ],
            [
                'name' => 'datum',
                'type' => 'raw',
                'value' => '$data->getDateColumn()',
            ],
            [
                'name' => 'trans_id',
                'type' => 'raw',
                'value' => '$data->trans_id',
                'htmlOptions' => ['class' => 'trans_id'],
            ],
            [
                'name' => 'g_id',
                'type' => 'raw',
                'value' => '$data->g_id',
                'htmlOptions' => ['class' => 'good_id'],
            ],
            [
                'name' => 'good_g_name',
                'type' => 'raw',
                'value' => 'isset($data->good->g_name) ? $data->good->g_name:""',
                'htmlOptions' => ['class' => 'good_name'],
            ],
            [
                'name' => 'req_qty',
                'type' => 'raw',
                'value' => '$data->req_qty',
                'footer' => $model->countSummRegQty($dataDataProvider),
                'htmlOptions' => ['class' => 'good_req_qty'],

            ],
            [
                'name' => 'qty',
                'type' => 'raw',
                'value' => '$data->qty',
                'footer' => $model->countSummQty($dataDataProvider),
                'htmlOptions' => ['class' => 'good_qty'],
            ],
            [
                'name' => 'price_recommend',
                'type' => 'raw',
                'value' => 'isset($data->good->price) && isset($data->qty) ? $data->good->price*$data->qty : ""',
                'footer' => $model->countPriceRecommend($dataDataProvider),
            ],

            [
                'name' => 'price_recommend_opt',
                'type' => 'raw',
                'value' => 'isset($data->good->price_opt) && isset($data->req_qty) ? $data->good->price_opt*$data->req_qty : ""',
                'footer' => $model->countOptPriceRecommend($dataDataProvider),
            ],
            [
                'name' => 'ko_to',
                'type' => 'raw',
                'value' => 'isset($data->koTo->kontr_name) ? $data->koTo->kontr_name : ""',
            ],
            [
                'name' => 'status_collected',
                'type' => 'raw',
                'value' => '$data->getDescrStatusCollected()',
            ],
            [
                'name' => 'status_sent',
                'type' => 'raw',
                'value' => '$data->getDescrStatusSent()',
            ],
            [
                'name' => 'description',
                'type' => 'raw',
                'value' => '$data->description',
            ],
        ];
        $this->getGridView($model, $columns, '$data->activeTransfersExspression()', 'transfer_table', 'Текущие перемещения', GoodsTransferModel::ACTIVE_TRANSFERS);
    }


    /**
     * Get gridView of transfers with status = 3.
     *
     * @param $dataProvider object
     * @return object
     */
    public function getExecutedTransfers($model)
    {
        $columns = [
            [
                'name' => 'column_index',
                'header' => '№',
                'type' => 'raw',
                'value' => '$row+1+$this->grid->dataProvider->getPagination()->offset',
                'filter' => false,
            ],
            [
                'name' => 'datum',
                'type' => 'raw',
                //'value' => '$data->datum',
                'value' => '$data->getDateColumn()',
            ],
            [
                'name' => 'trans_id',
                'type' => 'raw',
                'value' => '$data->trans_id',
            ],
            [
                'name' => 'g_id',
                'type' => 'raw',
                'value' => '$data->g_id',
            ],
            [
                'name' => 'good_g_name',
                'type' => 'raw',
                'value' => 'isset($data->good->g_name) ? $data->good->g_name:""',
            ],
            [
                'name' => 'req_qty',
                'type' => 'raw',
                'value' => '$data->req_qty',
            ],
            [
                'name' => 'qty',
                'type' => 'raw',
                'value' => '$data->qty',
            ],
            [
                'name' => 'price_recommend',
                'type' => 'raw',
                'value' => 'isset($data->good->price) && isset($data->qty) ? $data->good->price*$data->qty : ""',
            ],
            [
                'name' => 'price_recommend_opt',
                'type' => 'raw',
                'value' => 'isset($data->good->price_opt) && isset($data->req_qty) ? $data->good->price_opt*$data->req_qty : ""',
            ],
            [
                'name' => 'koFrom_kontr_name_from',
                'type' => 'raw',
                'value' => 'isset($data->koFrom->kontr_name) ? $data->koFrom->kontr_name : ""',
            ],
            [
                'name' => 'ko_to',
                'type' => 'raw',
                'value' => 'isset($data->koTo->kontr_name) ? $data->koTo->kontr_name : ""',
            ],
            [
                'name' => 'status_collected',
                'type' => 'raw',
                'value' => '$data->getDescrStatusCollected()',
            ],
            [
                'name' => 'status_sent',
                'type' => 'raw',
                'value' => '$data->getDescrStatusSent()',
            ],
            [
                'name' => 'datum_poluchil',
                'type' => 'raw',
                'value' => '$data->getDatumPoluchil()',
            ],
            [
                'name' => 'description',
                'type' => 'raw',
                'value' => '$data->description',
            ],
        ];

        $this->getGridView($model, $columns, '', 'executed_transfer_table', 'Выполненные перемещения', GoodsTransferModel::EXECUTED_TRANSFERS);
    }

    /**
     * Get gridView for print page. Use changed transfers of user.
     *
     * @param $dataProvider object
     * @return object
     */
    public function getPrintPageTransfers($dataProvider)
    {
        $dataDataProvider = $dataProvider->getData();

        $columns = [
            [
                'name' => '№',
                'type' => 'raw',
                'value' => '$row+1+$this->grid->dataProvider->getPagination()->offset',
            ],
            [
                'name' => 'koFrom_kontr_name_from',
                'type' => 'raw',
                'value' => 'isset($data->koFrom->kontr_name) ? $data->koFrom->kontr_name : ""',
                'class' => 'TitleColumn',
                'footer' => 'Итог:'
            ],
            [
                'name' => 'datum',
                'type' => 'raw',
                'value' => '$data->getDateColumn()',
            ],
            [
                'name' => 'trans_id',
                'type' => 'raw',
                'value' => '$data->trans_id',
                'htmlOptions' => ['class' => 'trans_id'],
            ],
            [
                'name' => 'g_id',
                'type' => 'raw',
                'value' => '$data->g_id',
                'htmlOptions' => ['class' => 'good_id'],
            ],
            [
                'name' => 'good_g_name',
                'type' => 'raw',
                'value' => 'isset($data->good->g_name) ? $data->good->g_name:""',
                'htmlOptions' => ['class' => 'good_name'],
            ],
            [
                'name' => 'req_qty',
                'type' => 'raw',
                'value' => '$data->req_qty',
                'footer' => GoodsTransferModel::model()->countSummRegQty($dataDataProvider),
                'htmlOptions' => ['class' => 'good_req_qty'],
            ],
            [
                'name' => 'qty',
                'type' => 'raw',
                'value' => '$data->qty',
                'footer' => GoodsTransferModel::model()->countSummQty($dataDataProvider),
                'htmlOptions' => ['class' => 'good_qty'],
            ],
            [
                'name' => 'price_recommend',
                'type' => 'raw',
                'value' => 'isset($data->good->price) && isset($data->qty) ? $data->good->price*$data->qty : ""',
                'footer' => GoodsTransferModel::model()->countPriceRecommend($dataDataProvider),
            ],
            [
                'name' => 'price_recommend_opt',
                'type' => 'raw',
                'value' => 'isset($data->good->price_opt) && isset($data->req_qty) ? $data->good->price_opt*$data->req_qty : ""',
                'footer' => GoodsTransferModel::model()->countOptPriceRecommend($dataDataProvider),
            ],
            [
                'name' => 'ko_to',
                'type' => 'raw',
                'value' => 'isset($data->koTo->kontr_name) ? $data->koTo->kontr_name : ""',
            ],
            [
                'name' => 'status_collected',
                'type' => 'raw',
                'value' => '$data->getDescrStatusCollected()',
            ],
            [
                'name' => 'status_sent',
                'type' => 'raw',
                'value' => '$data->getDescrStatusSent()',
            ],
            [
                'name' => 'description',
                'type' => 'raw',
                'value' => '$data->description',
            ],
        ];

        $this->getGridViewPrintTransfers($dataProvider, $columns, '$data->activeTransfersExspression()', '', 'Текущие перемещения');
    }

    /**
     * Registration source files for opt employee
     *
     * @return void
     *
     */
    protected function registerCssJsFileS()
    {
        Yii::app()->clientScript->registerCssFile(Yii::app()->baseUrl . '/css/transfers_page.css');
        Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl . '/js/goods_transfers.js', CClientScript::POS_END);
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
    protected function getGridView($model, $columns, $rowCssClassExpression = '', $cssClass = '', $summary = '', $dataProviderType = '')
    {
        return $this->widget('zii.widgets.grid.CGridView', [
            'dataProvider' => $model->search($dataProviderType),
            'filter' => $model,
            'columns' => $columns,
            'rowCssClassExpression' => $rowCssClassExpression,
            'emptyText' => 'Перемещения не найденны.',
            'htmlOptions' => ['class' => 'grid-view transfer_table ' . $cssClass],
            'cssFile' => Yii::app()->request->baseUrl . '/css/' . self::TRANSFER_CSS,
            'summaryText' => $summary,
        ]);
    }

    /**
     * Grid view for print page, dataProvider pass without search method
     *
     * @param $dataProvider - object
     * @param $columns - array
     * @param string $rowCssClassExpression - string
     * @param string $cssClass - string
     * @param string $summary - string
     * @return object
     */
    //protected function getGridView($dataProvider, $columns, $rowCssClassExpression = '', $cssClass = '', $summary = '', $model)
    protected function getGridViewPrintTransfers($dataProvider, $columns, $rowCssClassExpression = '', $cssClass = '', $summary = '')
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