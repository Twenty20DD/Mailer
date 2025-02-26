<?php

namespace Twenty20\Mailer\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Twenty20\Mailer\Mailer
 */
class Mailer extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Twenty20\Mailer\Mailer::class;
    }
}
