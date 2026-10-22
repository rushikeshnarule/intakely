# Intakely Automations & Follow-up Workflow Engine

Intakely's automation builder is designed to ensure no lead goes unanswered or falls through the cracks. Workflows are stored as declarative JSONB structures within PostgreSQL and executed by `AutomationEngine`.

---

## 1. Schema Specification

An automation consists of three primary blocks: **Trigger**, **Conditions**, and **Actions**.

```json
{
  "name": "Instant Legal Acknowledgement & Conflict Check",
  "trigger_type": "lead_created",
  "trigger_config": {},
  "conditions": [
    {
      "field": "service",
      "operator": "equals",
      "value": "Residential Conveyancing"
    }
  ],
  "actions": [
    {
      "type": "send_email",
      "template_slug": "legal-enquiry-ack"
    },
    {
      "type": "create_task",
      "title": "Perform AML & Conflict check for {{name}}",
      "due_hours": 4
    }
  ]
}
```

---

## 2. Trigger Types

| Trigger Type | Description | Context Provided |
|--------------|-------------|------------------|
| `lead_created` | Fires when a new lead enters the CRM (via web form, API, email webhook, or callback). | `$lead`, `$urgency_detected` |
| `stage_changed` | Fires when a lead moves from one pipeline stage to another. | `$lead`, `$from_stage`, `$to_stage` |
| `sla_breached` | Fires when an open lead's SLA deadline elapses without first response. | `$lead`, `$lead_id` |
| `form_submitted` | Fires when a specific intake form is completed. | `$form`, `$submission_data` |
| `appointment_booked` | Fires when a consultation is confirmed. | `$appointment`, `$lead` |
| `appointment_no_show` | Fires when an appointment is marked as no-show. | `$appointment`, `$lead` |
| `tag_added` | Fires when a specific tag (e.g. "Urgent Hearing") is attached. | `$lead`, `$tag` |

---

## 3. Conditions & Grouping Operators

Conditions support nested `AND` and `OR` groups as well as individual property checks:

| Operator | Comparison Type | Example |
|----------|-----------------|---------|
| `equals` | Exact match (case-insensitive) | `service equals "Commercial Litigation"` |
| `not_equals` | Negated match | `status not_equals "won"` |
| `contains` | Substring match | `details contains "injunction"` |
| `greater_than` | Numeric comparison | `value greater_than 5000` |
| `less_than` | Numeric comparison | `score less_than 40` |
| `is_empty` | Null or empty string | `assigned_to is_empty true` |
| `is_after_hours` | Boolean evaluated against tenant timezone | `is_after_hours equals true` |

---

## 4. Supported Actions

### 1. `send_email`
Sends a personalized email using an email template or inline text.
- Supports variable interpolation: `{{name}}`, `{{service}}`, `{{value}}`, `{{booking_url}}`, `{{tenant_name}}`.
- Automatically checks for lead opt-out (`do_not_contact`).

### 2. `send_sms`
Sends an instant text message to the lead's mobile phone. Respects quiet hours.

### 3. `send_whatsapp`
Dispatches a pre-approved template via WhatsApp Cloud API.

### 4. `create_task`
Creates an action item for the assigned advisor (or team lead) with a configurable due date:
```json
{ "type": "create_task", "title": "Check ID for {{name}}", "due_hours": 24 }
```

### 5. `change_stage`
Moves the lead card on the Kanban board to a target stage.

### 6. `add_tag`
Adds a tag to the lead (e.g. "VIP Client", "Urgent Hearing").

### 7. `call_webhook`
Dispatches an HMAC-signed POST request to external systems (Slack channel, Zapier, Make, internal practice management software).

---

## 5. Ten Prebuilt Automation Recipes

1. **Instant Legal Enquiry Acknowledgement**: Fires upon `lead_created`; sends personalized confirmation email with advisor booking link and creates a 4-hour conflict check task.
2. **Urgent Court Hearing Escalation**: Detects keywords (`injunction`, `tribunal`, `eviction`); sets priority to `HIGH`, adds `Urgent Hearing` tag, and alerts practice manager.
3. **24h Unanswered Drip**: If no first response is logged within 24 hours, sends gentle follow-up SMS to confirm receipt.
4. **72h Consultation Nudge**: Re-sends booking link if consultation has not been scheduled within 3 days.
5. **No-Show Consultation Recovery**: Triggers when appointment is marked `no_show`; sends empathetic email with instant self-reschedule link.
6. **Quote / Fee Proposal Reminder**: Triggers after quote is sent; sends polite reminder at 48 hours offering to answer legal or billing questions.
7. **Client Care Letter Signature Chaser**: Reminds client to e-sign engagement letter and terms of instruction.
8. **After-Hours Auto-Responder**: Greets evening/weekend enquiries instantly, confirms office opening time, and sets expectation for next business morning callback.
9. **Review Request after Instruction**: Triggers upon moving to `Instructed (Won)`; invites client to leave a Google Review.
10. **30-Day Cold Lead Reactivation**: Re-engages stagnant enquiries with a seasonal check-in email.

---

## 6. Quiet Hours & Regulatory Safeguards

- **Quiet Hours Enforcement**: Tenant settings define operating hours (e.g. `09:00 - 17:30`). Outbound promotional communications are delayed until the beginning of the next business window unless marked urgent.
- **Opt-Out Compliance**: If a lead texts back `STOP` or clicks unsubscribe, `do_not_contact` is set to `TRUE`. The `MessagingService` suppresses all subsequent outbound automated messages at the service layer.
