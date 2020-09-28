<?php
require_once("Partner_Data.php");

$Data_partner = array();

$data_LADD = Partner_Data::LADD_Data();
$data_DNZL = Partner_Data::DNZL_Data();

$Data_partner = array_merge($data_LADD, $data_DNZL);
Partner_Data::Partner_Init($Data_partner);


?>