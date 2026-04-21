<?php
require_once '../../php/config.php';

echo "<h3>Chatbot Debug — Groq</h3>";

$key = getenv('GROQ_API_KEY');
echo "GROQ_API_KEY: " . ($key ? "<span style='color:green'>✅ Set (" . substr($key,0,10) . "...)</span>" : "<span style='color:red'>❌ NOT SET</span>") . "<br>";
echo "cURL enabled: " . (function_exists('curl_init') ? "<span style='color:green'>✅ Yes</span>" : "<span style='color:red'>❌ No</span>") . "<br>";

if ($key && function_exists('curl_init')) {
    $payload = json_encode([
        'model'    => 'llama-3.1-8b-instant',
        'messages' => [
            ['role' => 'system', 'content' => 'You are a helpful assistant.'],
            ['role' => 'user',   'content' => 'Say hello in one word']
        ],
        'max_tokens' => 10
    ]);

    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $key,
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    echo "HTTP Code: <strong>$httpCode</strong><br>";
    if ($error) echo "cURL Error: <span style='color:red'>$error</span><br>";

    $data = json_decode($response, true);
    if (isset($data['choices'][0]['message']['content'])) {
        echo "✅ Groq Response: <strong>" . $data['choices'][0]['message']['content'] . "</strong>";
    } else {
        echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
    }
}
?>
