<?php

declare(strict_types=1);

namespace Stsbl\ExamPlanUnlockBundle\EventListener;

use IServ\CoreBundle\Event\DashboardEvent;
use IServ\CoreBundle\Event\HomePageEvent;
use IServ\CoreBundle\EventListener\HomePageListenerInterface;
use IServ\ManageBundle\EventListener\ManageDashboardListenerInterface;
use Stsbl\ExamPlanUnlockBundle\Security\Authorization\Voter\UnlockVoter;
use Stsbl\ExamPlanUnlockBundle\Service\GroupDetector;

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
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
final class DashboardListener implements HomePageListenerInterface, ManageDashboardListenerInterface
{
    public function __construct(
        private readonly GroupDetector $detector,
    ) {
    }

    /**
     * Adds notice if there are unlockable groups for exam plan.
     */
    public function onBuildManageDashboard(DashboardEvent $event): void
    {
        if (!$event->getAuthorizationChecker()->isGranted(UnlockVoter::ATTRIBUTE)) {
            // exit if user has no unlockable groups
            return;
        }

        $this->addDashboardContent($event, false);
    }

    /**
     * {@inheritdoc}
     */
    public function onBuildHomePage(HomePageEvent $event): void
    {
        $this->addDashboardContent($event, true);
    }

    /**
     * @param DashboardEvent $event
     * @param bool $isIDeskEvent
     */
    private function addDashboardContent(DashboardEvent $event, bool $isIDeskEvent): void
    {
        $groups = $this->detector->getGroups();

        if (\count($groups) > 0) {
            $event->addContent(
                'manage.stsblexamplanunlockgroups',
                '@StsblExamPlanUnlock/Dashboard/pending.html.twig',
                [
                    'title' => __n(
                        'You have to unlock one group for the exam plan',
                        'You have to unlock %d groups for the exam plan',
                        count($groups),
                        count($groups)
                    ),
                    'text' => _('The following groups are in queue for unlocking:'),
                    'additional_text' => _(
                        'Please go to „Unlock groups for exam plan“ and unlock these groups for the ' .
                        'exam plan.'
                    ),
                    'groups' => $groups,
                    'panel_class' => 'panel-warning',
                    'idesk' => $isIDeskEvent,
                    'icon' => [
                        'style' => 'fugue',
                        'name' => 'calendar-blue'
                    ],
                ],
                -2
            );
        }
    }

}
