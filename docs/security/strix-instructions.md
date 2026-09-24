Audit only the provided disposable SALE source directory. Do not scan public URLs,
campus systems, the Docker host, or services outside your sandbox. This is an audit,
not a remediation task: report findings and PoCs; do not patch application code.

SALE is Laravel 12 with Blade and JavaScript. Prioritize authentication bypass,
persona-to-database privilege escalation, admin-prodi scope, user password updates,
enrollment code disclosure, quiz attempt integrity, grade ownership, CSV injection,
uploads, AI access and quota enforcement. Distinguish prototype-only session data
from real database mutations. A prototype disclaimer does not protect database data.

The snapshot excludes live .env, databases, storage, credentials, dependencies and
Git history. Never try to obtain these from the host. If you need dynamic proofs,
install dependencies inside the sandbox, bootstrap only with .env.testing and an
isolated MySQL test database, and create synthetic accounts. Do not configure external
mail, AI tutoring or code-runner services. Do not send destructive or load-test traffic.

Report in Indonesian: severity, source path and line, prerequisites, reproduction,
observed versus expected behavior, impact, and a concrete remediation. Clearly label
static findings versus executed proofs and list checks blocked by missing tooling.
Do not claim an untested issue is verified. Avoid repeating the same root cause.
