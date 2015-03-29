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

use Rocketeer\Abstracts\AbstractBinary;
use Rocketeer\Interfaces\ScmInterface;

/**
 * The mercurial implementation of the ScmInterface.
 *
 * @author Siad Ardroumli <siad.ardroumli@gmail.com>
 */
class Hg extends AbstractBinary implements ScmInterface
{
    /**
     * The core binary.
     *
     * @type string
     */
    protected $binary = 'hg';

    ////////////////////////////////////////////////////////////////////
    ///////////////////////////// INFORMATIONS /////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Check if the SCM is available.
     *
     * @return string
     */
    public function check()
    {
        return $this->getCommand('--version');
    }

    /**
     * Get the current state.
     *
     * @return string
     */
    public function currentState()
    {
        return $this->identify();
    }

    /**
     * Get the current branch.
     *
     * @return string
     */
    public function currentBranch()
    {
        return $this->identify([], '--branch');
    }

    ////////////////////////////////////////////////////////////////////
    /////////////////////////////// ACTIONS ////////////////////////////
    ////////////////////////////////////////////////////////////////////

    /**
     * Clone a repository.
     *
     * @param string $destination
     *
     * @return string
     */
    public function checkout($destination)
    {
        $arguments = array_map([$this, 'quote'], [
            $this->connections->getRepositoryEndpoint(),
            $destination,
        ]);

        // Build flags
        $flags = ['--branch' => $this->connections->getRepositoryBranch()];

        return $this->clone($arguments, $flags);
    }

    /**
     * Resets the repository.
     *
     * @return string
     */
    public function reset()
    {
        return $this->getCommand('revert', [], ['--all']);
    }

    /**
     * Updates the repository.
     *
     * @return string
     */
    public function update()
    {
        return $this->pull([], '--update');
    }

    /**
     * Checkout the repository's submodules.
     *
     * @return string
     */
    public function submodules()
    {
        return $this->getCommand('update', [], ['-S']);
    }
}
