# Intakely REST API v1 Documentation

The Intakely REST API allows software teams, agencies, and integrations to programmatically capture leads, query pipeline status, and update client matters.

---

## 1. Authentication

All requests to `/api/v1/*` (except public endpoints) must provide an organization API key via the HTTP `Authorization` header:

```http
Authorization: Bearer <API_KEY>
```

API keys can be generated under **Settings > API Keys** in the Intakely web interface. Keys are scoped strictly to your organization's tenant and subject to PostgreSQL Row-Level Security.

---

## 2. Rate Limits

Standard rate limit: **60 requests per minute** per API key. Rate limit metadata is returned in response headers:
- `X-RateLimit-Limit: 60`
- `X-RateLimit-Remaining: 58`
- `X-RateLimit-Reset: 1730000000`

---

## 3. Endpoints

### `GET /api/v1/leads`
Retrieve recent leads for your organization.

#### Query Parameters:
- `status` (optional): `open`, `won`, `lost`, `all` (default: `open`)
- `limit` (optional): number of records (default: 50, max: 100)

#### Response `200 OK`:
```json
{
  "data": [
    {
      "id": "7a8b9c1d-0000-0000-0000-000000000001",
      "name": "Arthur Pendelton",
      "email": "arthur@example.com",
      "phone": "+447700900111",
      "service": "Residential Conveyancing",
      "value": 2800.00,
      "score": 68,
      "priority": "high",
      "status": "open",
      "created_at": "2026-10-22T10:00:00Z"
    }
  ]
}
```

---

### `POST /api/v1/leads`
Ingest a new lead. Intakely automatically executes duplicate detection, lead scoring, department routing, and trigger automations.

#### Request Body:
```json
{
  "name": "Helena Campbell",
  "email": "helena.campbell@example.com",
  "phone": "+447700900112",
  "company": "Campbell & Co",
  "service": "Commercial Litigation",
  "value": 18000.00,
  "source": "api",
  "custom_fields": {
    "urgency": "high",
    "court_date": "2026-11-15"
  }
}
```

#### Response `201 Created`:
```json
{
  "data": {
    "id": "9b8c7d6e-0000-0000-0000-000000000002",
    "name": "Helena Campbell",
    "score": 85,
    "priority": "high",
    "assigned_to": "david-ross-uuid",
    "sla_deadline": "2026-10-22T12:00:00Z",
    "created_at": "2026-10-22T10:00:00Z"
  }
}
```

---

### `POST /api/v1/board/move`
Move a lead card between pipeline stages (performs compliance gate validation).

#### Request Body:
```json
{
  "lead_id": "9b8c7d6e-0000-0000-0000-000000000002",
  "stage_id": "target-stage-uuid"
}
```

#### Response `200 OK`:
```json
{
  "success": true,
  "lead_id": "9b8c7d6e-0000-0000-0000-000000000002",
  "stage_id": "target-stage-uuid",
  "stage_name": "Consultation Booked"
}
```

#### Response `422 Unprocessable Entity` (Compliance Gate Failure):
```json
{
  "success": false,
  "error": "Compliance requirement not met: Cannot move to stage \"Instructed\" without: Conflict Check Passed, Id Aml Verified",
  "missing_fields": ["Conflict Check Passed", "Id Aml Verified"]
}
```
