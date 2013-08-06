<?php

namespace Qandidate\Application\Toggle;

use Silex\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    public function createApplication()
    {
        return require_once __DIR__ . '/../../../../app/bootstrap.php';
    }
}
