<?php
require_once 'vendor/autoload.php';

use App\ContentHelper;
use App\CSVHelper;

$cHelper = new ContentHelper();
$csvHelper = new CSVHelper();
$contents = stream_get_contents(STDIN);
$cHelper->validateContents($contents)->filterContents()->arraySorting('date');
$cHelper->cakeParser($cHelper->getFilteredContents());
$csvContent = $csvHelper::csvCleaner($cHelper->getFilteredContents());
//$csvHelper->outputCsv($csvHelper->csvCleaner($cHelper->getFilteredContents()));