<?php namespace Siamfesp\Newsletter\Components;

use Cms\Classes\ComponentBase;
use Siamfesp\Newsletter\Models\Subscriber;
use Mail;
use Request;

class NewsletterForm extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'Formulário Newsletter',
            'description' => 'Grava inscritos da newsletter no admin e envia notificação por e-mail.',
        ];
    }

    public function onSubscribe()
    {
        $email = trim((string) post('email'));
        $name = trim((string) post('name'));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [
                'status' => 'error',
                'message' => 'Informe um e-mail válido para receber a newsletter.',
            ];
        }

        $sourcePage = Request::path();
        $ipAddress = Request::ip();
        $userAgent = substr((string) Request::header('User-Agent'), 0, 500);

        $subscriber = Subscriber::firstOrNew(['email' => $email]);
        $isNew = !$subscriber->exists;

        if ($name !== '') {
            $subscriber->name = $name;
        }

        $subscriber->email = $email;
        $subscriber->source_page = $sourcePage ?: 'home';
        $subscriber->ip_address = $ipAddress;
        $subscriber->user_agent = $userAgent;
        $subscriber->is_active = true;
        $subscriber->last_subscribed_at = date('Y-m-d H:i:s');
        $subscriber->save();

        $mailError = null;

        try {
            $this->sendNotificationEmail($subscriber, $isNew);
        } catch (\Throwable $e) {
            $mailError = $e->getMessage();
        } catch (\Exception $e) {
            $mailError = $e->getMessage();
        }

        if ($mailError) {
            return [
                'status' => 'success',
                'message' => 'Cadastro gravado com sucesso no admin. O envio do e-mail precisa ser verificado na configuração de e-mail do servidor.',
            ];
        }

        return [
            'status' => 'success',
            'message' => $isNew
                ? 'Cadastro realizado com sucesso. Obrigado por se inscrever!'
                : 'Este e-mail já estava cadastrado. Atualizamos sua inscrição.',
        ];
    }

    protected function sendNotificationEmail(Subscriber $subscriber, bool $isNew): void
    {
        $to = 'comunicacao@siamfesp.org.br';
        $subject = $isNew ? 'Nova inscrição na Newsletter - SIAMFESP' : 'Newsletter atualizada - SIAMFESP';

        $body = implode("\n", [
            'Nova solicitação de newsletter recebida pelo site SIAMFESP.',
            '',
            'Nome: ' . ($subscriber->name ?: 'Não informado'),
            'E-mail: ' . $subscriber->email,
            'Origem: /' . trim((string) $subscriber->source_page, '/'),
            'IP: ' . ($subscriber->ip_address ?: 'Não informado'),
            'Data: ' . date('d/m/Y H:i:s'),
            'Destinatário: comunicacao@siamfesp.org.br',
            '',
            'O cadastro também foi gravado no admin em Newsletter.',
        ]);

        Mail::raw($body, function ($message) use ($to, $subject, $subscriber) {
            $message->to($to);
            $message->subject($subject);
            $message->replyTo($subscriber->email, $subscriber->name ?: null);
        });
    }
}
