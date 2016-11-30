<div class="autor_application">Автор заявки: <?php echo $trip->seller->lastname ?></div>

<div class="form trip_form">
    <?php
    $form=$this->beginWidget('CActiveForm', [
        'id'=>'trips-form',
        'enableAjaxValidation'=>false,
        'htmlOptions'=>[
            'class'=>'update_trip_form',
        ],
    ]);
    echo $form->errorSummary($trip);
    ?>
    <fieldset>
        <legend>Поездка</legend>
        <div class="row">
            <div class="trip_label">
                <?php echo $form->labelEx($trip, 'date'); ?>
            </div>
            <?php echo $form->textField($trip, 'date', ['id' => 'datetimepicker', 'value' => date('d-m-Y H:i', strtotime($trip->date) )] ); ?>
            <?php echo $form->error($trip, 'date'); ?>
        </div>
        <div class="row">
            <div class="trip_label">
                <span class="label_text"><?php echo Trips::model()->getAttributeLabel('place') . ':</span> ' . $trip->place ?>
            </div>
        </div>

        <div class="row">
            <div class="trip_label">
                <span class="label_text"><?php $trip->priority ? 'Приоритет:</span> Очень высокий ' : '' ?>
            </div>
        </div>

        <div class="row">
            <div class="trip_label">
                <span class="label_text"><?php echo Trips::model()->getAttributeLabel('target') . ':</span> ' . $trip->target ?>
            </div>
        </div>
    </fieldset>
    <fieldset>
        <legend>Информация о поездке</legend>
        <div class="row">
            <div class="trip_label">
                <?php echo $form->labelEx($trip, 'execution_date'); ?>
            </div>
            <?php
            echo $form->textField($trip,'execution_date', ['id' => 'datetimepicker2', 'value' => !empty($trip->execution_date) ? (new DateTime($trip->execution_date))->format('d-m-Y H:i') : (new DateTime($trip->date))->format('d-m-Y H:i')]);
            ?>
            <?php echo $form->error($trip, 'execution_date'); ?>
        </div>

        <div class="row">
            <div class="trip_label">
                <?php echo $form->label($trip, 'driver_id'); ?>
            </div>
            <?php echo $form->dropDownList($trip,'driver_id', CHtml::listData(SellersModel::model()->findAll( ['condition'=>'t.div_id IS NOT NULL', 'order'=>'t.lastname'] ), 'id', 'lastname')); ?>
            <?php echo $form->error($trip, 'driver_id'); ?>
        </div>

        <div class="row">
            <div class="trip_label">
                <?php echo $form->labelEx($trip, 'car_id'); ?>
            </div>
            <?php echo $form->dropDownList($trip, 'car_id', CHtml::listData(AutoCars::model()->findAll(), 'id', 'model'), ['autofocus' => 'autofocus']); ?>
            <?php echo $form->error($trip, 'car_id'); ?>
        </div>
    </fieldset>
    <div class="clearfix"></div>
    <div class="row buttons trips_submit">
        <?php echo CHtml::submitButton('Утвердить', ['class' => 'trips_submit_button']); ?>
    </div>
    <?php
    $this->endWidget();
    ?>
</div><!-- form -->

<?php echo $this->renderPartial('_form_js'); ?>