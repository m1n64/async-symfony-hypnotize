<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;

final class PullController extends AbstractController
{
    public function __construct(
        protected HttpClientInterface $httpClient,
    )
    {
    }

    #[Route('/api/pull', name: 'app_pull', methods: ['POST'])]
    public function index(Request $request, MessageBusInterface $messageBus): JsonResponse
    {
        $data = $request->toArray();

        $clientId = $data['client_id'] ?? '';
        $action = $data['action'] ?? '';

        switch ($action) {
            case 'getHashes':
                //$messageBus->dispatch(new \App\Message\HashPullMessage($clientId));
                for ($i = 0; $i < 10; $i++) {
                    $hash = md5(random_bytes(100));

                    $this->httpClient->request('POST', $_ENV['WS_BRIDGE_URL'], [
                        'json' => [
                            'client_id' => $clientId,
                            'payload' => [
                                'hash' => $hash,
                            ],
                        ],
                    ]);

                    delay(1.0); // emulate long operation
                }

                break;

            case "getHashesAsync":
//                $messageBus->dispatch(new \App\Message\HashPullMessage($clientId));
                $firstOp = function() use ($clientId): array {
                    $data = [];
                    for ($i = 0; $i < 10; $i++) {
                        $hash = md5(random_bytes(100));

                        $this->httpClient->request('POST', $_ENV['WS_BRIDGE_URL'], [
                            'json' => [
                                'client_id' => $clientId,
                                'payload' => [
                                    'hash' => $hash,
                                ],
                            ],
                        ]);

                        delay(1.0); // emulate long operation
                    }

                    return $data;
                };

                $secondOp = function() use ($clientId): array {
                    $data = [];
                    for ($i = 0; $i < 5; $i++) {
                        $hash = uniqid();

                        $this->httpClient->request('POST', $_ENV['WS_BRIDGE_URL'], [
                            'json' => [
                                'client_id' => $clientId,
                                'payload' => [
                                    'uniqid' => $hash,
                                ],
                            ],
                        ]);

                        delay(2.0); // emulate long operation
                    }

                    return $data;
                };

                $f1 = async($firstOp);
                $f2 = async($secondOp);

                [$first, $second] = await([$f1, $f2]);
                break;
        }

        return $this->json([
            'message' => 'ok',
            'clientId' => $clientId,
            'action' => $action,
            'bridge' => $_ENV['WS_BRIDGE_URL']
        ]);
    }
}
