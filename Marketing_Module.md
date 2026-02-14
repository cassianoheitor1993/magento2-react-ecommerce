# Petshop Marketing Module — Implementation Plan (Self-Implementation)

## 1) Goal

Build a new Magento 2 module `Petshop_Marketing` to run native email marketing with async processing, progress recovery after page refresh, and campaign analytics.

---

## 2) Scope (MVP)

### In-scope
- Campaign CRUD in Admin
- Audience selection (newsletter subscribers + customer groups)
- Template selection/edit
- Async sending via queue consumer (no long HTTP request)
- Job progress tracking (queued/processing/completed/failed)
- Pause/Resume/Cancel campaign
- Basic metrics (sent, delivered approximation, opens/clicks if tracked by pixel/link redirect)

### Out-of-scope (phase 2+)
- Full visual journey builder
- Advanced attribution modeling
- Multivariate testing

---

## 3) Use Native Magento First

## Native features to reuse
- `Magento_Newsletter` (subscribers + statuses)
- Email templates (`Marketing > Communications > Email Templates`)
- Cron framework
- Message queue framework
- Customer groups

## Build custom on top
- Campaign entity
- Audience rule presets
- Send queue job model
- Progress/status endpoints
- Analytics event tracking

---

## 4) High-Level Architecture

1. Admin user creates campaign.
2. Campaign is validated and moved to `scheduled` or `ready`.
3. System enqueues recipient batches (`campaign_job` records + MQ messages).
4. Consumer sends emails in background.
5. UI polls status endpoint for live progress.
6. Refresh-safe: UI reads campaign/job state from DB and resumes progress view.
7. Optional retries for failed jobs.

---

## 5) Module Skeleton

Create:
- `app/code/Petshop/Marketing/registration.php`
- `app/code/Petshop/Marketing/etc/module.xml`
- `app/code/Petshop/Marketing/composer.json`

Enable:
- `bin/magento module:enable Petshop_Marketing`
- `bin/magento setup:upgrade`
- `bin/magento cache:flush`

---

## 6) Data Model (DB)

Create tables in `etc/db_schema.xml`:

1. `petshop_marketing_campaign`
- `campaign_id` PK
- `name`, `status`, `subject`, `sender_name`, `sender_email`
- `template_identifier`
- `audience_type` (newsletter/customers/both)
- `audience_filter_json`
- `scheduled_at`, `started_at`, `finished_at`
- counters: `total_recipients`, `processed_count`, `sent_count`, `failed_count`
- timestamps

2. `petshop_marketing_campaign_job`
- `job_id` PK
- `campaign_id` FK
- `recipient_email`, `recipient_name`, `recipient_type`
- `status` (queued|processing|sent|failed|skipped)
- `attempts`, `last_error`
- `message_id`, `processed_at`
- unique key (`campaign_id`,`recipient_email`) for idempotency

3. `petshop_marketing_event`
- `event_id` PK
- `campaign_id`, `job_id`
- `event_type` (open|click|bounce|unsubscribe)
- `metadata_json`
- `created_at`

---

## 7) Queue / Async Worker

## Message queue files
- `etc/queue_topology.xml`
- `etc/queue_publisher.xml`
- `etc/queue_consumer.xml`
- `etc/communication.xml`

## Consumer
- `Model/Queue/Consumer/CampaignSendConsumer.php`
- Receives message `{campaign_id, job_id}`
- Locks job row (or optimistic update status transition)
- Sends email via `TransportBuilder`
- Updates job + campaign counters
- Writes failures and retry logic

## Retry strategy
- Max attempts: 3
- Backoff: immediate + delayed via cron requeue
- Permanent fail after max attempts

---

## 8) Admin UI

## Menu/ACL
- `etc/adminhtml/menu.xml`
- `etc/acl.xml`
- Sections:
  - Marketing > Email Campaigns
  - Marketing > Audience Presets
  - Marketing > Analytics

## Campaign screens
- Grid listing + status + progress bar
- Form: metadata, audience, template, schedule
- Actions: Save, Start, Pause, Resume, Cancel

## Async status endpoints
- `Controller/Adminhtml/Campaign/Enqueue.php`
- `Controller/Adminhtml/Campaign/Status.php`
- `Controller/Adminhtml/Campaign/Pause.php`
- `Controller/Adminhtml/Campaign/Resume.php`
- `Controller/Adminhtml/Campaign/Cancel.php`

Use JSON endpoints and frontend polling (same pattern used in Blog AI jobs).

---

## 9) Recipient Selection Strategy

## Initial audience filters
- Newsletter subscribers by status
- Customer group IDs
- Optional: store view

## Expansion plan
- Last order date
- Total spent ranges
- Purchased category/product
- Inactive X days

Implement in a dedicated service:
- `Model/Audience/RecipientProvider.php`

---

## 10) Email Rendering & Tracking

## Rendering
- Use Magento Email Templates with variables:
  - `{{var customer_name}}`
  - `{{var campaign_name}}`
  - custom block variables

## Tracking
- Open tracking pixel endpoint:
  - `Controller/Track/Open.php?c=...&j=...`
- Click redirect endpoint:
  - `Controller/Track/Click.php?c=...&j=...&u=...`
- Persist events in `petshop_marketing_event`

---

## 11) Robustness & Concurrency Rules

- Never send emails in admin request thread.
- Make workers idempotent (safe reprocessing).
- Use status transition checks (`queued -> processing -> sent/failed`).
- Release session lock in admin async endpoints.
- Keep polling lightweight and read-only.
- Add dead-letter/error logging for failed jobs.

---

## 12) Security & Compliance

- ACL on all admin routes
- CSRF/form key validation for POST actions
- Email unsubscribe link support
- Respect consent/subscription status before each send
- Audit log for campaign actions

---

## 13) Testing Plan

## Unit tests
- RecipientProvider filters
- Status transition logic
- Retry policy

## Integration tests
- Campaign enqueue creates jobs
- Consumer sends and updates counters
- Pause/Resume behavior

## Manual tests
1. Create campaign with small audience (5 recipients)
2. Start campaign and refresh page mid-send
3. Confirm progress resumes from DB status
4. Force one invalid recipient and confirm fail/retry path

---

## 14) Operational Commands

From project root:
- `bin/magento setup:upgrade`
- `bin/magento cache:flush`
- `bin/magento indexer:reindex`

Run consumer:
- `bin/magento queue:consumers:start petshop.marketing.campaign.send`

Watch logs:
- `tail -f var/log/system.log var/log/exception.log`

---

## 15) Milestones

## M1 (Core)
- Module scaffold + DB schema + campaign CRUD + enqueue/status

## M2 (Async send)
- MQ topology + consumer + retries + progress counters

## M3 (Tracking)
- Open/click tracking + analytics grid

## M4 (Quality)
- Tests + hardening + docs + runbook

---

## 16) Definition of Done (MVP)

- Campaign can be created and started in Admin
- Sending runs fully in background worker
- Page refresh does not lose progress
- Status and counters are accurate
- Failed jobs are visible and retryable
- Logs are sufficient for troubleshooting