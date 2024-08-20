<?php

namespace App\Services;

/**
 * Probably should return file to sign docs in the real App
 */
class CredentialsDTO
{
    public function __construct(
        private string $key,
        private string $cert
    )
    {

    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getCert(): string
    {
        return $this->cert;
    }

}
