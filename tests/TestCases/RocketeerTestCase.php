<?php

/*
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Rocketeer\TestCases;

use Rocketeer\Services\Storages\LocalStorage;
use Rocketeer\TestCases\Modules\Assertions;
use Rocketeer\TestCases\Modules\Mocks;
use Symfony\Component\Console\Tester\CommandTester;

abstract class RocketeerTestCase extends ContainerTestCase
{
    use Assertions;
    use Mocks;

    /**
     * The path to the local fake server.
     *
     * @type string
     */
    protected $server;

    /**
     * @type string
     */
    protected $customConfig;

    /**
     * The path to the local deployments file.
     *
     * @type string
     */
    protected $deploymentsFile;

    /**
     * A dummy AbstractTask to use for helpers tests.
     *
     * @type \Rocketeer\Abstracts\AbstractTask
     */
    protected $task;

    /**
     * Cache of the paths to binaries.
     *
     * @type array
     */
    protected $binaries = [];

    /**
     * Number of files an ls should yield.
     *
     * @type int
     */
    protected static $numberFiles;

    /**
     * Set up the tests.
     */
    public function setUp()
    {
        parent::setUp();

        // Compute ls results
        if (!static::$numberFiles) {
            $files               = preg_grep('/^([^.0])/', scandir(__DIR__.'/../..'));
            static::$numberFiles = count($files);
        }

        // Setup local server
        $this->server          = __DIR__.'/../_server/foobar';
        $this->customConfig    = $this->server.'/../.rocketeer';
        $this->deploymentsFile = $this->server.'/deployments.json';

        // Bind dummy AbstractTask
        $this->task = $this->task('Cleanup');
        $this->recreateVirtualServer();

        // Bind new LocalStorage instance
        $this->app->bind('rocketeer.storage.local', function ($app) {
            $folder = dirname($this->deploymentsFile);

            return new LocalStorage($app, 'deployments', $folder);
        });

        // Mock OS
        $this->localStorage->set('production.0.os', 'Linux');
        $this->localStorage->set('staging.0.os', 'Linux');

        // Cache paths
        $this->binaries = [
            'php'      => exec('which php') ?: 'php',
            'bundle'   => exec('which bundle') ?: 'bundle',
            'phpunit'  => exec('which phpunit') ?: 'phpunit',
            'composer' => exec('which composer') ?: 'composer',
        ];
    }

    /**
     * Cleanup tests.
     */
    public function tearDown()
    {
        parent::tearDown();

        // Restore superglobals
        $_SERVER['HOME'] = $this->home;
    }

    /**
     * Recreates the local file server.
     */
    protected function recreateVirtualServer()
    {
        // Save superglobals
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
        } else {
            $this->home = $_SERVER['HOME'];
        }

        // Cleanup files created by tests
        $cleanup = [
            realpath(__DIR__.'/../../.rocketeer'),
            realpath(__DIR__.'/../.rocketeer'),
            realpath($this->server),
            realpath($this->customConfig),
        ];
        array_map([$this->files, 'deleteDirectory'], $cleanup);

        // Recreate altered local server
        exec(sprintf('rm -rf %s', $this->server));
        exec(sprintf('cp -a %s %s', $this->server.'-stub', $this->server));
    }

    ////////////////////////////////////////////////////////////////////
    /////////////////////////////// HELPERS ////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Get and execute a command.
     *
     * @param string|null $command
     * @param array       $options
     *
     * @return CommandTester
     */
    protected function executeCommand($command = null, $options = [])
    {
        // Fetch command
        $command = $command ? '.'.$command : null;
        $command = $this->app['rocketeer.commands'.$command];

        // Build options
        $options = array_merge([
            'command' => $command->getName(),
        ], $options);

        // Execute
        $tester = new CommandTester($command);
        $tester->execute($options);

        return $tester;
    }

    /**
     * Get a pretend AbstractTask to run bogus commands.
     *
     * @param string $task
     * @param array  $options
     * @param array  $expectations
     *
     * @return \Rocketeer\Abstracts\AbstractTask
     */
    protected function pretendTask($task = 'Deploy', $options = [], array $expectations = [])
    {
        $this->pretend($options, $expectations);

        return $this->task($task);
    }

    /**
     * Get AbstractTask instance.
     *
     * @param string $task
     * @param array  $options
     *
     * @return \Rocketeer\Abstracts\AbstractTask
     */
    protected function task($task = null, $options = [])
    {
        if ($options) {
            $this->mockCommand($options);
        }

        if (!$task) {
            return $this->task;
        }

        return $this->builder->buildTaskFromClass($task);
    }
}
