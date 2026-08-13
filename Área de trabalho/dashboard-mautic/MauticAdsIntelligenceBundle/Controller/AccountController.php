<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAdsIntelligenceBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticAdsIntelligenceBundle\Entity\AdsAccount;
use MauticPlugin\MauticAdsIntelligenceBundle\Service\Sync\AdsAccountSyncService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountController extends CommonController
{
    public static function getSubscribedServices(): array
    {
        return array_merge(parent::getSubscribedServices(), [
            'mautic.ads_intelligence.service.account_sync' => AdsAccountSyncService::class,
        ]);
    }

    public function indexAction(Request $request, int $page = 1): Response
    {
        $accounts = $this->doctrine->getManager()->getRepository(AdsAccount::class)->findBy([], [
            'provider' => 'ASC',
            'name'     => 'ASC',
        ]);

        $accountsWithLimitedAccess = array_filter($accounts, static function (AdsAccount $account): bool {
            return 'google' === $account->getProvider()
                && (null === $account->getCurrency() || null === $account->getTimezone())
                && str_starts_with($account->getName(), 'Google Ads ');
        });

        $accounts = array_values(array_filter($accounts, static fn (AdsAccount $account): bool => !in_array($account, $accountsWithLimitedAccess, true)));

        return $this->delegateView([
            'viewParameters'  => [
                'page'                      => $page,
                'accounts'                  => $accounts,
                'accountsWithLimitedAccess' => 0,
                'currentRoute'              => 'mautic_ads_intelligence_accounts',
            ],
            'contentTemplate' => '@MauticAdsIntelligence/Account/index.html.twig',
            'passthroughVars' => [
                'activeLink'    => 'mautic_ads_intelligence_accounts',
                'mauticContent' => 'adsIntelligenceAccounts',
            ],
        ]);
    }

    public function executeAction(Request $request, $objectAction, $objectId = 0, $objectSubId = 0, $objectModel = ''): Response
    {
        return match ($objectAction) {
            'new'    => $this->newAction($request),
            'edit'   => $this->editAction($request, (int) $objectId),
            'delete' => $this->deleteAction($request, (int) $objectId),
            'view'   => $this->viewAction($request, (int) $objectId),
            default  => $this->indexAction($request),
        };
    }

    public function syncAction(Request $request): Response
    {
        if (!$this->isCsrfTokenValid('ads_intelligence_sync_accounts', (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $result = $this->container->get('mautic.ads_intelligence.service.account_sync')->syncAccounts();

        if ([] !== $result['errors']) {
            $this->addFlash('error', implode(' ', $result['errors']));
        } else {
            $this->addFlash('notice', $this->translator->trans('mautic.ads_intelligence.accounts.sync_success', [
                '%count%' => $result['synced'],
            ]));
        }

        return $this->redirectToRoute('mautic_ads_intelligence_accounts');
    }

    private function newAction(Request $request): Response
    {
        return $this->delegateView([
            'viewParameters'  => [],
            'contentTemplate' => '@MauticAdsIntelligence/Account/index.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'adsIntelligenceAccounts',
            ],
        ]);
    }

    private function editAction(Request $request, int $objectId): Response
    {
        return $this->delegateView([
            'viewParameters'  => ['objectId' => $objectId],
            'contentTemplate' => '@MauticAdsIntelligence/Account/index.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'adsIntelligenceAccounts',
            ],
        ]);
    }

    private function deleteAction(Request $request, int $objectId): Response
    {
        return $this->delegateView([
            'viewParameters'  => ['objectId' => $objectId],
            'contentTemplate' => '@MauticAdsIntelligence/Account/index.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'adsIntelligenceAccounts',
            ],
        ]);
    }

    private function viewAction(Request $request, int $objectId): Response
    {
        return $this->delegateView([
            'viewParameters'  => ['objectId' => $objectId],
            'contentTemplate' => '@MauticAdsIntelligence/Account/index.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'adsIntelligenceAccounts',
            ],
        ]);
    }
}
