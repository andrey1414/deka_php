<?php

/**
 * This is the model class for table "trips".
 *
 * The followings are the available columns in table 'trips':
 * @property string $id
 * @property string $date
 * @property string $sellers_id
 * @property string $place
 * @property string $priority
 * @property string $target
 * @property string $status
 * @property string $execution_date
 * @property string $driver_id
 * @property string $car_id
 * @property string $speedometer_start
 * @property string $speedometer_end
 * @property string $time_start_trip
 * @property string $time_end_trip
 * @property string $consumption_liters
 */
class Trips extends CActiveRecord
{
    const TRIP_NEW = 1;
    const TRIP_ACTIVE = 2;
    const TRIP_EXECUTED = 3;
    const TRIP_CONFIRMED = 4; //подтвержденная
    const TRIP_UNCOMFIRMED = 5; //не подтвержденная

    const PRIORITY_IMPORTANTLY = 'rgba(0, 237, 92, 0.54)'; //приоритет важный

    const TRIP_USER = 'user';
    const TRIP_SECRETARY = 'secretary';

    //по умолчанию
    public $priority = 0;

    protected $trips_on_page = 20;

    public function relations()
    {
        return [
            //поле sellers_id связывается с sellers.id
            'seller'=>[self::HAS_ONE, 'SellersModel', ['id' => 'sellers_id']],
            'driver'=>[self::HAS_ONE, 'SellersModel', ['id' => 'driver_id']],
            'car'=>[self::HAS_ONE, 'AutoCars', ['id' => 'car_id']],
            'trip_status'=>[self::HAS_ONE, 'TripsStatus', ['id' => 'status']],
        ];
    }
    /**
     * Returns the static model of the specified AR class.
     * @param string $className active record class name.
     * @return Trips the static model class
     */
    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'trips';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            ['date, sellers_id, place, priority', 'required'],
            ['priority', 'in', 'range'=>[0,1]],
            ['date, place, target', 'filter', 'filter' => 'htmlspecialchars'],
            ['date, place, target', 'filter', 'filter' => 'trim'],
            ['sellers_id, priority, status, driver_id, car_id, speedometer_start, speedometer_end, consumption_liters', 'numerical', 'integerOnly'=>true],
            ['consumption_liters', 'length', 'max'=>4],
            ['date, execution_date, driver_id, car_id', 'required', 'on' => 'update_secretary'],
            ['speedometer_start, speedometer_end, consumption_liters', 'required', 'on' => 'end_trip'],
            ['sellers_id', 'exist', 'attributeName' => 'id', 'className' => 'SellersModel'],
            ['driver_id', 'exist', 'allowEmpty' => true, 'attributeName' => 'id', 'className' => 'SellersModel'],
            ['car_id', 'exist', 'attributeName' => 'id', 'className' => 'AutoCars', 'on' => 'update_secretary'],
            ['status', 'exist', 'attributeName' => 'id', 'className' => 'TripsStatus'],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id' => '№',
            'date' => 'Желаемая дата поездки',
            'sellers_id' => 'id заявителя',
            'place' => 'Пункт назначения',
            'priority' => 'Обязательно(высокий приоритет)',
            'target' => 'Цель',
            'status' => 'Статус',
            'execution_date' => 'Дата выполнения',
            'driver_id' => 'Водитель',
            'car_id' => 'Авто',
            'speedometer_start' => 'Одометр в начале',
            'speedometer_end' => 'Одометр в конце',
            'time_end_trip' => 'Дата окончания поездки',
            'consumption_liters' => 'Расход бензина(литров)',
            'car.model' => 'Авто',
            'seller.lastname' => 'Автор',
        ];
    }

    //максимальное значение одомоетра для автомобля.
    public function getMaxToCarSpeedometrValue() {

        return Yii::app()->db->createCommand()
            ->select('MAX(speedometer_end) as speedometer_end')
            ->from('trips')
            ->where('car_id = :car_id',
                [':car_id' => $this->car_id])
            ->queryScalar();
    }

    public function isNewTrip() {
        return Trips::TRIP_NEW == $this->status;
    }
    public function isActiveTrip() {
        return Trips::TRIP_ACTIVE == $this->status;
    }
    public function isExecutedTrip() {
        return Trips::TRIP_EXECUTED == $this->status;
    }
    public function isConfirmedTrip() {
        return Trips::TRIP_CONFIRMED == $this->status;
    }
    public function isUnconfirmedTrip() {
        return Trips::TRIP_UNCOMFIRMED == $this->status;
    }

    //Поездки сегодня
    public function searchTodayTrips() {
        return $this->getDataOfDataprovider([
            'condition' => 'DATE(t.date) = CURDATE() AND t.status IN('.Trips::TRIP_NEW.','.Trips::TRIP_ACTIVE.','.Trips::TRIP_EXECUTED.','.Trips::TRIP_UNCOMFIRMED.')'
        ]);
    }

    //будующие поездки
    public function searchFeauteTrips() {
        return $this->getDataOfDataprovider([
            'condition' => 'DATE(t.date) > CURDATE()'
        ]);
    }

    //активные поездки(пользователя)
    public function searchActiveTrips() {
        return $this->getDataOfDataprovider([
            'params' => [':userId' => Yii::app()->user->getSellerId( ! Yii::app()->user->isSeller() )],
            'condition' => '(sellers_id = :userId OR driver_id = :userId) AND t.status IN('.Trips::TRIP_NEW.','.Trips::TRIP_ACTIVE.','.Trips::TRIP_EXECUTED.','.Trips::TRIP_UNCOMFIRMED.')'
        ]);
    }

    //выполенные поздки(пользователя и секретаря)
    public function searchExecutedTrips($userRole='') {
        $criteria = new CDbCriteria();
        if ($userRole == Trips::TRIP_SECRETARY) {
            $criteria->condition = 't.status IN('.Trips::TRIP_CONFIRMED.')';
        } else {
            $criteria->params = [':userId' => Yii::app()->user->getSellerId(!Yii::app()->user->isSeller())];
            $criteria->condition = '(sellers_id = :userId OR driver_id = :userId) AND t.status IN(' . Trips::TRIP_CONFIRMED.')';
        }
        return $this->getDataOfDataprovider($criteria);
    }

    //Проверяет является ли текущий водитель, воделем выбранной поездки
    public function isDriver() {
        return $this->driver_id == Yii::app()->user->getSellerId( ! Yii::app()->user->isSeller() );
    }
    public function isAuthor() {
        return $this->sellers_id == Yii::app()->user->getSellerId( ! Yii::app()->user->isSeller() );
    }

    //вместо after find, т.к. при использовании after find по умолчанию в модель загружались не корректные данные даты при создании/редактировании.
    public function getDisplayDate() {
        return (new DateTime($this->date))->format('d-m-Y H:i');
    }
    public function getDisplayExecutionDate() {
        return !empty($this->execution_date) ? (new DateTime($this->execution_date))->format('H:i') : '';
    }
    public function getDisplayTimeEndTrip() {
        return !empty($this->time_end_trip) ? (new DateTime($this->time_end_trip))->format('d-m-Y H:i') : '';
    }

    public function getUpdateAuthorLink() {
        return CHtml::link("", Yii::app()->createUrl("trips/createAndUpdate",[ "id" => $this->id ]), [ "class" => "trips_link update_trip_link controll_icon_edit", "data-title" => "Редактировать"]);
    }
    public function getDeleteLink() {
        return CHtml::link("", Yii::app()->createUrl("trips/deleteTrip", [ "id" => $this->id ]), [ "class" => "btn-delete delete_trip_link controll_icon_delete"]);
    }
    public function getEndTripLink() {
        return CHtml::link("", Yii::app()->createUrl("trips/endTrip",[ "id" => $this->id ]),[ "class" => "trips_link trip_trip_link controll_icon_trip", "data-title" => "Редактировать"]);
    }
    public function getUpdateSecretaryLink() {
        return CHtml::link("", Yii::app()->createUrl("trips/updateSecretary",[ "id" => $this->id ]),[ "class" => "trips_link controll_button_icon controll_icon_affirmation", "data-title" => "Редактировать"]);
    }
    public function getOdometrRightLink() {
        return CHtml::link("+", Yii::app()->createUrl("trips/confirmationOdometr", [ "id" => $this->id, "is_confirmed" => "right" ]), [ "class" => "odometr_right"]);
    }
    public function getOdometrWrongLink() {
        return CHtml::link("-", Yii::app()->createUrl("trips/confirmationOdometr", [ "id" => $this->id, "is_confirmed" => "wrong" ]), [ "class" => "odomet_wrong"]);
    }

    //Добавляет данные в таблицу auto_tracking. В случае успеха возвращает boolean.
    public function addAutoTracking() {
        $q = "INSERT INTO auto_tracking (car_id, driver, ondate, km_start, km_stop, fuel_rate, description, date_add ) 
                VALUES (:car_id, :driver_id, :ondate, :km_start, :km_stop, :fuel_rate, :description, now())";
        $command = Yii::app()->db->createCommand($q);

        $command->bindValues([
            ':car_id' => $this->car_id,
            ':driver_id' => $this->driver_id,
            ':ondate' => $this->execution_date,
            ':km_start' => $this->speedometer_start,
            ':km_stop' => $this->speedometer_end,
            ':fuel_rate' => $this->consumption_liters,
            ':description' => 'Пункт назначения: '.$this->place.'. Цель: '.$this->target
        ]);

        if( $command->execute() )
            return true;
        return false;
    }

    protected function getDataOfDataprovider($criteria) {
        return new CActiveDataProvider('Trips',[
            'criteria'=>$criteria,
            'pagination' => [
                'pageSize' => $this->trips_on_page
            ],
            'sort'=>[
                'attributes' => [
                    'id',
                    'place',
                    'target',
                    'execution_date',
                    'speedometer_start',
                    'speedometer_end',
                    'time_start_trip',
                    'time_end_trip',
                    'date'=>[
                        'asc' => 'date ASC',
                        'desc' => 'date DESC',
                    ],
                ],
                'defaultOrder'=>[
                    'date'=>'DESC',
                ]
            ],
        ]);
    }
}