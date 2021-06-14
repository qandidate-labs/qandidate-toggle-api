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
use Qandidate\Bundle\ToggleBundle\QandidateToggleBundle;
use Qandidate\Toggle\Serializer\OperatorConditionSerializer;
use Qandidate\Toggle\Serializer\OperatorSerializer;
use Qandidate\Toggle\Serializer\ToggleSerializer;
use Qandidate\Toggle\Toggle;
use Qandidate\Toggle\ToggleManager;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SecurityBundle(),
            new QandidateToggleBundle(),
        ];
    }

    protected function configureContainer(ContainerConfigurator $c): void
    {
        $c->extension('framework', [
            'secret' => '%env(string:APP_SECRET)%',
            'test' => 'test' === $this->environment,
        ]);
        $c->extension('security', [
            'enable_authenticator_manager' => true,
            'firewalls' => [
                'main' => [
                    'lazy' => true,
                ],
            ],
        ]);
        $c->extension('qandidate_toggle', [
            'persistence' => 'redis',
            'redis_namespace' => '%env(string:TOGGLE__PREFIX)%',
            'redis_client' => 'my_redis_client',
        ]);

        $c->services()->set('my_redis_client', Client::class)
            ->args(['%env(string:TOGGLE__REDIS_DSN)%'])
            ->public();
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->add('get_toggles', '/toggles')
            ->controller([$this, 'getToggles'])
            ->methods(['GET'])
        ;

        $routes->add('delete_toggle', '/toggles/{name}')
            ->controller([$this, 'deleteToggle'])
            ->methods(['DELETE'])
        ;

        $routes->add('update_toggle', '/toggles/{name}')
            ->controller([$this, 'updateToggle'])
            ->methods(['PUT'])
        ;
    }

    public function getToggles(): JsonResponse
    {
        /** @var ToggleManager $toggleManager */
        $toggleManager = $this->container->get('qandidate.toggle.manager');

        $toggleSerializer = new ToggleSerializer(new OperatorConditionSerializer(new OperatorSerializer()));

        $serializedToggles = [];

        /** @var Toggle $toggle */
        foreach ($toggleManager->all() as $toggle) {
            $serializedToggles[] = $toggleSerializer->serialize($toggle);
        }

        return new JsonResponse($serializedToggles);
    }

    public function deleteToggle(string $name): Response
    {
        /** @var ToggleManager $toggleManager */
        $toggleManager = $this->container->get('qandidate.toggle.manager');

        $toggleManager->remove($name);

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    public function updateToggle(string $name, Request $request): Response
    {
        /** @var ToggleManager $toggleManager */
        $toggleManager = $this->container->get('qandidate.toggle.manager');

        $toggleSerializer = new ToggleSerializer(new OperatorConditionSerializer(new OperatorSerializer()));
        $toggle = $toggleSerializer->deserialize(json_decode((string) $request->getContent(), true));

        if ($name !== $toggle->getName()) {
            return new Response('Name of toggle can not be changed.', Response::HTTP_BAD_REQUEST);
        }

        $toggleManager->update($toggle);

        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
