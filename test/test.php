<?php

//test date

$stop_date = date('Y-m-d 00:00:00');
$tomorrow = date('Y-m-d', strtotime($stop_date . ' +1 day'));

echo($tomorrow);
