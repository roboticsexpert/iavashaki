<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class TelegramMessenger
{

    private string $apiKey = '6395035803:AAETnKuPytF33PC4X6yfd8XH6qo6X3InoHA';

    /**
     * @param Client $client
     */
    public function __construct(readonly Client $client)
    {
    }

    public function sendMessage(string $message): bool
    {

        $data = [
            'chat_id' => '@iavashaki_channel',
            'text' => $message,
        ];

        try {
            $this->client->get("http://api.telegram.org/bot{$this->apiKey}/sendMessage", [
                RequestOptions::QUERY => $data,
//            RequestOptions::PROXY => 'socks5h://127.0.0.1:1089',
                RequestOptions::TIMEOUT => 2,
            ]);
            return true;
        } catch (\Exception $exception) {

        }
        return false;
    }
}
