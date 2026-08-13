<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ContactNoInterestController extends CommonController
{
    private const REASON_FIELD_ALIAS = 'ads_sem_interesse_motivo';

    public function modalAction(Request $request, int $contactId): Response
    {
        /** @var LeadModel $leadModel */
        $leadModel = $this->getModel('lead');
        $lead      = $leadModel->getEntity($contactId);

        if (null === $lead || !$this->security->hasEntityAccess('lead:leads:editown', 'lead:leads:editother', $lead->getPermissionUser())) {
            return $this->accessDenied();
        }

        $form = $this->createFormBuilder(null, [
            'action' => $this->generateUrl('mautic_ads_intelligence_contact_no_interest', ['contactId' => $contactId]),
        ])
            ->add('reason', TextareaType::class, [
                'label'    => 'mautic.ads_intelligence.no_interest.reason',
                'required' => true,
                'attr'     => [
                    'rows'        => 5,
                    'placeholder' => 'mautic.ads_intelligence.no_interest.reason.placeholder',
                ],
            ])
            ->add('save', SubmitType::class, [
                'label' => 'mautic.ads_intelligence.no_interest.submit',
                'attr'  => ['class' => 'btn btn-danger'],
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $reason = trim((string) $form->get('reason')->getData());

            $this->ensureReasonFieldExists();
            $lead->addUpdatedField(self::REASON_FIELD_ALIAS, $reason);
            $leadModel->modifyTags($lead, ['sem-interesse'], null, false);
            $leadModel->saveEntity($lead);

            return $this->postActionRedirect([
                'returnUrl'       => $this->generateUrl('mautic_contact_action', ['objectAction' => 'view', 'objectId' => $contactId]),
                'viewParameters'  => [
                    'objectAction' => 'view',
                    'objectId'     => $contactId,
                ],
                'contentTemplate' => 'Mautic\LeadBundle\Controller\LeadController::viewAction',
                'passthroughVars' => [
                    'closeModal' => 1,
                ],
                'flashes' => [
                    [
                        'type' => FlashBag::LEVEL_NOTICE,
                        'msg'  => 'mautic.ads_intelligence.no_interest.saved',
                    ],
                ],
            ]);
        }

        return $this->delegateView([
            'viewParameters'  => [
                'form' => $form->createView(),
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Contact/no_interest_modal.html.twig',
            'passthroughVars' => [
                'route' => false,
            ],
        ]);
    }

    private function ensureReasonFieldExists(): void
    {
        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getModel('lead.field');
        $repository = $this->doctrine->getManager()->getRepository(LeadField::class);

        if ($repository->findOneBy(['alias' => self::REASON_FIELD_ALIAS, 'object' => 'lead']) instanceof LeadField) {
            return;
        }

        $field = new LeadField();
        $field->setName('Motivo sem interesse')
            ->setAlias(self::REASON_FIELD_ALIAS)
            ->setType('textarea')
            ->setObject('lead')
            ->setIsVisible(true)
            ->setIsShortVisible(false)
            ->setIsListable(false);
        $field->setGroup('professional');

        $fieldModel->saveEntity($field);
    }
}
