<h3 class="inner_transfer_title">Внутренние перемещения</h3>
<div class="inner_transfers_wrap">
    <div class="inner_transfers_filter">
        <?php echo CHtml::beginForm(
            '',
            'GET',
            [
                'name' => 'sort',
                'id' => 'sort_good',
            ]
        ); ?>
        <fieldset>
            <legend>Отдел</legend>
            <?php

            //TODO error
            echo CHtml::activeDropDownList(
                $model,
                'sort_shop',
                CHtml::listData(Shops::model()->findAll([
                    'order' => 'shop_name',
                    'condition' => 'kontr_id = ' . $model->kontr_id
                ]), 'shop_id', 'shop_name'),
                [
                    'prompt' => '',
                    'class' => 'inner_shop'
                ]
            );
            ?>
        </fieldset>
        <fieldset>
            <legend>Бренд:</legend>
            <?php echo CHtml::activeDropDownList(
                $model,
                'sort_brand',
                CHtml::listData(OstatkiModel::model()->findAll($this->getCriteriaBrandList()),
                    'brands.id',
                    'brands.brand'),
                [
                    'prompt' => '',
                    'class' => 'inner_brand'
                ]
            ); ?>
        </fieldset>
        <fieldset>
            <legend>Сортировать:</legend>
            <?php echo CHtml::activeDropDownList(
                $model,
                'sort_on',
                OstatkiModel::$sortOn,
                [
                    'class' => 'inner_sort_on'
                ]
            );
            ?>
            <?php echo CHtml::activeDropDownList(
                $model,
                'sort_order',
                OstatkiModel::$sortOrder,
                [
                    'class' => 'inner_sort_order'
                ]
            );
            ?>
        </fieldset>
        <fieldset>
            <legend>Фото:</legend>
            <?php echo CHtml::activeCheckBox(
                $model,
                'sort_img',
                [
                    'class' => 'with_img',
                    'id' => 'with_img'
                ]
            ); ?>
            <label for="with_img">Показывать с фотографиями</label>
        </fieldset>
        <div class="button_cont">
            <?php echo CHtml::submitButton(
                'Показать',
                [
                    'class' => 'filter_button'
                ]
            ); ?>
        </div>
        <?php echo CHtml::endForm(); ?>
    </div>
</div>
<hr>

<?php $this->getInnerTransfers($dataProvider); ?>

<div id="move_good_cont">
    <?php echo CHtml::beginForm(
        Yii::app()->createUrl('innerTransfers/moveGood'),
        'POST',
        [
            'id' => 'move_good',
        ]
    ); ?>

    <?php echo CHtml::activeHiddenField($model, 'g_id', ['id' => 'g_id']); ?>
    <?php echo CHtml::activeHiddenField($model, 'shop_from', ['id' => 'shop_from']); ?>

    <div class="move_good_title">Переместить Пакет RG512</div>

    <div class="input_row">
        <span>Из магазина:</span>
        <span class="shop_from_name"></span>
    </div>

    <div class="input_row">
        <label for="shop_to" class="shop_to_label">В магазин:</label>
        <?php echo CHtml::activeDropDownList(
            $model,
            'shop_to',
            CHtml::listData(Shops::model()->findAll([
                'order' => 'shop_name',
                'condition' => 'kontr_id = ' . $model->kontr_id
            ]), 'shop_id', 'shop_name'),
            [
                'id' => 'shop_to',
                'prompt' => '',
                'class' => 'select_status'
            ]
        );
        ?>
    </div>
    <div class="input_row">
        <label for="move_qty" class="select_transfers_label">Колличество:</label>
        <?php echo CHtml::activeTextField($model, 'move_qty', ['id' => 'move_qty']); ?>
    </div>
    <?php echo CHtml::endForm(); ?>
</div>
<div id="dialog_move_good"></div>

<script>
    var phpVars = {
        'moveGoodAction': '<?php echo Yii::app()->createUrl('innerTransfers/moveGood');?>'
    };
</script>
