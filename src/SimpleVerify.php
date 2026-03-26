<?php

namespace SimpleVerify;

class SimpleVerify
{
    public static function make(string|array $config): Client
    {
        return new Client($config);
    }
}
