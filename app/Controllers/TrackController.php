<?php

namespace App\Controllers;

use App\Models\MessageSendModel;
use App\Models\MessageOpenModel;
use App\Models\MessageClickModel;
use App\Models\ContactModel;
use App\Models\MessageModel;

/**
 * Track Controller
 * 
 * Controller para tracking de aberturas e cliques
 * 
 * @package App\Controllers
 * @author  Mailer System
 * @version 1.0.0
 */
class TrackController extends BaseController
{
    /**
     * Tracking de abertura (pixel transparente)
     * 
     * @param string $hash Hash de tracking
     * 
     * @return \CodeIgniter\HTTP\Response Imagem 1x1 transparente
     */
    public function open(string $hash)
    {
        // Busca envio pelo hash
        $sendModel = new MessageSendModel();
        $send = $sendModel->where('tracking_hash', $hash)->first();

        if ($send) {
            // Registra abertura
            $this->recordOpen($send);
        }

        // Retorna pixel transparente 1x1
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        
        return $this->response
            ->setHeader('Content-Type', 'image/gif')
            ->setHeader('Content-Length', strlen($pixel))
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setBody($pixel);
    }

    /**
     * Tracking de clique (redirecionamento)
     * 
     * @param string $hash Hash de tracking
     * 
     * @return \CodeIgniter\HTTP\RedirectResponse Redirecionamento para URL original
     */
    public function click(string $hash)
    {
        // Busca envio pelo hash
        $sendModel = new MessageSendModel();
        $send = $sendModel->where('tracking_hash', $hash)->first();

        // URL de destino
        $url = $this->request->getGet('url');
        
        if (!$url) {
            return redirect()->to('/');
        }

        if ($send) {
            // Registra clique
            $this->recordClick($send, $url);
        }

        // Redireciona para URL original
        return redirect()->to($url);
    }

    /**
     * Registra uma abertura
     * 
     * @param array $send Dados do envio
     * 
     * @return void
     */
    protected function recordOpen(array $send): void
    {
        $sendModel = new MessageSendModel();
        $openModel = new MessageOpenModel();
        $contactModel = new ContactModel();
        $messageModel = new MessageModel();

        // Verifica se é primeira abertura
        $isFirstOpen = !$send['opened'];

        // Registra abertura detalhada
        $openModel->insert([
            'send_id' => $send['id'],
            'opened_at' => date('Y-m-d H:i:s'),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
        ]);

        // Atualiza registro de envio
        $updateData = [
            'opened' => 1,
            'total_opens' => $send['total_opens'] + 1,
            'last_open_at' => date('Y-m-d H:i:s'),
        ];

        if ($isFirstOpen) {
            $updateData['first_open_at'] = date('Y-m-d H:i:s');
        }

        $sendModel->update($send['id'], $updateData);

        // Atualiza contadores da mensagem
        if ($isFirstOpen) {
            $messageModel->increment($send['message_id'], 'total_opens');
        }

        // Atualiza estatísticas do contato
        $contact = $contactModel->find($send['contact_id']);
        
        if ($contact) {
            $contactUpdateData = [
                'total_opens' => $contact['total_opens'] + 1,
                'last_open_date' => date('Y-m-d H:i:s'),
            ];

            // Calcula tempo médio de abertura (se primeira abertura)
            if ($isFirstOpen && $send['sent_at']) {
                $sentTime = strtotime($send['sent_at']);
                $openTime = time();
                $timeToOpen = $openTime - $sentTime;

                // Atualiza média
                if ($contact['avg_open_time'] > 0) {
                    $contactUpdateData['avg_open_time'] = (int) (($contact['avg_open_time'] + $timeToOpen) / 2);
                } else {
                    $contactUpdateData['avg_open_time'] = $timeToOpen;
                }
            }

            $contactModel->update($send['contact_id'], $contactUpdateData);

            // Atualiza score de qualidade
            $contactModel->updateQualityScore($send['contact_id']);
        }

        // Atualiza contadores da campanha (se houver)
        $message = $messageModel->find($send['message_id']);
        if ($message && $message['campaign_id'] && $isFirstOpen) {
            $campaignModel = new \App\Models\CampaignModel();
            $campaignModel->increment($message['campaign_id'], 'total_opens');
        }
    }

    /**
     * Registra um clique
     * 
     * @param array  $send Dados do envio
     * @param string $url URL clicada
     * 
     * @return void
     */
    protected function recordClick(array $send, string $url): void
    {
        $sendModel = new MessageSendModel();
        $clickModel = new MessageClickModel();
        $contactModel = new ContactModel();
        $messageModel = new MessageModel();

        // Verifica se é primeiro clique
        $isFirstClick = !$send['clicked'];

        // Gera hash do link
        $linkHash = hash('md5', $url);

        // Registra clique detalhado
        $clickModel->insert([
            'send_id' => $send['id'],
            'link_url' => $url,
            'link_hash' => $linkHash,
            'clicked_at' => date('Y-m-d H:i:s'),
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => $this->request->getUserAgent()->getAgentString(),
        ]);

        // Atualiza registro de envio
        $updateData = [
            'clicked' => 1,
            'total_clicks' => $send['total_clicks'] + 1,
            'last_click_at' => date('Y-m-d H:i:s'),
        ];

        if ($isFirstClick) {
            $updateData['first_click_at'] = date('Y-m-d H:i:s');
        }

        $sendModel->update($send['id'], $updateData);

        // Atualiza contadores da mensagem
        if ($isFirstClick) {
            $messageModel->increment($send['message_id'], 'total_clicks');
        }

        // Atualiza estatísticas do contato
        $contact = $contactModel->find($send['contact_id']);
        
        if ($contact) {
            $contactModel->update($send['contact_id'], [
                'total_clicks' => $contact['total_clicks'] + 1,
                'last_click_date' => date('Y-m-d H:i:s'),
            ]);

            // Atualiza score de qualidade
            $contactModel->updateQualityScore($send['contact_id']);
        }

        // Atualiza contadores da campanha (se houver)
        $message = $messageModel->find($send['message_id']);
        if ($message && $message['campaign_id'] && $isFirstClick) {
            $campaignModel = new \App\Models\CampaignModel();
            $campaignModel->increment($message['campaign_id'], 'total_clicks');
        }
    }

    /**
     * Visualização web do email
     * 
     * @param string $hash Hash de tracking
     * 
     * @return string HTML do email
     */
    public function webview(string $hash)
    {
        $sendModel = new MessageSendModel();
        $send = $sendModel->where('tracking_hash', $hash)->first();

        if (!$send) {
            return view('errors/html/error_404');
        }

        $messageModel = new MessageModel();
        $message = $messageModel->find($send['message_id']);

        if (!$message) {
            return view('errors/html/error_404');
        }

        // Registra abertura
        $this->recordOpen($send);

        // Retorna HTML da mensagem
        return $message['html_content'];
    }

    /**
     * Opt-out (descadastramento)
     * 
     * @param string $hash Hash de tracking
     * 
     * @return string View de confirmação
     */
    public function optout(string $hash)
    {
        $sendModel = new MessageSendModel();
        $send = $sendModel->where('tracking_hash', $hash)->first();

        if (!$send) {
            return view('errors/html/error_404');
        }

        $contactModel = new ContactModel();
        $contact = $contactModel->find($send['contact_id']);

        if (!$contact) {
            return view('errors/html/error_404');
        }

        // Se já está opted out
        if ($contact['opted_out']) {
            return view('tracking/optout_already', [
                'email' => $contact['email'],
            ]);
        }

        // Se é POST, processa opt-out
        if ($this->request->getMethod() === 'post') {
            // Marca contato como opted out
            $contactModel->optOut($send['contact_id']);

            // Registra opt-out
            $optoutModel = new \App\Models\OptoutModel();
            $optoutModel->insert([
                'contact_id' => $send['contact_id'],
                'message_id' => $send['message_id'],
                'ip_address' => $this->request->getIPAddress(),
            ]);

            // Atualiza contadores
            $messageModel = new MessageModel();
            $messageModel->increment($send['message_id'], 'total_optouts');

            $message = $messageModel->find($send['message_id']);
            if ($message && $message['campaign_id']) {
                $campaignModel = new \App\Models\CampaignModel();
                $campaignModel->increment($message['campaign_id'], 'total_optouts');
            }

            return view('tracking/optout_success', [
                'email' => $contact['email'],
            ]);
        }

        // Mostra formulário de confirmação
        return view('tracking/optout_confirm', [
            'email' => $contact['email'],
            'hash' => $hash,
        ]);
    }
}
