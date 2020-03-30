<?php

declare(strict_types=1);

/*
 * This file is part of the qandidate/toggle-api package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Qandidate\Application\Toggle;

use Silex\WebTestCase as BaseWebTestCase;

class WebTestCase extends BaseWebTestCase
{
    public function createApplication()
    {
        return require __DIR__.'/../../../../app/bootstrap.php';
    }
}
