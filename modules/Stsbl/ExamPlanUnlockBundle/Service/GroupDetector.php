<?php

declare(strict_types=1);

namespace Stsbl\ExamPlanUnlockBundle\Service;

use IServ\CoreBundle\Entity\Group;
use IServ\CoreBundle\Repository\GroupRepository;
use IServ\CoreBundle\Security\Core\SecurityHandler;
use IServ\ExamPlanBundle\Security\Privilege as ExamPrivilege;
use Stsbl\ExamPlanUnlockBundle\Security\Privilege;

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
 * Service container which provides the detection of groups which can unlocked.
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class GroupDetector
{

    public function __construct(
        private readonly GroupRepository $repository,
        private readonly SecurityHandler $securityHandler
    ) {
    }

    /**
     * Returns detected groups
     *
     * @return Group[]
     */
    public function getGroups(): array
    {
        $privilegeQueryBuilder = $this->repository->createQueryBuilder('g2');

        $privilegeQueryBuilder
            ->select('g2.account')
            ->join('g2.flags', 'f')
            ->where('f.id = :flag')
        ;

        return $this->repository->createFindByFlagQueryBuilder(Privilege::FLAG_UNLOCKABLE)
            ->andWhere($privilegeQueryBuilder->expr()->eq('g.owner', ':owner'))
            ->andWhere($privilegeQueryBuilder->expr()->notIn('g.account', $privilegeQueryBuilder->getDQL()))
            ->andWhere($privilegeQueryBuilder->expr()->isNull('g.deleted'))
            ->setParameter('owner', $this->securityHandler->getUser())
            ->setParameter('flag', ExamPrivilege::FLAG_DOING_EXAMS)
            ->getQuery()
            ->getResult()
            ;
    }
}
