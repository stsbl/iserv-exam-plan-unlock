<?php

namespace Stsbl\ExamPlanUnlockBundle\Security\Authorization\Voter;

use Stsbl\ExamPlanUnlockBundle\Security\Privilege;
use Stsbl\ExamPlanUnlockBundle\Service\GroupDetector;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/*
 * The MIT License
 *
 * Copyright 2021 Felix Jacobi.
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
 * Security Voter for unlocking groups for exam plan
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class UnlockVoter extends Voter
{
    public const ATTRIBUTE = 'CAN_UNLOCK_GROUPS_FOR_EXAM_PLAN';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;

    /**
     * @var GroupDetector
     */
    private $detector;

    public function __construct(AccessDecisionManagerInterface $decisionManager, GroupDetector $detector)
    {
        $this->decisionManager = $decisionManager;
        $this->detector = $detector;
    }

    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        return $attribute === self::ATTRIBUTE;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        return $attribute === self::ATTRIBUTE &&
            $this->decisionManager->decide($token, $this->getSupportedPrivileges()) &&
            $this->hasUnlockableGroups();
    }

    /**
     * Get supported privileges
     *
     * @return string[]
     */
    private function getSupportedPrivileges(): array
    {
        return [Privilege::UNLOCKER];
    }

    /**
     * Checks if user has cancelable group memberships
     */
    private function hasUnlockableGroups(): bool
    {
        $availableGroups = $this->detector->getGroups();

        return count($availableGroups) > 0;
    }
}
