<?php

namespace AnourValar\ConfigHelper\Facades;

use Illuminate\Support\Facades\Facade;

class ConfigHelperFacade extends Facade
{
    /**
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \AnourValar\ConfigHelper\ConfigHelper::class;
    }
}
