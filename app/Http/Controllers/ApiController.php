<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use DateTime;

class ApiController extends Controller
{
    public function CreateRequest(Request $request){

        $seed = Carbon::now()->toIso8601String();
        $nonce = random_bytes(16);
        $nonceBase64 = base64_encode($nonce);
        $dataToHash = $nonce . $seed . env('SECRETKEY_TEST');
        $now = new DateTime();
        $now->add(new \DateInterval('PT10M')); // 'PT5M' significa un periodo de tiempo de 5 minutos
        $expirationDate = $now->format(DateTime::ISO8601);
        $json_request = [
            
            "auth"=> [
                "login" => env('LOGIN_KEY_TEST'),
                "tranKey"=> base64_encode(hash('sha256', $dataToHash, true)),
                //"tranKey" => "SnZP3D63n3I9dH9O",
                "nonce"=> $nonceBase64,
                "seed" => $seed
            ],
            "locale"=> "es_CL",
            "buyer"=> [
                "name"=> $request->get('name_pax'),
                "surname"=> $request->get('surname_pax'),
                "email"=> $request->get('email_pax'),
                "document"=> $request->get('rut_pax'),
                "documentType"=> "CLRUT",
                "mobile"=> $request->get('phone_pax')
            ],
            "payment" => [
                "reference" => $request->get('num_file'),
                "description"=> $request->get('pay_description'),
                "amount"=> [
                    "currency"=> "CLP",
                    "total"=> $request->get('amount_pay')
                ]
            ],
            "expiration" => $expirationDate,
            "ipAddress" => $request->get('ip_address'),
            "returnUrl" => $request->get('return_url'),
            "userAgent" => empty($request->get('userAgent'))? $request->get('userAgent') : "Mozilla/5.0, AppleWebKit/537.36, Chrome/104.0.0.0 Safari/537.36"
        ];
        $req = curl_init(env('URL_GETNET_TEST').'/api/session');
        curl_setopt($req,CURLOPT_POST,1);
        curl_setopt($req,CURLOPT_POSTFIELDS,json_encode($json_request));
        curl_setopt($req, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json"
        ]);
        curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($req,CURLOPT_RETURNTRANSFER,true);

        /*curl_setopt($req, CURLOPT_VERBOSE, true);
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($req, CURLOPT_STDERR, $verbose);*/

        //return json_encode($json_request);
        $response = curl_exec($req);
        if ($response === false) {
            $error = curl_error($req);
            curl_close($req);
            return response()->json(['error' => $error], 500); // Devuelve el error como respuesta JSON
        }
        /*rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        echo "Verbose information:\n", htmlspecialchars($verboseLog), "\n";*/
        //echo $response;
        curl_close($req);
        return response()->json(str_replace("\\/", "/",$response),200);
    }
}
