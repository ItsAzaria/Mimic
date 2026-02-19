<?php

namespace App\Services;

class Pastecord
{
    protected $client;

    const API_BASE_URL = 'https://pastecord.com';

    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client([
            'base_uri' => self::API_BASE_URL,
            'timeout' => 5.0,
        ]);
    }

    public function upload(string $content): string
    {
        try {
            // post with content in the body not form nor json
            $response = $this->client->post('/documents', [
                'body' => $content,
                'headers' => [
                    'Content-Type' => 'text/plain',
                ],
            ]);

            $data = json_decode($response->getBody(), true);

            return self::API_BASE_URL.'/'.$data['key'];
        } catch (\Exception $e) {
            return null;
        }
    }
}
