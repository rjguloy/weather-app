<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use \Illuminate\Routing\ResponseFactory;

class WeatherController extends Controller
{
    public function callWeather($lat, $long) {
        
        $ch = curl_init();
        $headers = [
            'Authorization: '.env('API_WEATHER', ''),
            'accept: application/json'
        ];  
        
        curl_setopt($ch, CURLOPT_URL, "http://api.openweathermap.org/data/2.5/forecast?lat={$lat}&lon={$long}&units=metric&appid=".env('API_WEATHER', ''));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $body = '{}';

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_POSTFIELDS,$body);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Timeout in seconds
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $authToken = curl_exec($ch);

        return response()->json($authToken);
    }
}
