<?php

namespace Elzdave\Benevolent\Facades;

use Illuminate\Support\Facades\Facade;

class Benevolent extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'benevolent';
    }
}
