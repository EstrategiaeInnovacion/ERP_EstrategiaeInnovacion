<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class MicrosoftGraphMailService
{
    private string $clientId;
    private string $tenantId;
    private string $clientSecret;
    private string $senderEmail;

    public function __construct()
    {
        $this->clientId = config('services.microsoft_graph.client_id');
        $this->tenantId = config('services.microsoft_graph.tenant_id');
        $this->clientSecret = config('services.microsoft_graph.client_secret');
        $this->senderEmail = config('services.microsoft_graph.sender_email', 'sistemas@estrategiaeinnovacion.com.mx');
    }

    /**
     * Obtiene un token de acceso usando Client Credentials Flow
     */
    private function getAccessToken(): ?string
    {
        $cacheKey = 'ms_graph_access_token';
        
        // Intentar obtener del caché
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::asForm()->post(
                "https://login.microsoftonline.com/{$this->tenantId}/oauth2/v2.0/token",
                [
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope' => 'https://graph.microsoft.com/.default',
                    'grant_type' => 'client_credentials',
                ]
            );

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'];
                $expiresIn = $data['expires_in'] - 60; // Restar 1 minuto por seguridad
                
                Cache::put($cacheKey, $token, $expiresIn);
                
                return $token;
            }

            Log::error('Microsoft Graph: Error obteniendo token', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Microsoft Graph: Excepción obteniendo token', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Envía un correo usando Microsoft Graph API
     */
    public function sendMail(string $to, string $subject, string $htmlContent, ?string $from = null): bool
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            Log::error('Microsoft Graph: No se pudo obtener token para enviar correo');
            return false;
        }

        $sender = $from ?? $this->senderEmail;

        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlContent,
                ],
                'toRecipients' => [
                    [
                        'emailAddress' => [
                            'address' => $to,
                        ],
                    ],
                ],
            ],
            'saveToSentItems' => true,
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(
                    "https://graph.microsoft.com/v1.0/users/{$sender}/sendMail",
                    $payload
                );

            if ($response->successful()) {
                Log::info('Microsoft Graph: Correo enviado exitosamente', [
                    'to' => $to,
                    'subject' => $subject,
                ]);
                return true;
            }

            Log::error('Microsoft Graph: Error enviando correo', [
                'status' => $response->status(),
                'body' => $response->body(),
                'to' => $to,
                'subject' => $subject,
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Microsoft Graph: Excepción enviando correo', [
                'error' => $e->getMessage(),
                'to' => $to,
            ]);
            return false;
        }
    }

    /**
     * Envía correo a múltiples destinatarios
     */
    public function sendMailToMultiple(array $recipients, string $subject, string $htmlContent, ?string $from = null): bool
    {
        $token = $this->getAccessToken();
        
        if (!$token) {
            return false;
        }

        $sender = $from ?? $this->senderEmail;

        $toRecipients = array_map(function ($email) {
            return ['emailAddress' => ['address' => $email]];
        }, $recipients);

        $payload = [
            'message' => [
                'subject' => $subject,
                'body' => [
                    'contentType' => 'HTML',
                    'content' => $htmlContent,
                ],
                'toRecipients' => $toRecipients,
            ],
            'saveToSentItems' => true,
        ];

        try {
            $response = Http::withToken($token)
                ->timeout(30)
                ->post(
                    "https://graph.microsoft.com/v1.0/users/{$sender}/sendMail",
                    $payload
                );

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Microsoft Graph: Error enviando correo múltiple', [
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
