<?php

namespace Elkuku\SymfonyUtils\Test;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Routing\DelegatingLoader;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Route;

/**
 * Controller "smoke" test
 */
abstract class ControllerBaseTest extends WebTestCase
{
    /**
     *                     [
    '.gitignore',
    'Security/GoogleController.php',
    'Security/GitHubController.php',
    ]

     * @var array<int, string>
     */
    protected array $ignoredFiles = [];

    /**
     * @var array<string, array<string, array<string, int>>>
     */
    protected array $exceptions = [];

    /**
     * @var array<string, array<string, array<string, int>>>
     */
    private array $usedExceptions = [];

    /**
     * Must be set in extending class.
     */
    protected string $controllerRoot = '';

    abstract public function testRoutes(): void;

    protected function runTests(KernelBrowser $client): void
    {
        /**
         * @var DelegatingLoader $routeLoader
         */
        $routeLoader = static::bootKernel()->getContainer()
            ->get('routing.loader');

        if (!$this->controllerRoot) {
            throw new \UnexpectedValueException(
                'Please set a controllerRoot directory!'
            );
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->controllerRoot)
        );

        $it->rewind();
        while ($it->valid()) {
            if (!$it->isDot()
                && !in_array($it->getSubPathName(), $this->ignoredFiles, true)
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

        self::assertEquals($this->exceptions, $this->usedExceptions);
    }

    /**
     * @param array<Route> $routes
     */
    private function processRoutes(
        array $routes,
        KernelBrowser $browser,
    ): void {
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
                $this->usedExceptions[$routeName]['statusCodes'] = $this->exceptions[$routeName]['statusCodes'];
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
