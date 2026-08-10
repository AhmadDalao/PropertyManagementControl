# Production Activation Runbook

Updated: August 10, 2026

This runbook starts the first real portfolio without changing the product scope or contaminating production with fake records. `/system/readiness` is the authority for launch status.

## Current checkpoint

- Production revision: `fe9d109802b33d253ff7b46c18fe860c2b80a499`
- Readiness: 9 ready, 1 attention, 2 blocked
- Queue: database connection, 0 pending, 0 failed
- Portfolios: 0 live, 12 showcase
- Recovery: Backup #4 available; downloaded and checksum-verified off-server again on August 10; restore evidence recorded
- Blockers: SMTP transport, one-minute scheduler, real opening data, business approvals, and four-role pilot

## 1. Configure production mail

1. In Hostinger hPanel, create a dedicated mailbox such as `no-reply@property.ahmaddalao.com` and retain its SMTP settings in the password manager.
2. Update the server-only `.env` with Hostinger's displayed SMTP host, port, encryption, username, password, and sender identity. Set `MAIL_MAILER=smtp`; never commit these values.
3. Clear and rebuild Laravel configuration caches using the verified PHP 8.4 binary.
4. From `/system/readiness`, send the readiness test to the signed-in superadmin and confirm receipt from the real mailbox.
5. Request one password-reset link through `/account-recovery`, confirm delivery, open the link, and stop before changing the production superadmin password unless rotation is intended.
6. Record SMTP receipt evidence only after both messages arrive. The automatic mail check and manual delivery evidence are separate gates.

## 2. Configure the scheduler

In **Websites -> Dashboard -> Advanced -> Cron Jobs**, add one Custom cron scheduled every minute in UTC:

```bash
/opt/alt/php84/usr/bin/php /home/u867436826/domains/ahmaddalao.com/public_html/property/artisan schedule:run
```

Do not append shell redirection. Wait at least three minutes, then verify `/system/readiness` reports three heartbeat samples spanning at least 90 seconds, zero failed jobs, and a draining queue.

## 3. Refresh recovery evidence

1. Create a new package from `/system/backups` after the scheduler is healthy.
2. Wait for completion, download it, and calculate its SHA-256 checksum.
3. Store the package outside Hostinger in protected storage.
4. Verify the archive manifest, database stream, and private-document stream before replacing the existing readiness evidence.
5. Do not mark restore evidence complete again unless the new package is actually restored into an isolated disposable database.

## 4. Onboard one real portfolio

1. Create one active, non-showcase portfolio with approved English and Arabic names, currency, owner, and module policy.
2. Download the current workbook from `/opening-data/template` and populate it from authoritative owner records.
3. Preview the import and resolve every hierarchy, reference, duplicate, date, tenancy, payment, balance, and currency error before commit.
4. Reconcile imported property/unit counts, occupancy, deposits, installments, posted payments, opening balances, and private PDFs against the source workbook.
5. Assign one active owner and one active manager to the correct property hierarchy. Create one tenant account through the controlled portal-access flow rather than sharing a temporary password.
6. Record legal terms, opening data, billing rules, and retention evidence against the selected portfolio in `/system/readiness`.

## 5. Run the 30-day pilot

Use one named superadmin, owner, manager, and tenant on real devices. Complete login and account recovery; property access; lease review; payment posting and receipt download; tenant balance review; maintenance submission with evidence; work-order assignment; resolution; tenant confirmation or reopen; service-report PDF download; and EN/AR checks at desktop and 390px mobile.

Record defects without tenant PII using this structure:

```text
Reference:
Severity: critical | high | medium | low
Role and locale:
Route and device:
Steps to reproduce:
Expected result:
Actual result:
Resolution and regression test:
Production revision verified:
```

The pilot passes only when all readiness gates are complete, financial totals reconcile, no critical or high defects remain, no authorization boundary fails, queued work drains, and the owner signs off the operating result.

## Required operator inputs

- Authenticated Hostinger hPanel access
- Dedicated mailbox credentials stored outside git
- Approved bilingual lease, renewal, termination, receipt, billing, and retention wording
- Completed opening-data workbook and source reconciliation totals
- Named real owner, manager, and tenant participants

Do not create substitute fake records, invent legal wording, or confirm evidence that was not observed.
