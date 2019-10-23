<?php
$ip = $_SERVER['REMOTE_ADDR'];
//$ip = '46.133.64.2'; // Olha mobile
$ip = '85.209.44.123'; // Ira work computer
//$ip = '178.133.73.146'; // Ira work mobile
echo 'IP: ' . $ip;
echo '<br/><hr/><br/>';

if (file_exists('DB/SxGeo/SxGeo.php')) {
	echo '<strong>SxGeo</strong><br/>';
	include_once('DB/SxGeo/SxGeo.php');
	$SxGeo = new SxGeo('DB/SxGeo/SxGeo.dat');
	print_r($SxGeo->getCountry($ip));
}

echo '<br/><hr/><br/>';
echo '<strong>geoip-db</strong><br/>';
$json = file_get_contents('https://geoip-db.com/json/' . $ip);
$data = json_decode($json);
print $data->country_code . '<br>';

echo '<br/><hr/><br/>';
echo '<strong>ip-api</strong><br/>';
$json = file_get_contents('http://ip-api.com/json/' . $ip);
$data = json_decode($json);
print_r($data->countryCode . '<br>');


echo '<br/><hr/><br/>';
echo '<strong>GeoIp2</strong><br/>';
require_once 'vendor/autoload.php';
use GeoIp2\Database\Reader;

// This creates the Reader object, which should be reused across lookups.
//$reader = new Reader('GeoIP/GeoIP2-City.mmdb');
//$reader = new Reader('GeoIP/GeoLite2-City.mmdb');
$reader = new Reader('DB/GeoIP/GeoLite2-Country.mmdb');
echo '<pre>';
//print_r($reader);
echo '</pre>';
// Replace "city" with the appropriate method for your database, e.g., "country".
//$record = $reader->city($ip);
$record = $reader->country($ip);
print($record->country->isoCode . "<br/>");

