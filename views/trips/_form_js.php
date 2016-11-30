<script>
($(function() {
    $.datetimepicker.setLocale('ru');
    $(".input_palce").autocomplete({
        source: "<?php echo yii::app()->createUrl('trips/getPlaces'); ?>",
        minLength: 2
    });
    $(".input_target").autocomplete({
        source: "<?php echo yii::app()->createUrl('trips/getTargets'); ?>",
        minLength: 2
    });

    $('#datetimepicker, #datetimepicker2').datetimepicker({
        step:10,
        format:'d-m-Y H:i',
        lang:'ru'
    });
}));
</script>