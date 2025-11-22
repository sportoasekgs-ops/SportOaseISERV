<?php

namespace SportOase;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SportOaseBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
