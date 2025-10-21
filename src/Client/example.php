<?php 

use Riyad\PolyCourier\Client\Http;
use Riyad\PolyCourier\Client\HttpException;
use Exception;

$http = new Http('https://api.example.com');
$http->setBearerToken('YOUR_TOKEN');

try {
    // JSON request and response
    $jsonData = $http->request(
        'users',
        'GET',
        [],
        [],
        ['page' => 1],
        'application/json'
    )->json();
    print_r($jsonData);

    // Raw response
    $rawData = $http->request(
        'status',
        'GET'
    )->body();
    echo $rawData;

} catch (HttpException $e) {
    echo "Error: " . $e->getMessage();
}