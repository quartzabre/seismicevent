<?php

$source_url = 'https://data.tmd.go.th/api/DailySeismicEvent/v1/?uid=api&ukey=api12345';

$response = file_get_contents($source_url);

$xml = simplexml_load_string($response) or die("Error: Cannot create object");
// var_dump($xml);

$lastupdate = file_get_contents(__DIR__.'/lastupdate.txt');
$lastupdate = strtotime($lastupdate);

foreach ($xml->DailyEarthquakes ?? [] as $item) {
	if (4 <= $item->Magnitude && $lastupdate < strtotime($item->DateTimeThai)) {
		// var_dump($item);
		$message = [];
		$message[] = 'เตือนแผ่นดินไหว';
		$message[] = $item->TitleThai;
		$message[] = "Datetime: {$item->DateTimeThai}";
		$message[] = "Magnitude: {$item->Magnitude}";
		$message[] = "Depth: {$item->Depth} km";
		$message[] = "Location: https://www.google.com/maps/search/?api=1&query={$item->Latitude}%2C{$item->Longitude}";
		$message = join("\r\n", $message);
		file_put_contents(__DIR__.'/lastupdate.txt', date('Y-m-d H:i:s'));
		push_to_sns($message);
		break;
	}
}


function push_to_sns($message, $topic = '') {
	$accessToken = 'YOUR-ACCESS-TOKEN';
	$arrayHeader = array();
	$arrayHeader[] = "Content-Type: application/json";
	$arrayHeader[] = "Authorization: Bearer {$accessToken}";

	$arrayPostData['to'] = 'YOUR-DESTINATION-UID';
	$arrayPostData['messages'][0]['type'] = "text";
	$arrayPostData['messages'][0]['text'] = $message;

	$url = "https://api.line.me/v2/bot/message/push";
	
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
	curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayPostData));
	return curl_exec($ch);
}

/* End of file seismicevent.php */