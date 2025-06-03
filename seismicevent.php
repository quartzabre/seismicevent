<?php

$current_lat = 12.3456789;
$current_lon = 123.4567891;

$source_url = 'https://data.tmd.go.th/api/DailySeismicEvent/v1/?uid=api&ukey=api12345';

$response = file_get_contents($source_url);

$xml = simplexml_load_string($response) or die("Error: Cannot create object");
// var_dump($xml);

$lastupdate = file_get_contents(__DIR__.'/lastupdate.txt');
$lastupdate = strtotime($lastupdate);

foreach ($xml->DailyEarthquakes ?? [] as $item) {
	if (4.5 <= $item->Magnitude && $lastupdate < strtotime($item->DateTimeThai)) {
		var_dump($item);
		$distance = round(distance((float)$item->Latitude, (float)$item->Longitude, $current_lat, $current_lon));
		$direction = bearing($current_lat, $current_lon, (float)$item->Latitude, (float)$item->Longitude)[1] ?? '';
		$message = [];
		// $message[] = 'เตือนแผ่นดินไหว';
		$message[] = $item->TitleThai;
		$message[] = "Datetime: {$item->DateTimeThai}";
		$message[] = "Magnitude: {$item->Magnitude}";
		$message[] = "Depth: {$item->Depth} km";
		$message[] = "Distance: {$distance} km {$direction}";
		$message[] = "Location: https://www.google.com/maps/search/?api=1&query={$item->Latitude}%2C{$item->Longitude}";
		$message = join("\r\n", $message);
		file_put_contents(__DIR__.'/lastupdate.txt', date('Y-m-d H:i:s'));
		push_to_sns($message);
		break;
	}
}


function push_to_sns($message, $topic = '') {
	$accessToken = 'LINE_MESSAGIN_API_ACCESS_TOKEN';
	$arrayHeader = array();
	$arrayHeader[] = "Content-Type: application/json";
	$arrayHeader[] = "Authorization: Bearer {$accessToken}";

	$arrayPostData['to'] = 'LINE_USER_ID';
	$arrayPostData['messages'][0]['type'] = "text";
	$arrayPostData['messages'][0]['text'] = $message;

	$url = "https://api.line.me/v2/bot/message/push";
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayPostData));
	return curl_exec($ch);
}

function distance($lat1, $lon1, $lat2, $lon2, $unit = 'K') {
  if (($lat1 == $lat2) && ($lon1 == $lon2)) {
    return 0;
  }
  else {
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
      return ($miles * 1.609344);
    } else if ($unit == "N") {
      return ($miles * 0.8684);
    } else {
      return $miles;
    }
  }
}

function bearing($lat1, $lon1, $lat2, $lon2) {
  if (round($lon1, 1) == round($lon2, 1)) {
    if ($lat1 < $lat2) {
      $bearing = 0;
    } else {
      $bearing = 180;
    }
  } else {
    $dist = distance($lat1, $lon1, $lat2, $lon2, 'N');
    $arad = acos((sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($dist / 60))) / (sin(deg2rad($dist / 60)) * cos(deg2rad($lat1))));
    $bearing = $arad * 180 / pi();
    if (sin(deg2rad($lon2 - $lon1)) < 0) {
      $bearing = 360 - $bearing;
    }
  }
  $bearing = round($bearing);
  if ($bearing === 0) {
  	$bearing = 360;
  }

  if ($bearing < 22.5) {
  	$dir = 'N';
  } elseif ($bearing < 22.5 + 45) {
  	$dir = 'NE';
  } elseif ($bearing < 22.5 + 90) {
  	$dir = 'E';
  } elseif ($bearing < 22.5 + 135) {
  	$dir = 'SE';
  } elseif ($bearing < 22.5 + 180) {
  	$dir = 'S';
  } elseif ($bearing < 22.5 + 225) {
  	$dir = 'SW';
  } elseif ($bearing < 22.5 + 270) {
  	$dir = 'W';
  } elseif ($bearing < 22.5 + 315) {
  	$dir = 'NW';
  } else {
  	$dir = 'N';
  }
  return [$bearing, $dir];
} 

/* End of file seismicevent.php */