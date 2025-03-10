<?php
if (!defined('sugarEntry') || !sugarEntry)
	die('Not A Valid Entry Point');


class SimpleReportOppCash extends SimpleReport
{
    protected $_tables_metadata = array(
        'default' => array(
            'NAMESPACE' => 'default',
            'PAGINATION' => true,
            'FIELDS' => array(
                array('name' => 'date_cash', 'label' => 'Дата закрытия', 'width' => '5', 'sort' => false),
                array('name' => 'sum_cash', 'label' => 'Выручка за наличные, руб', 'width' => '5', 'sort' => false),
            ),
            'DATA' => array(),
        ),
    );
    protected $_export = true;

    protected function buildFilterForm($request = null)
        /**
         * Создает и подготавливает форму фильтра для отчета.
         *
         * Эта функция настраивает форму фильтра, инициализируя значения по умолчанию,
         * загрузка необходимых зависимостей и подготовка полей диапазона дат.
         *
         * @param array|null $request Данные запроса. Если значение равно нулю, будет использоваться $_REQUEST.
         * @return array Подготовленный фильтр формирует данные.
         */
    {
        global $current_user, $app_list_strings, $current_language;

        if ($request === null)
            $request = &$_REQUEST;

        $form = parent::buildFilterForm($request);
        $bean = new Opportunity();

        require_once("modules/Accounts/Account.php");

        if (!isset($request['date_from']))
            $request['date_from'] = '';
        if (!isset($request['date_to']))
            $request['date_to'] = '';

        return $form;
    }

    protected function buildParams()
    {
        $params = parent::buildParams();
        return $params;
    }


    public function getTables()
    {
        global $app_list_strings;
        global $locale, $timedate;
        global $current_user;

        $assigned_user_str = "'" . implode("','", $this->getParam('user_id')) . "'";

        $table = $this->getTableMeta('default');

        $date_from = date("Y-m-d 00:00:00",strtotime($this->getParam("date_from")));
        $date_to = date("Y-m-d 23:59:59",strtotime($this->getParam("date_to")));

        $sql = "SELECT ROUND(SUM(amount),2) as amount,
                       date_closed as date
                FROM opportunities
                WHERE payment_form = 'Cash' 
                AND sales_stage = 'Closed Won' 
                AND date_closed BETWEEN '$date_from' AND '$date_to'
                AND opportunities.assigned_user_id IN (" . $assigned_user_str . ")
                GROUP BY date_closed
                ";

        //echo '<pre>'; var_dump($sql);

        $que = dbGetArray($sql);

        foreach ($que as  $acc) {
            $data[] = array(
                'date_cash' => $acc['date'],
                'sum_cash'  => $acc['amount']
            );

            $sum_amount += round($acc['amount'],2);
            $sum_team_id = "Итого: ";
        }

        $data[] = array(
            'sum_cash'      => "<b>".$sum_amount."</b>",
            'date_cash'     => "<b>".$sum_team_id."</b>",
        );
        $table['DATA'] = $data;
        $table['TITLE'] = 'Выручка по закрытым сделкам, оформленным за наличный расчет в разбивке по дням';

        if ($table['DATA']) return $table;
    }
}
