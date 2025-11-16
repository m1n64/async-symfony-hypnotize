<?php

namespace App\MessageHandler;

use App\Message\HashPullMessage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

#[AsMessageHandler]
final class HashPullMessageHandler
{
    public function __construct(
        private HttpClientInterface $httpClient,
    )
    {
    }

    public function __invoke(HashPullMessage $message): void
    {
        for ($i = 0; $i < 10; $i++) {
            $hash = md5(random_bytes(100));

            $this->httpClient->request('POST', $_ENV['WS_BRIDGE_URL'], [
                'json' => [
                    'clientId' => $message->getClientId(),
                    'payload' => [
                        'hash' => $hash,
                    ],
                ],
            ]);
            delay(1.0); // emulate long operation
        }
    }
}
