# Feature Roadmap & Feasibility

Status of the six requested feature areas against the **current** codebase
(commit `3291c3a`), and what each one requires on **Hostinger shared hosting**.

**None of the six features exist today.** This document is a scope plan, not a
description of shipped functionality. Effort is focused engineering time.

---

## 0. Hosting constraints that shape every decision

Hostinger shared hosting gives us PHP 8.3 + MySQL + cron + outbound HTTPS.
It does **not** give us long-running processes, so three things are off the table:

| Wanted | Blocked because | Workaround |
|---|---|---|
| Laravel Reverb / `php artisan reverb:start` | Never-ending process | Polling endpoint, or Pusher/Ably (hosted WebSockets) |
| Supervisor-managed queue workers | No supervisor | Existing cron: `queue:work --stop-when-empty --max-time=280` every 5 min |
| Node.js at request time (MJML CLI) | No Node guarantee | Compile MJML in the browser (`mjml-browser`) |
| Server-Sent Events for long streams | Proxy buffering / max execution time | Non-streaming JSON responses |

Outbound API calls (OpenAI, Anthropic, Gemini) are fine.
Anything that needs a persistent connection is not.

---

## 1. 👥 Live Visitors

**Reality check.** There is no visitor tracking. What exists is *email* tracking:
`message_events` (sent/opened/clicked/bounced) with `occurred_at` indexed by
`workspace_id`, written by the tracking pixel and click-redirect routes.

**Two different products hide behind this name.** Decide which you want:

- **(a) Email engagement feed** — "who opened/clicked in the last 5 minutes".
  Reuses `message_events` + `leads`. Roughly 1 day. No new tracking tech.
- **(b) Website visitor tracking** — a JS snippet on your own site, sessions,
  pageviews, live counter. This is a real analytics product. ~3 days.

**Plan for (b), the fuller version:**

| Layer | Add |
|---|---|
| Data | `visitor_sessions` (workspace_id, visitor_uuid, lead_id nullable, ip_hash, user_agent, referrer, landing_page, first_seen_at, last_seen_at, pageviews) and `visitor_pageviews` (session_id, url, title, occurred_at) |
| Endpoint | `POST /t/v/{workspaceKey}` public beacon; rate-limited; no cookies → `localStorage` UUID; store `ip_hash = hash_hmac(...)`, never raw IP |
| Realtime | `GET /api/visitors/live` returns count + last 20 events. Vue page polls every 5 s. Requires the API layer from §6 |
| UI | `Visitors/Index.vue` — live counter, country/referrer breakdown, session list |
| Privacy | Retention window in `CleanupJob`; no raw IP storage; workspace toggle to disable |

**Hostinger:** works. Polling at 5 s per open dashboard is cheap
(one indexed query). True WebSockets would need Pusher's free tier.
**Effort:** 3 days. **Risk:** low. **Blocked by:** nothing.

---

## 2. 📧 Email Marketing — MJML editor

**Reality check.** Campaigns and `campaign_steps` exist and store raw
`body_html` / `body_text` with `{{token}}` personalization. There is **no
editor at all** — bodies arrive from the create/edit forms as HTML.

**Decision needed:** MJML or a normal block editor?

- MJML is a compile target, not an editor. You still need a UI that produces
  MJML; then it compiles to responsive HTML.
- If the goal is "users can build pretty emails", a block editor (GrapesJS or
  Tiptap) produces HTML directly and is less work.

**Plan if MJML is required:**

| Layer | Add |
|---|---|
| Compile | `mjml-browser` (npm) in the Vue editor → compiled HTML POSTed to the server. No Node on the server, no PHP MJML port (there isn't a reliable one) |
| Storage | `campaign_steps.body_mjml` (nullable longText) alongside existing `body_html`; recompile on save so sent mail never depends on the client |
| Editor page | `Campaigns/Steps/Edit.vue`: MJML source pane + live `<iframe>` preview, sandboxed, `srcdoc` |
| Safety | Sanitize compiled HTML with DOMPurify before preview; strip `<script>`; keep outbound links http/https-only |
| Validation | `body_html` max size, compile errors surfaced per step |

**Hostinger:** works, at the cost of a larger JS bundle (~1 MB for `mjml-browser`,
lazy-loaded only on the editor page).
**Effort:** 4 days. **Risk:** medium (bundle size, HTML sanitization, email
client quirks). **Blocked by:** nothing, but better after §1/§6 UI patterns exist.

---

## 3. Automations — scenarios and workflows

**Reality check.** The current "campaign" is already a **linear drip**: ordered
steps with `delay_days` / `delay_hours` / `delay_minutes` and
`campaign_recipients.next_send_at`, advanced by `ProcessCampaignsJob` every
minute. There is no branching, no triggers, no "if/else", no re-entry.

**Plan:**

| Layer | Add |
|---|---|
| Data | `automations` (workspace_id, name, status, trigger_type, trigger_config, reentry_policy); `automation_nodes` (automation_id, type, config, next_node_id, alt_node_id, position); `automation_runs` (automation_id, lead_id, current_node_id, status, next_run_at, started_at); `automation_run_logs` |
| Node types | `trigger`, `delay`, `send_email`, `condition`, `add_tag`, `remove_tag`, `webhook`, `end` |
| Triggers | lead.created, tag.added, campaign.replied, form.submitted, deal.stage_changed |
| Engine | `App\Services\AutomationEngine` + `RunAutomationsJob`, scheduled every minute (same pattern as campaigns). Idempotent per run; `withoutOverlapping()`; hard cap on nodes per run to avoid loops |
| UI | Start with an ordered list + condition branches; a drag-and-drop canvas is a separate 1–2 week project on its own |
| Safety | per-workspace run cap, recursion guard, dry-run mode, run log visible in UI |

**Hostinger:** works (queue-driven, no daemon).
**Effort:** 8–10 days. **Risk:** high — this is a rules engine; needs careful
tests for loops, re-entry and duplicate sends. **Blocked by:** tags (§5).

---

## 4. 🤖 AI Suite

**Reality check.** Nothing exists. No `config/services.php`, no HTTP client
usage, no AI keys. Laravel's `Http` facade and queue are available.

**Plan:**

| Layer | Add |
|---|---|
| Config | `config/ai.php` — provider, model, timeout, max tokens, per-workspace monthly cost cap. Keys only in `.env` (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, `GEMINI_API_KEY`) |
| Abstraction | `App\Services\Ai\AiManager` + drivers `OpenAiDriver`, `AnthropicDriver`, `GeminiDriver` implementing `complete(messages, options): AiResult`; one shared HTTP timeout + retry policy |
| Audit trail | `ai_requests` (workspace_id, user_id, provider, model, feature, tokens_in, tokens_out, cost_usd, status, latency_ms). Prompt/response bodies stored only if the workspace opts in |
| Campaign Auditor | queued `AuditCampaignJob`: subject length/spam words, link count, text-image ratio, missing unsubscribe, personalization broken → returns score + findings |
| Advisor | contextual chat over workspace stats (aggregates only, not raw lead rows) |
| Content generation | subject lines, step bodies, personalization from lead fields — always requires human accept before saving |
| Safety | redact emails/phones before sending; no PII to providers without explicit workspace opt-in; per-user rate limit; graceful failure when a provider is down |

**Hostinger:** works (outbound HTTPS only).
**Effort:** 6–7 days. **Risk:** medium (cost control, provider outages, prompt
injection from lead-supplied content). **Blocked by:** nothing.

---

## 5. 👥 CRM — subscribers, groups, tags

**Reality check.**

- Subscribers ≈ `leads` (already has `status`, plus a `suppression_list` for
  opt-outs and unsubscribe tracking). Good enough, no new table.
- Groups ≈ `lead_lists` + `lead_list_memberships` (static and dynamic via
  `filters` JSON). Already exists.
- **Tags: only a JSON `leads.tags` column.** No tag table, no colors, no counts,
  no rename/merge, no bulk tag from the UI, no way to filter by tag across
  companies/tasks.

**Plan:** normalize tags.

| Layer | Add |
|---|---|
| Data | `tags` (workspace_id, name, slug, color, unique per workspace), `taggables` (tag_id, taggable_type, taggable_id) |
| Migration | backfill from `leads.tags` JSON, keep the column read-only for one release then drop |
| API/UI | tag CRUD, bulk tag/untag on the leads table, tag filter on lists, tag chips on lead/company rows |
| Permissions | `manage tags`, reuse owner override |

**Hostinger:** trivial. **Effort:** 2–3 days. **Risk:** low.
**Why first:** automations and segments both depend on it.

---

## 6. 🔌 MCP Server

**Reality check.** No API surface and no MCP anything. `routes/api.php` is an
empty placeholder; Sanctum is installed but unused; there are no API tokens.

**Prerequisite:** a real `/api/v1` with token auth and scopes. Without it, an
MCP server has nothing to call.

**Two delivery options:**

| Option | How it runs | Hostinger | Verdict |
|---|---|---|---|
| **(a) Remote MCP** | `POST /mcp` JSON-RPC 2.0 over HTTPS, Sanctum token auth, non-streaming JSON responses (MCP "streamable HTTP") | Works, but SSE streaming is unreliable behind shared-hosting proxies | Ship second |
| **(b) Local stdio MCP** | Small Node package in `mcp-server/`, run by Claude Desktop / Cursor / VS Code on the user's machine, talks to `/api/v1` | Nothing runs on the host; safest | **Ship first** |

**Plan:**

1. API layer: `api/v1` routes, Sanctum personal access tokens, per-token scopes
   (`leads:read`, `leads:write`, `campaigns:read`, …), rate limiting, JSON
   resources. ~4 days.
2. stdio MCP server: `mcp-server/` with stdio transport, tools
   `list_leads`, `get_lead`, `create_lead`, `list_campaigns`, `campaign_stats`,
   `list_pipelines`, `create_task`; config example for Claude/Cursor/VS Code. ~2 days.
3. Optional remote MCP endpoint. ~1 day, but treat streaming as unsupported.

**Effort:** 6–7 days total. **Risk:** medium (token scope mistakes leak data;
must be workspace-scoped and audited). **Blocked by:** API layer.

---

## Recommended order

| # | Feature | Effort | Why here |
|---|---|---|---|
| 1 | Tags (§5) | 2–3 d | Foundation for automations and segments; low risk, immediate user value |
| 2 | Live Visitors (§1) | 3 d | Independent, visible win, reuses existing tracking data |
| 3 | AI Suite (§4) | 6–7 d | Independent, high perceived value, no schema risk |
| 4 | MJML editor (§2) | 4 d | Improves the core product once email flows are proven |
| 5 | Automations (§3) | 8–10 d | Highest risk; needs tags and a stable queue |
| 6 | MCP Server (§6) | 6–7 d | Needs the API layer and stable endpoints first |

**Total: roughly 5–6 weeks** of focused work, plus review and deployment time.

---

## Cross-cutting work these features force

- **API layer** — needed by Live Visitors (polling), MCP, and any AI tooling.
  Currently `routes/api.php` is empty.
- **`config/services.php` does not exist** — create it for AI and any future
  third-party integration.
- **No `broadcasting.php`, no `channels.php`** — only relevant if you choose
  Pusher/Ably instead of polling.
- **No HTML sanitizer** — required before either the MJML preview or AI-generated
  content can be rendered.
- **Queue throughput** — one bounded worker every 5 minutes is enough for
  campaigns today; automations + AI + visitor rollups will need either a longer
  window or a second cron entry.
- **Observability** — `queue:failed`, `storage/logs/laravel.log` and a dead
  scheduler went unnoticed until now; add a `/up`-style heartbeat plus a failed
  job notice before adding six features.

---

## Fixed while preparing this plan

`app/Console/Kernel.php` was dead code — Laravel 12 does not load it, so
`php artisan schedule:list` reported **no scheduled tasks**. Campaign sending,
task reminders and cleanup would never have run in production. The schedule now
lives in `routes/console.php` and `schedule:list` shows all three jobs.

```
*   *   *   *   *  App\Jobs\ProcessCampaignsJob
*/5 *   *   *   *  App\Jobs\SendReminderEmailsJob
0   2   *   *   *  App\Jobs\CleanupJob
```

## Next step

Pick one item above and say "build it". I would start with **Tags (§5)** or
**Live Visitors (§1)**: both are self-contained, testable end-to-end, and carry
the least risk to the production site you are about to deploy.
