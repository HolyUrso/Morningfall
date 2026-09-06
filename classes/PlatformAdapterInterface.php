<?php

interface PlatformAdapterInterface
{
    public function resolve(string $username): array;
}
