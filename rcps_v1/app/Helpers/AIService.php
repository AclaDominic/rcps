<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Http;

class LlamaService
{
    public function ask(string $prompt)
    {
        $response = Http::post('http://localhost:11434/api/generate', [
            'model'   => 'llama3',
            'prompt'  => $prompt,
            'stream'  => false, // para hindi chunked output
        ]);

        if (!$response->successful()) {
            throw new \Exception('Llama request failed');
        }

        $json = $response->json();

        // Response is always inside “response”
        return $json['response'];
    }

    public function breakdown(string $task)
    {
        $prompt = "
        Break down this task into 3–6 smaller actionable tasks.
        Respond ONLY with a valid JSON array:

        [
          {
            \"title\": \"Task title\",
            \"description\": \"Short description\",
            \"complexity\": 1,
            \"dependency\": null
          }
        ]

        MAIN_TASK: $task
        ";

        // Call ask() function
        $raw = $this->ask($prompt);

        // Convert JSON string to array
        return json_decode($raw, true);
    }
}
