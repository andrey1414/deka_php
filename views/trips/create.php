<div class="form">
	<div class="form_error"></div>
	<?php $form=$this->beginWidget('CActiveForm', [
		'id'=>'trips-form',
		'htmlOptions'=>[
			'class'=>'create_form',
		],
		'enableAjaxValidation'=>false,
	]); ?>
	<?php echo $form->errorSummary($model); ?>
	<fieldset>
		<legend>Новая поездка</legend>
		<div class="row">
			<div class="trip_label">
				<?php echo $form->labelEx($model,'date'); ?>
			</div>
			<?php echo $form->textField($model,'date', ['id' => 'datetimepicker', 'value' => (new DateTime($model->date))->format('d-m-Y H:i')] ); ?>
			<?php echo $form->error($model,'date'); ?>
		</div>
		<div class="row">
			<div class="trip_label">
				<?php echo $form->labelEx($model,'place'); ?>
			</div>
			<?php echo $form->textField($model,'place',['size'=>60,'maxlength'=>70, 'autofocus' => 'autofocus', 'class' => 'input_palce']); ?>
			<?php echo $form->error($model,'place'); ?>
		</div>

		<div class="row">
			<div class="trip_label">
				<?php echo $form->labelEx($model,'priority'); ?>
			</div>
			<?php
			echo $form->checkBox($model,'priority');
			echo $form->error($model,'target');
			?>
		</div>
		<div class="row">
			<div class="trip_label">
				<?php echo $form->labelEx($model,'target'); ?>
			</div>
			<?php echo $form->textField($model,'target',['size'=>60,'maxlength'=>70, 'class' => 'input_target']); ?>
			<?php echo $form->error($model,'target'); ?>
		</div>
	</fieldset>

	<div class="row buttons trips_submit">
		<?php echo CHtml::submitButton($model->isNewRecord ? 'Добавить' : 'Обновить', ['class' => 'trips_submit_button']); ?>
	</div>
	<?php $this->endWidget(); ?>
</div><!-- form -->

<?php echo $this->renderPartial('_form_js'); ?>