<?php
declare(strict_types=1);

namespace Stsbl\ExamPlanUnlockBundle\Controller;

use IServ\CoreBundle\Controller\AbstractPageController;
use Knp\Menu\ItemInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\ExamPlanUnlockBundle\Service\GroupDetector;
use Stsbl\ExamPlanUnlockBundle\Service\Unlock;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

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
 * Exam plan unlock contoller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class ExamPlanUnlockController extends AbstractPageController
{
    /**
     * @var GroupDetector
     */
    private $detector;

    /**
     * @var ItemInterface
     */
    private $managementMenu;

    /**
     * @var Unlock
     */
    private $unlocker;

    public function __construct(GroupDetector $detector, ItemInterface $managementMenu, Unlock $unlocker)
    {
        $this->detector = $detector;
        $this->managementMenu = $managementMenu;
        $this->unlocker = $unlocker;
    }

    /**
     * Creates form for exam plan group unlocking
     */
    private function getUnlockForm(): FormInterface
    {
        /* @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $this->get('form.factory')->createNamedBuilder('exam_plan_unlock');
        $availableGroups = $this->detector->getGroups();
        
        $builder
            ->add('groups', EntityType::class, [
                'label' => _('Groups'),
                'class' => 'IServCoreBundle:Group',
                'select2-icon' => 'legacy-act-group',
                'multiple' => true,
                'required' => false,
                'constraints' => [
                    new NotBlank(['message' => _('Please choose the groups which you want to unlock.')]),
                    new Count(['min' => 1, 'minMessage' => _('Please choose the groups which you want to unlock.')])
                ],
                'by_reference' => false,
                'choices' => $availableGroups,
                'attr' => [
                    'help_text' => _('Select the groups which you want to unlock for the exam plan.'),
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => _('Unlock groups'),
                'buttonClass' => 'btn-success',
                'icon' => 'ok'
            ])
        ;
        
        return $builder->getForm();
    }
    
    /**
     * Provides page with form for group unlocking
     *
     * @Route("/manage/examplan/unlock", name="manage_examplan_unlock")
     * @Route("/admin/examplan/unlock", name="admin_examplan_unlock")
     * @Security("is_granted('CAN_UNLOCK_GROUPS_FOR_EXAM_PLAN')")
     * @Template()
     */
    public function unlockAction(Request $request): array
    {
        $form = $this->getUnlockForm();
        $form->handleRequest($request);
        $routeName = $request->get('_route');
        $failedGroups = null;
        
        if ($form->isSubmitted() && $form->isValid()) {
            $groups = $form->getData()['groups'];

            $this->unlocker->setGroups($groups);
            $this->unlocker->unlock();
            
            if (!empty($errors = $this->unlocker->getErrors())) {
                $this->addFlash('error', implode("\n", $errors));
            }
            
            if (!empty($errors = $this->unlocker->getErrorOutput())) {
                $this->addFlash('error', implode("\n", $errors));
            }
            
            if (!empty($output = $this->unlocker->getOutput())) {
                $this->addFlash('success', implode("\n", $output));
            }
            
            // replace handled form with unhandled one
            $form = $this->getUnlockForm();
            // re-add failed groups
            if (!empty($failedGroups = $this->unlocker->getFailedGroups())) {
                $form->get('groups')->setData($failedGroups);
            }
        } else {
            foreach ($form->getErrors(true) as $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }
        
        // move page into admin section for administrators
        if ($routeName === 'admin_examplan_unlock') {
            $bundle = 'IServAdminBundle';
            $menu = null;
        } else {
            $bundle = 'IServCoreBundle';
            $menu = $this->managementMenu;
        }
        
        // track path
        if ($bundle === 'IServCoreBundle') {
            $this->addBreadcrumb(_('Administration'), $this->generateUrl('manage_index'));
            $this->addBreadcrumb(_('Unlock groups for exam plan'), $this->generateUrl($routeName));
        } else {
            $this->addBreadcrumb(_('Unlock groups for exam plan'), $this->generateUrl($routeName));
        }
        
        $view = $form->createView();
        
        return [
            'bundle' => $bundle,
            'menu' => $menu,
            'form' => $view,
            'failed' => $failedGroups,
            'help' => 'https://it.stsbl.de/documentation/mods/stsbl-iserv-exam-plan-unlock'
        ];
    }
}
