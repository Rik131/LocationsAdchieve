
<?php

// Calculate distance between two locations given their latitude and longitude
function distance($lat1, $lat2, $lon1, $lon2){

  $lat1 = deg2rad($lat1);
  $lat2 = deg2rad($lat2);
  $lon1 = deg2rad($lon1);
  $lon2 = deg2rad($lon2);
  
  $a = sin($lat1)*sin($lat2) + cos($lat1) * cos($lat2) * cos($lon2 - $lon1); //Haversine formula

  $c = acos($a);
  
  $r = 6371; //Earth radius in km

    return $c * $r;
}

// Query positionstack API with a location
function query($location){

  $queryString = http_build_query([
    'access_key' => '9eb61b6aa98a57d4201f19b0253c92aa',
    'query' => $location,
    'output' => 'json',
    'limit' => 1,
  ]);
  
  $ch = curl_init(sprintf('%s?%s', 'http://api.positionstack.com/v1/forward', $queryString));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  
  $json = curl_exec($ch);
  
  curl_close($ch);
  
  $apiResult = json_decode($json, true);
  
  return $apiResult["data"][0];
}

#Read out locations using "=" as a delimiter to separate name and location
$locations = array_map(function($l) { return str_getcsv($l, '='); }, file('locations.txt'));


//Query for each locations coordinates
foreach ($locations as &$location){
  $answer = query($location[1]);
  $location["latitude"] = $answer["latitude"];
  $location["longitude"] = $answer["longitude"];  
}

$lat1 = $locations[0]["latitude"]; //Initialize coordinates of HQ
$lon1 = $locations[0]["longitude"];

//Calculate distance from HQ to each location
foreach ($locations as &$location){
  $lat2 = $location["latitude"];
  $lon2 = $location["longitude"];
  $dist = round(distance($lat1, $lat2, $lon1, $lon2),2);
  $location["distance"] = $dist;
}


$distance = array_column($locations, "distance");

array_multisort($distance, SORT_ASC, $locations);

print_r($locations);

$results = fopen("distances.csv", "w") or die("Unable to open file!");
$rank = 1;
foreach ($locations as &$location){
  if ($location["distance"] == 0){continue;} //Skip HQ location in output
  $txt = "{$rank}, ";
  fwrite($results, $txt);
  $txt = "{$location["distance"]} km, ";
  fwrite($results, $txt);
  $txt = "{$location[0]}, ";
  fwrite($results, $txt);
  $txt = "{$location[1]}\r\n";
  fwrite($results, $txt);

  $rank++;
}
fclose($results);



?>
