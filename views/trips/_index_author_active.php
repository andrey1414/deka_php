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
    //если не автор, скрыто.
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
        'value' => '!empty($data->driver->lastname) ? $data->driver->lastname : ""',
    ],
    [
        'name' => 'Дата выполнения',
        'type' => 'raw',
        'value' => '$data->getDisplayExecutionDate()',
    ],
    [
        'name' => 'Авто',
        'type' => 'raw',
        'value' => '!empty($data->car->model) ? $data->car->model : ""',
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
        'name' => 'Расход бензина(литров)',
        'type' => 'raw',
        'value' => '$data->isDriver() && !empty($data->consumption_liters) ? $data->consumption_liters : ""',
    ],
    [
        'name' => '',
        'type' => 'raw',
        'value' => '
            "<div class=\"td_icons_wrap\">" .
            ($data->isNewTrip() ? $data->getUpdateAuthorLink() : "" ) .
            ($data->isDriver() && ($data->isActiveTrip() || $data->isExecutedTrip()) ? $data->getEndTripLink() : "" ) .
            ($data->isNewTrip() ? $data->getDeleteLink() : "" ) .
            ($data->isDriver() && $data->isUnconfirmedTrip() ? $data->getEndTripLink() : "" ) .
            "</div>"',
        'cssClassExpression'=>'"td_icons"',
    ]
];
?>
<div class="active_trips_title trips_title">Активные поездки:</div>
<?php
//добавление статуса. Если приоритет указан и это новая заявка, добавле соотвествующдего цвета для приоритета, в другом случае, цвет для статуса
$this->getGridView($data['dataProvider'], $columns, '($data->isNewTrip() && $data->priority) ? "priority_important" : "status" . $data->status', 'Вы не добавили поездок');