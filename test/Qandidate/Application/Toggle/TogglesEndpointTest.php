<?php

/*
 * This file is part of the qandidate/toggle-api package.
 *
 * (c) Qandidate.com <opensource@qandidate.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Qandidate\Application\Toggle;

use Qandidate\Toggle\Operator\LessThan;
use Qandidate\Toggle\OperatorCondition;
use Qandidate\Toggle\Toggle;
use Qandidate\Toggle\ToggleManager;

class TogglesEndpointTest extends WebTestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->loadToggleFixtures($this->app['toggle.manager']);
    }

    /**
     * @test
     */
    public function it_exposes_all_toggle_names()
    {
        $client  = $this->createClient();
        $crawler = $client->request('GET', '/toggles');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertJsonStringEqualsJsonString(
            json_encode(
                array(
                    array(
                        'name' => 'toggling',
                        'conditions' => array(
                            array(
                                'name' => 'operator-condition',
                                'key' => 'user_id',
                                'operator' => array('name' => 'less-than', 'value' => 42),
                            ),
                        ),
                        'status' => 'conditionally-active'
                    )
                )
            ),
            $client->getResponse()->getContent()
        );
    }

    /**
     * @test
     */
    public function it_can_delete_a_toggle()
    {
        $client  = $this->createClient();
        $crawler = $client->request('DELETE', '/toggles/toggling');

        $this->assertTrue($client->getResponse()->isOk());

        $crawler = $client->request('GET', '/toggles');
        $this->assertTrue($client->getResponse()->isOk());

        $toggles = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(0, $toggles);
    }

    /**
     * @test
     */
    public function it_returns_400_on_deleting_non_existing_toggle()
    {
        $client  = $this->createClient();
        $crawler = $client->request('DELETE', '/toggles/nothere');

        $this->assertFalse($client->getResponse()->isOk());
    }

    /**
     * @test
     */
    public function it_updates_a_toggle_on_put()
    {
        $toggleData = array(
            'name' => 'toggling',
            'conditions' => array(
                array(
                    'name' => 'operator-condition',
                    'key' => 'company_id',
                    'operator' => array('name' => 'greater-than', 'value' => 42),
                ),
            ),
            'status' => 'conditionally-active'
        );
        $toggle = json_encode($toggleData);

        // Do the PUT
        $client  = $this->createClient();
        $crawler = $client->request('PUT', '/toggles/toggling', array(), array(), array(), $toggle);

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());

        // Check the endpoint!
        $crawler = $client->request('GET', '/toggles');

        $this->assertTrue($client->getResponse()->isOk());
        $this->assertJsonStringEqualsJsonString(
            json_encode(array($toggleData)),
            $client->getResponse()->getContent()
        );
    }

    /**
     * @test
     */
    public function it_does_not_accept_a_new_name_on_put()
    {
        $toggleData = array('name' => 'new-name', 'conditions' => array());
        $toggle = json_encode($toggleData);

        // Do the PUT
        $client  = $this->createClient();
        $crawler = $client->request('PUT', '/toggles/toggling', array(), array(), array(), $toggle);

        $response = $client->getResponse();
        $this->assertTrue($response->isClientError());
    }

    public function tearDown()
    {
        $keys = $this->app['predis']->keys($this->app['toggle.manager.prefix'] . '*');

        foreach ($keys as $key) {
            $this->app['predis']->del($key);
        }
    }

    private function loadToggleFixtures(ToggleManager $manager)
    {
        // A toggle that will be active is the user id is less than 42
        $operator  = new LessThan(42);
        $condition = new OperatorCondition('user_id', $operator);
        $toggle    = new Toggle('toggling', array($condition));

        $manager->add($toggle);
    }
}
