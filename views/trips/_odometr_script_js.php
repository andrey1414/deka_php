<script>
    $(function() {
        $('.odometr_right, .odomet_wrong').click(function(event) {
            event.preventDefault(false);
            $.ajax({
                url: $(event.target).attr('href'),
                type: "GET",
                success: function(data){
                    if(!data) {
                        deka.showSuccess('Отправленно.');
                        window.location.reload();
                    } else {
                        deka.showError('Произошла ошибка.' + '<br>' + data);
                    }
                },
                beforeSend: function(){
                    deka.showMessage('Загрузка формы.');
                },
                error: function(data){
                    deka.showError('Произошла ошибка.');
                }
            });
        });
    });
</script>