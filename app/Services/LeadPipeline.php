<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * LeadPipeline
 *
 * Centralised fire-and-forget CRM push for all lead form types.
 * Failures are caught and logged — they never prevent save or email.
 *
 * Usage:
 *   LeadPipeline::pushToFollowUpBoss($agent, $data);
 *   LeadPipeline::pushToGoHighLevel($agent, $data);
 *   LeadPipeline::pushToLofty($agent, $data);
 *
 * $data keys (all optional unless noted):
 *   name, first_name, last_name, email, phone, form_type,
 *   message, property_address, timeline, budget, preferred_date,
 *   source_url, listing_slug
 */
class LeadPipeline
{
    /**
     * Push lead to Follow Up Boss Events API.
     * Fires only when agent_settings.fub_enabled = true AND fub_api_key is set.
     */
    public static function pushToFollowUpBoss(
        \App\Models\Agent $agent,
        array $data
    ): void {
        $settings = $agent->settings;
        if (!$settings) return;

        $enabled = false;
        $apiKey  = null;
        try {
            $enabled = (bool) $settings->fub_enabled;
            $apiKey  = $settings->fub_api_key;
        } catch (\Throwable $e) {
            Log::warning('LeadPipeline: could not read FUB settings for agent ' . $agent->slug . ': ' . $e->getMessage());
        }

        if (!$enabled || !$apiKey) return;

        $fubTypeMap = [
            'w1'               => 'Showing Request',
            'w2'               => 'Home Evaluation',
            'w3'               => 'Mortgage Pre-Qualification',
            'w4'               => 'Quick Contact',
            'contact'          => 'General Inquiry',
            'ask'              => 'General Inquiry',
            'registration'     => 'New Registration',
            'market_subscribe' => 'General Inquiry',
        ];

        $name      = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
        if (!$name) $name = $data['name'] ?? '';
        $parts     = explode(' ', trim($name), 2);
        $firstName = $parts[0] ?? '';
        $lastName  = $parts[1] ?? '';

        $email     = $data['email']      ?? null;
        $phone     = $data['phone']      ?? null;
        $formType  = $data['form_type']  ?? 'contact';
        $sourceUrl = $data['source_url'] ?? null;

        $person = [
            'contacted' => false,
            'firstName' => $firstName,
            'lastName'  => $lastName,
            'stage'     => 'Lead',
            'source'    => $agent->name . ' Website',
        ];
        if ($email)     $person['emails']    = [['value' => $email]];
        if ($phone)     $person['phones']    = [['value' => $phone]];
        if ($sourceUrl) $person['sourceUrl'] = $sourceUrl;

        $noteLines = [];
        if (!empty($data['message']))          $noteLines[] = $data['message'];
        if (!empty($data['property_address'])) $noteLines[] = 'Property: ' . $data['property_address'];
        if (!empty($data['timeline']))         $noteLines[] = 'Timeline: ' . $data['timeline'];
        if (!empty($data['budget']))           $noteLines[] = 'Budget: ' . $data['budget'];
        if (!empty($data['preferred_date']))   $noteLines[] = 'Preferred date: ' . $data['preferred_date'];
        if (!empty($data['listing_slug']))     $noteLines[] = 'Listing: /listing/' . $data['listing_slug'];
        if ($sourceUrl)                        $noteLines[] = 'Source: ' . $sourceUrl;

        $payload = [
            'source' => $agent->name . ' Website',
            'system' => 'website_api',
            'type'   => $fubTypeMap[$formType] ?? 'General Inquiry',
            'person' => $person,
        ];
        if (!empty($noteLines)) {
            $payload['note'] = implode("\n", $noteLines);
        }
        if (!empty($data['property_address'])) {
            $payload['property'] = ['street' => $data['property_address']];
        }

        try {
            $response = Http::withBasicAuth($apiKey, '')
                ->timeout(8)
                ->post('https://api.followupboss.com/v1/events', $payload);

            if (!$response->successful()) {
                Log::warning('LeadPipeline FUB push failed [' . $response->status() . '] agent=' . $agent->slug . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('LeadPipeline FUB push error agent=' . $agent->slug . ': ' . $e->getMessage());
        }
    }

    /**
     * Push lead to GoHighLevel Contacts API.
     * Fires only when agent_settings.ghl_enabled = true AND ghl_api_key is set.
     * Guards for column existence — safe to deploy before GHL columns are added.
     */
    public static function pushToGoHighLevel(
        \App\Models\Agent $agent,
        array $data
    ): void {
        try {
            if (!Schema::hasColumn('agent_settings', 'ghl_enabled')
                || !Schema::hasColumn('agent_settings', 'ghl_api_key')
                || !Schema::hasColumn('agent_settings', 'ghl_location_id')) {
                return;
            }

            $selectCols = ['ghl_enabled', 'ghl_api_key', 'ghl_location_id'];
            if (Schema::hasColumn('agent_settings', 'ghl_source_label')) {
                $selectCols[] = 'ghl_source_label';
            }

            $row = \Illuminate\Support\Facades\DB::table('agent_settings')
                ->where('agent_id', $agent->id)
                ->select($selectCols)
                ->first();

            if (!$row || !$row->ghl_enabled || !$row->ghl_api_key || !$row->ghl_location_id) return;

            $sourceLabel = (!empty($row->ghl_source_label))
                ? $row->ghl_source_label
                : $agent->name . ' Website';

            $name      = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            if (!$name) $name = $data['name'] ?? '';
            $parts     = explode(' ', trim($name), 2);
            $firstName = $parts[0] ?? '';
            $lastName  = $parts[1] ?? '';
            $formType  = $data['form_type'] ?? 'contact';

            $sourceTypeTag  = !empty($data['source_type']) ? $data['source_type'] . '-gate' : null;
            $contactPayload = [
                'firstName'  => $firstName ?: null,
                'lastName'   => $lastName  ?: null,
                'email'      => $data['email'] ?? null,
                'phone'      => $data['phone'] ?? null,
                'locationId' => $row->ghl_location_id,
                'source'     => $sourceLabel,
                'tags'       => array_values(array_filter([$formType, $sourceTypeTag, 'website-lead'])),
            ];
            $ghlNoteLines = [];
            if (!empty($data['message']))       $ghlNoteLines[] = $data['message'];
            if (!empty($data['listing_id']))    $ghlNoteLines[] = 'Listing ID: ' . $data['listing_id'];
            if (!empty($data['building_slug'])) $ghlNoteLines[] = 'Building: ' . $data['building_slug'];
            if (!empty($data['subarea']))       $ghlNoteLines[] = 'Area: ' . $data['subarea'];
            if (!empty($data['property_type'])) $ghlNoteLines[] = 'Type: ' . $data['property_type'];
            if (!empty($data['min_beds']))      $ghlNoteLines[] = 'Min Beds: ' . $data['min_beds'];
            if (!empty($data['min_price']))     $ghlNoteLines[] = 'Min Price: $' . number_format((int) $data['min_price']);
            if (!empty($data['max_price']))     $ghlNoteLines[] = 'Max Price: $' . number_format((int) $data['max_price']);
            if (!empty($data['source_url']))    $ghlNoteLines[] = 'Source: ' . $data['source_url'];
            if (!empty($ghlNoteLines)) {
                $contactPayload['customField'] = [
                    ['key' => 'message', 'field_value' => substr(implode("\n", $ghlNoteLines), 0, 1000)],
                ];
            }
            $contactPayload = array_filter($contactPayload, fn($v) => $v !== null && $v !== '');

            $response = Http::withToken($row->ghl_api_key)
                ->withHeaders(['Version' => '2021-07-28'])
                ->timeout(8)
                ->post('https://services.leadconnectorhq.com/contacts/', $contactPayload);

            if (!$response->successful()) {
                Log::warning('LeadPipeline GHL push failed [' . $response->status() . '] agent=' . $agent->slug . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('LeadPipeline GHL push error agent=' . $agent->slug . ': ' . $e->getMessage());
        }
    }

    /**
     * Push lead to Lofty CRM.
     * Fires only when agent_settings.lofty_enabled = true AND lofty_api_key is set.
     * Guards for column existence — safe to deploy before Lofty columns are added.
     *
     * Docs: https://developer.lofty.com/guides/lead-management
     *
     * Flow:
     *   1. POST /v1.0/leads              — create lead (name, emails, phones, source)
     *   2. POST /v1.0/leads/{id}/inquiry — attach property inquiry (location, price, type)
     *   3. POST /v1.0/notes              — attach free-text note (message, form type, source URL)
     */
    /**
     * Push a lead to the agent's CRM once its phone has been OTP-verified.
     *
     * A lead is already pushed at account creation, BEFORE verification, so this is an
     * update, not a new contact. Both existing pushes are plain creates with no dedupe, so
     * re-using them here would hand the agent two records for one person.
     *
     * GHL: uses /contacts/upsert (not /contacts/) so the existing contact is matched on
     * email/phone within the location, and adds a `phone-verified` tag the agent can filter
     * and automate on.
     *
     * Lofty: has no documented upsert on /leads. Rather than risk duplicating a contact in
     * a live CRM on an assumption, this deliberately does NOT re-push - the verified-lead
     * email still reaches the agent via AgentLeadVerifiedJob. Wire Lofty in here only after
     * confirming its dedupe behaviour against a throwaway contact.
     */
    public static function pushVerifiedLead(
        \App\Models\Agent $agent,
        array $data
    ): void {
        try {
            if (!Schema::hasColumn('agent_settings', 'ghl_enabled')
                || !Schema::hasColumn('agent_settings', 'ghl_api_key')
                || !Schema::hasColumn('agent_settings', 'ghl_location_id')) {
                return;
            }

            $row = \Illuminate\Support\Facades\DB::table('agent_settings')
                ->where('agent_id', $agent->id)
                ->select(['ghl_enabled', 'ghl_api_key', 'ghl_location_id'])
                ->first();

            if (!$row || !$row->ghl_enabled || !$row->ghl_api_key || !$row->ghl_location_id) return;

            $name  = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
            if (!$name) $name = $data['name'] ?? '';
            $parts = explode(' ', trim($name), 2);

            $payload = [
                'firstName'  => ($parts[0] ?? '') ?: null,
                'lastName'   => ($parts[1] ?? '') ?: null,
                'email'      => $data['email'] ?? null,
                'phone'      => $data['phone'] ?? null,
                'locationId' => $row->ghl_location_id,
                'tags'       => array_values(array_filter([
                    'phone-verified',
                    'website-lead',
                    $data['form_type'] ?? null,
                ])),
            ];

            $response = Http::withToken($row->ghl_api_key)
                ->withHeaders(['Version' => '2021-07-28'])
                ->timeout(8)
                ->post('https://services.leadconnectorhq.com/contacts/upsert', $payload);

            if (!$response->successful()) {
                Log::warning('LeadPipeline GHL verified-upsert failed [' . $response->status() . '] agent=' . $agent->slug . ': ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error('LeadPipeline GHL verified-upsert error agent=' . $agent->slug . ': ' . $e->getMessage());
        }
    }

    public static function pushToLofty(
        \App\Models\Agent $agent,
        array $data
    ): void {
        try {
            if (!Schema::hasColumn('agent_settings', 'lofty_enabled')
                || !Schema::hasColumn('agent_settings', 'lofty_api_key')) {
                return;
            }

            $selectCols = ['lofty_enabled', 'lofty_api_key'];
            if (Schema::hasColumn('agent_settings', 'lofty_source_label')) {
                $selectCols[] = 'lofty_source_label';
            }

            $row = \Illuminate\Support\Facades\DB::table('agent_settings')
                ->where('agent_id', $agent->id)
                ->select($selectCols)
                ->first();

            if (!$row || !$row->lofty_enabled) return;

            $apiKey = $row->lofty_api_key;
            if (!$apiKey) return;

            try {
                $apiKey = decrypt($apiKey);
            } catch (\Throwable $ignored) {}
            if (!$apiKey) return;

            $sourceLabel = (!empty($row->lofty_source_label))
                ? $row->lofty_source_label
                : $agent->name . ' Website';

            // Split name
            $firstName = trim($data['first_name'] ?? '');
            $lastName  = trim($data['last_name']  ?? '');
            if (!$firstName && !$lastName) {
                $parts     = explode(' ', trim($data['name'] ?? ''), 2);
                $firstName = $parts[0] ?? '';
                $lastName  = $parts[1] ?? '';
            }

            // Lofty v1.0 uses arrays for emails and phones
            $emailsVal = (!empty($data['email'])) ? [$data['email']] : null;
            $phonesVal = (!empty($data['phone'])) ? [$data['phone']] : null;

            $leadPayload = array_filter([
                'firstName' => $firstName ?: null,
                'lastName'  => $lastName  ?: null,
                'emails'    => $emailsVal,
                'phones'    => $phonesVal,
                'source'    => $sourceLabel,
            ], fn($v) => $v !== null && $v !== '');

            $http = Http::withHeaders([
                'Authorization' => 'token ' . $apiKey,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->timeout(8);

            // Step 1: create the lead
            $leadResponse = $http->post('https://api.lofty.com/v1.0/leads', $leadPayload);

            if (!$leadResponse->successful()) {
                Log::warning('LeadPipeline Lofty lead failed [' . $leadResponse->status() . '] agent=' . $agent->slug . ': ' . $leadResponse->body());
                return;
            }

            $leadId = $leadResponse->json('leadId') ?? $leadResponse->json('id') ?? null;

            if ($leadId) {
                // Step 2: attach property inquiry
                // Extract city from property_address (e.g. "123 Main St, Surrey BC")
                $city = null;
                if (!empty($data['property_address'])) {
                    $addrParts = array_map('trim', explode(',', $data['property_address']));
                    if (count($addrParts) >= 2) {
                        $city = trim(preg_replace('/\s+[A-Z]{2}(\s+[A-Z]\d[A-Z]\s*\d[A-Z]\d)?$/i', '', $addrParts[1]));
                    }
                }
                // Fall back to agent's first territory city
                if (!$city) {
                    $territory = \Illuminate\Support\Facades\DB::table('agent_territories')
                        ->where('agent_id', $agent->id)
                        ->value('city');
                    $city = $territory ?: null;
                }

                $inquiryPayload = [];
                if ($city) {
                    $inquiryPayload['locations'] = [['city' => $city, 'stateCode' => 'BC']];
                }
                if (!empty($data['min_beds'])) {
                    $inquiryPayload['minBeds'] = (int) $data['min_beds'];
                }
                if (!empty($data['min_price'])) {
                    $inquiryPayload['minPrice'] = (int) $data['min_price'];
                }
                if (!empty($data['max_price'])) {
                    $inquiryPayload['maxPrice'] = (int) $data['max_price'];
                }

                if (!empty($inquiryPayload)) {
                    $inqResponse = $http->post('https://api.lofty.com/v1.0/leads/' . $leadId . '/inquiry', $inquiryPayload);
                    if (!$inqResponse->successful()) {
                        Log::warning('LeadPipeline Lofty inquiry failed [' . $inqResponse->status() . '] agent=' . $agent->slug . ': ' . $inqResponse->body());
                    }
                }

                // Step 3: attach note with form context
                $noteLines = [];
                if (!empty($data['message']))          $noteLines[] = $data['message'];
                if (!empty($data['form_type']))         $noteLines[] = 'Form: ' . $data['form_type'];
                if (!empty($data['property_address'])) $noteLines[] = 'Property: ' . $data['property_address'];
                if (!empty($data['listing_id']))        $noteLines[] = 'Listing ID: ' . $data['listing_id'];
                if (!empty($data['building_slug']))     $noteLines[] = 'Building: ' . $data['building_slug'];
                if (!empty($data['subarea']))           $noteLines[] = 'Area: ' . $data['subarea'];
                if (!empty($data['property_type']))     $noteLines[] = 'Type: ' . $data['property_type'];
                if (!empty($data['min_beds']))          $noteLines[] = 'Min Beds: ' . $data['min_beds'];
                if (!empty($data['min_price']))         $noteLines[] = 'Min Price: $' . number_format((int) $data['min_price']);
                if (!empty($data['max_price']))         $noteLines[] = 'Max Price: $' . number_format((int) $data['max_price']);
                if (!empty($data['timeline']))          $noteLines[] = 'Timeline: ' . $data['timeline'];
                if (!empty($data['budget']))            $noteLines[] = 'Budget: ' . $data['budget'];
                if (!empty($data['listing_slug']))      $noteLines[] = 'Listing: /listing/' . $data['listing_slug'];
                if (!empty($data['source_url']))        $noteLines[] = 'Source: ' . $data['source_url'];

                if ($noteLines) {
                    $noteResponse = $http->post('https://api.lofty.com/v1.0/notes', [
                        'leadId'  => $leadId,
                        'content' => substr(implode("\n", $noteLines), 0, 2000),
                        'isPin'   => false,
                    ]);
                    if (!$noteResponse->successful()) {
                        Log::warning('LeadPipeline Lofty note failed [' . $noteResponse->status() . '] agent=' . $agent->slug . ': ' . $noteResponse->body());
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('LeadPipeline Lofty push error agent=' . $agent->slug . ': ' . $e->getMessage());
        }
    }

    /**
     * Push a "Property Viewed" activity note to Lofty and/or GHL.
     *
     * Called from AgentDataController::recordPropertyView() when last_crm_push_at
     * is NULL or > 24 h ago — so at most once per day per (user, property).
     *
     * $data keys: email, name, first_name, last_name,
     *             listing_id (nullable), building_slug (nullable),
     *             address_label, view_count.
     */
    public static function pushPropertyViewEvent(
        \App\Models\Agent $agent,
        array $data
    ): void {
        if (empty($data['email'])) return; // no email → can't upsert CRM contact

        $viewCount   = max(1, (int) ($data['view_count'] ?? 1));
        $times       = $viewCount > 1 ? " ({$viewCount}× total)" : '';
        $address     = $data['address_label'] ?? 'a property';

        // Canonical URL — use website.pixilink.com (agent slug) as the public base
        $agentPfx   = 'https://website.pixilink.com/agent/' . $agent->slug;
        if (!empty($data['listing_id'])) {
            $propertyUrl = $agentPfx . '/listing/' . $data['listing_id'];
        } elseif (!empty($data['building_slug'])) {
            $propertyUrl = $agentPfx . '/building/' . $data['building_slug'];
        } else {
            $propertyUrl = null;
        }

        $noteContent  = '🏠 Property Viewed' . $times . ': ' . $address;
        if (!empty($data['listing_id']))    $noteContent .= "\nListing ID: " . $data['listing_id'];
        if (!empty($data['building_slug'])) $noteContent .= "\nBuilding: "   . $data['building_slug'];
        if ($propertyUrl)                   $noteContent .= "\nURL: "         . $propertyUrl;

        // ── Lofty ───────────────────────────────────────────────────────────────
        try {
            if (Schema::hasColumn('agent_settings', 'lofty_enabled') &&
                Schema::hasColumn('agent_settings', 'lofty_api_key')) {

                $row = \Illuminate\Support\Facades\DB::table('agent_settings')
                    ->where('agent_id', $agent->id)
                    ->select(['lofty_enabled', 'lofty_api_key'])
                    ->first();

                if ($row && $row->lofty_enabled && $row->lofty_api_key) {
                    $apiKey = $row->lofty_api_key;
                    try { $apiKey = decrypt($apiKey); } catch (\Throwable $ignored) {}

                    if ($apiKey) {
                        $http = Http::withHeaders([
                            'Authorization' => 'token ' . $apiKey,
                            'Content-Type'  => 'application/json',
                            'Accept'        => 'application/json',
                        ])->timeout(8);

                        // Upsert the contact (Lofty de-dupes by email)
                        $firstName = trim($data['first_name'] ?? '');
                        $lastName  = trim($data['last_name']  ?? '');
                        if (!$firstName && !$lastName) {
                            $parts     = explode(' ', trim($data['name'] ?? ''), 2);
                            $firstName = $parts[0] ?? '';
                            $lastName  = $parts[1] ?? '';
                        }
                        $leadPayload = array_filter([
                            'firstName' => $firstName ?: null,
                            'lastName'  => $lastName  ?: null,
                            'emails'    => [$data['email']],
                            'source'    => $agent->name . ' Website',
                        ], fn ($v) => $v !== null && $v !== '');

                        $leadResp = $http->post('https://api.lofty.com/v1.0/leads', $leadPayload);
                        $leadId   = $leadResp->json('leadId') ?? $leadResp->json('id') ?? null;

                        if ($leadId) {
                            $noteResp = $http->post('https://api.lofty.com/v1.0/notes', [
                                'leadId'  => $leadId,
                                'content' => substr($noteContent, 0, 2000),
                                'isPin'   => false,
                            ]);
                            if (!$noteResp->successful()) {
                                Log::warning('LeadPipeline pushPropertyViewEvent Lofty note [' . $noteResp->status() . '] agent=' . $agent->slug);
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('LeadPipeline pushPropertyViewEvent Lofty error agent=' . $agent->slug . ': ' . $e->getMessage());
        }

        // ── GoHighLevel ─────────────────────────────────────────────────────────
        try {
            if (Schema::hasColumn('agent_settings', 'ghl_enabled') &&
                Schema::hasColumn('agent_settings', 'ghl_api_key') &&
                Schema::hasColumn('agent_settings', 'ghl_location_id')) {

                $row = \Illuminate\Support\Facades\DB::table('agent_settings')
                    ->where('agent_id', $agent->id)
                    ->select(['ghl_enabled', 'ghl_api_key', 'ghl_location_id'])
                    ->first();

                if ($row && $row->ghl_enabled && $row->ghl_api_key && $row->ghl_location_id) {
                    $ghlHttp = Http::withToken($row->ghl_api_key)
                        ->withHeaders(['Version' => '2021-07-28'])
                        ->timeout(8);

                    // Upsert the contact to get/create a contactId
                    $firstName = trim($data['first_name'] ?? '');
                    $lastName  = trim($data['last_name']  ?? '');
                    if (!$firstName && !$lastName) {
                        $parts     = explode(' ', trim($data['name'] ?? ''), 2);
                        $firstName = $parts[0] ?? '';
                        $lastName  = $parts[1] ?? '';
                    }
                    $contactPayload = array_filter([
                        'firstName'  => $firstName ?: null,
                        'lastName'   => $lastName  ?: null,
                        'email'      => $data['email'],
                        'locationId' => $row->ghl_location_id,
                    ], fn ($v) => $v !== null && $v !== '');

                    $contactResp = $ghlHttp->post(
                        'https://services.leadconnectorhq.com/contacts/',
                        $contactPayload
                    );
                    $contactId = $contactResp->json('contact.id')
                        ?? $contactResp->json('id')
                        ?? null;

                    if ($contactId) {
                        $noteResp = $ghlHttp->post(
                            'https://services.leadconnectorhq.com/contacts/' . $contactId . '/notes',
                            ['body' => substr($noteContent, 0, 2000)]
                        );
                        if (!$noteResp->successful()) {
                            Log::warning('LeadPipeline pushPropertyViewEvent GHL note [' . $noteResp->status() . '] agent=' . $agent->slug);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('LeadPipeline pushPropertyViewEvent GHL error agent=' . $agent->slug . ': ' . $e->getMessage());
        }
    }
}
