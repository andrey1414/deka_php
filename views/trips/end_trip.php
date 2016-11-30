<div class="form">
    <?php
    $form=$this->beginWidget('CActiveForm', array(
        'id'=>'trips-form',
        'enableAjaxValidation'=>false,
        'htmlOptions'=>array(
            'class'=>'update_trip_form',
        ),
    )); ?>
    <fieldset>
        <legend>Завершение поездки</legend>
        <div class="row">
            <div class="trip_label">
                <?php echo $form->labelEx($trip, 'speedometer_start'); ?>
            </div>
            <?php echo $form->textField($trip, 'speedometer_start', array('size' => 10, 'maxlength' => 7)); ?>
            <?php echo $form->error($trip, 'speedometer_start'); ?>
        </div>

        <div class="row">
            <div class="trip_label">
                <?php echo $form->labelEx($trip, 'speedometer_end'); ?>
            </div>
            <?php echo $form->textField($trip, 'speedometer_end', array('size' => 10, 'maxlength' => 7)); ?>
            <?php echo $form->error($trip, 'speedometer_end'); ?>
        </div>

        <div class="row">
            <div class="trip_label">
                <?php echo $form->labelEx($trip, 'consumption_liters'); ?>
            </div>
            <?php echo $form->textField($trip, 'consumption_liters', array('size' => 10, 'maxlength' => 10)); ?>
            <?php echo $form->error($trip, 'consumption_liters'); ?>
        </div>

        <p>Дата будет сгенерированна автоматически</p>
        <div class="clearfix"></div>
        <div class="row buttons trips_submit">
            <?php echo CHtml::submitButton('Завершить поездку', ['class' => 'trips_submit_button']); ?>
        </div>
    </fieldset>
    <?php
    $this->endWidget();
    ?>
</div><!-- form -->
