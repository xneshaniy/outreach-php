<?php

namespace App\Services;

use App\Models\AIConfiguration;
use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    /**
     * Get the active AI configuration for a workspace.
     */
    public function getConfig(?Workspace $workspace): ?AIConfiguration
    {
        if (!$workspace) {
            return null;
        }

        return AIConfiguration::where('workspace_id', $workspace->id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Test connection to provider with given credentials.
     */
    public function testConnection(string $provider, string $apiKey, string $model): array
    {
        try {
            $prompt = 'Respond with exact phrase: "Connection Successful"';
            $result = $this->callModelDirect($provider, $apiKey, $model, 'You are a test assistant.', $prompt, 20);

            if ($result['success']) {
                return ['success' => true, 'message' => "Successfully connected to {$provider} ({$model})!"];
            }

            return ['success' => false, 'message' => $result['error'] ?? 'Connection test failed.'];
        } catch (\Throwable $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generate 5 subjects, 3 openings, 3 bodies, 3 CTAs, and follow-ups.
     */
    public function generateAssistantSuggestions(Workspace $workspace, array $params): array
    {
        $config = $this->getConfig($workspace);
        if (!$config || empty($config->api_key)) {
            return [
                'success' => false,
                'error' => 'No AI provider configured. Please visit Settings -> AI Configuration to add your API key.',
            ];
        }

        $objective = $params['objective'] ?? 'B2B Sales Outreach';
        $audience = $params['audience'] ?? 'Decision makers';
        $product = $params['product'] ?? 'Our professional services';
        $tone = $params['tone'] ?? 'Professional and consultative';
        $length = $params['length'] ?? 'Concise (under 120 words)';
        $cta = $params['cta'] ?? 'A quick 10-minute introductory call next week';

        $systemPrompt = "You are an elite B2B email copywriter. You produce high-converting, human-sounding outreach emails.
CRITICAL RULE: Return ONLY a valid JSON object with the exact keys:
'subjects' (array of 5 strings),
'openings' (array of 3 strings),
'bodies' (array of 3 strings),
'ctas' (array of 3 strings),
'follow_ups' (array of 2 strings).
Use natural personalization tags like {{first_name}}, {{company}}, {{job_title}} appropriately. Do not fabricate unverifiable claims.";

        $userPrompt = "Generate high-converting outreach email variations based on:
- Objective: {$objective}
- Target Audience: {$audience}
- Service / Product: {$product}
- Desired Tone: {$tone}
- Length: {$length}
- Call to Action: {$cta}

Output pure JSON matching the requested structure.";

        $response = $this->callModel($config, $systemPrompt, $userPrompt, 1200);

        if (!$response['success']) {
            return $response;
        }

        $parsed = $this->parseJson($response['content']);
        if ($parsed) {
            return [
                'success' => true,
                'data' => $parsed,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'subjects' => [
                    "Quick question regarding {$product}",
                    "Ideas for {{company}}'s growth",
                    "Connecting on {$objective}",
                    "Thought this might help {{company}}",
                    "Exploring synergies with {{company}}",
                ],
                'openings' => [
                    "Hi {{first_name}}, I noticed your work at {{company}} and wanted to reach out.",
                    "Hello {{first_name}}, hope your week is off to a productive start.",
                    "Hi {{first_name}}, with your focus on {{industry}}, I thought this would be relevant.",
                ],
                'bodies' => [
                    "We help teams like {{company}} achieve {$objective} through {$product}.",
                    "I wanted to share how we assist companies in {{industry}} to improve their workflow.",
                    "Seeing your leadership as {{job_title}} at {{company}}, I wanted to share a quick perspective on {$product}.",
                ],
                'ctas' => [
                    "Are you open to a brief 10-minute chat this Thursday?",
                    "Would you be against a quick introductory conversation next week?",
                    "Does it make sense to connect for 5 minutes this week?",
                ],
                'follow_ups' => [
                    "Hi {{first_name}}, following up on my previous note. Any thoughts on whether this could benefit {{company}}?",
                    "Hi {{first_name}}, just checking in to see if you had a chance to review my note below.",
                ],
            ],
        ];
    }

    /**
     * Rewrite or polish existing email content.
     */
    public function rewriteContent(Workspace $workspace, string $content, string $mode, string $instructions = ''): array
    {
        $config = $this->getConfig($workspace);
        if (!$config || empty($config->api_key)) {
            return [
                'success' => false,
                'error' => 'No AI provider configured. Please visit Settings -> AI Configuration.',
            ];
        }

        $instructionsMap = [
            'shorter' => 'Make the text significantly more concise, direct, and under 90 words while keeping key points and variables intact.',
            'more_professional' => 'Elevate the tone to be highly polished, executive, and consultative.',
            'more_friendly' => 'Make the tone warm, approachable, conversational, and natural, without sounding overly casual.',
            'improve_subject' => 'Provide 5 improved, high-open-rate subject lines for this email.',
            'generate_follow_up' => 'Write a natural 2-sentence polite follow-up email that references this initial email.',
        ];

        $directive = $instructionsMap[$mode] ?? ($instructions ?: 'Improve this email copy for maximum engagement.');

        $systemPrompt = "You are an expert cold email editor. Maintain all template variables like {{first_name}}, {{company}} exactly as written. Return ONLY the rewritten text without conversational commentary.";
        $userPrompt = "Task: {$directive}\n\nOriginal Text:\n{$content}";

        $response = $this->callModel($config, $systemPrompt, $userPrompt, 600);

        return $response;
    }

    /**
     * Generate grounded personalized email for a specific lead.
     */
    public function generatePersonalizedEmail(Workspace $workspace, Lead $lead, string $objective = '', string $template = ''): array
    {
        $config = $this->getConfig($workspace);

        // Build lead facts context
        $leadFacts = [
            'first_name' => $lead->first_name ?: 'there',
            'last_name' => $lead->last_name ?: '',
            'company' => $lead->company ?: ($lead->company?->name ?? 'your company'),
            'job_title' => $lead->job_title ?: 'Professional',
            'industry' => $lead->industry ?: 'your sector',
            'website' => $lead->website ?: '',
            'country' => $lead->country ?: '',
            'city' => $lead->city ?: '',
            'notes' => $lead->notes ?: '',
        ];

        if (!$config || empty($config->api_key)) {
            // High-quality template-based personalization fallback when no AI API key is set
            $subject = "Quick question regarding {$leadFacts['company']}";
            $body = "<p>Hi {$leadFacts['first_name']},</p>
<p>I noticed your role as {$leadFacts['job_title']} at {$leadFacts['company']} and wanted to reach out directly.</p>
<p>We work with leaders in {$leadFacts['industry']} to help streamline operations and drive measurable results.</p>
<p>Would you have 10 minutes sometime this week for a brief conversation?</p>
<p>Best regards,<br>{{from_name}}</p>";

            return [
                'success' => true,
                'data' => [
                    'subject' => $subject,
                    'body' => $body,
                    'source' => 'template',
                ],
            ];
        }

        $systemPrompt = "You are a personalized email writing specialist.
IMPORTANT:
- Ground your writing ONLY on the provided lead facts.
- DO NOT invent company achievements, news, revenue figures, or personal claims that are not in the provided facts.
- If information is missing, speak naturally without making unsupported claims.
- Return ONLY a JSON object with: 'subject' (string) and 'body' (HTML string with <p> tags).";

        $userPrompt = "Lead Information:\n" . json_encode($leadFacts, JSON_PRETTY_PRINT) . "\n\nCampaign Objective: {$objective}\nExisting Template/Guidelines: {$template}\n\nCreate a natural, tailored email for this lead.";

        $response = $this->callModel($config, $systemPrompt, $userPrompt, 600);

        if (!$response['success']) {
            return $response;
        }

        $parsed = $this->parseJson($response['content']);
        if ($parsed && isset($parsed['subject'], $parsed['body'])) {
            return [
                'success' => true,
                'data' => $parsed,
            ];
        }

        return [
            'success' => true,
            'data' => [
                'subject' => "Connecting with {$leadFacts['first_name']} at {$leadFacts['company']}",
                'body' => nl2br($response['content']),
            ],
        ];
    }

    /**
     * Conduct AI-assisted lead research based on company & name.
     */
    public function researchLead(Workspace $workspace, array $leadData): array
    {
        $config = $this->getConfig($workspace);

        $name = trim(($leadData['first_name'] ?? '') . ' ' . ($leadData['last_name'] ?? ''));
        $company = $leadData['company'] ?? '';
        $website = $leadData['website'] ?? '';
        $jobTitle = $leadData['job_title'] ?? '';
        $linkedin = $leadData['linkedin_url'] ?? '';

        if (!$config || empty($config->api_key)) {
            // Intelligent deterministic summary when API key not yet connected
            return [
                'success' => true,
                'data' => [
                    'company_overview' => "{$company} operates in the {$leadData['industry'] ?? 'technology / business'} space" . ($website ? " ({$website})" : "") . ".",
                    'industry' => $leadData['industry'] ?? 'Business Services',
                    'potential_needs' => "Optimizing outreach efficiency, scaling customer acquisition, and automating follow-up workflows for {$jobTitle} roles.",
                    'relevant_services' => "Cold email outreach automation, mailbox rotation, deliverability monitoring, and CRM pipeline tracking.",
                    'outreach_angle' => "Focus on time-savings and pipeline growth relevant to {$name}'s leadership responsibilities at {$company}.",
                    'suggested_subject' => "Idea for {$company}'s growth initiatives",
                    'suggested_opening' => "Hi {$leadData['first_name']}, seeing your work as {$jobTitle} at {$company}, I wanted to reach out regarding pipeline scalability.",
                    'verified' => false,
                ],
            ];
        }

        $systemPrompt = "You are a B2B sales intelligence analyst.
Analyze the provided company and prospect info.
CRITICAL: Do NOT invent false facts or fake news. If details are uncertain, provide general industry insights clearly marked as prospective.
Return ONLY a valid JSON object with the following exact keys:
'company_overview' (string),
'industry' (string),
'potential_needs' (string),
'relevant_services' (string),
'outreach_angle' (string),
'suggested_subject' (string),
'suggested_opening' (string).";

        $userPrompt = "Prospect Details:
- Name: {$name}
- Job Title: {$jobTitle}
- Company: {$company}
- Website: {$website}
- LinkedIn: {$linkedin}";

        $response = $this->callModel($config, $systemPrompt, $userPrompt, 800);

        if (!$response['success']) {
            return $response;
        }

        $parsed = $this->parseJson($response['content']);
        if ($parsed) {
            $parsed['verified'] = false;
            return ['success' => true, 'data' => $parsed];
        }

        return [
            'success' => false,
            'error' => 'Failed to parse AI research response',
        ];
    }

    /**
     * Classify an incoming email reply.
     */
    public function classifyReply(Workspace $workspace, string $replyText, string $subject = ''): array
    {
        $replyLower = strtolower($replyText);

        // Fast-path rule matching for obvious keywords
        if (preg_match('/(unsubscribe|remove me|opt out|stop emailing|take me off)/i', $replyLower)) {
            return [
                'classification' => 'unsubscribe',
                'confidence' => 0.99,
                'reason' => 'Direct unsubscribe or opt-out keyword detected',
                'return_date' => null,
            ];
        }

        if (preg_match('/(out of (the )?office|on annual leave|away from my email|maternity leave|auto-reply)/i', $replyLower)) {
            $returnDate = null;
            if (preg_match('/(back on|return(ing)? on|back in the office on)\s+([A-Za-z]+ \d{1,2}(st|nd|rd|th)?( \d{4})?|\d{1,2}[\/\.-]\d{1,2}[\/\.-]\d{2,4})/i', $replyText, $matches)) {
                try {
                    $returnDate = date('Y-m-d', strtotime($matches[3]));
                } catch (\Throwable $e) {}
            }

            return [
                'classification' => 'out_of_office',
                'confidence' => 0.95,
                'reason' => 'Automated out-of-office autoreply detected',
                'return_date' => $returnDate,
            ];
        }

        if (preg_match('/(calendar|book a time|let\'s talk|grab 10 mins|schedule a call|free on (monday|tuesday|wednesday|thursday|friday)|calendly\.com|hubspot\.com\/meetings)/i', $replyLower)) {
            return [
                'classification' => 'meeting_requested',
                'confidence' => 0.95,
                'reason' => 'Prospect explicitly offered or requested a meeting time / booking link',
                'return_date' => null,
            ];
        }

        if (preg_match('/(sounds good|interested|send more info|share a deck|let\'s do it|sure, send it over)/i', $replyLower)) {
            return [
                'classification' => 'interested',
                'confidence' => 0.90,
                'reason' => 'Positive interest expressed in message',
                'return_date' => null,
            ];
        }

        if (preg_match('/(not interested|no thank you|no thanks|not looking|already have a solution|pass on this)/i', $replyLower)) {
            return [
                'classification' => 'not_interested',
                'confidence' => 0.95,
                'reason' => 'Prospect declined the outreach',
                'return_date' => null,
            ];
        }

        // Fall back to AI classification if configured
        $config = $this->getConfig($workspace);
        if ($config && !empty($config->api_key)) {
            $systemPrompt = "You are an incoming email sentiment & intent classifier.
Classify the email reply into exactly one of:
'interested', 'positive', 'meeting_requested', 'not_interested', 'unsubscribe', 'out_of_office', 'wrong_person', 'referral', 'question', 'other'.
Return ONLY a valid JSON object with:
'classification' (string),
'confidence' (float between 0.5 and 1.0),
'reason' (short string),
'return_date' (YYYY-MM-DD string or null).";

            $userPrompt = "Subject: {$subject}\nReply Body:\n{$replyText}";

            $aiRes = $this->callModel($config, $systemPrompt, $userPrompt, 200);
            if ($aiRes['success']) {
                $parsed = $this->parseJson($aiRes['content']);
                if ($parsed && isset($parsed['classification'])) {
                    return $parsed;
                }
            }
        }

        return [
            'classification' => 'other',
            'confidence' => 0.6,
            'reason' => 'General response received',
            'return_date' => null,
        ];
    }

    /**
     * Dispatch HTTP request to the selected model provider.
     */
    protected function callModel(AIConfiguration $config, string $systemPrompt, string $userPrompt, int $maxTokens = 800): array
    {
        return $this->callModelDirect(
            $config->provider,
            $config->api_key,
            $config->model,
            $systemPrompt,
            $userPrompt,
            $maxTokens
        );
    }

    /**
     * Low-level API client for OpenAI, Google Gemini, and Anthropic.
     */
    protected function callModelDirect(string $provider, string $apiKey, string $model, string $systemPrompt, string $userPrompt, int $maxTokens = 800): array
    {
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API Key is empty'];
        }

        try {
            if ($provider === 'gemini') {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";
                $body = [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => "{$systemPrompt}\n\n{$userPrompt}"]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'maxOutputTokens' => $maxTokens,
                        'temperature' => 0.4,
                    ]
                ];

                $response = Http::timeout(30)->post($url, $body);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    return ['success' => true, 'content' => trim($text)];
                }

                return ['success' => false, 'error' => $response->json()['error']['message'] ?? $response->body()];
            }

            // OpenAI / Compatible by default
            $url = 'https://api.openai.com/v1/chat/completions';
            $body = [
                'model' => $model ?: 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'max_tokens' => $maxTokens,
                'temperature' => 0.5,
            ];

            $response = Http::withToken($apiKey)->timeout(30)->post($url, $body);

            if ($response->successful()) {
                $data = $response->json();
                $text = $data['choices'][0]['message']['content'] ?? '';
                return ['success' => true, 'content' => trim($text)];
            }

            return ['success' => false, 'error' => $response->json()['error']['message'] ?? $response->body()];
        } catch (\Throwable $e) {
            Log::error('AI Model invocation failed', ['provider' => $provider, 'error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Clean and parse json out of markdown code blocks or plain text.
     */
    protected function parseJson(string $raw): ?array
    {
        $clean = preg_replace('/```json\s*/i', '', $raw);
        $clean = preg_replace('/```\s*$/i', '', $clean);
        $clean = trim($clean);

        $decoded = json_decode($clean, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try substring search for {...}
        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $sub = substr($clean, $start, $end - $start + 1);
            $decoded = json_decode($sub, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
