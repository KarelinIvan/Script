<?php

global $current_user, $app_list_strings, $current_language;
$time = time();
$dateBeging = $time;


while (date("l",$dateBeging) != "Monday") {
    $dateBeging -= 86400;
}
$dateBegingSQL = date("Y-m-d 00:00:00",$time);
$dateEnd = date("Y-m-d 23:59:59",$time);

$daytreeday = strtotime("-2 day");
$dateStart = date("Y-m-d 00:00:00",$daytreeday);

$datetomorrow = strtotime("+1 day");
$dateEnding = date("Y-m-d 23:59:59",$datetomorrow);

/*echo "<pre>"; var_dump($dateStart);
echo "<pre>"; var_dump($dateEnding);
echo ($dateBegingSQL);
echo "<br>";
echo ($dateEnd);*/
//echo ($dateBegingSQL);
//echo ($dateEnd);

function excelCol($num=false) {
    if (($num !== false) and (($num=($num*1)) > 0)) {
        $result = "";
        while ($num > 36) {
            $digit = floor($num / 36);
            $result .= chr(64+$digit);
            $num -= $digit*36;
        }
        $result .= chr(64+$num);
        return $result;
    }
    return false;
}



$teamsList = array("2c3965cc-926b-1f5e-4bb6-550aa8a02c6c",
                   "dcb6b8d5-9f47-f619-5b58-65ae4e01080e",
                   "563150b1-7e4e-0c78-59cc-660e731bbe8b",
                   "81bbf244-8c01-10a4-4be1-659d17feab10",
                   "b3f0f476-03f6-7f45-1d48-54230800c268",
                   "b57e4d8a-0256-2358-c128-661e2510fb46",
                   "d3233b41-8093-7baf-1447-6617a99ce29f",
                   "e28c0094-06a8-c65e-4795-6617a9f70d62",
                   "f383a801-ada4-8236-c8d2-6617a9d7ac23",
                   "aaca3c55-5605-de8e-9318-660fb15cefa2",
                   "57d9fbea-24a3-bf7d-1770-660fb16dcdb1",
                   "a1c48a96-ff27-f312-5d41-660fb1d5e721",
                   "e0a42418-6a26-3adc-6c5b-55d431ddfad8",
                   "670da544-2c37-2c67-7f5b-65ae4e6f0feb",
                   "e1bcfe3e-5b4f-6e4b-83b3-65aa1dd37aab",
                   "a7030372-21b6-1e95-fec1-65aa1dfdfd9e",
                   "126394f7-0570-a22b-873f-59314e2fb541",
                   "18f7a4ff-3c42-0daf-22cf-661f97ae987a",
                   "17b95305-c01e-1d8b-cdc7-65ae4e40a651",
                   "75c06441-0ea1-4cbc-80b6-660fdbbfafcb",
                   "be0f2c6f-7839-dd83-c2f1-65aa2bea1ea1",
                   "b6785fea-e055-ca93-4594-65aa2b3a0267",
                   "52042a38-7201-b7b0-9868-65a622c4a29f",
                   "d6b53d22-d869-6b92-69e7-65ae4d331e0c",
                   "944af9fe-3105-3a21-aa95-5f6aefc3a517",
                   "55d21fef-f328-0c38-dcbf-660e461cf8fc",
                   "85ad4646-f4fe-cfbb-414e-65aa63d60105",
                   "327eae14-1976-16f1-f743-660e46b9abac"
);

$user = 'krylov.pavel';
$pass = ')Q123456789';
$dbh = new PDO('mysql:host=172.16.0.8;dbname=crm', $user, $pass);



    # Запрос для вывода количества сделок, товаров в сделках у каждого менеджера
    # поиск производиться по массиву $teamsList содержащему id отделов продаж
    $sql = "SELECT teams.name AS team,
                    CONCAT(users.last_name, ' ', users.first_name) AS full_name,
                    users.id as users_id,
                    COUNT(DISTINCT opportunities.id) AS opp,
                    -- COUNT(DISTINCT productcat.id) AS count_categories,
                    COUNT(DISTINCT productsale.id) AS count_product
            FROM opportunities
            LEFT JOIN productsale ON productsale.opportunity_id = opportunities.id 
            LEFT JOIN product ON productsale.product_id = product.id 
            LEFT JOIN productcat ON productcat.id = product.category_id 
            LEFT JOIN users ON users.id = opportunities.assigned_user_id
            LEFT JOIN teams ON teams.id = users.team_id
            -- LEFT JOIN DirtyProfitSave ON DirtyProfitSave.opportunity_id = opportunities.id
            WHERE teams.id IN ('". implode("','", $teamsList) ."')
            AND productsale.deleted = 0
            AND opportunities.deleted = 0
            AND productsale.product_id != '7349e862-4fd8-7ef0-02b2-51065deac3fb'
            AND opportunities.sales_stage NOT IN ('Closed Lost')
            AND opportunities.id IN (SELECT parent_id 
                                     FROM opportunities_audit 
                                     WHERE opportunities_audit.parent_id = opportunities.id 
                                     AND opportunities_audit.date_created BETWEEN '$dateBegingSQL' AND '$dateEnd' 
                                     AND opportunities_audit.after_value_string = 'Shipment performance'
                                    )
            GROUP BY teams.name, 
                     CONCAT(users.last_name, ' ', users.first_name)";

            $stmp = $dbh->query($sql);
            # var_dump($stmp);
//            echo "<pre>"; var_dump($stmp);


    # Запрос для вывода количества событий у каждого менеджера
    # поиск производиться по массиву $teamsList содержащему id отделов продаж
    $sql_events = "SELECT CONCAT(users.last_name, ' ', users.first_name) AS full_name,
                            teams.name AS team,
                            users.id as users_id,
                            COUNT(missions.id) AS number_events
                    FROM missions
                    LEFT JOIN missions_audit ON missions_audit.parent_id = missions.id
                    LEFT JOIN users ON missions_audit.created_by = users.id
                    LEFT JOIN teams ON teams.id = users.team_id
                    WHERE teams.id IN ('". implode("','", $teamsList) ."')
                    AND missions_audit.after_value_string IN ('completed')
                    AND missions.parent_type IN ('Accounts', 'Contacts', 'Leads', 'Opportunities')                      
                    AND DATE_ADD(missions.date_modified, INTERVAL 3 HOUR) <= '$dateEnd'
                    AND DATE_ADD(missions.date_modified, INTERVAL 3 HOUR) >= '$dateBegingSQL'
                    AND DATE_ADD(missions_audit.date_created, INTERVAL 3 HOUR) <= '$dateEnd'
                    AND DATE_ADD(missions_audit.date_created, INTERVAL 3 HOUR) >= '$dateBegingSQL'
                    GROUP BY teams.name, 
                             CONCAT(users.last_name, ' ', users.first_name)";

            $stmp2 = $dbh->query($sql_events);
//            echo '<pre>'; var_dump($sql_events);
//            echo '<pre>'; var_dump($stmp2);


    # Запрос для вывода количества совершенных звонков менеджерами с рабочих номеров контрагентам
    # поиск производиться по массиву $teamsList содержащему id отделов продаж
    $sql_calls ="SELECT COUNT(asteriskcdrdb.cdr.src) AS calls,
                        crm.users.id AS users_id,
                        crm.teams.name AS team,
                        CONCAT(crm.users.last_name, ' ', crm.users.first_name) AS full_name
                 FROM asteriskcdrdb.cdr
                 JOIN crm.users ON crm.users.phone_work COLLATE utf8_general_ci = asteriskcdrdb.cdr.src COLLATE utf8_general_ci
                 JOIN crm.teams ON crm.teams.id = crm.users.team_id
                 WHERE asteriskcdrdb.cdr.calldate BETWEEN '$dateBegingSQL' AND '$dateEnd'
                 AND LENGTH(asteriskcdrdb.cdr.dst) > 5
                 AND asteriskcdrdb.cdr.dcontext = 'from-internal'
                 AND asteriskcdrdb.cdr.disposition IN ('ANSWERED','NO ANSWER')
                 AND asteriskcdrdb.cdr.billsec >= 30
                 AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%БАЗА%'
                 AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Отказ%'
                 AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Неотработанные%'
                 AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Необработанные%'
                 AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%УВОЛЕН%'
                 AND CONCAT(crm.users.last_name, ' ', crm.users.first_name) NOT LIKE '%Свободный Привод%'
                 AND crm.teams.id IN ('". implode("','", $teamsList) ."')
                 GROUP BY crm.teams.name, CONCAT(crm.users.last_name, ' ', crm.users.first_name)
                     ";
                $stmp3 = $dbh->query($sql_calls);



# Цикл для формирования массива $data из запросов выше
foreach ($teamsList as $teams) {
    while (($row_s = $stmp->fetch(PDO::FETCH_ASSOC))){
        $data[$teams][$row_s['users_id']]['full_name'] = $row_s['full_name'];
        $data[$teams][$row_s['users_id']]['team'] = $row_s['team'];
        $data[$teams][$row_s['users_id']]['opp'] = $row_s['opp'];
        $data[$teams][$row_s['users_id']]['count_product'] = $row_s['count_product'];
//        echo "<pre>";var_dump($row_s);
//        echo "<pre>";var_dump($stmp);
    }

    while (($row_e = $stmp2->fetch(PDO::FETCH_ASSOC))){
        $data[$teams][$row_e['users_id']]['full_name'] = $row_e['full_name'];
        $data[$teams][$row_e['users_id']]['team'] = $row_e['team'];
        $data[$teams][$row_e['users_id']]['number_events'] = $row_e['number_events'];
//        echo "<pre>";var_dump($row_e);
    }

    while (($row_c = $stmp3->fetch(PDO::FETCH_ASSOC))){
        $data[$teams][$row_c['users_id']]['full_name'] = $row_c['full_name'];
        $data[$teams][$row_c['users_id']]['team'] = $row_c['team'];
        $data[$teams][$row_c['users_id']]['calls'] = $row_c['calls'];
    }
//    echo "<pre>";var_dump($teams);
//    echo "<pre>";var_dump($data);
}



echo "<table border =1>";
echo	"<thead>";
echo		"<tr>";
echo			"<th>Отдел</th>";
echo			"<th>Менеджер</th>";
echo			"<th>Кол-во сделок</th>";
echo			"<th>Кол-во товаров</th>";
echo			"<th>Кол-во событий</th>";
echo			"<th>Кол-во исходящих звонков (не менее 30 сек.)</th>";
echo		"</tr>";
echo	"</thead>";
echo	"<tbody>";
foreach ($data as $i => $arData) {
//    echo "<pre>";var_dump($arData);

    foreach ($arData as $row) {
//            $count++;
            echo "<tr>";
//            echo "<td>{$count}</td>";
            echo "<td>{$row['team']}</td>";
            echo "<td>{$row['full_name']}</td>";
            echo "<td>{$row['opp']}</td>";
            echo "<td>{$row['count_product']}</td>";
            echo "<td>{$row['number_events']}</td>";
            echo "<td>{$row['calls']}</td>";
            echo "</tr>";
    };
}
echo	"</tbody>";
echo "</table>";

require_once "/var/www/html/nikolaevevge/Classes/PHPExcel.php";
require_once "/var/www/html/nikolaevevge/Classes/PHPExcel/Writer/Excel2007.php";

$xls = new PHPExcel();
$xls->getProperties()->setCreator("Робот CRM");
$xls->setActiveSheetIndex(0);
$sheet = $xls->getActiveSheet();
$sheet->setTitle('Сделки');


$sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(
    PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
$sheet->getStyle("A1:F1")->getFont()->setBold(true);
$sheet->setCellValue("A1", 'Отдел');
$sheet->setCellValue("B1", 'Менеджер');
$sheet->setCellValue("C1", 'Кол-во сделок');
$sheet->setCellValue("D1", 'Кол-во товаров');
$sheet->setCellValue("E1", 'Кол-во событий');
$sheet->setCellValue("F1", 'Кол-во исходящих звонков (не менее 30 сек.)');

$sheet->getColumnDimension("A")->setAutoSize(true);
$sheet->getColumnDimension("B")->setAutoSize(true);
$sheet->getColumnDimension("C")->setAutoSize(true);
$sheet->getColumnDimension("D")->setAutoSize(true);
$sheet->getColumnDimension("E")->setAutoSize(true);
$sheet->getColumnDimension("F")->setAutoSize(true);

$index = 2;

foreach ($data as $i => $arData) {
    foreach ($arData as $row) {
        //    echo "<pre>";var_dump($row);
        $sheet->setCellValue("A" . $index, $row['team']);
        $sheet->setCellValue("B" . $index, $row['full_name']);
        $sheet->setCellValue("C" . $index, $row['opp']);
        $sheet->setCellValue("D" . $index, $row['count_product']);
        $sheet->setCellValue("E" . $index, $row['number_events']);
        $sheet->setCellValue("F" . $index, $row['calls']);

//    echo "<pre>";var_dump($sheet);
        $index++;
    }
}

$border = array(
    "borders" => array(
        "allborders" => array(
            "style" => PHPExcel_Style_Border::BORDER_THIN,
            "color" => array("rgb" => "000000")
        )
    )
);
$sheet->getStyle("A1:F{$index}")->applyFromArray($border);

$name = str_replace(".","-","Список сделок, событий и звонков $dateEnd").".xlsx";
$fileDir = "/var/www/html/reportsToMail/everydayManagersMissionsNew/";
$fileAdr = "$fileDir$name";

$objWriter = new PHPExcel_Writer_Excel2007($xls);
//echo ($dateEnd);
$objWriter->save($fileAdr);
//echo ($dateEnd);

$thm = "Сформирован список сделок, событий и звонков за период  $dateBegingSQL - $dateEnd.";
$html = "Сформирован список сделок, событий и звонков за период  $dateBegingSQL - $dateEnd.<br />Отчёт находится в приложенном к письму файле.";

//$mail_to = "crm.report@siz37.ru";
//$mail_to2 = "karelin.ivan@siz37.ru";
$mail_to3 = "manager.890@siz37.ru";


$fp = fopen($fileAdr,"rb");
if (!$fp)
{ print "Cannot open file";
    exit();
}
$file = fread($fp, filesize($fileAdr));
fclose($fp);

$EOL = "\r\n"; // ограничитель строк, некоторые почтовые сервера требуют \n - подобрать опытным путём
$boundary = "--".md5(uniqid(time())); // любая строка, которой не будет ниже в потоке данных.
$headers = "MIME-Version: 1.0;$EOL";
$headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"$EOL";
$headers .= "From: robot@siz37.ru";

$multipart = "--$boundary$EOL";
$multipart .= "Content-Type: text/html; charset=UTF-8$EOL";
$multipart .= "Content-Transfer-Encoding: base64$EOL";
$multipart .= $EOL; // раздел между заголовками и телом html-части
$multipart .= chunk_split(base64_encode($html));

$multipart .= "$EOL--$boundary$EOL";
$multipart .= "Content-Type: application/octet-stream; name=\"$name\"$EOL";
$multipart .= "Content-Transfer-Encoding: base64$EOL";
$multipart .= "Content-Disposition: attachment; filename=\"$name\"$EOL";
$multipart .= $EOL; // раздел между заголовками и телом прикрепленного файла
$multipart .= chunk_split(base64_encode($file));

$multipart .= "$EOL--$boundary--$EOL";

//mail($mail_to, $thm, $multipart, $headers);
//mail($mail_to2, $thm, $multipart, $headers);
mail($mail_to3, $thm, $multipart, $headers);
