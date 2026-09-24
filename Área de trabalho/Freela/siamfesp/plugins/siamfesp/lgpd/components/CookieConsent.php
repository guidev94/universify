<?php namespace Siamfesp\Lgpd\Components;

use Cms\Classes\ComponentBase;
use Cookie;
use Request;
use Siamfesp\Lgpd\Models\Consent;

class CookieConsent extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Consentimento LGPD Cookies',
            'description' => 'Exibe o aviso de cookies e grava o aceite no admin.',
        ];
    }

    public function onAccept()
    {
        $allowed = ['necessary', 'analytics', 'marketing'];
        $labels = [
            'necessary' => 'Necessários',
            'analytics' => 'Medição e estatísticas',
            'marketing' => 'Marketing e conteúdo externo',
        ];

        $postedCategories = post('categories', []);
        if (is_string($postedCategories)) {
            $postedCategories = explode(',', $postedCategories);
        }

        if (!is_array($postedCategories)) {
            $postedCategories = [];
        }

        $categories = [];
        foreach ($postedCategories as $category) {
            $category = trim((string) $category);
            if (in_array($category, $allowed, true) && !in_array($category, $categories, true)) {
                $categories[] = $category;
            }
        }

        if (!in_array('necessary', $categories, true)) {
            array_unshift($categories, 'necessary');
        }

        $consentType = trim((string) post('consent_type')) ?: 'custom';
        if (!in_array($consentType, ['accept_all', 'necessary_only', 'custom'], true)) {
            $consentType = 'custom';
        }

        $token = trim((string) post('token'));
        if ($token === '') {
            $token = (string) Cookie::get('siamfesp_lgpd_cookie_token');
        }
        if ($token === '') {
            $token = $this->makeToken();
        }

        $consent = Consent::firstOrNew(['consent_token' => $token]);
        $consent->consent_type = $consentType;
        $consent->categories = implode(',', $categories);
        $consent->preferences_json = json_encode([
            'categories' => $categories,
            'labels' => array_intersect_key($labels, array_flip($categories)),
        ], JSON_UNESCAPED_UNICODE);
        $consent->source_page = '/' . trim((string) Request::path(), '/');
        if ($consent->source_page === '/') {
            $consent->source_page = '/';
        }
        $consent->ip_address = Request::ip();
        $consent->user_agent = substr((string) Request::header('User-Agent'), 0, 500);
        $consent->accepted_at = date('Y-m-d H:i:s');
        $consent->save();

        Cookie::queue('siamfesp_lgpd_cookie_token', $token, 60 * 24 * 365, null, null, false, false);
        Cookie::queue('siamfesp_lgpd_cookie_accepted', '1', 60 * 24 * 365, null, null, false, false);
        Cookie::queue('siamfesp_lgpd_cookie_categories', implode(',', $categories), 60 * 24 * 365, null, null, false, false);

        return [
            'status' => 'success',
            'message' => 'Preferências de cookies salvas com sucesso.',
            'token' => $token,
            'categories' => $categories,
            'consent_type' => $consentType,
        ];
    }

    protected function makeToken(): string
    {
        try {
            return bin2hex(random_bytes(20));
        } catch (\Throwable $e) {
            return sha1(uniqid('siamfesp_lgpd_', true) . microtime(true));
        } catch (\Exception $e) {
            return sha1(uniqid('siamfesp_lgpd_', true) . microtime(true));
        }
    }
}
