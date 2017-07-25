<?php
// src/Stsbl/ExamPlanUnlockBundle/Service/GroupDetector.php
namespace Stsbl\ExamPlanUnlockBundle\Service;

use Doctrine\ORM\EntityManager;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\ExamPlanBundle\Security\Privilege as ExamPrivilege;
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
 * Service container which provides the detection of groups which can unlocked.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class GroupDetector 
{
    /**
     * @var EntityManager 
     */
    private $em;
    
    /**
     * @var SecurityHandler
     */
    private $securityHandler;
    
    /**
     * The constructor
     * 
     * @param EntityManager $em
     * @param SecurityHandler $securityHandler
     */
    public function __construct(EntityManager $em, SecurityHandler $securityHandler) 
    {
        $this->em = $em;
        $this->securityHandler = $securityHandler;
    }
    
    /**
     * Returns detected groups
     * 
     * @return array<\IServ\CoreBundle\Entity\Group>
     */
    public function getGroups()
    {
        /* @var $groupRepo \IServ\CoreBundle\Entity\GroupRepository */
        $groupRepo = $this->em->getRepository('IServCoreBundle:Group');
        
        $privilegeQueryBuilder = $groupRepo->createQueryBuilder('g2');

        $privilegeQueryBuilder
            ->select('g2.account')
            ->join('g2.privileges', 'p')
            ->where('p.id = :priv')
        ;
        
        /* @var $detectedGroups array<\IServ\CoreBundle\Entity\Group> */
        $detectedGroups = $groupRepo->createFindByFlagQueryBuilder(Privilege::FLAG_UNLOCKABLE)
            ->andWhere($privilegeQueryBuilder->expr()->eq('g.owner', ':owner'))
            ->andWhere($privilegeQueryBuilder->expr()->notIn('g.account', $privilegeQueryBuilder->getDQL()))
            ->andWhere($privilegeQueryBuilder->expr()->isNull('g.deleted'))
            ->setParameter('owner', $this->securityHandler->getUser())
            ->setParameter('priv', strtolower(substr(ExamPrivilege::DOING_EXAMS, 5)))
            ->getQuery()
            ->getResult()
        ;
        
        return $detectedGroups;
    }
}
