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
    [
        'name' => '',
        'type' => 'raw',
        'value' => '
            "<div class=\"td_icons_wrap\">" .
            ( $data->isNewTrip() && $data->isAuthor() ? $data->getUpdateAuthorLink() : "" ) .
            ( $data->isNewTrip() && $data->isAuthor() ? $data->getDeleteLink() : "" ) .
            ( $data->isDriver() && ($data->isActiveTrip() || $data->isExecutedTrip()) ? $data->getEndTripLink() : "" ) .
            ( $data->isNewTrip() || $data->isActiveTrip() ? $data->getUpdateSecretaryLink() : ""  ) .
            ( $data->isExecutedTrip() || $data->isUnconfirmedTrip()  ? $data->getOdometrRightLink() : "" ) .
            ( $data->isExecutedTrip() ? $data->getOdometrWrongLink() : "" ) .
            ($data->isDriver() && $data->isUnconfirmedTrip() ? $data->getEndTripLink() : "" ) .
            "</div>"',
        'cssClassExpression'=>'"td_icons"'
    ],
];
?>

<div class="active_trips_title trips_title">Поездки сегодня:</div>

<?php
$this->getGridView($data['dataProvider'], $columns, '$data->priority && $data->isNewTrip() ? "priority_important" : "status" . $data->status');