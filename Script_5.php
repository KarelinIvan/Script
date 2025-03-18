<?php
if (!defined('sugarEntry') || !sugarEntry)
	die('Not A Valid Entry Point');


class SimpleReportDeliverySDEK extends SimpleReport
{
    protected $_tables_metadata = array(
        'default' => array(
            'NAMESPACE' => 'default',
            'PAGINATION' => true,
            'FIELDS' => array(
                array('name' => 'teams', 'label' => 'Отдел продаж', 'width' => '5', 'sort' => false),
                array('name' => 'recipient_city', 'label' => 'Город получателя', 'width' => '5', 'sort' => false),
                array('name' => 'issue_date', 'label' => 'Дата оформления заказа', 'width' => '5', 'sort' => false),
                array('name' => 'order_amount', 'label' => 'Сумма заказа', 'width' => '5', 'sort' => false),
                array('name' => 'order_status', 'label' => 'Статус заказа', 'width' => '5', 'sort' => false),
                array('name' => 'product_categories', 'label' => 'Категории товаров', 'width' => '5', 'sort' => false),
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

        $managers = "'" . implode("','", $this->getParam('user_id')) . "'";

        $table = $this->getTableMeta('default');

        $date_from = date("Y-m-d 00:00:00",strtotime($this->getParam("date_from")));
        $date_to = date("Y-m-d 23:59:59",strtotime($this->getParam("date_to")));

        // Массив переводов статусов
        $status_translations = [
            'Prospecting' => 'Разведка',
            'Invoice send' => 'Выставление счета',
            'Invoice exposed' => 'Счет выставлен',
            'Shipment performance' => 'Выполнение отгрузки',
            'Shipment expectation' => 'Ожидание отгрузки',
            'Order send' => 'Товар отправлен',
            'Closed Won' => 'Закрыто с успехом',
            'Control' => 'На контроле',
            'Specialorder production' => 'Спецзаказ в производстве',
            'Specialorder processing' => 'Спецзаказ в обработке',
            'Specialorder' => 'Заказ',
            'Specialorder create' => 'Спецзаказ',
            'Specialorder transit' => 'Спецзаказ отправлен',
            'Rollback' => 'Возврат',
            'Rollback Won' => 'Возврат произведен',
            'ReDelivery' => 'Довоз внутритарки',
            'ReDelivery Won' => 'Довоз согласован',
            'Swap Won' => 'Обмен произведен',
            'Closed Lost' => 'Отмена',
            'Closed Lost performance' => 'Выполнение отмены',
            'Account is liquidated' => 'Счет оплачен',
            'Check multiplicity' => 'Подбор кратности',
            'Confirm multiplicity' => 'Кратность подобрана',
            'Invoice sber' => 'Обмен со Сбером',
            'Edit multiplicity' => 'Изменение кратности'
        ];

        // Получаем список сделок по СДЭК
        $sql_sdek = "SELECT teams.name AS team,
		                    opportunities.shipping_address_city AS city,
                            DATE(opportunities.date_entered) AS date_of,
                            opportunities.amount AS summ,
                            GROUP_CONCAT(productsale.name SEPARATOR ', ') AS categories,
                            opportunities.id AS id,
                            opportunities.sales_stage AS status
                            -- CASE opportunities.sales_stage
                                -- WHEN 'Prospecting' THEN 'Разведка'
	                            -- WHEN 'Invoice send' THEN 'Выставление счета'
	                            -- WHEN 'Invoice exposed' THEN 'Счет выставлен'
	                            -- WHEN 'Shipment performance' THEN 'Выполнение отгрузки'
	                            -- WHEN 'Shipment expectation' THEN 'Ожидание отгрузки'
	                            -- WHEN 'Order send' THEN 'Товар отправлен'
	                            -- WHEN 'Closed Won' THEN 'Закрыто с успехом'
	                            -- WHEN 'Control' THEN 'На контроле'
	                            -- WHEN 'Specialorder production' THEN 'Спецзаказ в производстве'
	                            -- WHEN 'Specialorder processing' THEN 'Спецзаказ в обработке'
	                            -- WHEN 'Specialorder' THEN 'Заказ'
	                            -- WHEN 'Specialorder create' THEN 'Спецзаказ'
	                            -- WHEN 'Specialorder transit' THEN 'Спецзаказ отправлен'
	                            -- WHEN 'Rollback' THEN 'Возврат'
	                            -- WHEN 'Rollback Won' THEN 'Возврат произведен'
	                            -- WHEN 'ReDelivery' THEN 'Довоз внутритарки'
	                            -- WHEN 'ReDelivery Won' THEN 'Довоз согласован'
	                            -- WHEN 'Swap Won' THEN 'Обмен произведен'
	                            -- WHEN 'Closed Lost' THEN 'Отмена'
	                            -- WHEN 'Closed Lost performance' THEN 'Выполнение отмены'
	                            -- WHEN 'Account is liquidated' THEN 'Счет оплачен'
	                            -- WHEN 'Check multiplicity' THEN 'Подбор кратности'
	                            -- WHEN 'Confirm multiplicity' THEN 'Кратность подобрана'
	                            -- WHEN 'Invoice sber' THEN 'Обмен со сбер.'
	                            -- WHEN 'Edit multiplicity' THEN 'Изменение кратности' 
                                -- ELSE opportunities.sales_stage
                            -- END AS status
                    FROM opportunities
                    LEFT JOIN opportunities_cstm ON opportunities.id = opportunities_cstm.id_c
                    LEFT JOIN users ON users.id = opportunities.assigned_user_id
                    LEFT JOIN teams ON teams.id = users.team_id
                    LEFT JOIN productsale ON productsale.opportunity_id = opportunities.id
                    WHERE opportunities.date_entered BETWEEN '$date_from' AND '$date_to'
                    AND opportunities_cstm.code_pvz_c <> ''
                    AND opportunities.assigned_user_id IN (" . $managers . ")
                    GROUP BY id
                    ORDER BY date_of
                       ";
//        echo '<pre>'; var_dump($sql_sdek);

        $que = dbGetArray($sql_sdek);


        foreach ($que as $acc) {
            // Переводим статус заказа на русский язык для отображения пользователю
            if (isset($status_translations[$row['status']])) {
                $acc['status'] = $status_translations[$acc['status']];
            }
            $data[] = array(
                'teams' => $acc['team'],
                'recipient_city' => $acc['city'],
                'issue_date'  => $acc['date_of'],
                'order_amount'  => $acc['summ'],
                'product_categories'  => $acc['categories'],
                'order_status'  => $acc['status'],
            );

            $sum_amount += round($acc['summ'],2);
            $sum_team_id = "Итого: ";
        }


        $data[] = array(
            'order_amount'      => "<b>".$sum_amount."</b>",
            'teams'             => "<b>".$sum_team_id."</b>",
        );
        $table['DATA'] = $data;
        $table['TITLE'] = 'Отчёт по отправкам СДЭК';

        if ($table['DATA']) return $table;
    }
}
