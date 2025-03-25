<?php
if (!defined('sugarEntry') || !sugarEntry)
    die('Not A Valid Entry Point');

class SimpleReportManagersDailyMissionsNew extends SimpleReport
{
    protected $_tables_metadata = array (
        'default' => array (
            'NAMESPACE' => 'default',
            'PAGINATION' => true,
            'FIELDS' => array (
                array ('name' => 'name_manager',       'label' => 'Менеджер',      'width' => '10', 'sort' => true),
                array ('name' => 'department',       'label' => 'Отдел',      'width' => '10', 'sort' => true),
                array ('name' => 'mission_count',       'label' => 'Кол-во отработанных событий',      'width' => '10', 'sort' => true),
                array ('name' => 'counterparties',       'label' => 'Кол-во событий по КА',      'width' => '10', 'sort' => true),
                array ('name' => 'preliminary_counterparties',       'label' => 'Кол-во событий по ПКА',      'width' => '10', 'sort' => true),
                array ('name' => 'count_transaction',       'label' => 'Кол-во сделок',      'width' => '10', 'sort' => true),
                array ('name' => 'count_calls',       'label' => 'Кол-во звонков',      'width' => '10', 'sort' => true),
                array ('name' => 'efficiency',       'label' => 'Эффективность, %',      'width' => '10', 'sort' => true),
                array ('name' => 'avg_quantity_transaction',       'label' => 'Среднее кол-во товара в сделке',      'width' => '10', 'sort' => true),
                array ('name' => 'total_amount',       'label' => 'Общая сумма по сделкам',      'width' => '10', 'sort' => true),
                array ('name' => 'avg_amount_trade',       'label' => 'Средняя сумма чека',      'width' => '10', 'sort' => true),
            ),
            'DATA' => array (),
        ),
    );
    protected $_export = true;

    /**
     * (non-PHPdoc)
     * @see SimpleReport::buildFilterForm()
     */

    // предназначена для построения формы фильтра
    protected function buildFilterForm($request = null)
    {
        global $current_user,$app_list_strings,$current_language;
//      echo '<pre>'; var_dump($request);
//      echo '<pre>'; var_dump($_REQUEST);
        if ($request === null)
            $request = & $_REQUEST;
        if (!isset($request['date_interval']))
            $request['date_interval'] = 'today';
        if (!isset($request['date_from']))
            $request['date_from'] = '';
        if (!isset($request['date_to']))
            $request['date_to'] = '';
        if ($this->getMedia() === self::MEDIA_DASHLET)
        {
            if (!isset($request['user_id']) &&
                !isset($request['team_id']) &&
                !isset($request['team_only']))
                $request['user_id'] = array_keys(get_user_array(false));
            if (!isset($request['display_fields']))
                $request['display_fields'] = array(
                    'request_num',
                    'user_name',
                    'account_name',
                    'cash',
                    'products',
                    'contact',
                );
        }
        $form = parent::buildFilterForm($request);

        $form['options']['mission_status'] = $app_list_strings['missions_status_list'];
        $form['params']['mission_status'] = isset($request['mission_status'])
            ? $request['mission_status']
            : 'completed';

        $form['options']['mission_type'] = $app_list_strings['missions_type_list'];
        $form['params']['mission_type'] = isset($request['mission_type'])
            ? $request['mission_type']
            : array_keys($form['options']['mission_type']);

        $form['options']['mission_parent_type'] = array('Accounts' => 'Контрагенты','Contacts' => 'Контакты','Leads' => 'Предв.контакты','Opportunities' => 'Сделки',);
        $form['params']['mission_parent_type'] = isset($request['mission_parent_type'])
            ? $request['mission_parent_type']
            : array_keys($form['options']['mission_parent_type']);

        $form['options']['mission_date_type'] = array('date_created' => 'создания','date_modified' => 'изменения','date_due' => 'выполнения');
        $form['params']['mission_date_type'] = isset($request['mission_date_type'])
            ? $request['mission_date_type']
            : 'date_modified';

        $form['options']['account_status_dom'] = $app_list_strings['account_status_dom'];
        if (isset($request['account_status_dom'])) {
            $form['params']['account_status_dom'] =  $request['account_status_dom'];
        }


        $form['params']['deals'] = '0';
        if (isset($request['notDeals'])){
            $form['params']['deals'] = '1';
        }
        return $form;

    }


    /**
     * (non-PHPdoc)
     * @see SimpleReport::buildParams()
     */
    protected function buildParams()
    {
        $params = parent::buildParams();
        return $params;
    }
    /**
     * (non-PHPdoc)
     * @see SimpleReport::getTables()
     */
    public function getTables()
    {
        $this->statistics("Результаты менеджеров (события)");
        global $app_list_strings, $image_path;;
        global $locale,$timedate;
        global $current_user;

        $users = $this->getParam('user_id');

        $mission_account_type = $this->getParam('account_status_dom');

        $mission_status = $this->getParam('mission_status');

        $mission_parent_type = $this->getParam('mission_parent_type');

        $notDeals = $this->getParam('deals');

        $datetime_from = $this->getParam('datetime_from');

        $datetime_to = $this->getParam('datetime_to');

        $date_from = $this->getParam('date_from');
        $date_from_t = date("Y-m-d 00:00:00",strtotime($date_from));
        $date_to = $this->getParam('date_to');
        $date_to_t = date("Y-m-d 23:59:59",strtotime($date_to));

        $table = $this->getTableMeta('default');
        /*$table['TITLE'] = '';
        $table['DESC'] = '';*/


        $user = '***';
        $pass = '***';
        $dbh = new PDO('mysql:host=172.16.0.8;dbname=***', $user, $pass);
        $dbс = new PDO('mysql:host=172.16.0.8;dbname=***', $user, $pass);



        // Запрос для выгрузки данных о событиях
        $sql_events = "SELECT CONCAT(users.last_name, ' ', users.first_name) AS full_name,
                        users.id AS users_id,
                        missions.id AS miss_id,
                        teams.name AS team,
                        COUNT(missions.id) AS number_events,
                        SUM(CASE WHEN missions.parent_type = 'Leads' THEN 1 ELSE 0 END) AS count_pc,
                        -- COUNT(DISTINCT(if(missions.parent_type = 'Leads',missions.id,null))) AS count_pc,
                        SUM(CASE WHEN missions.parent_type = 'Accounts' THEN 1 ELSE 0 END) AS count_c
                        -- COUNT(DISTINCT(if(missions.parent_type = 'Accounts',missions.id,null))) as acc_count
                FROM missions
                LEFT JOIN missions_audit ON missions_audit.parent_id = missions.id
                LEFT JOIN users ON missions_audit.created_by = users.id
                LEFT JOIN teams ON teams.id = users.team_id
                LEFT JOIN accounts ON missions.parent_id = accounts.id
                LEFT JOIN contacts ON missions.parent_id = contacts.id
                LEFT JOIN leads ON missions.parent_id = leads.id
                LEFT JOIN opportunities ON missions.parent_id = opportunities.id
                WHERE missions_audit.created_by IN ('". implode("','", $users) ."')
                AND missions_audit.after_value_string IN ('".implode("','", $mission_status)."')
                AND missions.parent_type IN ('". implode("', '", $mission_parent_type) ."')
                 -- AND accounts.status IN ('". implode("', '", $mission_account_type) ."')
                AND DATE_ADD(missions.date_modified, INTERVAL 3 HOUR) <= '$datetime_to'
                AND DATE_ADD(missions.date_modified, INTERVAL 3 HOUR) >= '$datetime_from'
                AND DATE_ADD(missions_audit.date_created, INTERVAL 3 HOUR) <= '$datetime_to'
                AND DATE_ADD(missions_audit.date_created, INTERVAL 3 HOUR) >= '$datetime_from'
                GROUP BY missions_audit.created_by
                ORDER BY full_name";

//                echo '<pre>'; var_dump($sql_events);

        $result_events = $dbh->query($sql_events);
        //echo '<pre>'; var_dump($result_events);
        //echo '<pre>'; var_dump($notDeals);

        // Условия для галочки "Только ожидающие отгрузки"
        if($notDeals){
            $closed_won = "AND opportunities.id IN (SELECT parent_id 
                           FROM opportunities_audit 
                           WHERE opportunities_audit.parent_id = opportunities.id 
                           AND opportunities_audit.date_created BETWEEN '$date_from_t' AND '$date_to_t' 
                           AND opportunities_audit.after_value_string = 'Shipment performance'
                           AND opportunities.sales_stage NOT IN ('Closed Lost')
                           )";
        }else{
            $closed_won = "AND opportunities.date_closed BETWEEN '$datetime_from' AND '$datetime_to'
                           AND opportunities.sales_stage = 'Closed Won'";
        }


        //echo '<pre>'; var_dump($notDeals);
        // Запрос для вывода данных о сделках
        $sql_deals ="SELECT CONCAT(users.last_name, ' ', users.first_name) AS full_name,
                            users.id as users_id,
                            opportunities.id as opp_id,
                            teams.name AS team,
                            COUNT(DISTINCT opportunities.id) AS count_transactions,
                            -- ROUND(AVG(opportunities.amount),2) AS avg_sum,
                            COUNT(DISTINCT users.id) AS count_user,
                            ROUND(SUM(DISTINCT opportunities.amount) / COUNT(DISTINCT opportunities.id),2) AS avg_sum,
                            ROUND(SUM(DISTINCT opportunities.amount),2) AS total,
                           -- ROUND(AVG(opportunities.pair_count),0) AS avg_product
                           -- ROUND(AVG(productsale.id),0) AS avg_product
                            ROUND(COUNT(productsale.id) / COUNT(DISTINCT opportunities.id),1) AS avg_product
                    FROM opportunities
                    LEFT JOIN users ON users.id = opportunities.assigned_user_id
                    LEFT JOIN productsale on productsale.opportunity_id = opportunities.id
                    LEFT JOIN teams ON teams.id = users.team_id
                    WHERE opportunities.assigned_user_id IN ('". implode("','", $users) ."')
                    AND opportunities.deleted = '0'
                    AND productsale.deleted = '0'
                    AND productsale.product_id != '7349e862-4fd8-7ef0-02b2-51065deac3fb'
                    $closed_won                    
                    GROUP BY full_name
                    ORDER BY full_name
                    ";

        $result_deals = $dbh->query($sql_deals);
//        echo '<pre>'; var_dump($sql_deals);

        // Запрос для вывода количества совершенных звонков менеджером
        $sql_calls ="SELECT COUNT(asteriskcdrdb.cdr.src) AS calls,
                            crm.users.id AS users_id,
                            crm.teams.name AS team,
                            CONCAT(crm.users.last_name, ' ', crm.users.first_name) AS full_name
                     FROM asteriskcdrdb.cdr
                     JOIN crm.users ON crm.users.phone_work COLLATE utf8_general_ci = asteriskcdrdb.cdr.src COLLATE utf8_general_ci
                     JOIN crm.teams ON crm.teams.id = crm.users.team_id
                     WHERE asteriskcdrdb.cdr.calldate BETWEEN '$date_from_t' AND '$date_to_t'
                     AND crm.users.id IN ('". implode("','", $users) ."')
                     AND LENGTH(asteriskcdrdb.cdr.dst) >= 10
                     AND asteriskcdrdb.cdr.billsec >= 30
                     -- AND crm.users.deleted = 0
                     -- AND crm.users.status = 'Active'
                     -- AND asteriskcdrdb.cdr.disposition = 'ANSWERED' -- Выведет только отвеченные звонки
                     GROUP BY full_name
                     ";

        $result_calls = $dbс->query($sql_calls);
//        echo '<pre>'; var_dump($sql_calls);


        // Цикл для перебора значений ассоциативного массива
        foreach ($users as $users_arr) {
            // Цикл для перебора значений $result_events
            while (($row_events = $result_events->fetch(PDO::FETCH_ASSOC))) {
                $data[$users_arr][$row_events['users_id']]['full_name'] = $row_events['full_name'];
                $data[$users_arr][$row_events['users_id']]['number_events'] = $row_events['number_events'];
                $data[$users_arr][$row_events['users_id']]['count_pc'] = $row_events['count_pc'];
                $data[$users_arr][$row_events['users_id']]['count_c'] = $row_events['count_c'];
                $data[$users_arr][$row_events['users_id']]['team'] = $row_events['team'];
                // echo '<pre>';var_dump($data);
            }
            // Цикл для перебора значений $result_deals
            while (($row_deals = $result_deals->fetch(PDO::FETCH_ASSOC))) {
                $data[$users_arr][$row_deals['users_id']]['full_name'] = $row_deals['full_name'];
                $data[$users_arr][$row_deals['users_id']]['count_transactions'] = $row_deals['count_transactions'];
                $data[$users_arr][$row_deals['users_id']]['avg_product'] = $row_deals['avg_product'];
                $data[$users_arr][$row_deals['users_id']]['avg_sum'] = $row_deals['avg_sum'];
                $data[$users_arr][$row_deals['users_id']]['total'] = $row_deals['total'];
                $data[$users_arr][$row_deals['users_id']]['count_user'] = $row_deals['count_user'];
                $data[$users_arr][$row_deals['users_id']]['team'] = $row_deals['team'];
                // echo '<pre>'; var_dump($data);
            }
            // Цикл для перебора значений $result_call
            while (($row_calls = $result_calls->fetch(PDO::FETCH_ASSOC))) {
                $data[$users_arr][$row_calls['users_id']]['full_name'] = $row_calls['full_name'];
                $data[$users_arr][$row_calls['users_id']]['calls'] = $row_calls['calls'];
                $data[$users_arr][$row_calls['users_id']]['team'] = $row_calls['team'];
            }
        }
        //echo '<pre>';var_dump($data);


        // Цикл для вывода значений массива $data
        foreach ($data as  $data_array) {
            foreach ($data_array as $row) {
//            echo '<pre>'; var_dump($row);
                // Функция для подсчёта эффективности менеджера
                $efficiency = round(($row['count_transactions']/$row['number_events'])*100, 2);

                $data_result[] = array(
                    'name_manager' => $row['full_name'],
                    'department' => $row['team'],
                    'mission_count' => $row['number_events'],
                    'preliminary_counterparties' => $row['count_pc'],
                    'counterparties' => $row['count_c'],
                    'count_transaction' => $row['count_transactions'],
                    'avg_quantity_transaction' => $row['avg_product'],
                    'avg_amount_trade' => $row['avg_sum'],
                    'total_amount' => $row['total'],
                    'efficiency' => $efficiency,
                    'count_calls' => $row['calls'],
                );
                $column_total = "Итого: ";
                $sum_total += round($row['total'],2);
                $sum_avg_trade += round($row['avg_sum'],2);
                $sum_users += $row['count_user'];
                $sum_events += $row['number_events'];
                $sum_count_pc += $row['count_pc'];
                $sum_count_c += $row['count_c'];
                $sum_transactions += $row['count_transactions'];
                $avg_count_product += $row['avg_product'];
                $sum_calls += $row['calls'];
//                echo '<pre>'; var_dump($sum_users);
            }
        }

        // Подсчитывает среднее значение среднего чека по количеству менеджеров
        $avg_sum_users = round($sum_avg_trade / $sum_users, 2);
        // Подсчитывает среднее значение товаров в сделках по количеству менеджеров
        $avg_prod = round($avg_count_product / $sum_users,1);
        // Подсчитывает общую эффективность
        $sum_efficiency = round(($sum_transactions / $sum_events)*100, 2) ;

        // Вывод итого
        $data_result[] = array(
            'name_manager'                  => "<b>".$column_total."</b>",
            'total_amount'                  => "<b>".$sum_total."</b>",
            'avg_amount_trade'              => "<b>".$avg_sum_users."</b>",
            'mission_count'                 => "<b>".$sum_events."</b>",
            'preliminary_counterparties'    => "<b>".$sum_count_pc."</b>",
            'counterparties'                => "<b>".$sum_count_c."</b>",
            'count_transaction'             => "<b>".$sum_transactions."</b>",
            'avg_quantity_transaction'      => "<b>".$avg_prod."</b>",
            'efficiency'                    => "<b>".$sum_efficiency."</b>",
            'count_calls'                   => "<b>".$sum_calls."</b>",
        );

        $table['DATA'] = $data_result;
        $table['TITLE'] = 'Результаты за период: '.$timedate->to_display_date($datetime_from).' - '.$timedate->to_display_date($datetime_to);

        if ($table['DATA']) return $table;
    }
}
