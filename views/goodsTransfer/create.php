<?php $kontragentList = CHtml::listData(
    KontragentModel::model()->findAll(['order' => 't.kontr_name ASC']),
    'kontr_id',
    'kontr_name'
); ?>

<?php $form = $this->beginWidget('CActiveForm', [
    'id' => 'form_add_transfer',
    'enableAjaxValidation' => false,
    'htmlOptions' => [
        'method' => 'POST',
        'class' => 'hidden ui-dialog-content ui-widget-content form_transfer'
    ]
]); ?>
<?php echo $form->errorSummary($model); ?>

<div class="row">
    <label for="ko_from">
        <?php echo $model->getAttributeLabel('ko_from') . ':'; ?>
    </label>

    <?php echo $form->dropDownList(
        $model,
        'ko_from',
        $kontragentList,
        [
            'id' => 'ko_from',
            'prompt' => '',
        ]
    ); ?>
</div>
<div class="row">
    <label for="ko_to">
        <?php echo $model->getAttributeLabel('ko_to') . ':'; ?>
    </label>
    <?php echo $form->dropDownList(
        $model,
        'ko_to',
        $kontragentList,
        [
            'id' => 'ko_from',
            'prompt' => '',
        ]
    ); ?>

</div>
<div class="row">
    <label for="g_id">Товар</label>
    <?php echo $form->textField($model, 'g_id', ['placeholder' => 'Код товара', 'value' => '', 'g_id']); ?>,
    <?php echo $form->textField($model, 'qty', ['value' => '']); ?>шт.
</div>
<div class="row">
    <label for="trans_id">Номер проводки:</label>
    <?php echo $form->textField($model, 'trans_id', ['id' => 'trans_id']); ?>
</div>
<div class="row">
    <label for="description">
        <?php echo $model->getAttributeLabel('description') . ':'; ?>
    </label>
    <?php echo $form->textArea($model, 'description', ['id' => 'description']); ?>
</div>
<div>
    <div class="row buttons">
        <?php echo CHtml::submitButton('Добавить', ['class' => 'button_create_transfer', 'data-title' => 'Добавить перемещение.']); ?>
    </div>
</div>

<?php $this->endWidget(); ?>

<style>
    label {
        display: block;
    }
</style>
