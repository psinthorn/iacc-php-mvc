<?php
namespace App\Services;

/**
 * LineService — LINE Messaging API Client
 * 
 * Handles all communication with LINE Platform:
 * - Sending text, image, flex messages
 * - Reply and push messages
 * - User profile fetching
 * - Webhook signature validation
 * - Rich menu management
 * 
 * Usage:
 *   $lineService = new LineService($channelAccessToken, $channelSecret);
 *   $lineService->replyText($replyToken, 'Hello!');
 *   $lineService->pushText($userId, 'Your order is confirmed');
 *   $lineService->pushImage($userId, $imageUrl);
 *   $profile = $lineService->getUserProfile($userId);
 */
class LineService
{
    private string $channelAccessToken;
    private string $channelSecret;

    private const API_BASE = 'https://api.line.me/v2/bot';
    private const API_DATA = 'https://api-data.line.me/v2/bot';

    public function __construct(string $channelAccessToken, string $channelSecret)
    {
        $this->channelAccessToken = $channelAccessToken;
        $this->channelSecret = $channelSecret;
    }

    // ========== Webhook Validation ==========

    /**
     * Validate LINE webhook signature
     */
    public function validateSignature(string $body, string $signature): bool
    {
        $hash = hash_hmac('sha256', $body, $this->channelSecret, true);
        $expectedSignature = base64_encode($hash);
        return hash_equals($expectedSignature, $signature);
    }

    // ========== Reply Messages ==========

    /**
     * Reply with text message (using reply token)
     */
    public function replyText(string $replyToken, string $text): array
    {
        return $this->replyMessage($replyToken, [
            ['type' => 'text', 'text' => $text]
        ]);
    }

    /**
     * Reply with multiple messages
     */
    public function replyMessage(string $replyToken, array $messages): array
    {
        return $this->post('/message/reply', [
            'replyToken' => $replyToken,
            'messages' => $messages
        ]);
    }

    // ========== Push Messages ==========

    /**
     * Push text message to a user
     */
    public function pushText(string $userId, string $text): array
    {
        return $this->pushMessage($userId, [
            ['type' => 'text', 'text' => $text]
        ]);
    }

    /**
     * Push image message to a user
     */
    public function pushImage(string $userId, string $originalUrl, ?string $previewUrl = null): array
    {
        return $this->pushMessage($userId, [
            [
                'type' => 'image',
                'originalContentUrl' => $originalUrl,
                'previewImageUrl' => $previewUrl ?? $originalUrl
            ]
        ]);
    }

    /**
     * Push flex message to a user
     */
    public function pushFlex(string $userId, string $altText, array $contents): array
    {
        return $this->pushMessage($userId, [
            [
                'type' => 'flex',
                'altText' => $altText,
                'contents' => $contents
            ]
        ]);
    }

    /**
     * Push multiple messages to a user
     */
    public function pushMessage(string $userId, array $messages): array
    {
        return $this->post('/message/push', [
            'to' => $userId,
            'messages' => $messages
        ]);
    }

    // ========== User Profile ==========

    /**
     * Get user profile by LINE userId
     */
    public function getUserProfile(string $userId): array
    {
        return $this->get("/profile/{$userId}");
    }

    // ========== Message Content ==========

    /**
     * Get binary content (image, video, audio) by messageId
     * Returns raw binary data
     */
    public function getMessageContent(string $messageId): ?string
    {
        $url = self::API_DATA . "/message/{$messageId}/content";
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->channelAccessToken
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return ($httpCode === 200) ? $response : null;
    }

    // ========== Flex Message Templates ==========

    /**
     * Build order confirmation flex message
     */
    public function buildOrderConfirmFlex(string $orderRef, array $items, float $total, string $currency = 'THB'): array
    {
        $itemBubbles = [];
        foreach ($items as $item) {
            $itemBubbles[] = [
                'type' => 'box',
                'layout' => 'horizontal',
                'contents' => [
                    ['type' => 'text', 'text' => $item['name'] ?? 'Item', 'size' => 'sm', 'flex' => 3],
                    ['type' => 'text', 'text' => 'x' . ($item['qty'] ?? 1), 'size' => 'sm', 'flex' => 1, 'align' => 'center'],
                    ['type' => 'text', 'text' => number_format($item['price'] ?? 0, 2), 'size' => 'sm', 'flex' => 2, 'align' => 'end']
                ]
            ];
        }

        return [
            'type' => 'bubble',
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => 'Order Confirmation', 'weight' => 'bold', 'size' => 'lg', 'color' => '#1DB446'],
                    ['type' => 'text', 'text' => $orderRef, 'size' => 'xs', 'color' => '#aaaaaa']
                ]
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => array_merge(
                    $itemBubbles,
                    [
                        ['type' => 'separator', 'margin' => 'md'],
                        [
                            'type' => 'box',
                            'layout' => 'horizontal',
                            'margin' => 'md',
                            'contents' => [
                                ['type' => 'text', 'text' => 'Total', 'weight' => 'bold', 'size' => 'md', 'flex' => 3],
                                ['type' => 'text', 'text' => $currency . ' ' . number_format($total, 2), 'weight' => 'bold', 'size' => 'md', 'flex' => 3, 'align' => 'end']
                            ]
                        ]
                    ]
                )
            ],
            'footer' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    [
                        'type' => 'button',
                        'action' => [
                            'type' => 'postback',
                            'label' => 'Confirm Order',
                            'data' => 'action=confirm_order&ref=' . $orderRef
                        ],
                        'style' => 'primary',
                        'color' => '#1DB446'
                    ]
                ]
            ]
        ];
    }

    /**
     * Build payment slip received flex message
     */
    public function buildPaymentReceivedFlex(string $orderRef, float $amount, string $currency = 'THB'): array
    {
        return [
            'type' => 'bubble',
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => 'Payment Slip Received', 'weight' => 'bold', 'size' => 'lg', 'color' => '#1DB446'],
                    ['type' => 'text', 'text' => 'Order: ' . $orderRef, 'size' => 'sm', 'margin' => 'md'],
                    ['type' => 'text', 'text' => 'Amount: ' . $currency . ' ' . number_format($amount, 2), 'size' => 'sm'],
                    ['type' => 'text', 'text' => 'We will verify your payment shortly.', 'size' => 'xs', 'color' => '#888888', 'margin' => 'lg', 'wrap' => true]
                ]
            ]
        ];
    }

    /**
     * Build booking confirmation flex message
     */
    public function buildBookingFlex(string $orderRef, string $date, string $time, string $name): array
    {
        return [
            'type' => 'bubble',
            'header' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => 'Booking Confirmed', 'weight' => 'bold', 'size' => 'lg', 'color' => '#1DB446']
                ]
            ],
            'body' => [
                'type' => 'box',
                'layout' => 'vertical',
                'contents' => [
                    ['type' => 'text', 'text' => 'Ref: ' . $orderRef, 'size' => 'sm', 'color' => '#aaaaaa'],
                    ['type' => 'separator', 'margin' => 'md'],
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'margin' => 'md',
                        'contents' => [
                            ['type' => 'text', 'text' => 'Name', 'size' => 'sm', 'color' => '#888888', 'flex' => 2],
                            ['type' => 'text', 'text' => $name, 'size' => 'sm', 'flex' => 4]
                        ]
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            ['type' => 'text', 'text' => 'Date', 'size' => 'sm', 'color' => '#888888', 'flex' => 2],
                            ['type' => 'text', 'text' => $date, 'size' => 'sm', 'flex' => 4]
                        ]
                    ],
                    [
                        'type' => 'box',
                        'layout' => 'horizontal',
                        'contents' => [
                            ['type' => 'text', 'text' => 'Time', 'size' => 'sm', 'color' => '#888888', 'flex' => 2],
                            ['type' => 'text', 'text' => $time, 'size' => 'sm', 'flex' => 4]
                        ]
                    ]
                ]
            ]
        ];
    }

    // ========== HTTP Helpers ==========

    private function post(string $endpoint, array $data): array
    {
        return $this->request('POST', self::API_BASE . $endpoint, $data);
    }

    private function get(string $endpoint): array
    {
        return $this->request('GET', self::API_BASE . $endpoint);
    }

    private function request(string $method, string $url, ?array $data = null): array
    {
        $ch = curl_init($url);
        $headers = [
            'Authorization: Bearer ' . $this->channelAccessToken,
            'Content-Type: application/json'
        ];

        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = json_encode($data);
        }

        curl_setopt_array($ch, $opts);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error, 'http_code' => 0];
        }

        $decoded = json_decode($response, true) ?? [];
        $decoded['http_code'] = $httpCode;
        $decoded['success'] = ($httpCode >= 200 && $httpCode < 300);

        return $decoded;
    }
}
