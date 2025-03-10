<?php

$user = 'krylov.pavel';
$pass = ')Q123456789';
$dbh = new PDO('mysql:host=172.16.0.8;dbname=crm', $user, $pass);

require_once("modules/Dashboard/reports/ManagersTransactions/report.php");
$transactions_id = $_GET['user_id'];
$date_from = $_GET['date_from'];
$date_to = $_GET['date_to'];
//echo '<pre>'; var_dump($_GET);


$transactions ="SELECT opportunities.name AS name_counterparty,
                        opportunities.amount AS sum_transaction,
                        opportunities.date_closed AS date_transaction,
                        opportunities.list_items AS product,
                        teams.name as team_name,
                        opportunities.request_num AS application_number
                FROM opportunities
                LEFT JOIN users ON users.id = opportunities.assigned_user_id
                LEFT JOIN teams on teams.id = users.team_id
                WHERE opportunities.assigned_user_id = '$transactions_id'
                AND opportunities.date_closed BETWEEN '$date_from' AND '$date_to'
                AND opportunities.payment_form = 'Cash'
                AND opportunities.name LIKE '%Образцы%'
                AND opportunities.sales_stage = 'Closed Won'
                ORDER BY date_transaction DESC";

//echo '<pre>'; print_r($transactions_id);
//echo '<pre>'; print_r($transactions);
$stmp = $dbh->query($transactions);

$list = $stmp->fetchAll(PDO::FETCH_ASSOC);
$rownumber = 0;
//echo '<pre>'; var_dump($list);
//echo '<pre>'; var_dump($list);
echo "<table border = 1 style=\"font-size: 20px;\">";
echo	"<thead>";
echo		"<tr>";
echo			"<th>№</th>";
echo			"<th>Номер заявки</th>";
echo			"<th>Контрагент</th>";
echo			"<th>Дата закрытия</th>";
echo			"<th>Сумма</th>";
echo			"<th>Товар</th>";
echo			"<th>Отдел</th>";
echo		"</tr>";
echo	"</thead>";
echo	"<tbody >";
foreach ($list as $row) {
//  echo '<pre>'; var_dump($row);
  $rownumber++;
echo "<tr>";
echo "<td style=\"font-size: 15px; text-align: center; font-weight: bold; \">{$rownumber}</td>";
echo "<td style=\"font-size: 15px; text-align: center; font-weight: bold; \">{$row['application_number']}</td>";
echo "<td style=\"font-size: 15px; text-align: center; font-weight: bold; \">{$row['name_counterparty']}</td>";
echo "<td style=\"font-size: 15px; text-align: center; font-weight: bold; \">{$row['date_transaction']}</td>";
echo "<td style=\"font-size: 15px; text-align: center; font-weight: bold; \">{$row['sum_transaction']}</td>";
echo "<td style=\"font-size: 15px; text-align: center; font-weight: bold; \">{$row['product']}</td>";
echo "<td style=\"font-size: 15px; text-align: center; font-weight: bold; \">{$row['team_name']}</td>";

echo "</tr>";
};
echo	"</tbody>";
echo "</table>";


?>
