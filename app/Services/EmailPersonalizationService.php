<?php

namespace App\Services;

use App\Models\Lead;

class EmailPersonalizationService
{
    /**
     * Professional, culturally compliant B2B Outreach Templates
     * Stating the clear, respectful value proposition:
     * "We research suitable companies, identify relevant prospects and help you connect
     * with potential customers through targeted outreach to support business growth."
     */
    public static function generateContent(Lead $lead, string $senderName = 'Outreach Team'): array
    {
        $lang = $lead->detected_language ?: 'en';
        $company = $lead->company_name ?: ($lead->company?->name ?: 'votre entreprise');
        $contactPerson = trim("{$lead->first_name} {$lead->last_name}");
        $isGeneric = str_contains(strtolower($contactPerson), 'sales') || str_contains(strtolower($contactPerson), 'team') || empty($lead->first_name);

        switch ($lang) {
            case 'fr':
                return self::generateFrenchEmail($company, $contactPerson, $isGeneric, $senderName);
            case 'de':
                return self::generateGermanEmail($company, $contactPerson, $isGeneric, $senderName);
            default:
                return self::generateEnglishEmail($company, $contactPerson, $isGeneric, $senderName);
        }
    }

    /**
     * French (France & Francophone B2B)
     */
    protected static function generateFrenchEmail(string $company, string $contactPerson, bool $isGeneric, string $senderName): array
    {
        $greeting = $isGeneric ? "Bonjour l'équipe {$company}," : "Bonjour {$contactPerson},";
        $subject = "Développement commercial pour {$company}";

        $bodyText = "{$greeting}\n\n"
            . "Je me permets de vous contacter car nous accompagnons les entreprises comme {$company} dans l'identification de nouvelles opportunités commerciales.\n\n"
            . "Concrètement, nous ciblons et qualifions des entreprises partenaires potentielles, puis facilitons une mise en relation directe avec les décideurs pertinents grâce à des approches sur mesure.\n\n"
            . "Notre objectif est de soutenir le développement de votre activité en vous aidant à vous connecter régulièrement avec de nouveaux clients qualifiés.\n\n"
            . "Seriez-vous disponible pour un court échange de quelques minutes dans le courant de la semaine prochaine ?\n\n"
            . "Bien cordialement,\n"
            . "{$senderName}\n"
            . "Outreach.click\n\n"
            . "---\n"
            . "Si vous ne souhaitez plus recevoir de messages de notre part, veuillez simplement répondre 'STOP' ou cliquer sur le lien de désinscription.";

        $bodyHtml = "<div style=\"font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #2d3748;\">"
            . "<p>{$greeting}</p>"
            . "<p>Je me permets de vous contacter car nous accompagnons les entreprises comme <strong>{$company}</strong> dans l'identification de nouvelles opportunités commerciales.</p>"
            . "<p>Concrètement, nous ciblons et qualifions des entreprises partenaires potentielles, puis facilitons une mise en relation directe avec les décideurs pertinents grâce à des approches sur mesure.</p>"
            . "<p>Notre objectif est de soutenir le développement de votre activité en vous aidant à vous connecter régulièrement avec de nouveaux clients qualifiés.</p>"
            . "<p>Seriez-vous disponible pour un court échange de quelques minutes dans le courant de la semaine prochaine ?</p>"
            . "<p>Bien cordialement,<br><strong>{$senderName}</strong><br><span style=\"color: #718096; font-size: 12px;\">Outreach.click</span></p>"
            . "<hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 12px;\">"
            . "<p style=\"font-size: 11px; color: #a0aec0;\">Si vous ne souhaitez plus recevoir de messages de notre part, veuillez simplement répondre 'STOP' à cet e-mail.</p>"
            . "</div>";

        return [
            'subject'   => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'language'  => 'fr',
        ];
    }

    /**
     * German (Germany, Austria, Switzerland B2B - Formal & compliant)
     */
    protected static function generateGermanEmail(string $company, string $contactPerson, bool $isGeneric, string $senderName): array
    {
        $greeting = $isGeneric ? "Guten Tag an das Team von {$company}," : "Guten Tag {$contactPerson},";
        $subject = "Neukundengewinnung & B2B-Potenziale für {$company}";

        $bodyText = "{$greeting}\n\n"
            . "ich wende mich an Sie, da wir Unternehmen wie {$company} dabei unterstützen, passende B2B-Geschäftskontakte und Neukunden systematisch zu identifizieren.\n\n"
            . "Wir recherchieren geeignete Zielunternehmen, qualifizieren Entscheidungsträger und unterstützen Sie bei der direkten Kontaktaufnahme durch gezielte, relevante Ansprachen.\n\n"
            . "Unser Ziel ist es, Ihr Unternehmenswachstum nachhaltig zu fördern und Ihnen neue Geschäftschancen zu eröffnen.\n\n"
            . "Hätten Sie nächste Woche Zeit für ein kurzes, unverbindliches Gespräch zur Abstimmung möglicher Synergien?\n\n"
            . "Mit freundlichen Grüßen,\n"
            . "{$senderName}\n"
            . "Outreach.click\n\n"
            . "---\n"
            . "Falls Sie keine weiteren Informationen wünschen, antworten Sie bitte kurz mit 'Abmelden'.";

        $bodyHtml = "<div style=\"font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #2d3748;\">"
            . "<p>{$greeting}</p>"
            . "<p>ich wende mich an Sie, da wir Unternehmen wie <strong>{$company}</strong> dabei unterstützen, passende B2B-Geschäftskontakte und Neukunden systematisch zu identifizieren.</p>"
            . "<p>Wir recherchieren geeignete Zielunternehmen, qualifizieren Entscheidungsträger und unterstützen Sie bei der direkten Kontaktaufnahme durch gezielte, relevante Ansprachen.</p>"
            . "<p>Unser Ziel ist es, Ihr Unternehmenswachstum nachhaltig zu fördern und Ihnen neue Geschäftschancen zu eröffnen.</p>"
            . "<p>Hätten Sie nächste Woche Zeit für ein kurzes, unverbindliches Gespräch zur Abstimmung möglicher Synergien?</p>"
            . "<p>Mit freundlichen Grüßen,<br><strong>{$senderName}</strong><br><span style=\"color: #718096; font-size: 12px;\">Outreach.click</span></p>"
            . "<hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 12px;\">"
            . "<p style=\"font-size: 11px; color: #a0aec0;\">Falls Sie keine weiteren Nachrichten wünschen, antworten Sie bitte einfach mit 'Abmelden'.</p>"
            . "</div>";

        return [
            'subject'   => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'language'  => 'de',
        ];
    }

    /**
     * English (Global / UK / US / International)
     */
    protected static function generateEnglishEmail(string $company, string $contactPerson, bool $isGeneric, string $senderName): array
    {
        $greeting = $isGeneric ? "Hi {$company} Team," : "Hi {$contactPerson},";
        $subject = "Client acquisition & growth for {$company}";

        $bodyText = "{$greeting}\n\n"
            . "I'm reaching out because we help companies like {$company} identify and connect with qualified prospective clients.\n\n"
            . "We research suitable businesses, identify relevant decision-makers, and assist you in initiating targeted outreach to explore new opportunities.\n\n"
            . "Our goal is to support your business growth by consistently connecting you with high-fit potential partners.\n\n"
            . "Would you have a few minutes next week for a brief chat to see if this aligns with your current priorities?\n\n"
            . "Best regards,\n"
            . "{$senderName}\n"
            . "Outreach.click\n\n"
            . "---\n"
            . "If you prefer not to receive future emails, simply reply 'unsubscribe'.";

        $bodyHtml = "<div style=\"font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #2d3748;\">"
            . "<p>{$greeting}</p>"
            . "<p>I'm reaching out because we help companies like <strong>{$company}</strong> identify and connect with qualified prospective clients.</p>"
            . "<p>We research suitable businesses, identify relevant decision-makers, and assist you in initiating targeted outreach to explore new opportunities.</p>"
            . "<p>Our goal is to support your business growth by consistently connecting you with high-fit potential partners.</p>"
            . "<p>Would you have a few minutes next week for a brief chat to see if this aligns with your current priorities?</p>"
            . "<p>Best regards,<br><strong>{$senderName}</strong><br><span style=\"color: #718096; font-size: 12px;\">Outreach.click</span></p>"
            . "<hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 24px 0 12px;\">"
            . "<p style=\"font-size: 11px; color: #a0aec0;\">If you prefer not to receive future emails, simply reply 'unsubscribe'.</p>"
            . "</div>";

        return [
            'subject'   => $subject,
            'body_text' => $bodyText,
            'body_html' => $bodyHtml,
            'language'  => 'en',
        ];
    }
}
