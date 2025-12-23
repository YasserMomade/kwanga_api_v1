<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class SmsService
{

    // public function send(string $phone, string $message) //: void
    // {

    //     $token = config('mozesms.token');

    //     // if (1 == 1) {
    //     //     return response()->json([
    //     //         'data' => 'ola'
    //     //     ]);
    //     // }

    //     if (!$token) {
    //         throw new Exception('MOZESMS_TOKEN não configurado.');
    //     }

    //     $baseUrl = rtrim(config('mozesms.base_url', 'https://api.mozesms.com'), '/');
    //     $senderId = config('mozesms.sender_id');

    //     $phone = ltrim($phone, '+');

    //     //$response = HTTp::timeout(config('mozesms.timeout'))
    //     $response = Http::withOptions(['verify' => false])
    //         ->withHeaders([
    //             'Authorization' => 'Bearer ' . $token,
    //             'Content-Type' => 'application/json',
    //         ])->post($baseUrl . '/v2/sms/send', [
    //             'phone' => $phone,
    //             'message' => $message,
    //             'sender_id' => $senderId,
    //         ]);

    //     if (! $response->successful()) {
    //         $body = $response->json();
    //         $msg = is_array($body) ? ($body['message'] ?? null) : null;

    //         throw new Exception($msg ?: ('Falha ao enviar SMS. HTTP ' . $response->status()));
    //     }
    // }


    public function send(String $phone, String $message): void
    {

        //TODO: codificar o codigo de envio de dmd

        logger()->info("SMS to {$phone}: {$message}");
    }
}
