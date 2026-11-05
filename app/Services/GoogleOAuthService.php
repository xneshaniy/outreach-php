<?php

namespace App\Services;

use App\Models\GoogleIntegration;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GoogleOAuthService
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    // Scopes requested for the integration
    private const SCOPES = [
        'openid',
        'email',
        'profile',
        'https://www.googleapis.com/auth/spreadsheets.readonly',
        'https://www.googleapis.com/auth/drive.metadata.readonly',
        // Gmail readonly is optional — only add if user opts in
    ];

    public function __construct()
    {
        $this->clientId     = config('google.client_id', '');
        $this->clientSecret = config('google.client_secret', '');
        $this->redirectUri  = config('google.redirect_uri', url('/google/callback'));
    }

    /**
     * Whether Google OAuth is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Generate the Google OAuth consent URL.
     */
    public function getAuthUrl(bool $includeGmail = false): string
    {
        $scopes = self::SCOPES;
        if ($includeGmail) {
            $scopes[] = 'https://www.googleapis.com/auth/gmail.readonly';
        }

        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => implode(' ', $scopes),
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => csrf_token(),
        ]);

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . $params;
    }

    /**
     * Exchange authorization code for tokens.
     */
    public function exchangeCode(string $code): array
    {
        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (!$response->successful()) {
            Log::error('GoogleOAuthService: token exchange failed', ['status' => $response->status()]);
            throw new \Exception('Google OAuth token exchange failed: ' . ($response->json('error_description') ?? $response->status()));
        }

        return $response->json();
    }

    /**
     * Refresh an expired access token.
     */
    public function refreshToken(GoogleIntegration $integration): bool
    {
        if (empty($integration->refresh_token)) {
            return false;
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'refresh_token' => $integration->refresh_token,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'refresh_token',
        ]);

        if (!$response->successful()) {
            Log::warning("GoogleOAuthService: token refresh failed for integration {$integration->id}");
            return false;
        }

        $data = $response->json();
        $integration->access_token    = $data['access_token'];
        $integration->token_expires_at = now()->addSeconds($data['expires_in'] ?? 3600);
        if (!empty($data['refresh_token'])) {
            $integration->refresh_token = $data['refresh_token'];
        }
        $integration->save();

        return true;
    }

    /**
     * Get a valid access token (refresh if expired).
     */
    public function getValidToken(GoogleIntegration $integration): ?string
    {
        if ($integration->isExpired()) {
            if (!$this->refreshToken($integration)) {
                return null;
            }
            $integration->refresh();
        }
        return $integration->access_token;
    }

    /**
     * Revoke a token and deactivate the integration.
     */
    public function revokeToken(GoogleIntegration $integration): bool
    {
        try {
            $token = $integration->access_token;
            Http::post('https://oauth2.googleapis.com/revoke', ['token' => $token]);
        } catch (\Exception $e) {
            Log::warning("GoogleOAuthService: revoke failed for integration {$integration->id}: " . $e->getMessage());
        }

        $integration->update(['is_active' => false]);
        return true;
    }

    /**
     * Fetch Google user profile from token data.
     */
    public function getUserInfo(string $accessToken): array
    {
        $response = Http::withToken($accessToken)
            ->get('https://www.googleapis.com/oauth2/v2/userinfo');

        if (!$response->successful()) {
            throw new \Exception('Failed to fetch Google user info');
        }

        return $response->json();
    }

    /**
     * Store tokens and user info into google_integrations.
     */
    public function storeIntegration(Workspace $workspace, User $user, array $tokenData, array $userInfo): GoogleIntegration
    {
        return GoogleIntegration::updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'google_email' => $userInfo['email'],
            ],
            [
                'user_id'          => $user->id,
                'google_account_id'=> $userInfo['id'] ?? null,
                'google_name'      => $userInfo['name'] ?? null,
                'google_avatar'    => $userInfo['picture'] ?? null,
                'access_token'     => $tokenData['access_token'],
                'refresh_token'    => $tokenData['refresh_token'] ?? null,
                'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'scopes'           => explode(' ', $tokenData['scope'] ?? ''),
                'is_active'        => true,
            ]
        );
    }
}
