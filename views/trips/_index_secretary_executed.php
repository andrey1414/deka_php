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
    ],
    'seller.lastname',
    'place',
    'target',
    [
        'name' => 'Утвержденное время начала поездки',
        'type' => 'raw',
        'value' => '$data->getDisplayExecutionDate()',
    ],
    [
        'name' => 'Водитель',
        'type' => 'raw',
        'value' => 'isset($data->driver->lastname) ? $data->driver->lastname : ""',
    ],
    'car.model',
    [
        'name' => 'Дата окончания поездки',
        'type' => 'raw',
        'value' => '$data->getDisplayTimeEndTrip()',
    ],
    'speedometer_start',
    'speedometer_end',
    'consumption_liters',
    [
        'name' => '',
        'type' => 'raw',
        //2 проверки если указанно для водителя и статус актвиная заявка
        'value' => '$data->isDriver() && $data->isUnconfirmedTrip() ? $data->getEndTripLink() : "" ',
    ],
];
?>
<div class="active_trips_title trips_title executed_trips_title">Выполненные поездки:</div>
<?php
$this->getGridView($data['dataProvider'], $columns, '$data->priority && $data->isNewTrip() ? "priority_important" : "status" . $data->status', '', 'executed_trips');