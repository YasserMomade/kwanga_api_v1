<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;


class SmsService
{

    public function send(string $phone, string $message): void
    {
        $token = config('mozesms.token');
        $sender = config('mozesms.sender');
        $url = config('mozesms.url');

        if (! $token || ! $url || ! $sender) {
            throw new Exception('Configuração do MozeSMS incompleta.');
        }


        $phone = ltrim($phone, '+');
        $response = Http::withOptions(['verify' => false])->withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($url, [
            'phone' => $phone,
            'message' => $message,
            'sender_id' => $sender,
        ]);

        if ($response->failed()) {
            logger()->error('Erro MozeSMS', [
                'status' => $response->status(),
                'body' => $response->body(),
                'json' => $response->json(),
            ]);
            throw new Exception('Falha ao enviar SMS.');
        }

        logger()->info('SMS enviado com sucesso', [
            'phone' => $phone,
        ]);
    }
}


/** 

    public function send(String $phone, String $message): void
    {
        logger()->info("SMS to {$phone}: {$message}");
    }

 */
