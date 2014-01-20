<?php
// vim:ts=4:st=4:sw=4:ai

require 'WeatherSentence.php';

if (!isset($_GET['loc'])) $_GET['loc'] = null;
if (!isset($_GET['debug'])) {
	$_GET['debug'] = false;
} else {
	$_GET['debug'] = true;
}

$weather = new WeatherSentence($_GET['loc'], $_GET['debug']);
$weather->SetForecastAPIKey('2be88ca4242c76e8253ac62474851065032d6833');
$weather->SetGeonamesUser('user');
echo $weather->MakeCurrentSentence();
echo ' ';
echo $weather->MakeForecastSentence();
echo ' ';
echo $weather->MakeAlertSentence();

?>
