<?php

# Initialise variables
$loc = "51.0000,-1.0000";
$APIKEY = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
$geousername = 'demo';

if (isset($_GET['loc'])){
	$loc = $_GET['loc'];
}

// Start by asking GeoNames what place is at this Lat/Long 
$json_string = file_get_contents("http://api.geonames.org/findNearbyPlaceNameJSON?lat=$lat&lng=$lon&username=$geousername");
$parsed_json = json_decode($json_string);
if (isset($_GET['debug'])) echo "<pre><code>" . json_encode($parsed_json, JSON_PRETTY_PRINT) . "</code></pre><br />";
$location_name = $parsed_json->{'geonames'}[0]->{'toponymName'};

// Next, ask forecast.io for the current and daily weather (that is, we skip the minutely and hourly blocks, but get everything else)
$json_string = file_get_contents("http://api.forecast.io/forecast/$APIKEY/$loc?units=uk&exclude=minutely,hourly");
$parsed_json = json_decode($json_string);

if (isset($_GET['debug'])) echo "<pre><code>" . json_encode($parsed_json, JSON_PRETTY_PRINT) . "</code></pre><br />";

$currentphrase = 'The Weather for ' . $location_name . ' is ' . $parsed_json->{'currently'}->{'summary'} . '. ';

# Parts
$tempstring = "";
$windstring = "";
$precipstring = "";
if (isset($parsed_json->{'currently'}->{'temperature'})){
	$tempstring = 'it is ' . number_format($parsed_json->{'currently'}->{'temperature'}) . ' degrees';
}
if (isset($parsed_json->{'currently'}->{'windBearing'})){
	$windspeed = $parsed_json->{'currently'}->{'windSpeed'};
	$windbearing = $parsed_json->{'currently'}->{'windBearing'};
	if ($windspeed <= 3){
		// do nothing
		continue;
	} else {
		$windstring = 'there is a ' . MPHtoBeaufort($windspeed);
		$windstring .= ' from the ' . BearingToCardinal($windbearing);
	}
}
if (isset($parsed_json->{'currently'}->{'precipType'})){
	$precipstring = 'there is a ' . ProbabilityToChance($parsed_json->{'currently'}->{'precipProbability'});
	$precipstring .= ' of ' . PrecipIntensity($parsed_json->{'currently'}->{'precipIntensity'}) . $parsed_json->{'currently'}->{'precipType'};
}

if ($tempstring != "" and $windstring != "" and $precipstring != ""){
	$currentphrase .= ucfirst($tempstring) . ", $windstring and $precipstring.";
} elseif ($windstring != "" and $precipstring != "") {
	$currentphrase .= ucfirst($windstring) . " and $precipstring.";
} elseif ($tempstring != "" and $precipstring != "") {
	$currentphrase .= ucfirst($tempstring) . " and $precipstring.";
} elseif ($tempstring != "" and $windstring != "") {
	$currentphrase .= ucfirst($tempstring) . " and $windstring.";
} else {
	// Only one string, just joining them will work
	$currentphrase .= ucfirst($tempstring . $windstring . $precipstring);
}

// Forecast
$forecast = $parsed_json->{'daily'}->{'data'}[0];

$forecastphase = "Today's forecast is " . $forecast->{'summary'};

# Parts
$tempstring = "";
$windstring = "";
$precipstring = "";
if (isset($forecast->{'temperatureMin'})){
	$tempstring = 'there will be a high of ' . number_format($forecast->{'temperatureMax'}) . ' and a low of ' . number_format($forecast->{'temperatureMin'});
}
if (isset($forecast->{'windBearing'})){
	if ($windspeed <= 3){
		// do nothing
		continue;
	} else {
		$windstring = 'there will be a ' . MPHtoBeaufort($forecast->{'windSpeed'});
		$windstring .= ' from the ' . BearingToCardinal($forecast->{'windBearing'});
	}
}
if (isset($forecast->{'precipType'})){
	$precipstring = 'there is a ' . ProbabilityToChance($forecast->{'precipProbability'});
	$precipstring .= ' of ' . PrecipIntensity($forecast->{'precipIntensity'}) . $forecast->{'precipType'};
}

if ($tempstring != "" and $windstring != "" and $precipstring != ""){
	$forecastphrase .= ucfirst($tempstring) . ", $windstring and $precipstring.";
} elseif ($windstring != "" and $precipstring != "") {
	$forecastphrase .= ucfirst($windstring) . " and $precipstring.";
} elseif ($tempstring != "" and $precipstring != "") {
	$forecastphrase .= ucfirst($tempstring) . " and $precipstring.";
} elseif ($tempstring != "" and $windstring != "") {
	$forecastphrase .= ucfirst($tempstring) . " and $windstring.";
} else {
	// Only one string, just joining them will work
	$forecastphrase .= ucfirst($tempstring . $windstring . $precipstring);
}


echo $currentphrase;
echo ' ' . $forecastphrase;
if (isset($parsed_json->{'alert'})){
	foreach ($parsed_json->{'alert'} as $alert){
		echo ' Alert: ' . $alert->{'description'};
	}
}


function BearingToCardinal($bearing){
	if ($bearing > 348.75 or $bearing <= 11.25){
		return 'North';
	} elseif ($bearing <= 33.75) {
		return 'North North East';
	} elseif ($bearing <= 56.25) {
		return 'North East';
	} elseif ($bearing <= 78.75) {
		return 'East North East';
	} elseif ($bearing <= 101.25) {
		return 'East';
	} elseif ($bearing <= 123.75) {
		return 'East South East';
	} elseif ($bearing <= 146.25) {
		return 'South East';
	} elseif ($bearing <= 168.75) {
		return 'South South East';
	} elseif ($bearing <= 191.25) {
		return 'South';
	} elseif ($bearing <= 213.75) {
		return 'South South West';
	} elseif ($bearing <= 236.25) {
		return 'South West';
	} elseif ($bearing <= 258.75) {
		return 'West South West';
	} elseif ($bearing <= 281.25) {
		return 'West';
	} elseif ($bearing <= 303.75) {
		return 'West North West';
	} elseif ($bearing <= 326.25) {
		return 'North West';
	} elseif ($bearing <= 348.75) {
		return 'North North West';
	}
}

function MPHtoBeaufort($mph){
	// Beaufort definitions
	if ($mph <= 7) {
		return 'light breeze';
	} elseif ($mph <= 12){
		return 'gentle breeze';
	} elseif ($mph <= 17){
		return 'moderate breeze';
	} elseif ($mph <= 24){
		return 'fresh breeze';
	} elseif ($mph <= 30){
		return 'strong breeze';
	} elseif ($mph <= 38){
		return 'moderate gale';
	} elseif ($mph <= 46){
		return 'gale';
	} elseif ($mph <= 54){
		return 'strong gale';
	} elseif ($mph <= 63){
		return 'storm';
	} elseif ($mph <= 73){
		return 'violent storm';
	} else {
		return 'hurricane';
	}
}

function ProbabilityToChance($prob){
	if ($prob <= 0.2){
		return 'remote chance';
	} elseif ($prob <= 0.4){
		return 'slight chance';
	} elseif ($prob <= 0.6){
		return 'strong chance';
	} elseif ($prob <= 0.8){
		return 'possibility';
	} else {
		return 'certainty';
	}
}

function PrecipIntensity($amount){
	// mm/hr
	if ($amount < 0.05){
		return '';
	} elseif ($amount < 0.432){
		return 'very light ';
	} elseif ($amount < 2.54){
		return 'light ';
	} elseif ($amount < 10.2){
		return 'moderate ';
	} else {
		return 'heavy ';
	}
}





?>
