<?php
/* @var $this GoodsTransferController */
/* @var $model GoodsTransfer */
?>
<div>
    <div class="main_form_elements">
        <?php echo CHtml::beginForm(
            '',
            'post',
            [
                'id' => 'form_filter'
            ]
        ); ?>

        <?php echo CHtml::htmlButton(
            'Отметить проводку...',
            ['class' => 'mark_trans']
        ); ?>

        <?php
        echo $model->getAttributeLabel('status') . ': ' .
            CHtml::activeDropDownList(
                $model,
                'status',
                CHtml::listData(GoodsTransferStatus::model()->findAll([
                    'condition' => 'id IN(' . GoodsTransferModel::STATUS_COLLETED . ',' . GoodsTransferModel::STATUS_SENT . ',' . GoodsTransferModel::STATUS_NOT_SENT . ')'
                ]), 'id', 'name'),
                [
                    'prompt' => '',
                    'class' => 'select_status'
                ]
            );
        ?>

        <?php echo ' ' . $model->getAttributeLabel('ko_from') . ': ' .
            CHtml::activeDropDownList(
                $model,
                'ko_from',
                GoodsTransferModel::getKoFromList($model->search(GoodsTransferModel::ACTIVE_TRANSFERS)->getData()),
                [
                    'prompt' => '',
                    'class' => 'ko_from'
                ]
            );
        ?>

    </div>
    <div class="add_transfer">
        <?php echo CHtml::htmlButton(
            'Добавить перемещение...',
            ['class' => 'add_transfer_button']
        ); ?>
    </div>
    <?php echo CHtml::endForm(); ?>
    <div class="clearfix"></div>
</div>
<div class="action_buttons">
    <?php
    echo 'С отмеченными: ' .
        CHtml::htmlButton(
            'Собраны',
            [
                'class' => 'status_collected',
                'data-status' => GoodsTransferModel::STATUS_COLLETED,
            ]
        );
    ?>
    <?php echo CHtml::htmlButton(
        'Отправлены',
        [
            'class' => 'status_sent',
            'data-status' => GoodsTransferModel::STATUS_SENT,
        ]
    ); ?>
    <?php echo CHtml::htmlButton(
        'Получены',
        [
            'class' => 'status_recd change_status',
            'data-status' => GoodsTransferModel::STATUS_RECD,
        ]
    ); ?>
    <?php echo CHtml::htmlButton(
        'Не будут отправлены',
        [
            'class' => 'status_will_not_be_sent change_status',
            'data-status' => GoodsTransferModel::STATUS_NOT_SENT,
        ]
    ); ?>

    <?php

    if (\CurrentUser::isDekaUser()) {
        echo '<span>|</span>' . CHtml::htmlButton('Удалить', [
                'class' => 'delete_transfer',
            ]);
    }
    if ($isSetPrintPages)
        echo CHtml::link('<img class="print_img" src="/images/gfx/print.png" />Распечатать последний отчет', Yii::app()->createUrl('goodsTransfer/printPage'), ['class' => 'print_img']);

    ?>
</div>

<div id="mark_transfer_cont">
    <?php echo CHtml::beginForm(
        Yii::app()->createUrl('goodsTransfer/index'),
        'POST',
        [
            'id' => 'mark_transfer'
        ]
    ); ?>

    <label for="select_transfers" class="select_transfers_label">Введите номер проводки:</label>
    <?php echo CHtml::activeTextField($model, 'trans_id', ['id' => 'select_transfers']); ?>


    <?php echo CHtml::checkBox('clear_tarans', false, ['id' => 'clear_transfers', 'class' => 'clear_transfers_button']); ?>
    <label for="clear_transfers" class="checkbox_label">Очистить текущий выбор</label>

    <?php echo CHtml::endForm(); ?>

</div>

<?php echo CHtml::beginForm(
    Yii::app()->createUrl('goodsTransfer/changeStatus'),
    'POST',
    [
        'id' => 'form_filter',
        'class' => 'all_transfers_form'
    ]
); ?>

<?php $this->getActiveTransfers($model); ?>

<?php echo CHtml::endForm(); ?>

<div class="executed_form_filters">
    <?php echo CHtml::beginForm(
        '',
        'post',
        [
            'id' => 'executed_filter_form'
        ]
    ); ?>
    <?php echo ' ' . $model->getAttributeLabel('executed_ko_from') . ': ' .
        CHtml::activeDropDownList(
            $model,
            'executed_ko_from',
            CHtml::listData(KontragentModel::model()->findAll([
                'with' => 'goodsTransfer',
                'order' => 'kontr_name',
                'condition' => 'goodsTransfer.status=3',
            ]), 'kontr_id', 'kontr_name'
            ),
            [
                'prompt' => '',
                'class' => 'exec_ko_from'
            ]
        );
    ?>
    <?php echo ' ' . $model->getAttributeLabel('executed_ko_to') . ': ' .
        CHtml::activeDropDownList(
            $model,
            'executed_ko_to',
            CHtml::listData(GoodsTransferModel::getKontrNames(), 'kontr_id', 'kontr_name'),
            [
                'prompt' => '',
                'class' => 'exec_ko_to'
            ]
        );
    ?>
    <label for="date_from">С:</label>
    <?php echo CHtml::activeTextField($model, 'from_date', ['id' => 'date_from', 'class' => 'date_input from_date']); ?>
    <label for="date_to">по:</label>
    <?php echo CHtml::activeTextField($model, 'to_date', ['id' => 'date_to', 'class' => 'date_input to_date']); ?>

    <?php echo CHtml::htmlButton(
        'Фильтровать',
        [
            'class' => 'filter_executed',
        ]
    ); ?>
    <?php echo CHtml::htmlButton(
        'Сбросить фильтр',
        [
            'class' => 'reset_filter_executed',
        ]
    ); ?>

    <?php echo CHtml::endForm(); ?>
</div>

<div id="collection_table_cont">
    <?php echo CHtml::beginForm(
        Yii::app()->createUrl('goodsTransfer/changeStatus'),
        'POST',
        [
            'id' => 'form_change_qty',
            'class' => 'change_reg_qty'
        ]
    ); ?>
    <table id="collection_table">
        <thead>
        <tr>
            <th>Код</th>
            <th>Наименование</th>
            <th>Кол-во
                запрошенное
            </th>
            <th>Кол-во
                отправленное
            </th>
        </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
    <?php echo CHtml::endForm(); ?>
</div>

<div id="sent_delivery_service">
    <?php echo CHtml::beginForm(
        Yii::app()->createUrl('goodsTransfer/deliveryService'),
        'POST',
        [
            'id' => 'form_delivery_service',
            'class' => 'delivery_service'
        ]
    ); ?>
    <label class="delivery_service_label"><?php echo $model->getAttributeLabel('delivery_service') . ': ' ?></label>
    <?php echo CHtml::activeDropDownList(
        $model,
        'delivery_service',
        CHtml::listData(
            DekaOrdersOtpravkaType::model()->findAll(['order' => 'type_name']),
            'type_id',
            'type_name'
        ),
        [
            'prompt' => '',
            'class' => 'delivery_service'
        ]
    );
    ?>
    <label class="declaration_number_label"
           for="declaration_number"><?php echo $model->getAttributeLabel('declaration_number') . ': ' ?></label>
    <?php echo CHtml::activeTextField($model, 'declaration_number', ['id' => 'declaration_number', 'class' => 'declaration_number_input']); ?>

    <?php echo CHtml::endForm(); ?>
</div>

<?php $this->getExecutedTransfers($modelExecuted); ?>
<div id="transfer_dialog"></div>
<div id="form_mark"></div>
<div id="form_transfers"></div>
<div id="dialog_collected"></div>
<div id="dialog_sent"></div>
<script>
    var phpVars = {
        'createTransferAction': '<?php echo Yii::app()->createUrl('goodsTransfer/create');?>',
        'indexAction': '<?php echo Yii::app()->createUrl('goodsTransfer/index');?>',
        'changeStatusAction': '<?php echo Yii::app()->createUrl('goodsTransfer/changeStatus');?>',
        'deleteAction': '<?php echo Yii::app()->createUrl('goodsTransfer/delete');?>',
        'changeCollectedQuantityAction': '<?php echo Yii::app()->createUrl('goodsTransfer/ChangeCollectedQuantity');?>',
        'deliveryServiceAction': '<?php echo Yii::app()->createUrl('goodsTransfer/deliveryService');?>'
    };
</script>