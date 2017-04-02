<?php
// src/Stsbl/ExamPlanUnlockBundle/Security/Authorization/Voter/UnlockVoter.php
namespace Stsbl\ExamPlanUnlockBundle\Security\Authorization\Voter;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Entity\User;
use IServ\ExamPlanBundle\Security\Privilege as ExamPrivilege;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Stsbl\ExamPlanUnlockBundle\Security\Privilege;

/*
 * The MIT License
 *
 * Copyright 2017 Felix Jacobi.
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
    const ATTRIBUTE = 'CAN_UNLOCK_GROUPS_FOR_EXAM_PLAN';

    /**
     * @var AccessDecisionManagerInterface
     */
    private $decisionManager;
    
    /*
     * @var EntityManager
     */
    private $em; 
    
    /**
     * The constructor.
     * 
     * @param AccessDecisionManagerInterface $decisionManager
     */
    public function __construct(AccessDecisionManagerInterface $decisionManager, EntityManager $em) 
    {
        $this->decisionManager = $decisionManager;
        $this->em = $em;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject) 
    {
        return $attribute === self::ATTRIBUTE;
    }

    /**
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        if ($attribute === self::ATTRIBUTE) {
            if ($this->decisionManager->decide($token, $this->getSupportedPrivileges()) && $this->hasUnlockableGroups($token->getUser())) {
                return true;
            }
            
            return false;
        }
    }

    /**
     * Get supported privileges
     * 
     * @return string[]
     */
    private function getSupportedPrivileges()
    {
        return [Privilege::UNLOCKER];
    }
    
    /**
     * Checks if user has cancelable group memberships
     * 
     * @return bool
     */
    private function hasUnlockableGroups(User $user)
    {
        $groupRepository = $this->em->getRepository('IServCoreBundle:Group');

        $privilegeQueryBuilder = $groupRepository->createQueryBuilder('g2');

        $privilegeQueryBuilder
            ->select('g2.account')
            ->join('g2.privileges', 'p')
            ->where('p.id = :priv')
        ;
        
        /* @var $groupsWithFlag array<\IServ\CoreBundle\Entity\Group> */
        $availableGroups = $groupRepository->createFindByFlagQueryBuilder(Privilege::FLAG_UNLOCKABLE)
            ->andWhere('g.owner = :owner')
            ->andWhere($privilegeQueryBuilder->expr()->notIn('g.account', $privilegeQueryBuilder->getDQL()))
            ->setParameter('owner', $user)
            ->setParameter('priv', strtolower(substr(ExamPrivilege::DOING_EXAMS, 5)))
            ->getQuery()
            ->getResult()
        ;
        
        return count($availableGroups) > 0;
    }
}
