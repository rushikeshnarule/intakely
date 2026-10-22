# WordPress Integration & Embed Guide for Intakely

Intakely provides three simple ways to embed intake forms and capture leads from any WordPress website.

---

## Option 1: Responsive Iframe Embed (Easiest)

Embed your Intakely intake form directly on any WordPress Page or Post (such as `/contact` or `/book-consultation`).

### Step-by-Step:
1. In your WordPress admin dashboard, navigate to **Pages > Add New** (or edit your existing contact page).
2. Add a **Custom HTML** block in the Block Editor (Gutenberg) or switch to the Text tab in the Classic Editor.
3. Paste the following snippet (replacing `apex-law` and `legal-intake` with your firm's subdomain and form slug):

```html
<iframe 
    src="http://localhost:8000/f/legal-intake" 
    width="100%" 
    height="750" 
    style="border:none;border-radius:16px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.06);max-width:680px;margin:0 auto;display:block;" 
    title="Client Intake Form"
    loading="lazy">
</iframe>
```

---

## Option 2: Floating "Request a Call Back" Widget

Add a high-converting floating phone widget to every page of your WordPress site.

### Step-by-Step:
1. In your WordPress Admin, go to **Appearance > Theme File Editor** or install a lightweight header/footer script plugin (like *WPCode*).
2. Add the following line just before the closing `</body>` tag (replace `YOUR_TENANT_UUID` with your tenant's ID found under **Settings > General**):

```html
<script src="http://localhost:8000/js/widget.js" data-tenant="YOUR_TENANT_UUID" async></script>
```

When visitors tap the widget, a clean callback modal appears. Submitted phone numbers immediately appear on your Intakely Kanban board flagged as **High Priority Callback Requests**.

---

## Option 3: Connect Existing WordPress Form Plugins via Webhook

If you already use **Gravity Forms**, **Contact Form 7**, or **WPForms**, you can send entries directly into Intakely via our public REST API.

### Webhook Target:
```http
POST http://localhost:8000/api/v1/leads
Authorization: Bearer intakely_key_...
Content-Type: application/json
```

### JSON Body:
```json
{
  "name": "{input_1}",
  "email": "{input_2}",
  "phone": "{input_3}",
  "service": "{input_4}",
  "source": "web_form",
  "source_detail": "WordPress Gravity Forms"
}
```
Intakely will automatically score the lead, check for duplicates, route it to the right department, and dispatch confirmation messages.
