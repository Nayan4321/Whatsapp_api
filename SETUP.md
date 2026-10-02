# Team Inbox — Install & Connect Guide

This is a team inbox + agent-monitoring platform built on the official
**Meta WhatsApp Business Cloud API**. This guide covers:

1. Installing on a server (via SSH)
2. First login
3. Connecting a Meta / WhatsApp Business account (what you need and where to get it)
4. Adding more numbers / connecting **different** accounts
5. Going live

---

## 1. Install on a server (SSH)

You need: PHP **8.2+** (8.3/8.4 fine) with `pdo`, `mbstring`, `openssl`, `gd`,
`curl`, `zip`; and a domain with **HTTPS**. Dependencies (`vendor/`) and a
starter database are already inside this package — no Composer needed.

### Step 1 — Upload & extract
Upload `team-inbox.zip` to your server and extract it into the folder your
domain/subdomain serves. **Extract over SSH (not a File Manager) — File
Managers sometimes silently drop folders:**
```bash
cd /path/to/your/webroot
unzip -o team-inbox.zip
```
After extracting, the webroot contains: `index.php`, `.htaccess`, `core/`,
`icons/`, `manifest.webmanifest`, `sw.js`.

> **Document root:** the folder that holds `index.php` (above) must be what
> your domain serves.
> - If you control the vhost docroot, point the domain at this folder.
> - On shared hosting where the subdomain folder is fixed, just extract
>   directly into that fixed folder — `index.php` sits at the top, so it works
>   with no rewrite tricks.

### Step 2 — Set your domain & permissions
```bash
cd /path/to/your/webroot
# set your real URL
sed -i 's#APP_URL=.*#APP_URL=https://YOUR-DOMAIN#' core/.env
# make runtime folders writable
chmod -R 755 core/storage core/bootstrap/cache
php core/artisan optimize:clear
```

### Step 3 — Open the site
Visit `https://YOUR-DOMAIN/` → you'll get the login page.

**Default owner login (change immediately):**
- Email: `owner@example.com`
- Password: `ChangeMe123!`

Change it: log in → **Settings → Users → Owner → Edit** → set a new password.
Add supervisors/agents from the same **Users** screen.

> Prefer to create your own owner from scratch instead of the default? Delete
> the starter DB and run the web installer:
> ```bash
> rm core/database/database.sqlite && touch core/database/database.sqlite
> php core/artisan migrate --force
> ```
> then open `https://YOUR-DOMAIN/install`.

### (Optional) Use MySQL instead of SQLite
```bash
# create a MySQL db+user in your panel, then:
cd /path/to/your/webroot
nano core/.env     # set:
#   DB_CONNECTION=mysql
#   DB_HOST=localhost
#   DB_PORT=3306
#   DB_DATABASE=...  DB_USERNAME=...  DB_PASSWORD=...
php core/artisan migrate --force
php core/artisan db:seed --force          # default flag rules
```

---

## 2. First login
- Owner = full access (settings, users, monitoring, inbox)
- Supervisor = monitoring + inbox (no settings)
- Agent = inbox for their assigned numbers only

---

## 3. Connect a Meta / WhatsApp Business account

This is what links the platform to WhatsApp. Do it once per number.

### A. What you need from Meta (per number)
| Value | Where to get it |
|-------|-----------------|
| **Phone number ID** | developers.facebook.com → your App → **WhatsApp → API Setup** |
| **WhatsApp Business Account (WABA) ID** | same API Setup page |
| **Permanent access token** | Business Settings → **System Users** → create a system user → **Generate token** with `whatsapp_business_messaging` + `whatsapp_business_management`. (Use a *permanent* system-user token — the temporary one expires in 24h.) |
| **App Secret** | your App → **Settings → Basic** |

### B. One-time Meta setup
1. **Meta Business account** at business.facebook.com (if you don't have one).
2. **Create/choose an App** at developers.facebook.com → add the **WhatsApp** product.
3. **Business Verification** (Business Settings → Security Center) — needed to
   raise messaging limits beyond the trial.
4. Add your business phone number under **WhatsApp → API Setup** (or migrate an
   existing WhatsApp Business *app* number to the Cloud API).

### C. Point Meta's webhook at this platform
In your App → **WhatsApp → Configuration → Edit**:
- **Callback URL:** `https://YOUR-DOMAIN/webhook/whatsapp`
- **Verify token:** the value of `WHATSAPP_VERIFY_TOKEN` in `core/.env`
  (a per-number token can also be set in the app — see below)
- **Subscribe** to the **`messages`** field.
- Put your **App Secret** into `core/.env` as `WHATSAPP_APP_SECRET=...` and run
  `php core/artisan optimize:clear` (this enables signature verification).

### D. Add the number inside the platform
Log in as owner → **Settings → Numbers → Add number**:
- **Label** — any name (e.g. "Sales — North")
- **Display phone** — optional, for your reference
- **Phone number ID** — from Meta
- **WABA ID** — from Meta
- **Permanent access token** — from Meta (stored encrypted)
- **Webhook verify token** — optional per-number token (otherwise the global
  one in `.env` is used)
- **Assigned agents** — tick which agents may use this number

Save. Send a test message **to** that WhatsApp number from your phone — it
should appear in the inbox within a second or two. Reply from the inbox to
confirm two-way messaging.

---

## 4. Connect MORE numbers / DIFFERENT accounts

The platform is multi-number and multi-account out of the box:

- **More numbers, same business:** just repeat section 3D for each number
  (up to 20 per WABA).
- **A different Meta business / client account:** each number carries **its
  own access token and WABA ID**, so to connect a completely separate Meta
  account you simply **Add number** with that account's phone number ID, WABA
  ID and permanent token. No code changes. Point that account's webhook to the
  **same** `https://YOUR-DOMAIN/webhook/whatsapp` URL — the platform routes
  each incoming message to the right number automatically by its phone number
  ID.
- Give each client's agents access only to their numbers (Settings → Users →
  assign numbers), so teams stay isolated.

> Tip: one webhook URL serves all numbers and all accounts. You only need to
> register that URL (with your verify token) once per Meta **App**.

---

## 5. Going live checklist
- [ ] Changed the default owner password
- [ ] Set `APP_URL` to your real https domain
- [ ] Set `APP_DEBUG=false` in `core/.env`, then `php core/artisan optimize:clear`
- [ ] Set `WHATSAPP_APP_SECRET` (webhook signature verification on)
- [ ] `chmod -R 755 core/storage core/bootstrap/cache`
- [ ] Added at least one number and sent a successful test message
- [ ] (If you run Click-to-WhatsApp ads) tested that ads still deliver to a
      Cloud-API number **before** migrating all ad numbers

---

## Troubleshooting
- **500 error:** run `php core/artisan route:list` over SSH — it prints the
  real error. Usually it's `core/storage` not writable → `chmod -R 755 core/storage`.
- **Webhook not receiving:** verify the Callback URL is exactly
  `https://YOUR-DOMAIN/webhook/whatsapp`, the verify token matches, and you
  subscribed to `messages`.
- **Media images not showing:** run `php core/artisan storage:link` (or, if
  symlinks are blocked, copy `core/storage/app/public` to `storage` at the webroot).
