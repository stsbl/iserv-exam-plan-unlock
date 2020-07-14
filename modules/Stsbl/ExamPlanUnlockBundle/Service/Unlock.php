<?php
declare(strict_types=1);

namespace Stsbl\ExamPlanUnlockBundle\Service;

use Doctrine\Common\Collections\Collection;
use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Exception\ShellExecException;
use IServ\CoreBundle\Exception\TypeException;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\CoreBundle\Service\ClientIp;
use IServ\CoreBundle\Service\Config;
use IServ\CoreBundle\Service\Shell;

/*
 * The MIT License
 *
 * Copyright 2020 Fleix Jacobi.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Exam plan unlock service
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class Unlock
{
    const COMMAND = '/usr/lib/iserv/exam_plan_unlock';

    /**
     * @var ClientIp
     */
    private $clientIp;

    /**
     * @var array<Group>
     */
    private $groups;
    
    /**
     * @var array<Group>
     */
    private $failedGroups;
    
    /**
     * @var Shell
     */
    private $shell;
    
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var array<string>
     */
    private $errors;
    
    /**
     * Set groups for next operation
     *
     * @param Group[]|Collection $groups
     */
    public function setGroups($groups): void
    {
        if ($groups instanceof Collection) {
            $groups = $groups->toArray();
        }

        if (!is_array($groups)) {
            throw TypeException::invalid(gettype($groups), ['array', Collection::class], '$groups');
        }
        
        $this->groups = $groups;
    }
    
    /**
     * Add a single group
     */
    public function addGroup(Group $group): void
    {
        $this->groups[] = $group;
    }

    public function __construct(Shell $shell, SecurityHandler $securityHandler, Config $config, ClientIp $clientIp)
    {
        $this->shell = $shell;
        $this->securityHandler = $securityHandler;
        $this->config = $config;
        $this->clientIp = $clientIp;
    }

    /**
     * Unlock groups which were previously set via <tt>setGroups</tt>.
     */
    public function unlock()
    {
        $args = [];
        $args[] = self::COMMAND;
        $args[] = $this->securityHandler->getUser()->getUsername();
        
        if (count($this->groups) < 1) {
            throw new \InvalidArgumentException('No groups specified!');
        }
        
        $this->validateMemberAmount();
        
        // exit if all group did not pass the member check
        if (count($this->groups) < 1) {
            return;
        }
        
        foreach ($this->groups as $g) {
            $args[] = $g->getAccount();
        }

        try {
            $this->shell->exec('sudo', $args, null, [
                'SESSPW' => $this->securityHandler->getSessionPassword(),
                'IP' => $this->clientIp->getIp(),
                'IPFWD' => $this->clientIp->getForwardedIp(),
            ]);
        } catch (ShellExecException $e) {
            throw new \RuntimeException('Could not run exam_plan_unlock!', 0, $e);
        }
    }
    
    /**
     * Validates member amount of groups.
     *
     * Moves failed groups into <tt>$failedGroups</tt> (they can later get by <tt>getFailedGroups()</tt>).
     */
    private function validateMemberAmount(): void
    {
        if (count($this->groups) < 1) {
            throw new \InvalidArgumentException('No groups specified!');
        }
        
        // reset
        $this->errors = [];
        $this->failedGroups = [];
        
        $minMembers = $this->config->get('ExamPlanUnlockMinMembers');
        // return if there a no restrictions
        if ($minMembers === 0) {
            return;
        }
        
        foreach ($this->groups as $k => $v) {
            if ($v->getUsers()->count() < $minMembers) {
                $this->errors[] = __('Group "%s" has too less members for unlocking.', (string)$v);
                // move group to failed
                $this->failedGroups[] = $v;
                // remove invalid group from pending operation - to keep them would cause exam_plan_unlock errors
                unset($this->groups[$k]);
            }
        }
    }
    
    /**
     * Get last shell output
     *
     * @return string[]
     */
    public function getOutput(): array
    {
        return $this->shell->getOutput();
    }
    
    /**
     * Get last shell error output
     *
     * @return string[]
     */
    public function getErrorOutput(): array
    {
        return $this->shell->getError();
    }
    
    /**
     * Gets last shell exit code
     */
    public function getExitCode(): ?int
    {
        return $this->shell->getExitCode();
    }
    
    /**
     * Get errors thrown during unlocking
     *
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    
    /**
     * Get groups which didn't pass the member check
     *
     * @return Group[]
     */
    public function getFailedGroups(): array
    {
        return $this->failedGroups;
    }
}
