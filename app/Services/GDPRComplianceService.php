<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Lead;
use App\Models\SuppressionList;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class GDPRComplianceService
{
    /**
     * Regional Outreach Disclosure Footers
     */
    public const REGIONAL_FOOTERS = [
        'eu_general' => [
            'name' => 'EU GDPR (General B2B Legitimate Interest)',
            'footer_text' => "\n\n---\nThis professional message is sent pursuant to Article 6(1)(f) GDPR (legitimate interest). If you do not wish to receive future communications, simply reply 'UNSUBSCRIBE' or click the opt-out link.",
        ],
        'fr_cnil' => [
            'name' => 'France (CNIL Article L.34-5 CPCE B2B)',
            'footer_text' => "\n\n---\nConformément à l'article L.34-5 du CPCE et aux recommandations de la CNIL relatives à la prospection B2B, ce message vous est adressé au titre de vos fonctions professionnelles. Vous disposez d'un droit d'opposition à tout moment en répondant 'STOP'.",
        ],
        'de_uwg' => [
            'name' => 'Germany (UWG §7 Abs. 2 & DSGVO / BDSG)',
            'footer_text' => "\n\n---\nDiese geschäftliche Nachricht richtet sich an Sie im Rahmen Ihrer beruflichen Tätigkeit. Falls Sie keine weiteren Kontaktaufnahmen wünschen, antworten Sie bitte mit 'Abmelden'.",
        ],
        'uk_pecr' => [
            'name' => 'United Kingdom (PECR & UK GDPR B2B)',
            'footer_text' => "\n\n---\nSent in accordance with the Privacy and Electronic Communications Regulations (PECR) for corporate subscribers. To opt out, reply 'STOP'.",
        ],
    ];

    /**
     * Export all data for a specific lead (GDPR Article 20 Right to Data Portability).
     */
    public function exportLeadDossier(Lead $lead): array
    {
        $lead->load(['company', 'owner', 'messages', 'deals', 'tasks', 'internalNotes']);

        return [
            'lead_profile' => $lead->toArray(),
            'company'      => $lead->company ? $lead->company->toArray() : null,
            'messages'     => $lead->messages->map(fn($m) => [
                'subject'    => $m->subject,
                'sent_at'    => $m->sent_at,
                'status'     => $m->status,
                'from_email' => $m->from_email,
            ]),
            'deals'        => $lead->deals->toArray(),
            'tasks'        => $lead->tasks->toArray(),
            'notes'        => $lead->internalNotes->toArray(),
            'export_date'  => now()->toIso8601String(),
        ];
    }

    /**
     * Permanently erase lead data (GDPR Article 17 Right to be Forgotten).
     */
    public function executeErasure(Lead $lead, $adminUser): void
    {
        DB::transaction(function () use ($lead, $adminUser) {
            $email = strtolower(trim($lead->email));
            $workspaceId = $lead->workspace_id;

            // 1. Add to suppression list so the contact can never be re-acquired or re-contacted
            SuppressionList::suppress(
                $workspaceId,
                $email,
                SuppressionList::REASON_UNSUBSCRIBE,
                'GDPR Right to be Forgotten erasure request'
            );

            // 2. Anonymize messages
            $lead->messages()->update([
                'subject'   => '[GDPR ANONYMIZED]',
                'body_text' => '[Content deleted under GDPR Article 17]',
                'body_html' => '<p>[Content deleted under GDPR Article 17]</p>',
            ]);

            // 3. Delete internal notes & tasks
            $lead->internalNotes()->delete();
            $lead->tasks()->delete();

            // 4. Force delete the lead record
            $leadId = $lead->id;
            $lead->forceDelete();

            // 5. Log audit entry
            AuditLog::create([
                'workspace_id'   => $workspaceId,
                'user_id'        => $adminUser?->id,
                'action'         => 'gdpr_erasure',
                'auditable_type' => Lead::class,
                'auditable_id'   => $leadId,
                'old_values'     => ['email' => $email],
                'new_values'     => ['status' => 'permanently_erased_and_suppressed'],
            ]);
        });
    }
}
