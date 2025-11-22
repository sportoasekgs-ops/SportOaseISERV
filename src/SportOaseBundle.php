<?php

namespace SportOase;

use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SportOaseBundle extends AbstractBundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
