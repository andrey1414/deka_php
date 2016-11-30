<?php
$columns = [
    [
        'name' => '№',
        'type' => 'raw',
        'value' => '$row+1+$this->grid->dataProvider->getPagination()->offset',
    ],
    [
        'name' => 'date',
        'type' => 'raw',
        'value' => '$data->getDisplayDate()',
        'sortable' => true
    ],
    [
        'name' => 'Автор заявки',
        'type' => 'raw',
        'value' => '$data->isAuthor() ? $data->seller->lastname : ""',
    ],
    'place',
    'target',
    [
        'name' => 'Водитель',
        'type' => 'raw',
        'value' => '$data->isDriver() && isset($data->driver->lastname) ? $data->driver->lastname : ""',
    ],
    [
        'name' => 'Время выполнения',
        'type' => 'raw',
        'value' => '$data->isDriver() ? $data->getDisplayExecutionDate() : ""',
    ],
    [
        'name' => 'Авто',
        'type' => 'raw',
        'value' => '$data->isDriver() ? $data->car->model : ""',
    ],
    [
        'name' => 'Дата окончания поездки',
        'type' => 'raw',
        'value' => '$data->isDriver() ? $data->getDisplayTimeEndTrip() : ""',
    ],
    [
        'name' => 'Одометр в начале поездки',
        'type' => 'raw',
        'value' => '$data->isDriver() ? $data->speedometer_start : ""',
    ],
    [
        'name' => 'Одометр в конце поездки',
        'type' => 'raw',
        'value' => '$data->isDriver() ? $data->speedometer_end : ""',
    ],
    [
        'name' => 'Раход бензина(литров)',
        'type' => 'raw',
        'value' => '$data->isDriver() && !empty($data->consumption_liters) ? $data->consumption_liters : ""',
    ],
    [
        'name' => '',
        'type' => 'raw',
        //2 проверки если указанно для водителя и статус актвиная заявка
        'value' => '$data->isDriver() && $data->isUnconfirmedTrip() ? $data->getEndTripLink() : ""',
    ],
];
?>
<div class="active_trips_title trips_title executed_trips_title">Выполненные поездки:</div>
<?php
$this->getGridView($data['dataProvider'], $columns, '"status" . $data->status', '', 'executed_trips');