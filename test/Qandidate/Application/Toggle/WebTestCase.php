<?php

namespace Qandidate\Application\Toggle;

use Silex\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    public function createApplication()
    {
        return require __DIR__ . '/../../../../app/bootstrap.php';
    }
}
