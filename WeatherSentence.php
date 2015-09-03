<?php
// vim:ts=4:st=4:sw=4:ai

class WeatherSentence
{
    // properties
    public $loc = "51.0000,-1.0000";
    protected $APIKEY = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';
    protected $geousername = 'demo';
    private $weather;
	private $debug = false;
    
    // methods
    public function __construct($loc = null, $debug = false)
	{
		if (isset($loc) and $loc != null){
			$this->loc = $loc;
		}
		$this->debug = $debug;
    }
    
    public function SetForecastAPIKey($key)
    {
        $this->APIKEY = $key;
    }
    
    public function SetGeonamesUser($user)
    {
        $this->geousername = $user;
    }
    
    public function LookupLocation()
    {
        // Convert a Lat/Long to a placename using Geonames
        if ($this->geousername == '' or $this->geousername == 'demo') {
            // Don't lookup without a (potentially) valid username
            return 'your location';
        }
        
        list($lat, $lon) = explode(',', $this->loc);
        $json_string = file_get_contents("http://api.geonames.org/findNearbyPlaceNameJSON?lat=$lat&lng=$lon&username=$this->geousername");
        $parsed_json = json_decode($json_string);
        if ($this->debug)
            echo "<pre><code>" . json_encode($parsed_json, JSON_PRETTY_PRINT) . "</code></pre><br />";
        if (isset($parsed_json->geonames[0]->name)) {
            $location_name = $parsed_json->geonames[0]->name;
        } else {
            $location_name = 'your location';
        }
        
        return $location_name;
    }
    
    private function fetchWeather($force = false)
    {
        if (isset($this->weather) and !$force) {
            return $this->weather;
        }
        
        if ($this->APIKEY == '' or $this->APIKEY == 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx') {
            return null;
        }
        
        // Otherwise, fetch the current and daily weather (that is, we skip the minutely and hourly blocks, but get everything else)
        $json_string   = file_get_contents("http://api.forecast.io/forecast/$this->APIKEY/$this->loc?units=uk&exclude=minutely,hourly");
        $this->weather = json_decode($json_string);
        if ($this->debug)
            echo "<pre><code>" . json_encode($this->weather, JSON_PRETTY_PRINT) . "</code></pre><br />";
        
        return $this->weather;
    }
    
    public function MakeCurrentSentence($weather = null)
    {
        $currentphrase = '';
        $tempstring    = '';
        $windstring    = '';
        $precipstring  = '';
        
        if (!isset($weather) or $weather == null) {
            $weather = $this->fetchWeather();
        }
        
        $currentphrase = 'The weather for ' . $this->LookupLocation() . ' is ' . $weather->currently->summary . '. ';
        
        // Parts
        if (isset($weather->currently->temperature)) {
            $tempstring = 'it is ' . number_format($weather->currently->temperature) . ' degrees';
        }
        if (isset($weather->currently->windBearing)) {
            $windspeed   = $weather->currently->windSpeed;
            $windbearing = $weather->currently->windBearing;
            if ($windspeed <= 3) {
                // do nothing
                ;
            } else {
                $windstring = 'there is a ' . $this->MPHtoBeaufort($windspeed);
                $windstring .= ' from the ' . $this->BearingToCardinal($windbearing);
            }
        }
        if (isset($weather->currently->precipType)) {
            $precipstring = 'there is a ' . $this->ProbabilityToChance($weather->currently->precipProbability);
            $precipstring .= ' of ' . $this->PrecipIntensity($weather->currently->precipIntensity) . $weather->currently->precipType;
        }
        
        if ($tempstring != "" and $windstring != "" and $precipstring != "") {
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

		if (isset($weather->currently->nearestStormDistance) and ($weather->currently->nearestStormDistance < 100)) {
			$currentphrase .= ' There is a storm ';
			if ($weather->currently->nearestStormDistance < 1) {
				$currentphrase .= 'here.';
			} else {
			    $currentphrase .= round($weather->currently->nearestStormDistance, 1);
				if (isset($weather->currently->nearestStormDistance)) {
					$currentphrase .= ' miles to the  ' . $this->BearingToCardinal($weather->currently->nearestStormBearing) . '.';
				} else {
					$currentphrase .= ' miles away.';
				}
			}
		}
        
        return $currentphrase;
    }
    
    public function MakeForecastSentence($weather = null)
    {
        $forecastphrase = '';
        $tempstring     = '';
        $windstring     = '';
        $precipstring   = '';
        
        if (!isset($weather) or $weather == null) {
            $weather = $this->fetchWeather();
        }
        
        // Forecast
        $forecast = $weather->daily->data[0];
        
        $forecastphrase = "Today's forecast is " . $forecast->summary . ' ';
        
        // Parts
        $tempstring   = "";
        $windstring   = "";
        $precipstring = "";
        if (isset($forecast->temperatureMin)) {
            $tempstring = 'there will be a high of ' . number_format($forecast->temperatureMax) . ' and a low of ' . number_format($forecast->temperatureMin);
        }
        if (isset($forecast->windBearing)) {
            if ($forecast->windSpeed <= 3) {
                // do nothing
                ;
            } else {
                $windstring = 'there will be a ' . $this->MPHtoBeaufort($forecast->windSpeed);
                $windstring .= ' from the ' . $this->BearingToCardinal($forecast->windBearing);
            }
        }
        if (isset($forecast->precipType)) {
            $precipstring = 'there is a ' . $this->ProbabilityToChance($forecast->precipProbability);
            $precipstring .= ' of ' . $this->PrecipIntensity($forecast->precipIntensity) . $forecast->precipType;
        }
        
        if ($tempstring != "" and $windstring != "" and $precipstring != "") {
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
        
        return $forecastphrase;
    }
    
    public function MakeAlertSentence($weather = null)
    {
        $alertphrase = '';
        
        if (!isset($weather) or $weather == null) {
            $weather = $this->fetchWeather();
        }

		$alertlist = array();

        if (isset($weather->alerts)) {
            foreach ($weather->alerts as $alert) {
				if ($alert->expires > time()){
					// You may want $alert->description here,
					// but I found that to be TOO verbose
					if (!in_array($alert->title, $alertlist)){
		                $alertphrase .= ' Alert: ' . $alert->title;
						array_push($alertlist, $alert->title);
					}
				}
            }
        }
		return $alertphrase;
    }
    
    private function BearingToCardinal($bearing)
    {
        if ($bearing > 348.75 or $bearing <= 11.25) {
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
    
    private function MPHtoBeaufort($mph)
    {
        // Beaufort definitions
        if ($mph <= 7) {
            return 'light breeze';
        } elseif ($mph <= 12) {
            return 'gentle breeze';
        } elseif ($mph <= 17) {
            return 'moderate breeze';
        } elseif ($mph <= 24) {
            return 'fresh breeze';
        } elseif ($mph <= 30) {
            return 'strong breeze';
        } elseif ($mph <= 38) {
            return 'moderate gale';
        } elseif ($mph <= 46) {
            return 'gale';
        } elseif ($mph <= 54) {
            return 'strong gale';
        } elseif ($mph <= 63) {
            return 'storm';
        } elseif ($mph <= 73) {
            return 'violent storm';
        } else {
            return 'hurricane';
        }
    }
    
    private function ProbabilityToChance($prob)
    {
        if ($prob <= 0.2) {
            return 'remote chance';
        } elseif ($prob <= 0.4) {
            return 'slight chance';
        } elseif ($prob <= 0.6) {
            return 'strong chance';
        } elseif ($prob <= 0.8) {
            return 'possibility';
        } else {
            return 'certainty';
        }
    }
    
    private function PrecipIntensity($amount)
    {
        // mm/hr
        if ($amount < 0.05) {
            return '';
        } elseif ($amount < 0.432) {
            return 'very light ';
        } elseif ($amount < 2.54) {
            return 'light ';
        } elseif ($amount < 10.2) {
            return 'moderate ';
        } else {
            return 'heavy ';
        }
    }
}


?>
