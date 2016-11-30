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
    [
        'name' => '',
        'type' => 'raw',
        'value' => '"<div class=\"td_icons_wrap\">".
            ( $data->isNewTrip() && $data->isAuthor() ? $data->getUpdateAuthorLink() : "" ) .
            ( $data->isNewTrip() && $data->isAuthor() ? $data->getDeleteLink() : "" ) .
            ( $data->isDriver() && ($data->isActiveTrip() || $data->isExecutedTrip()) ? ( $data->getEndTripLink ) : "" ) .
            ( $data->isNewTrip() || $data->isActiveTrip() ? $data->getUpdateSecretaryLink() : ""  ) .
            "</div>"
        ',
        'cssClassExpression'=>'"td_icons"'
    ],
];
?>

<div class="active_trips_title trips_title">Поездки в будующем:</div>
<?php
$this->getGridView($data['dataProvider'], $columns, '$data->priority ? "priority_important" : "status" . $data->status');