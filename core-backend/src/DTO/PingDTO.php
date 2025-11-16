<?php
declare(strict_types=1);

namespace App\DTO;

final class PingDTO
{
    public function __construct(
        public string $message,
    )
    {
    }
}