<?php

Yii::import('zii.widgets.grid.CDataColumn');

/*
 * Для вывода заголовков ko_from(Откуда).
 *
 * */
class TitleColumn extends CDataColumn
{
    protected $data = '';

    /**
     * Count values of the columns for footer
     *
     * @param $row
     * @param $data
     * @return mixed
     */
    protected function renderDataCellContent($row, $data)
    {
        $this->data = $data;

        //custom title, will display if unique
        $kontrName = isset($this->data->koFrom->kontr_name) ? $this->data->koFrom->kontr_name : '';
        if (!in_array($kontrName, GoodsTransferModel::model()->titles)) {
            echo '<div class="column_title" data-koFrom="' . $this->data->ko_from . '">' . $kontrName . '</div>';
            GoodsTransferModel::model()->titles[] = $this->data->koFrom->kontr_name;
        }
        return $data;
    }
}