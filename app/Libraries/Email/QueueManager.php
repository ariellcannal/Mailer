<?php

namespace App\Libraries\Email;

use App\Libraries\AWS\SESService;
use App\Models\MessageSendModel;
use App\Models\ContactModel;
use App\Models\MessageModel;

/**
 * Queue Manager
 * 
 * Gerencia fila de envio de emails em massa
 * 
 * @package App\Libraries\Email
 * @author  Mailer System
 * @version 1.0.0
 */
class QueueManager
{
    /**
     * @var SESService
     */
    protected $sesService;

    /**
     * @var MessageSendModel
     */
    protected $sendModel;

    /**
     * @var ContactModel
     */
    protected $contactModel;

    /**
     * @var MessageModel
     */
    protected $messageModel;

    /**
     * Taxa de throttling (emails por segundo)
     * 
     * @var int
     */
    protected $throttleRate = 14;

    /**
     * Construtor
     */
    public function __construct()
    {
        $this->sesService = new SESService();
        $this->sendModel = new MessageSendModel();
        $this->contactModel = new ContactModel();
        $this->messageModel = new MessageModel();
        
        $this->throttleRate = (int) getenv('app.throttleRate') ?: 14;
    }

    /**
     * Adiciona mensagem à fila de envio
     * 
     * @param int   $messageId ID da mensagem
     * @param array $contactIds IDs dos contatos destinatários
     * @param int   $resendNumber Número do reenvio (0 = original)
     * 
     * @return array Resultado da operação
     */
    public function queueMessage(int $messageId, array $contactIds, int $resendNumber = 0): array
    {
        $queued = 0;
        $errors = [];

        foreach ($contactIds as $contactId) {
            try {
                // Gera hash único para tracking
                $trackingHash = $this->generateTrackingHash($messageId, $contactId, $resendNumber);

                // Insere na fila
                $this->sendModel->insert([
                    'message_id' => $messageId,
                    'contact_id' => $contactId,
                    'resend_number' => $resendNumber,
                    'tracking_hash' => $trackingHash,
                    'status' => 'pending',
                ]);

                $queued++;
            } catch (\Exception $e) {
                $errors[] = [
                    'contact_id' => $contactId,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'success' => true,
            'queued' => $queued,
            'errors' => $errors,
        ];
    }

    /**
     * Processa fila de envio
     * 
     * @param int $batchSize Tamanho do lote
     * 
     * @return array Resultado do processamento
     */
    public function processQueue(int $batchSize = 100): array
    {
        // Busca envios pendentes
        $pending = $this->sendModel
            ->where('status', 'pending')
            ->orderBy('id', 'ASC')
            ->findAll($batchSize);

        if (empty($pending)) {
            return [
                'success' => true,
                'processed' => 0,
                'message' => 'No pending sends',
            ];
        }

        $sent = 0;
        $failed = 0;
        $errors = [];

        foreach ($pending as $send) {
            try {
                // Envia email
                $result = $this->sendEmail($send);

                if ($result['success']) {
                    $sent++;
                    
                    // Throttling
                    usleep(1000000 / $this->throttleRate); // Microsegundos
                } else {
                    $failed++;
                    $errors[] = $result['error'];
                }
            } catch (\Exception $e) {
                $failed++;
                $errors[] = $e->getMessage();
                log_message('error', 'Queue processing error: ' . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'processed' => $sent + $failed,
            'sent' => $sent,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * Envia um email individual
     * 
     * @param array $send Dados do envio
     * 
     * @return array Resultado do envio
     */
    protected function sendEmail(array $send): array
    {
        // Busca dados da mensagem
        $message = $this->messageModel->find($send['message_id']);
        if (!$message) {
            return ['success' => false, 'error' => 'Message not found'];
        }

        // Busca dados do contato
        $contact = $this->contactModel->find($send['contact_id']);
        if (!$contact) {
            return ['success' => false, 'error' => 'Contact not found'];
        }

        // Verifica se contato está ativo
        if (!$contact['is_active'] || $contact['opted_out'] || $contact['bounced']) {
            $this->sendModel->update($send['id'], ['status' => 'cancelled']);
            return ['success' => false, 'error' => 'Contact inactive'];
        }

        // Prepara conteúdo do email
        $htmlBody = $this->prepareEmailContent(
            $message['html_content'],
            $contact,
            $send['tracking_hash']
        );

        // Busca dados do remetente
        $senderModel = new \App\Models\SenderModel();
        $sender = $senderModel->find($message['sender_id']);
        
        if (!$sender) {
            return ['success' => false, 'error' => 'Sender not found'];
        }

        // Envia via AWS SES
        $result = $this->sesService->sendEmail(
            from: $sender['email'],
            fromName: $message['from_name'],
            to: $contact['email'],
            subject: $message['subject'],
            htmlBody: $htmlBody,
            replyTo: $message['reply_to'],
            tags: [
                ['Name' => 'message_id', 'Value' => (string) $message['id']],
                ['Name' => 'campaign_id', 'Value' => (string) $message['campaign_id']],
            ]
        );

        if ($result['success']) {
            // Atualiza status do envio
            $this->sendModel->update($send['id'], [
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s'),
            ]);

            // Atualiza contadores da mensagem
            $this->messageModel->increment($message['id'], 'total_sent');

            return ['success' => true, 'messageId' => $result['messageId']];
        } else {
            // Marca como falha
            $this->sendModel->update($send['id'], [
                'status' => 'failed',
            ]);

            return ['success' => false, 'error' => $result['error']];
        }
    }

    /**
     * Prepara conteúdo do email com tracking e personalização
     * 
     * @param string $htmlContent Conteúdo HTML original
     * @param array  $contact Dados do contato
     * @param string $trackingHash Hash de tracking
     * 
     * @return string HTML preparado
     */
    protected function prepareEmailContent(string $htmlContent, array $contact, string $trackingHash): string
    {
        // Substitui variáveis do contato
        $htmlContent = str_replace('{{nome}}', $contact['name'] ?? '', $htmlContent);
        $htmlContent = str_replace('{{email}}', $contact['email'], $htmlContent);
        $htmlContent = str_replace('{{name}}', $contact['name'] ?? '', $htmlContent);

        // Adiciona pixel de tracking (abertura)
        $baseUrl = getenv('app.baseURL');
        $trackingPixel = '<img src="' . $baseUrl . 'track/open/' . $trackingHash . '" width="1" height="1" style="display:none;" />';
        
        // Insere pixel antes do </body>
        if (stripos($htmlContent, '</body>') !== false) {
            $htmlContent = str_ireplace('</body>', $trackingPixel . '</body>', $htmlContent);
        } else {
            $htmlContent .= $trackingPixel;
        }

        // Substitui links por links de tracking
        $htmlContent = $this->replaceLinksWithTracking($htmlContent, $trackingHash);

        // Substitui link de opt-out
        $optoutUrl = $baseUrl . 'optout/' . $trackingHash;
        $htmlContent = str_replace('{{optout_link}}', $optoutUrl, $htmlContent);
        $htmlContent = str_replace('{{unsubscribe_link}}', $optoutUrl, $htmlContent);

        // Substitui link de visualização web
        $webviewUrl = $baseUrl . 'webview/' . $trackingHash;
        $htmlContent = str_replace('{{webview_link}}', $webviewUrl, $htmlContent);
        $htmlContent = str_replace('{{view_online}}', $webviewUrl, $htmlContent);

        return $htmlContent;
    }

    /**
     * Substitui links por links de tracking
     * 
     * @param string $html HTML content
     * @param string $trackingHash Hash de tracking
     * 
     * @return string HTML com links modificados
     */
    protected function replaceLinksWithTracking(string $html, string $trackingHash): string
    {
        $baseUrl = getenv('app.baseURL');
        
        // Regex para encontrar links
        $pattern = '/<a\s+(?:[^>]*?\s+)?href=(["\'])((?:(?!\1).)*)\1/i';
        
        $html = preg_replace_callback($pattern, function($matches) use ($trackingHash, $baseUrl) {
            $quote = $matches[1];
            $url = $matches[2];
            
            // Ignora links especiais
            if (strpos($url, 'mailto:') === 0 || 
                strpos($url, 'tel:') === 0 ||
                strpos($url, '#') === 0 ||
                strpos($url, 'javascript:') === 0 ||
                strpos($url, '{{') !== false) {
                return $matches[0];
            }
            
            // Cria URL de tracking
            $trackingUrl = $baseUrl . 'track/click/' . $trackingHash . '?url=' . urlencode($url);
            
            return '<a href=' . $quote . $trackingUrl . $quote;
        }, $html);
        
        return $html;
    }

    /**
     * Gera hash único para tracking
     * 
     * @param int $messageId ID da mensagem
     * @param int $contactId ID do contato
     * @param int $resendNumber Número do reenvio
     * 
     * @return string Hash único
     */
    protected function generateTrackingHash(int $messageId, int $contactId, int $resendNumber): string
    {
        $data = $messageId . '-' . $contactId . '-' . $resendNumber . '-' . time() . '-' . rand(1000, 9999);
        return hash('sha256', $data);
    }

    /**
     * Obtém estatísticas da fila
     * 
     * @return array Estatísticas
     */
    public function getQueueStats(): array
    {
        $pending = $this->sendModel->where('status', 'pending')->countAllResults(false);
        $sent = $this->sendModel->where('status', 'sent')->countAllResults(false);
        $failed = $this->sendModel->where('status', 'failed')->countAllResults(false);

        return [
            'pending' => $pending,
            'sent' => $sent,
            'failed' => $failed,
            'total' => $pending + $sent + $failed,
        ];
    }

    /**
     * Limpa envios antigos
     * 
     * @param int $days Dias para manter
     * 
     * @return int Número de registros removidos
     */
    public function cleanOldSends(int $days = 90): int
    {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $this->sendModel
            ->where('sent_at <', $date)
            ->where('status', 'sent')
            ->delete();
    }
}
