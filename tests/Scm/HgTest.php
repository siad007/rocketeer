<?php
/**
 * This file is part of Rocketeer
 *
 * (c) Maxime Fabre <ehtnam6@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rocketeer\Scm;

use Rocketeer\TestCases\RocketeerTestCase;

class HgTest extends RocketeerTestCase
{
    /**
     * The current SCM instance.
     *
     * @type Hg
     */
    protected $scm;

    public function setUp()
    {
        parent::setUp();

        $this->scm = new Hg($this->app);
    }

    ////////////////////////////////////////////////////////////////////
    //////////////////////////////// TESTS /////////////////////////////
    ////////////////////////////////////////////////////////////////////

    public function testCanGetCheck()
    {
        $command = $this->scm->check();

        $this->assertEquals('hg --version', $command);
    }

    public function testCanGetCurrentState()
    {
        $command = $this->scm->currentState();

        $this->assertEquals('hg identify', $command);
    }

    public function testCanGetCurrentBranch()
    {
        $command = $this->scm->currentBranch();

        $this->assertEquals('hg identify --branch', $command);
    }

    public function testCanGetCheckout()
    {
        $this->mock('rocketeer.connections', 'ConnectionsHandler', function ($mock) {
            return $mock
                ->shouldReceive('getRepositoryEndpoint')->once()->andReturn('http://github.com/my/repository')
                ->shouldReceive('getRepositoryBranch')->once()->andReturn('develop');
        });

        $command = $this->scm->checkout($this->server);

        $this->assertEquals('hg clone "http://github.com/my/repository" "'.$this->server.'" --branch="develop"', $command);
    }

    public function testCanGetReset()
    {
        $command = $this->scm->reset();

        $this->assertEquals('hg revert --all', $command);
    }

    public function testCanGetUpdate()
    {
        $command = $this->scm->update();

        $this->assertEquals('hg pull --update', $command);
    }

    public function testCanGetSubmodules()
    {
        $command = $this->scm->submodules();

        $this->assertEquals('hg update -S', $command);
    }
}
