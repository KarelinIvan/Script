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
                array ('name' => 'count_calls',       'label' => 'Кол-во исходящих звонков',      'width' => '10', 'sort' => true),
                array ('name' => 'count_calls_ka',       'label' => 'Кол-во исходящих звонков КА',      'width' => '10', 'sort' => true),
                array ('name' => 'count_calls_pka',       'label' => 'Кол-во исходящих звонков ПКА',      'width' => '10', 'sort' => true),
                array ('name' => 'count_inc_calls',       'label' => 'Кол-во входящих звонков',      'width' => '10', 'sort' => true),
                array ('name' => 'efficiency',       'label' => 'Эффективность, %',      'width' => '10', 'sort' => true),
                array ('name' => 'count_product',       'label' => 'Кол-во товаров',      'width' => '10', 'sort' => true),
                array ('name' => 'categories_quantity',       'label' => 'Кол-во кат.товара в сделке',      'width' => '10', 'sort' => true),
                array ('name' => 'avg_quantity_transaction',       'label' => 'Ср.кол-во кат.товара в сделке',      'width' => '10', 'sort' => true),
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
        $dbh = new PDO('mysql:host=172.16.0.8;dbname=crm', $user, $pass);
        $dbc = new PDO('mysql:host=172.16.0.8;dbname=asteriskcdrdb', $user, $pass);

        // Список отделов
        $list_teams = array(
            '2c3965cc-926b-1f5e-4bb6-550aa8a02c6c',
            'dcb6b8d5-9f47-f619-5b58-65ae4e01080e',
            '563150b1-7e4e-0c78-59cc-660e731bbe8b',
            '81bbf244-8c01-10a4-4be1-659d17feab10',
            'b3f0f476-03f6-7f45-1d48-54230800c268',
            'b57e4d8a-0256-2358-c128-661e2510fb46',
            'd3233b41-8093-7baf-1447-6617a99ce29f',
            'e28c0094-06a8-c65e-4795-6617a9f70d62',
            'f383a801-ada4-8236-c8d2-6617a9d7ac23',
            'aaca3c55-5605-de8e-9318-660fb15cefa2',
            '57d9fbea-24a3-bf7d-1770-660fb16dcdb1',
            'a1c48a96-ff27-f312-5d41-660fb1d5e721',
            'e0a42418-6a26-3adc-6c5b-55d431ddfad8',
            '670da544-2c37-2c67-7f5b-65ae4e6f0feb',
            'e1bcfe3e-5b4f-6e4b-83b3-65aa1dd37aab',
            'a7030372-21b6-1e95-fec1-65aa1dfdfd9e',
            '126394f7-0570-a22b-873f-59314e2fb541',
            '18f7a4ff-3c42-0daf-22cf-661f97ae987a',
            '17b95305-c01e-1d8b-cdc7-65ae4e40a651',
            '75c06441-0ea1-4cbc-80b6-660fdbbfafcb',
            'be0f2c6f-7839-dd83-c2f1-65aa2bea1ea1',
            'b6785fea-e055-ca93-4594-65aa2b3a0267',
            '52042a38-7201-b7b0-9868-65a622c4a29f',
            'd6b53d22-d869-6b92-69e7-65ae4d331e0c',
            '944af9fe-3105-3a21-aa95-5f6aefc3a517',
            '55d21fef-f328-0c38-dcbf-660e461cf8fc',
            '85ad4646-f4fe-cfbb-414e-65aa63d60105',
            '327eae14-1976-16f1-f743-660e46b9abac'
        );

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
                AND DATE_ADD(missions.date_modified, INTERVAL 3 HOUR) <= '$datetime_to'
                AND DATE_ADD(missions.date_modified, INTERVAL 3 HOUR) >= '$datetime_from'
                AND DATE_ADD(missions_audit.date_created, INTERVAL 3 HOUR) <= '$datetime_to'
                AND DATE_ADD(missions_audit.date_created, INTERVAL 3 HOUR) >= '$datetime_from'
                AND teams.id IN ('". implode("','", $list_teams) ."')
                AND CONCAT(users.last_name, ' ', users.first_name) NOT LIKE '%УВОЛЕН%'
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
                           )";
        }else{
            $closed_won = "AND opportunities.date_closed BETWEEN '$date_from_t' AND '$date_to_t'
                           AND opportunities.sales_stage = 'Closed Won'";
        }


        //echo '<pre>'; var_dump($notDeals);
        // Запрос для вывода данных о сделках
        $sql_deals ="SELECT CONCAT(users.last_name, ' ', users.first_name) AS full_name,
                            users.id as users_id,
                            opportunities.id as opp_id,
                            teams.name AS team,
                            COUNT(DISTINCT productcat.id) AS col_cat,
                            COUNT(DISTINCT productsale.id) AS col_pr,
                            COUNT(DISTINCT opportunities.id) AS count_transactions,
                            COUNT(DISTINCT users.id) AS count_user,
                            ROUND(SUM(DISTINCT opportunities.amount) / COUNT(DISTINCT opportunities.id),2) AS avg_sum,
                            ROUND(SUM(DISTINCT opportunities.amount),2) AS total,
                            ROUND(COUNT(DISTINCT productcat.id) / COUNT(DISTINCT opportunities.id),1) AS avg_product
                    FROM opportunities
                    LEFT JOIN users ON users.id = opportunities.assigned_user_id
                    LEFT JOIN productsale on productsale.opportunity_id = opportunities.id
                    LEFT JOIN teams ON teams.id = users.team_id
                    LEFT JOIN product ON product.id = productsale.product_id
                    LEFT JOIN productcat ON productcat.id = product.category_id
                    WHERE opportunities.assigned_user_id IN ('". implode("','", $users) ."')
                    AND opportunities.deleted = '0'
                    AND productsale.deleted = '0'
                    AND productsale.product_id != '7349e862-4fd8-7ef0-02b2-51065deac3fb'
                    AND opportunities.sales_stage NOT IN ('Closed Lost')
                    AND teams.id IN ('". implode("','", $list_teams) ."')
                    AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Отказ%'
                    $closed_won                    
                    GROUP BY users.id
                    ORDER BY users.id
                    ";

        $result_deals = $dbh->query($sql_deals);
//        echo '<pre>'; var_dump($sql_deals);

        # Запрос для вывода количества совершенных звонков менеджером
//        $sql_calls ="SELECT COUNT(asteriskcdrdb.cdr.src) AS calls,
//                            crm.users.id AS users_id,
//                            crm.teams.name AS team,
//                            CONCAT(crm.users.last_name, ' ', crm.users.first_name) AS full_name
//                     FROM asteriskcdrdb.cdr
//                     JOIN crm.users ON crm.users.phone_work COLLATE utf8_general_ci = asteriskcdrdb.cdr.src COLLATE utf8_general_ci -- приводим к одной кодировке
//                     JOIN crm.teams ON crm.teams.id = crm.users.team_id
//                     WHERE asteriskcdrdb.cdr.calldate BETWEEN '$date_from_t' AND '$date_to_t'
//                     AND crm.users.id IN ('". implode("','", $users) ."')
//                     AND LENGTH(asteriskcdrdb.cdr.dst) > 5
//                     AND asteriskcdrdb.cdr.dcontext = 'from-internal'
//                     AND asteriskcdrdb.cdr.disposition IN ('ANSWERED','NO ANSWER')
//                     AND asteriskcdrdb.cdr.billsec >= 30
//                     AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%БАЗА%'
//                     AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Отказ%'
//                     AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Неотработанные%'
//                     AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Необработанные%'
//                     AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%УВОЛЕН%'
//                     AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Свободный Привод%'
//                     AND crm.teams.id IN ('". implode("','", $list_teams) ."')
//                     GROUP BY full_name
//                     ";
//
//        $result_calls = $dbc->query($sql_calls);
//        echo '<pre>'; var_dump($sql_calls);



        # Создание временной таблицы с номерами телефонов контрагентов
        $table_accounts = "CREATE TEMPORARY TABLE asteriskcdrdb.temp_account_phones (INDEX  (clean_phone)) AS
                    SELECT REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_office, '+', ''), '(', ''), ')', ''), '-', ''), ' ', '') AS clean_phone
                    FROM crm.accounts
                    WHERE phone_office IS NOT NULL
                    AND phone_office != ''
                    ";
        $dbc->query($table_accounts);
//        echo '<pre>'; var_dump($table_accounts);
//        echo '<pre>'; var_dump($dbc);

        # Создание временной таблицы для с номерами предварительных контрагентов
        $table_leads = "CREATE TEMPORARY TABLE asteriskcdrdb.temp_lead_phones (INDEX  (clean_phone)) AS
                    SELECT REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(phone_work, '+', ''), '(', ''), ')', ''), '-', ''), ' ', '') AS clean_phone
                    FROM crm.leads
                    WHERE phone_work IS NOT NULL
                    AND phone_work != ''
                    ";
        $dbc->query($table_leads);
//        echo '<pre>'; var_dump($table_leads);

        # SQL запрос для вывода количества исходящих звонков менеджером, а так же разделение звонков совершенны контрагентам и предварительным контрагентам
        # подсчёт звонков по категориям выполняется с помощью создания временных таблиц(это сделано для оптимизации запроса)
        # при создании временных таблиц используется функция REPLACE, для нормализации данных содержащихся в таблицах leads и accounts и сравнения их с данными в таблице cdr
        $sql_call ="SELECT
                        CONCAT(crm.users.last_name, ' ', crm.users.first_name) AS full_name,
                        crm.users.id AS users_id,
                        crm.teams.name AS team,
                        COUNT(asteriskcdrdb.cdr.src) AS calls,
                        SUM(
                            CASE WHEN EXISTS (
                                SELECT 1 FROM asteriskcdrdb.temp_account_phones
                                WHERE asteriskcdrdb.temp_account_phones.clean_phone = asteriskcdrdb.cdr.dst COLLATE utf8_general_ci
                            ) THEN 1 ELSE 0 END
                        ) AS accounts_calls,

                        SUM(
                            CASE WHEN EXISTS (
                                SELECT 1 FROM asteriskcdrdb.temp_lead_phones
                                WHERE asteriskcdrdb.temp_lead_phones.clean_phone = asteriskcdrdb.cdr.dst COLLATE utf8_general_ci
                            ) THEN 1 ELSE 0 END
                        ) AS leads_calls
                    FROM asteriskcdrdb.cdr
                    JOIN crm.users ON crm.users.phone_work COLLATE utf8_general_ci = asteriskcdrdb.cdr.src COLLATE utf8_general_ci
                    JOIN crm.teams ON crm.teams.id = crm.users.team_id
                    WHERE asteriskcdrdb.cdr.calldate BETWEEN '$date_from_t' AND '$date_to_t'
                    AND crm.users.id IN ('". implode("','", $users) ."')
                    AND LENGTH(asteriskcdrdb.cdr.dst) > 5
                    AND asteriskcdrdb.cdr.dcontext = 'from-internal'
                    AND asteriskcdrdb.cdr.disposition IN ('ANSWERED', 'NO ANSWER')
                    AND asteriskcdrdb.cdr.billsec >= 30
                    AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%БАЗА%'
                    AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Отказ%'
                    AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Неотработанные%'
                    AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Необработанные%'
                    AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%УВОЛЕН%'
                    AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Свободный Привод%'
                    AND crm.teams.id IN ('". implode("','", $list_teams) ."')
                    GROUP BY full_name
                    ";

        $result_calls = $dbc->query($sql_call);
//        echo '<pre>'; var_dump($result_call);
//        echo '<pre>'; var_dump($sql_call);


        // Запрос для вывода количества входящих звонков менеджеру
        $sql_incoming = "SELECT COUNT(asteriskcdrdb.phone) AS incoming_calls,
                                      CONCAT(users.last_name, ' ', users.first_name) AS full_name,
                                      teams.name AS team,
                                      users.id as users_id
                                FROM (SELECT CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(cdr.userfield, 'g', -1), '-', 1) AS UNSIGNED) AS phone
                                      FROM asteriskcdrdb.cdr
                                      WHERE cdr.calldate BETWEEN '$date_from_t' AND '$date_to_t'
                                      AND cdr.dcontext IN ('ext-group', 'from-internal')
                                      AND cdr.disposition IN ('ANSWERED')
                                      AND LENGTH(cdr.src) > 5
                                      AND cdr.billsec >= 10) asteriskcdrdb
                                JOIN crm.users users ON users.phone_work COLLATE utf8_general_ci = asteriskcdrdb.phone COLLATE utf8_general_ci
                                JOIN crm.teams teams ON teams.id = users.team_id
                                WHERE users.id IN ('". implode("','", $users) ."')
                                AND teams.id IN ('". implode("','", $list_teams) ."')
                                AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%БАЗА%'
                                AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Отказ%'
                                AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Неотработанные%'
                                AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Необработанные%'
                                AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%УВОЛЕН%'
                                AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Свободный Привод%'
                                GROUP BY full_name";

        $result_incoming = $dbc->query($sql_incoming);
//        echo '<pre>'; var_dump($result_incoming);


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
                $data[$users_arr][$row_deals['users_id']]['col_cat'] = $row_deals['col_cat'];
                $data[$users_arr][$row_deals['users_id']]['col_pr'] = $row_deals['col_pr'];
                // echo '<pre>'; var_dump($data);
            }
            // Цикл для перебора значений $result_calls
            while (($row_calls = $result_calls->fetch(PDO::FETCH_ASSOC))) {
                $data[$users_arr][$row_calls['users_id']]['full_name'] = $row_calls['full_name'];
                $data[$users_arr][$row_calls['users_id']]['calls'] = $row_calls['calls'];
                $data[$users_arr][$row_calls['users_id']]['team'] = $row_calls['team'];
                $data[$users_arr][$row_calls['users_id']]['accounts_calls'] = $row_calls['accounts_calls'];
                $data[$users_arr][$row_calls['users_id']]['leads_calls'] = $row_calls['leads_calls'];
            }

            // Цикл для перебора значений $result_inc_calls
            while (($row_incoming = $result_incoming->fetch(PDO::FETCH_ASSOC))) {
                $data[$users_arr][$row_incoming['users_id']]['full_name'] = $row_incoming['full_name'];
                $data[$users_arr][$row_incoming['users_id']]['incoming_calls'] = $row_incoming['incoming_calls'];
                $data[$users_arr][$row_incoming['users_id']]['team'] = $row_incoming['team'];
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
                    'count_inc_calls' => $row['incoming_calls'],
                    'categories_quantity' => $row['col_cat'],
                    'count_product' => $row['col_pr'],
                    'count_calls_ka' => $row['accounts_calls'],
                    'count_calls_pka' => $row['leads_calls'],
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
                $sum_incoming += $row['incoming_calls'];
                $sum_calls_ka += $row['accounts_calls'];
                $sum_calls_pka += $row['leads_calls'];
//                echo '<pre>'; var_dump($sum_users);
            }
        }

        // Подсчитывает среднее значение среднего чека по количеству менеджеров
        $avg_sum_users = round($sum_avg_trade / $sum_users, 2);
        // Подсчитывает среднее значение категорий товаров в сделках по количеству менеджеров
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
            'count_inc_calls'               => "<b>".$sum_incoming."</b>",
            'count_calls_ka'                => "<b>".$sum_calls_ka."</b>",
            'count_calls_pka'               => "<b>".$sum_calls_pka."</b>",
        );

        $table['DATA'] = $data_result;
        $table['TITLE'] = 'Результаты за период: '.$timedate->to_display_date($datetime_from).' - '.$timedate->to_display_date($datetime_to);

        if ($table['DATA']) return $table;
    }
}
