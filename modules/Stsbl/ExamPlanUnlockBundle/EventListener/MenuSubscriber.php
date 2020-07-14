<?php
declare(strict_types=1);

namespace Stsbl\ExamPlanUnlockBundle\EventListener;

use IServ\AdminBundle\Event\Events;
use IServ\AdminBundle\EventListener\AdminMenuListenerInterface;
use IServ\CoreBundle\Event\MenuEvent;
use IServ\ManageBundle\Event\MenuEvent as ManageMenuEvent;
use Stsbl\ExamPlanUnlockBundle\Security\Authorization\Voter\UnlockVoter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/*
 * The MIT License
 *
 * Copyright 2020 Felix Jacobi.
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
class MenuSubscriber implements AdminMenuListenerInterface, EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public function onBuildAdminMenu(MenuEvent $event): void
    {
        // check privilege
        if ($event->getAuthorizationChecker()->isGranted(UnlockVoter::ATTRIBUTE)) {
            $menu = $event->getMenu();
            $child = $menu->getChild('modules');
            
            $item = $child->addChild('admin_examplan_unlock', [
                'route' => 'admin_examplan_unlock',
                'label' => _('Unlock groups for exam plan'),
            ]);
            
            $item->setExtra('icon', 'calendar-blue');
            $item->setExtra('icon_style', 'fugue');
        }
    }

    public function onBuildManageMenu(MenuEvent $event): void
    {
        // check privilege
        if ($event->getAuthorizationChecker()->isGranted(UnlockVoter::ATTRIBUTE)) {
            $menu = $event->getMenu();
            
            $item = $menu->addChild('manage_examplan_unlock', [
                'route' => 'manage_examplan_unlock',
                'label' => _('Unlock groups for exam plan'),
            ]);
            
            $item->setExtra('icon', 'calendar-blue');
            $item->setExtra('icon_style', 'fugue');
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            Events::MENU => 'onBuildAdminMenu',
            ManageMenuEvent::MANAGEMENTMENU => 'onBuildManageMenu'
        ];
    }

}
