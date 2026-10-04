# CSP inline migration

Status: open — compatibility cleanup after the 2026-10-04 audit remediation.

The application now uses a per-response nonce for Blade and Vite `<script>` elements. `unsafe-eval` is prohibited and broad production `connect-src` access has been removed.

Two compatibility exceptions remain explicit in the policy:

- `script-src-attr 'unsafe-inline'` while legacy `onclick`/`onload` handlers are moved into delegated listeners in `resources/js/app.js`.
- `style-src 'unsafe-inline'` while dynamic style attributes and Blade-computed styles are migrated to classes or nonce-aware style blocks.

Removal gate:

1. `rg -n 'on(click|load|error|change|input|submit)=' resources/views` returns no application handlers.
2. Inline `style=` usage needed for runtime state is replaced or documented.
3. Login, every role dashboard, Reverb/polling, code runner, PDF/image/video preview, and upload flows pass under Report-Only without unexpected violations.
4. Remove both compatibility exceptions and run the full release gate.
