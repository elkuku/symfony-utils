<?php

namespace Elkuku\SymfonyUtils\Test;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;

/**
 * Controller "smoke" test
 */
class ControllerBaseTest extends WebTestCase
{
    /**
     * @var array<string, array<string, array<string, int>>>
     */
    private array $exceptions
        = [
            'default' => [
                'statusCodes' => ['GET' => 200],
            ],
            'login' => [
                'statusCodes' => ['GET' => 200],
            ],
            'connect_google_check' => [
                'statusCodes' => ['GET' => 500],
            ],
            'connect_github_check' => [
                'statusCodes' => ['GET' => 500],
            ],
        ];

    /**
     * @throws Exception
     */
    public function testRoutes(): void
    {
        $client = static::createClient();

        /**
         * @var DelegatingLoader $routeLoader
         */
        $routeLoader = static::bootKernel()->getContainer()
            ->get('routing.loader');

        $directory = __DIR__.'/../../src/Controller';

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        $it->rewind();
        while ($it->valid()) {
            if (!$it->isDot()
                && !in_array(
                    $it->getSubPathName(),
                    [
                        '.gitignore',
                        'Security/GoogleController.php',
                        'Security/GitHubController.php',
                    ]
                )
            ) {
                $sub = $it->getSubPath() ? $it->getSubPath().'\\' : '';

                $routerClass = 'App\Controller\\'.$sub.basename(
                        $it->key(),
                        '.php'
                    );
                $routes = $routeLoader->load($routerClass)->all();

                $this->processRoutes($routes, $client);
            }

            $it->next();
        }
    }

    /**
     * @param array<Route> $routes
     */
    private function processRoutes(array $routes, KernelBrowser $browser): void
    {
        foreach ($routes as $routeName => $route) {
            $defaultId = 1;
            $expectedStatusCodes = [];
            if (array_key_exists($routeName, $this->exceptions)
                && array_key_exists(
                    'statusCodes',
                    $this->exceptions[$routeName]
                )
            ) {
                $expectedStatusCodes = $this->exceptions[$routeName]['statusCodes'];
            }

            $methods = $route->getMethods();

            if (!$methods) {
                echo sprintf(
                        'No methods set in controller "%s"',
                        $route->getPath()
                    ).PHP_EOL;
                $methods = ['GET'];
            }

            $path = str_replace('{id}', (string)$defaultId, $route->getPath());
            foreach ($methods as $method) {
                $expectedStatusCode = 302;
                if (array_key_exists($method, $expectedStatusCodes)) {
                    $expectedStatusCode = $expectedStatusCodes[$method];
                }

                $browser->request($method, $path);

                self::assertEquals(
                    $expectedStatusCode,
                    $browser->getResponse()->getStatusCode(),
                    sprintf(
                        'failed: %s (%s) with method: %s',
                        $routeName,
                        $path,
                        $method
                    )
                );
            }
        }
    }

}
