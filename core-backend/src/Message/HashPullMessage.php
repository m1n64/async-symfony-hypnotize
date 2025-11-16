<?php

namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('roadrunner')]
final readonly class HashPullMessage
{
     public function __construct(
         private string $clientId,
     )
     {
     }

    public function getClientId(): string
    {
        return $this->clientId;
    }
}
