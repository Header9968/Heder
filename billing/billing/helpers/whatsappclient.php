<?php
/**
 * WhatsApp client helper
 */

class WhatsappClient
{
    protected array $config = [];

    /**
     * @var callable|null
     */
    protected $logger = null;

    public function __construct(array $config = [], callable $logger = null)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function isReady(): bool
    {
        return !empty($this->config['token'])
            && !empty($this->config['phone_id'])
            && !empty($this->config['template']);
    }

    public function sendCode(string $phone, string $code): array
    {
        if( ! $this->isReady() )
        {
            return [
                'success' => false,
                'message' => 'WhatsApp integration is not configured'
            ];
        }

        if( $this->shouldStub($phone) )
        {
            $this->log('info', 'WhatsApp stub mode', ['phone' => $this->maskPhone($phone), 'code' => $code]);

            return [
                'success' => true,
                'stub' => true,
                'message' => 'Stub mode',
            ];
        }

        if( ! function_exists('curl_init') )
        {
            return [
                'success' => false,
                'message' => 'cURL extension is required for WhatsApp API'
            ];
        }

        $payload = $this->buildPayload($phone, $code);
        $response = $this->request($payload);

        if( ! $response['success'] )
        {
            $this->log('error', 'WhatsApp send error', [
                'phone' => $this->maskPhone($phone),
                'error' => $response['message'],
                'body' => $response['body'] ?? null
            ]);

            return [
                'success' => false,
                'message' => $response['message']
            ];
        }

        if( ! empty($this->config['debug_phone']) && $this->config['debug_phone'] === $phone )
        {
            $this->log('debug', 'WhatsApp debug phone', ['phone' => $phone, 'code' => $code]);
        }

        $this->log('info', 'WhatsApp code sent', ['phone' => $this->maskPhone($phone)]);

        return [
            'success' => true,
            'response' => $response['body']
        ];
    }

    protected function shouldStub(string $phone): bool
    {
        if( !empty($this->config['stub_mode']) )
        {
            return true;
        }

        if( !empty($this->config['debug_phone']) && $this->config['debug_phone'] === $phone )
        {
            return true;
        }

        return false;
    }

    protected function buildPayload(string $phone, string $code): array
    {
        $language = $this->config['language'] ?? 'ru';

        return [
            'messaging_product' => 'whatsapp',
            'to' => $phone,
            'type' => 'template',
            'template' => [
                'name' => $this->config['template'],
                'language' => [
                    'code' => $language
                ],
                'components' => [
                    [
                        'type' => 'body',
                        'parameters' => [
                            [
                                'type' => 'text',
                                'text' => $code
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }

    protected function request(array $payload): array
    {
        $apiVersion = $this->config['api_version'] ?? 'v18.0';
        $url = "https://graph.facebook.com/{$apiVersion}/" . $this->config['phone_id'] . "/messages";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->config['token'],
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if( $errno )
        {
            return [
                'success' => false,
                'message' => $error ?: 'Curl error',
                'body' => null
            ];
        }

        $decoded = json_decode($body, true);

        if( $status >= 200 && $status < 300 )
        {
            return [
                'success' => true,
                'body' => $decoded
            ];
        }

        $message = $decoded['error']['message'] ?? 'Unknown WhatsApp API error';

        return [
            'success' => false,
            'message' => $message,
            'body' => $decoded
        ];
    }

    protected function maskPhone(string $phone): string
    {
        if( strlen($phone) <= 4 )
        {
            return $phone;
        }

        return str_repeat('*', strlen($phone) - 4) . substr($phone, -4);
    }

    protected function log(string $level, string $message, array $context = []): void
    {
        if( is_callable($this->logger) )
        {
            call_user_func($this->logger, $level, $message, $context);
        }
    }
}
