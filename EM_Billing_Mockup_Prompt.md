# Prompt for Claude — EM Billing System UI Mockups

Copy everything below the line into Claude (claude.ai artifacts, or Claude Code with an HTML-prototype request). It is self-contained — no repo access needed.

---

Design high-fidelity, clickable HTML mockups for a new billing subsystem inside **REDCap** (an academic research data platform) at Stanford. The system lets research teams request paid External Modules (EMs — plugins with a monthly maintenance fee), routes the request to their PI for approval, invoices a university account (a "PTA") monthly, and tracks unpaid charges as debt that carries forward until paid.

Build **one HTML file per screen** (or one tabbed prototype) with realistic sample data. Use plain HTML/CSS/JS with **Bootstrap 4**, **DataTables-style tables**, and **Font Awesome 5** icons — the mockups must look native to a REDCap admin page (utilitarian, dense, light-gray/white, blue primary buttons, small text) rather than a modern SaaS product. Prefer function over flash; leadership will review these to approve the project.

## Personas
- **Requester** — a research-team member with the project's "Design" permission. Requests EMs for their own project, pays from their grant's PTA.
- **PI / Admin approver** — the professor who owns the grant. Gets an email, opens a survey, approves or rejects the whole request. Non-technical.
- **RSSD super user** — REDCap platform admin. Runs monthly billing, imports Finance results, manages debt, works Jira tickets for EMs that can't auto-enable, reviews reconciliation alerts.
- **Finance** — offline; edits a CSV outside the system (no screen needed, but the import screen shows their output).

## Shared vocabulary (badge these consistently everywhere)
- **Request line statuses** (one per EM in a request): `pending` (gray), `approved` (teal), `enabled` (green), `awaiting_super_user` (purple, shows a Jira key like RSSD-1234), `approval_error` (red, with a Retry button for admins), `rejected` (red), `deactivated` (dark gray).
- **Billing line statuses**: `pending` (indigo), `charged` (green), `rejected` (red), `waived` (teal), `hold` (gray). Rejected/hold lines carry forward monthly as new attempts (show "↩ attempt #2", "#3"…) until charged or waived.
- **Billing run statuses**: `draft` (amber) → `exported` (blue) → `finalized` (green).
- Amounts are whole US dollars (e.g. $150/mo).

## Screens to mock

### 1. EM Catalog & Cart (project page — Requester)
- Header: "Request External Modules" within a REDCap project context (project title visible).
- Searchable/filterable catalog (table or cards) of requestable EMs: name, short description, monthly fee, and a badge: ⚡ "Auto-enables on approval" vs 🎫 "Enabled by RSSD after approval".
- EMs already enabled on this project appear disabled/badged "Already enabled".
- Cart panel (sidebar or sticky footer): selected EMs, monthly total.
- Checkout modal: PTA number field with format validation (regex, e.g. `1234567-100-ABCDE`; show an inline validation error state), note that the PI will be asked to approve, primary button "Continue to request form" (explain it redirects to a REDCap survey).
- Below the catalog: "My requests" table — request date, EMs (each with its line-status badge), PTA, approver, Jira link when applicable.

### 2. PI Approval Survey (REDCap survey look — PI persona)
- Style it like a REDCap survey (centered single column, plain header with Stanford/REDCap branding).
- Read-only summary: project title/ID, requester, PTA, table of requested EMs with monthly fees and a monthly total.
- Radio: **Approve entire request** / **Reject entire request** (one decision for everything — no per-EM choice), required note textarea (labeled "reason, required if rejecting"), name + date signature row, Submit.
- After-submit confirmation states for both outcomes (approved → "EMs are being enabled / ticketed"; rejected → "requester will see your note").

### 3. Billing Runs (Control Center — super user)
- Tabbed admin page: **Billing Runs | Generate | Import CSV | Outstanding Balances | PTA Accounts | Project↔PTA Mapping** (mock the first four; the last two can be placeholders).
- Runs list table: month/year, status badge, total $, carried-forward $, line count, generated-by/when, buttons: View, ⬇ Summary CSV, ⬇ Detail CSV.
- Run detail view: collapsible hierarchy **PTA group → project → EM line**. PTA group header row: PTA number + description + contact, project count, new $ + carried $ = total $, group status, and a "Set status for PTA…" button. Line rows: EM name/prefix, amount, status badge, carried flag "↩ attempt #N (from May 2026)", and a per-line "Override status…" button. An **Unmapped — no PTA** group renders last with warning styling (all lines `hold`, note "No PTA mapping").
- Manual override modal: status dropdown (charged/rejected/waived/hold), **required** note, warning text "This is audited (who/when/source=manual). Allowed even after the run is finalized — used to close settled backcharges."
- Generate tab: month/year pickers, Preview button (renders the hierarchy without saving), Generate button; blocked state ("A finalized run already exists for June 2026") and a draft state offering "Regenerate in place".

### 4. Import Finance CSV (Control Center — super user)
- Run picker (defaults to latest `exported` run), file dropzone.
- Validation-failure state: all-or-nothing error list ("Row 4: unknown PTA for this run", "Row 7: status 'paid' invalid — use charged/rejected/waived/hold", "Row 9: rejected requires a note") + "Nothing was applied."
- Success state: summary cards — PTAs processed, lines fanned out per status (e.g. 41 charged / 6 rejected / 2 waived), lines skipped because already manually resolved, run marked **finalized**.

### 5. Outstanding Balances — the backcharge ledger (Control Center — super user)
- Per-PTA rows/cards: PTA number + description + contact, **total owed**, months outstanding, expandable to per-project then per-line detail (month billed, EM, amount, attempt count, age).
- "Mark paid…" action per line/PTA opening the same audited override modal (status=charged).
- Callout explaining the rule: open rejected/hold lines re-bill automatically each month to the project's *current* PTA until charged or waived.

### 6. Request Admin & Reconciliation (Control Center — super user)
- All-requests DataTable: date, project, requester, PI, PTA, per-EM status badges, Jira keys as links, Retry button on `approval_error` lines.
- **Acknowledgment queue** section: auto-detected out-of-band deactivations (project, EM, detected date) with Acknowledge ✓ / Flag for investigation ⚑ buttons.
- One-time "Seed grandfathered records" banner/button with result count ("Created 212 tracking records for currently-enabled billable EMs").
- Bonus strip (separate small mockup on the same page is fine): REDCap's native **External Modules manager** list for a project, with our injected per-row annotations — "$150/mo · PTA 1234567-100-ABCDE · status: enabled" — and a red module-owned **Disable** button opening a confirm dialog with a required reason ("Billing for this EM stops next month; already-billed charges still carry forward. Re-enabling requires a new request.").

## Global requirements
- Consistent status-badge color system across all screens (legend on each page footer).
- Realistic Stanford-flavored sample data (project names like "CHOIR PRO Registry", EM names like "Survey Dashboard", "Text-to-Download", PTAs like `1234567-100-ABCDE`, Jira keys `RSSD-…`).
- Each screen titled and annotated with a short caption of who uses it and when, so leadership can follow the flow: Catalog → PI survey → (auto-enable/Jira) → monthly Billing Run → Finance CSV → Ledger.
- No real backend — stub interactions with JS so tabs, modals, expand/collapse, and validation states are clickable.
