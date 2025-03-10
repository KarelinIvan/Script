<?php
if (!defined('sugarEntry') || !sugarEntry)
	die('Not A Valid Entry Point');


class SimpleReportManagersTransactions extends SimpleReport
{
    protected $_tables_metadata = array(
        'default' => array(
            'NAMESPACE' => 'default',
            'PAGINATION' => true,
            'FIELDS' => array(
                array('name' => 'name_manager', 'label' => 'Менеджер', 'width' => '5', 'sort' => false),
                array('name' => 'quantity_transactions', 'label' => 'Количество совершенных сделок', 'width' => '5', 'sort' => false),
                array('name' => 'sum_transactions', 'label' => 'Сумма, руб', 'width' => '5', 'sort' => false),
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

        // Получаем список сделок по образцам менеджеров за выбранную дату
        $sql = "SELECT opportunities.id AS opp_id,
                        opportunities.assigned_user_id AS user_id,
                        CONCAT(first_name,' ', last_name) AS full_name,
                        COUNT(opportunities.amount) AS count_amount,
                        ROUND(SUM(opportunities.amount),2) AS total_amount
                FROM opportunities
                LEFT JOIN users ON users.id = opportunities.assigned_user_id
                WHERE opportunities.date_closed BETWEEN '$date_from' AND '$date_to'
                AND opportunities.payment_form = 'Cash'
                AND opportunities.name LIKE '%Образцы%'
                AND opportunities.sales_stage = 'Closed Won'
                AND opportunities.assigned_user_id IN (" . $assigned_user_str . ")
                GROUP BY full_name
                HAVING total_amount > 0";

//        echo '<pre>'; var_dump($sql);

        $que = dbGetArray($sql);


        $manager_link = "index.php?module=Dashboard&action=transactions&user_id=";


        foreach ($que as $acc) {
            $date_from = date("Y-m-d",strtotime($date_from));
            $date_to = date("Y-m-d",strtotime($date_to));
            $linkWithDates = $manager_link . $acc['user_id'] . '&date_from=' . $date_from . '&date_to=' . $date_to;
//            echo '<pre>'; var_dump($linkWithDates);
            $data[] = array(
                'name_manager' => $acc['full_name'],
                'quantity_transactions' => "<a href=" . $linkWithDates . " target='_blank'>" .$acc['count_amount']. "</a>",
                'sum_transactions'  => $acc['total_amount']
            );

            $sum_amount += round($acc['total_amount'],2);
            $sum_team_id = "Итого: ";
        }


        $data[] = array(
            'sum_transactions'                  => "<b>".$sum_amount."</b>",
            'quantity_transactions'             => "<b>".$sum_team_id."</b>",
        );
        $table['DATA'] = $data;
        $table['TITLE'] = 'Отчёт сделок по образцам за выбранный период';

        if ($table['DATA']) return $table;
    }
}
