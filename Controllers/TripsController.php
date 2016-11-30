<?php

class TripsController extends RetailProtectedController
{
    const GRID_CSS = 'css/trips_grid.css';

    public function actionIndex()
    {
        //css,js
        if(! Yii::app()->request->isAjaxRequest) {
            Yii::app()->clientScript->registerCssFile('css/datetimepicker.min.css');
            Yii::app()->clientScript->registerScriptFile('js/datetimepicker.full.min.js');
        }

        //передаем данные в view, по умочанию value указываем как массив, т.к. необходимо чтоб key был равен имени файла.
        //передача в search role пользователя, т.к. нужно для методов модели.
        if(Yii::app()->user->isSecretary()) {
            $this->renderText($this->renderArray([
                '_index_trips_head' => [],
                '_index_secretary_today' => ['dataProvider' => Trips::model()->searchTodayTrips() ],
                '_index_secretary_feature' => ['dataProvider' => Trips::model()->searchFeauteTrips() ],
                '_index_secretary_executed' => ['dataProvider' => Trips::model()->searchExecutedTrips(Trips::TRIP_SECRETARY) ],
                '_odometr_script_js'  => []
            ]));
        } else {
            $this->renderText($this->renderArray([
                '_index_trips_head' => [],
                '_index_author_active' => ['dataProvider' => Trips::model()->searchActiveTrips() ],
                '_index_author_executed' => ['dataProvider' =>  Trips::model()->searchExecutedTrips() ],
            ]));
        }
    }
    //Передача вложенного массива, где каждый массив:
    // key = имя файла,
    // значение массива - массив данных для view
    public function renderArray($fileNames) {
        $result = '';

        foreach($fileNames as $fileName => $arrayValues)
            $result .= $this->renderPartial($fileName, ['data' => $arrayValues], TRUE);
        return $result;
    }

    /**
     * Creates a new model.
     * If creation is successful, the browser will be redirected to the 'index' page.
     */
    public function actionCreateAndUpdate($id=0)
    {
        //если id указан, значит редактирование поездки, загрузка модели по id, если не указан, загрузка пустой модели и указываем приоритет '', т.к. по умолчанию 1
        if($id) {
            $model = $this->loadModel( (int)$id );
        } else {
            $model = new Trips;
        }

        if(!empty($_POST['Trips'])) {

            //Если это не новая запись и если не достаточно прав, выбрасываем исключение
            if(!$model->isNewRecord && (!$model->isAuthor() || !Yii::app()->request->isAjaxRequest || !$model->isNewTrip()))
                throw new CHttpException(404,'Недостаточно прав для доступа');

            $model->attributes=$_POST['Trips'];
            $model->sellers_id = Yii::app()->user->getSellerId( ! Yii::app()->user->isSeller( Yii::app()->user->id) );

            $model->date = (new Deka\DateTime($model->date))->formatDBDateTime();

            if($model->save()) {
                echo json_encode(['status' => 200]);
                return;
            } else {
                //Если ошибка, записываем в буфер.
                echo json_encode(['status' => 400, 'error' => current( current( $model->getErrors() ) ) ]);
                return;
            }
        } else {
            echo json_encode( [ 'status' => 200, 'html' => $this->renderPartial('create',['model'=>$model], TRUE) ] );
        }
    }

    public function actionUpdateSecretary($id) {
        if( !Yii::app()->user->isSecretary() || !Yii::app()->request->isAjaxRequest)
            throw new CHttpException(404,'Указанная поездка не найдена или недостаточно прав для доступа');

        $trip = $this->loadModel( (int)$id );
        $trip->scenario = 'update_secretary';

        if( !( $trip->isNewTrip() || $trip->isActiveTrip()) )
            throw new CHttpException(404, 'Недостаточно прав для доступа');

        if(!empty($_POST['Trips'])) {
            $trip->attributes = $_POST['Trips'];

            $trip->date = (new Deka\DateTime($trip->date))->formatDBDateTime();

            $trip->execution_date = (new Deka\DateTime($trip->execution_date))->formatDBDateTime();

            //если указанны дата и id водителя, статус "Активный"
            if($trip->execution_date && $trip->driver_id)
                $trip->status = Trips::TRIP_ACTIVE;

            if( $trip->save() ) {
                echo json_encode(['status' => 200]);
                return;
            } else {
                //Если ошибка.
                echo json_encode(['status' => 400, 'error' => current( current( $trip->getErrors() ) ) ]);
                return;
            }
        } else {
            //форма
            echo json_encode( [ 'html' => $this->renderPartial('update_secretary',['trip' => $trip], TRUE) ] );
        }
    }

    /**
     * Deletes a particular model.
     * If deletion is successful, the browser will be redirected to the 'admin' page.
     * @param integer $id the ID of the model to be deleted
     */
    public function actionDeleteTrip($id)
    {
        $trip = $this->loadModel( (int) $id );
        //поездка найденна и пользователь - водитель поездки., заявка - новая
        if (!$trip->isAuthor() || !Yii::app()->request->isAjaxRequest || !$trip->isNewTrip())
            throw new CHttpException(404, 'Недостаточно прав для доступа');
        $trip->delete();
    }
    public function actionEndTrip($id) {

        $trip = $this->loadModel( (int) $id );
        $trip->scenario = 'end_trip';

        //Пользователь - водитель поездки. Статус поездки: активная, выполненная, завершенная(данные не корректны)
        if( !$trip->isDriver() || !Yii::app()->request->isAjaxRequest || !($trip->isActiveTrip() || $trip->isExecutedTrip() || $trip->isUnconfirmedTrip()) )
            throw new CHttpException(404,'Недостаточно прав для доступа');

        //Значение должно быть не меньше минимального для данного авто и дата выполнения должна быть меньше чем дата выполнения данной поездки
        if(!empty($_POST['Trips'])) {

            if($_POST['Trips']['speedometer_start'] > $_POST['Trips']['speedometer_end']) {
                echo json_encode(['status' => 400, 'error' => 'Значение одометра в конце позедки, меньше значения в начале поездки.' ]);
                return;
            }

            $trip->attributes = $_POST['Trips'];
            //текущая дата и время
            $trip->time_end_trip = new CDbExpression('NOW()');
            $trip->status = Trips::TRIP_EXECUTED;

            if( $trip->save() ) {
                echo json_encode(['status' => 200]);
                return;
            } else {
                //Если ошибка, записываем в буфер.
                echo json_encode(['status' => 400, 'error' => current( current( $trip->getErrors() ) ) ]);
                return;
            }
        } else {
            //если данные не указанны
            if( !isset($trip->speedometer_start) )
                $trip->speedometer_start = $trip->getMaxToCarSpeedometrValue();
            //форма
            echo json_encode( [ 'html' => $this->renderPartial('end_trip',['trip' => $trip], TRUE) ] );
        }
    }

    //значение right = одометр подтвержден
    //значение wrong - одометр не подтвержден
    public function actionConfirmationOdometr($id, $is_confirmed) {

        $trip = $this->loadModel( (int) $id );
        //может отправялть запрос если заявка выполенная или выполненная(не подтвержденная)
        if( !Yii::app()->user->isSecretary() || !Yii::app()->request->isAjaxRequest || !( $trip->isExecutedTrip() || $trip->isUnconfirmedTrip() ))
            throw new CException('Недостаточно прав');

        $trip->status = $is_confirmed == 'right' ? Trips::TRIP_CONFIRMED : Trips::TRIP_UNCOMFIRMED;

        if( ! ($trip->save() && $trip->addAutoTracking()) )
            echo current( current( $trip->getErrors() ) );
    }

	//autocomplite
    public function actionGetPlaces($term) {
        if(!Yii::app()->request->isAjaxRequest)
            throw new CException('Попытка прямого доступа');

        $places = Yii::app()->db->createCommand()
                ->selectDistinct('place')
                ->from('trips')
                ->where('place LIKE :place AND sellers_id = :sellers_id',
                        [':place' => '%'.CHtml::encode( $term ).'%', ':sellers_id' => Yii::app()->user->getSellerId( ! Yii::app()->user->isSeller() )])
                ->queryColumn();

        echo CJSON::encode($places);
    }

    //autocomplite
    public function actionGetTargets($term) {
        if(!Yii::app()->request->isAjaxRequest)
            throw new CException('Попытка прямого доступа');

        $targets = Yii::app()->db->createCommand()
            ->selectDistinct('target')
            ->from('trips')
            ->where('target LIKE :target AND sellers_id = :sellers_id',
                [':target' => '%'.CHtml::encode($term).'%', ':sellers_id' => Yii::app()->user->getSellerId( ! Yii::app()->user->isSeller() )])
            ->queryColumn();

        echo CJSON::encode($targets);
    }


    public function getGridView($dataProvider, $columns, $rowCssClassExpression='', $emptyText = 'Поездки не найденны', $cssClass='') {
        return $this->widget('zii.widgets.grid.CGridView', [
            'dataProvider'=>$dataProvider,
            'columns'=>$columns,
            'rowCssClassExpression' => $rowCssClassExpression,
            'emptyText' => $emptyText,
            'htmlOptions' => ['class' => 'grid-view trip_table '.$cssClass],
            'cssFile' => Yii::app()->request->baseUrl.'/'.self::GRID_CSS,
        ]);
    }

    /**
     * Returns the data model based on the  rimary key given in the GET variable.
     * If the data model is not found, an HTTP exception will be raised.
     * @param integer $id the ID of the model to be loaded
     * @return Trips the loaded model
     * @throws CHttpException
     */

    public function loadModel($id)
    {
        $model=Trips::model()->findByPk($id);

        if($model===null)
            throw new CHttpException(404,'The requested page does not exist.');
        return $model;
    }
}