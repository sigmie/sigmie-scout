<?php

namespace Sigmie\Scout;

use Illuminate\Support\Facades\Facade;

class SigmieScoutFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'sigmie-scout';
    }
}
