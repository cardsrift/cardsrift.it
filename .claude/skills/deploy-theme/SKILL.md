---
name: deploy-theme
description: Build and deploy the theme to production (Aruba) via FTP mirror. Only the user can trigger this.
disable-model-invocation: true
---

Deploy the built theme to the Aruba production server. Only `public/wp-content/themes/cardsrift/` is uploaded — never core, plugins, or uploads.

Steps:

1. Check `.env.deploy` exists at the project root. If missing, stop and tell the user to copy `.env.deploy.example` to `.env.deploy` and fill in the Aruba FTP credentials (Aruba panel → Hosting → FTP details).
2. If this is the first deploy ever (ask if unsure), run a dry run first and show the user what would be uploaded before proceeding:
   `npm run wp-build && bash scripts/deploy.sh --dry-run`
3. Run the deploy: `npm run deploy`
4. Report the lftp transfer summary (files uploaded/deleted). If lftp fails with a certificate error, suggest setting `FTP_VERIFY_CERT=false` in `.env.deploy` (common with Aruba shared-hosting certs).
5. Remind the user to spot-check the live site.

Note: the mirror uses `--delete` scoped to the theme folder — files removed from the repo are also removed from the server's theme directory. That is intended (source of truth is git).
