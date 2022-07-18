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

use Predis\Client;
use Qandidate\Toggle\Operator\LessThan;
use Qandidate\Toggle\OperatorCondition;
use Qandidate\Toggle\Toggle;
use Qandidate\Toggle\ToggleManager;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TogglesEndpointTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = static::createClient();

        $this->loadToggleFixtures($this->client->getContainer()->get('qandidate.toggle.manager'));
    }

    protected function tearDown(): void
    {
        /** @var Client $redisClient */
        $redisClient = $this->client->getContainer()->get('my_redis_client');

        $namespace = $this->client->getContainer()->getParameter('qandidate.toggle.redis.namespace');
        $keys = $redisClient->keys($namespace.'*');

        foreach ($keys as $key) {
            $redisClient->del($key);
        }

        parent::tearDown();
    }

    /** @test */
    public function it_exposes_all_toggles(): void
    {
        $this->client->request('GET', '/toggles');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertJsonStringEqualsJsonString(json_encode([
                [
                    'name' => 'toggling',
                    'conditions' => [
                        [
                            'name' => 'operator-condition',
                            'key' => 'user_id',
                            'operator' => ['name' => 'less-than', 'value' => 42],
                        ],
                    ],
                    'status' => 'conditionally-active',
                    'strategy' => 'affirmative',
                ],
            ]
        ),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_deletes_a_toggle(): void
    {
        $this->client->request('DELETE', '/toggles/toggling');
        $this->assertTrue($this->client->getResponse()->isEmpty());

        $this->client->request('GET', '/toggles');
        $this->assertTrue($this->client->getResponse()->isOK());

        $toggles = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(0, $toggles);
    }

    /** @test */
    public function it_does_not_error_when_deleting_non_existing_toggle(): void
    {
        $this->client->request('DELETE', '/toggles/nothere');

        $this->assertTrue($this->client->getResponse()->isEmpty());
    }

    /** @test */
    public function it_updates_a_toggle(): void
    {
        $toggleData = [
            'name' => 'toggling',
            'conditions' => [
                [
                    'name' => 'operator-condition',
                    'key' => 'company_id',
                    'operator' => ['name' => 'greater-than', 'value' => 42],
                ],
            ],
            'status' => 'conditionally-active',
            'strategy' => 'affirmative',
        ];
        $toggle = json_encode($toggleData);

        $this->client->request('PUT', '/toggles/toggling', [], [], [], $toggle);

        $response = $this->client->getResponse();
        $this->assertTrue($response->isSuccessful());

        // Check the endpoint!
        $this->client->request('GET', '/toggles');

        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertJsonStringEqualsJsonString(
            json_encode([$toggleData]),
            $this->client->getResponse()->getContent()
        );
    }

    /** @test */
    public function it_does_not_allow_changing_the_name_of_a_toggle(): void
    {
        $toggleData = ['name' => 'new-name', 'conditions' => []];
        $toggle = json_encode($toggleData);

        $this->client->request('PUT', '/toggles/toggling', [], [], [], $toggle);

        $response = $this->client->getResponse();
        $this->assertTrue($response->isClientError());
    }

    private function loadToggleFixtures(ToggleManager $manager): void
    {
        // A toggle that will be active is the user id is less than 42
        $toggle = new Toggle('toggling', [
            new OperatorCondition(
                'user_id',
                new LessThan(42)
            ),
        ]);

        $manager->add($toggle);
    }
}
