<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use \Illuminate\Routing\ResponseFactory;

class CityController extends Controller
{
    //
    public function getCountries() {
        return response()->json("Getting countries");
    }


    public function CallFourSquareAPI() {
        
        $ch = curl_init();
        $headers = [
            'Authorization: fsq3ojQeNxEpItChqKEdwKxFMdT8gl1sHafG5cYryz/Z6BI=',
            'accept: application/json'
        ];
        curl_setopt($ch, CURLOPT_URL, 'https://api.foursquare.com/v3/places/search');
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
