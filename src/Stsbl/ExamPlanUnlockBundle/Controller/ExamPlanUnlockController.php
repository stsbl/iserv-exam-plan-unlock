<?php
// src/Stsbl/ExamPlanUnlockBundle/Controller/ExamPlanUnlockController.php
namespace Stsbl\ExamPlanUnlockBundle\Controller;

use IServ\CoreBundle\Controller\PageController;
use IServ\ExamPlanBundle\Security\Privilege as ExamPrivilege;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stsbl\ExamPlanUnlockBundle\Security\Privilege;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\HttpFoundation\Request;

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
 * Exam plan unlock contoller
 *
 * @author Felix Jacobi <felix.jacobi@stsbl.de>
 * @license MIT license <https://opensource.org/licenses/MIT>
 */
class ExamPlanUnlockController extends PageController
{
    /**
     * Creates form for exam plan group unlocking
     * 
     * @return \Symfony\Component\Form\Form
     */
    private function getUnlockForm()
    {
        /* @var $builder \Symfony\Component\Form\FormBuilder */
        $builder = $this->get('form.factory')->createNamedBuilder('exam_plan_unlock');
        $availableGroups = $this->get('stsbl.exam_plan_unlock.detector')->getGroups();
        
        $builder
            ->add('groups', EntityType::class, [
                'label' => _('Groups'),
                'class' => 'IServCoreBundle:Group',
                'select2-icon' => 'legacy-act-group',
                'multiple' => true,
                'required' => false,
                'constraints' => [new NotBlank()],
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
     * @param Request $request
     * @return array
     * @Route("/manage/examplan/unlock", name="manage_examplan_unlock")
     * @Route("/admin/examplan/unlock", name="admin_examplan_unlock")
     * @Security("is_granted('CAN_UNLOCK_GROUPS_FOR_EXAM_PLAN')")
     * @Template()
     */
    public function unlockAction(Request $request)
    {
        $form = $this->getUnlockForm();
        $form->handleRequest($request);
        $routeName = $request->get('_route');
        $failedGroups = null;
        
        if ($form->isSubmitted() && $form->isValid()) {
            $groups = $form->getData()['groups'];
            
            /* @var $unlockService \Stsbl\ExamPlanUnlockBundle\Service\Unlock */
            $unlockService = $this->get('stsbl.exam_plan_unlock.unlock');
            $unlockService->setGroups($groups);
            $unlockService->unlock();
            $failedGroups = $unlockService->getFailedGroups();
            
            if (count($unlockService->getErrors()) > 0) {
                $this->get('iserv.flash')->error(implode("\n", $unlockService->getErrors()));
            }
            
            if (count($unlockService->getErrorOutput()) > 0) {
                $this->get('iserv.flash')->error(implode("\n", $unlockService->getErrorOutput()));
            }
            
            if (count($unlockService->getOutput()) > 0) {
                $this->get('iserv.flash')->success(implode("\n", $unlockService->getOutput()));
            }
            
            // replace handled form with unhandled one
            unset($form);
            $form = $this->getUnlockForm();
        } else {
            foreach ($form->getErrors(true) as $e) {
                $this->get('iserv.flash')->error($e);
            }
        }
        
        // move page into admin section for administrators
        if ($routeName === 'admin_examplan_unlock') {
            $bundle = 'IServAdminBundle';
            $menu = null;
        } else {
            $bundle = 'IServCoreBundle';
            $menu = $this->get('iserv.menu.managment');
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
            'help' => 'https://it.stsbl.de/documentation/mods/stsbl-iserv-exam-plan-unlock'];
    }
}
