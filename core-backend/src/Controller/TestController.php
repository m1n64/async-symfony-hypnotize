<?php

namespace App\Controller;

use App\DTO\PingDTO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function Amp\async;
use function Amp\delay;
use function Amp\Future\await;
use function Amp\ParallelFunctions\parallel;

final class TestController extends AbstractController
{
    #[Route('/api/ping', name: 'ping', methods: ['GET'])]
    public function ping(): Response
    {
        return new JsonResponse(new PingDTO('pong'), 200);
    }

    #[Route('/api/parse-sync', name: 'parse_sync', methods: ['GET'])]
    public function parseSync(): Response
    {
        $data = [];
        $firstOp = function(): array {
            $data = [];
            for ($i = 0; $i < 10; $i++) {
                $data[] = md5(random_bytes(100));
                sleep(1); // emulate long operation
            }

            return $data;
        };

        $secondOp = function(): array {
            $data = [];
            for ($i = 0; $i < 5; $i++) {
                $data[] = uniqid();
                sleep(2); // emulate long operation
            }

            return $data;
        };

        $data['first'] = $firstOp();
        $data['second'] = $secondOp();

        // ~21-25 sec
        return new JsonResponse($data, 200);
    }

    #[Route('/api/parse-async', name: 'parse_async', methods: ['GET'])]
    public function parseAsync(): Response
    {
        $firstOp = function(): array {
            $data = [];
            for ($i = 0; $i < 10; $i++) {
                $data[] = md5(random_bytes(100));
                delay(1.0); // emulate long operation
            }

            return $data;
        };

        $secondOp = function(): array {
            $data = [];
            for ($i = 0; $i < 5; $i++) {
                $data[] = uniqid();
                delay(2.0); // emulate long operation
            }

            return $data;
        };

        $f1 = async($firstOp);
        $f2 = async($secondOp);

        [$first, $second] = await([$f1, $f2]);

        // ~10-15 sec
        return new JsonResponse([
            'first'  => $first,
            'second' => $second,
        ], 200);
    }

    #[Route('/api/parse-parallel', name: 'parse_parallel', methods: ['GET'])]
    public function parseParallel(): Response
    {
        $firstOp = function(): array {
            $data = [];
            for ($i = 0; $i < 10; $i++) {
                $data[] = md5(random_bytes(100));
                delay(1); // emulate long operation
            }

            return $data;
        };

        $secondOp = function(): array {
            $data = [];
            for ($i = 0; $i < 5; $i++) {
                $data[] = uniqid();
                delay(2); // emulate long operation
            }

            return $data;
        };

        $pFirst  = parallel($firstOp);
        $pSecond = parallel($secondOp);

        $f1 = async(fn() => $pFirst());
        $f2 = async(fn() => $pSecond());

        [$first, $second] = await([$f1, $f2]);

        // ~10-11 sec
        return new JsonResponse([
            'first'  => $first,
            'second' => $second,
        ], 200);
    }
}
