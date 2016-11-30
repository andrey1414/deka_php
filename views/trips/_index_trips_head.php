<script>
$(function() {
    var urlToUpdate;
    $('body').on('click', '.trips_link', function(event) {
        event.preventDefault();
        urlToUpdate = $(event.target).attr('href');
        var title = $(event.target).data('title');
        $.ajax({
            url: urlToUpdate,
            type: "GET",
            dataType: 'json',
            success: function(data){
                addForm(data.html, $('#form_trip'), title);
            },
            beforeSend: function(){
                $('.submit_button').attr('disabled', 'disabled');
                deka.showMessage('Загрузка формы.');
            },
            error: function(){
                $('.submit_button').attr('disabled', '');
                deka.showError('Произошла ошибка.');
            }
        });
    });
    $('#form_trip').click(function(event) {
        if ($(event.target).hasClass('trips_submit_button')) {
            event.preventDefault();
            $.ajax({
                url: urlToUpdate,
                type: "POST",
                dataType: 'json',
                data: $('#trips-form').serialize(),
                success: function(data){
                    if(data.status == 200) {
                        deka.showSuccess('Отправленно.');
                        window.location.reload();
                    } else {
                        deka.showError('Произошла ошибка.' + '<br>' + data.error);
                        $(event.target).removeAttr('disabled');
                    }
                },
                beforeSend: function(){
                    $(event.target).attr('disabled', 'disabled');
                    deka.showMessage('Загрузка формы.');
                },
                error: function(data){
                    $(event.target).removeAttr('disabled');
                    deka.showError('Произошла ошибка.');
                }
            });
        }
    });

    //принимает html и jquery элемент к которому нужно присоеденить html
    function addForm(html, $form_container, title) {
        $form_container.html(html);
        $form_container.dialog({
            modal: true,
            resizable: false,
            title: title,
            width: 1200,
            closeText: 'Закрыть',
            dialogClass: 'trips_dialog'
        });
    }

    $(function() {
        $('.delete_trip_link').click(function(event) {
            event.preventDefault();
            $.ajax({
                url: $(this).attr('href'),
                type: "GET",
                success: function(data){
                    if(!data) {
                        $(event.target).closest('tr').hide('slow');
                        deka.showSuccess('Поездка удалена.');
                    } else {
                        deka.showError('Произошла ошибка. ' + '<br>' + data);
                    }
                },
                beforeSend: function(){
                    deka.showMessage('Идет удаление');
                },
                error: function(data){
                    deka.showError('Произошла ошибка.');
                }
            });
        });
    });

    $('.executed_trips_title').click(function() {
        $('.executed_trips').slideToggle();
    });
});
</script>

<button href="<?= Yii::app()->createUrl('trips/createAndUpdate'); ?>" id="create_trip_link" class="trips_link" data-title="Заказать поездку">Заказать поездку</button>

<div id="form_trip"></div>

<?php
/*legend*/
$statuses = TripsStatus::model()->findAll();
?>
<style>
    <?php
    foreach($statuses as $status) {
        echo '.status' . $status->id . ' { background-color: ' . $status->color . '; } ';
    }
        echo '.priority_important' . ' { background-color: ' . Trips::PRIORITY_IMPORTANTLY . '; } ';
    ?>
</style>
<div class="legend">
    <?php
    $i=1;

    foreach($statuses as $status) {
        ?>
        <div class="legend_block">
            <div class="legend_block_color <?php echo 'status' . $status->id ?>"><?php echo $status->name?></div>
        </div>
        <?php
    }
    ?>

    <div class="legend_block">
        <div class="legend_block_color priority_important">Обязательно</div>
    </div>
</div>

<ul class="controll_buttons">
    <li class="controll_button">
        <div class="controll_button_icon controll_icon_edit"></div>
        <div class="controll_button_text">- <span class="controll_title">"Редактировать заявку".</span> Кнопка, для редактирования своей заявки </div>
    </li>
    <li class="controll_button">
        <div class="controll_button_icon controll_icon_trip"></div>
        <div class="controll_button_text">- <span class="controll_title">"Добавить информацию о поезде".</span> Кнопка, для добавления и изменения информации о поездке</div>
    </li>
    <li class="controll_button">
        <div class="controll_button_icon controll_icon_delete"></div>
        <div class="controll_button_text">- <span class="controll_title">"Удалить заявку".</span> Кнопка, для удаляения заявки </div>
    </li>
    <?php if(Yii::app()->user->isSecretary()):?>
        <li class="controll_button">
            <div class="controll_button_icon controll_icon_affirmation"></div>
            <div class="controll_button_text">- <span class="controll_title">"Утвердить заявку".</span> Кнопка, для утверждения заявки </div>
        </li>
        <li class="controll_button">
            <div class="controll_button_icon controll_icon_text">+</div>
            <div class="controll_button_text">- <span class="controll_title">"Показания одометра - верны".</span> Кнопка, для подтверждения правильности показаний одометра </div>
        </li>
        <li class="controll_button">
            <div class="controll_button_icon controll_icon_text">-</div>
            <div class="controll_button_text">- <span class="controll_title">"Показания спидометра - не верны".</span> Кнопка, для указания не правильности показаний одометра </div>
        </li>
    <?php endif; ?>
</ul>