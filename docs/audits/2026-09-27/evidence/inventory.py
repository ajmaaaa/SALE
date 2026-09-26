"""Read-only repository inventory. Run from the SALE repository root."""
import collections
import csv
import json
from pathlib import Path
import re
import subprocess
import xml.etree.ElementTree as ET

root = Path.cwd()
out = root / 'docs/audits/2026-09-27/evidence'
paths = [Path(p) for p in subprocess.check_output(['rg', '--files', 'app', 'resources', 'routes', 'database', 'config', 'tests'], text=True).splitlines()]
source = {p: p.read_text(errors='replace') for p in paths if p.suffix in {'.php', '.js', '.mjs'}}
runtime = {p: text for p, text in source.items() if p.parts[0] != 'tests'}
routes = json.loads((out / 'routes.json').read_text())
with (out / 'routes.csv').open('w', newline='') as handle:
    writer = csv.writer(handle)
    writer.writerow(['method', 'uri', 'name', 'action', 'middleware', 'literal_references_in_runtime'])
    for row in routes:
        name = row.get('name') or ''
        refs = [str(p) for p, text in runtime.items() if p.parts[0] != 'routes' and name and (f"'{name}'" in text or f'"{name}"' in text)]
        writer.writerow([row['method'], row['uri'], name, row['action'], ', '.join(row.get('middleware', [])), '; '.join(refs)])

used_controllers = {r['action'].split('@')[0] for r in routes}
unused_controllers = []
for p in paths:
    if str(p).startswith('app/Http/Controllers/') and p.name != 'Controller.php':
        cls = 'App\\' + str(p.relative_to('app')).removesuffix('.php').replace('/', '\\')
        if cls not in used_controllers:
            unused_controllers.append(str(p))

# Reachability through literal view names; dynamic includes require manual review.
views = {str(p.relative_to('resources/views')).removesuffix('.blade.php').replace('/', '.'): p for p in paths if str(p).startswith('resources/views/') and p.name.endswith('.blade.php')}
view_refs = {name: [str(p) for p, text in runtime.items() if p != path and (f"'{name}'" in text or f'"{name}"' in text)] for name, path in views.items()}
rooted = set()
for row in routes:
    action = row['action']
    if not action.startswith('App\\Http\\Controllers\\') or '@' not in action:
        continue
    cls, method = action.split('@', 1)
    path = Path('app/' + cls.removeprefix('App\\').replace('\\', '/') + '.php')
    text = source.get(path, '')
    chunks = re.split(r'(?=public function )', text)
    body = next((chunk for chunk in chunks if chunk.startswith('public function ' + method + '(')), '')
    rooted.update(re.findall(r"\bview\(\s*['\"]([^'\"]+)['\"]", body))
# Shared class-list helper returns a fixed view outside public methods.
rooted.add('dosen.class-section.index')
# AdminPreviewController uses view('admin.'.$section), known allowlist.
rooted.update('admin.' + n for n in ['dashboard', 'akademik', 'pengguna', 'aktivitas', 'monitoring', 'laporan', 'pengaturan'])
for _ in range(len(views)):
    before = len(rooted)
    children = set()
    for parent in rooted:
        if parent not in views:
            continue
        children.update(re.findall(r"@(?:include|includeIf|includeWhen|extends|component|each)\(\s*['\"]([^'\"]+)['\"]", source[views[parent]]))
    rooted |= children
    if len(rooted) == before:
        break

summary = {
    'commit': subprocess.check_output(['git', 'rev-parse', 'HEAD'], text=True).strip(),
    'route_count': len(routes),
    'routes_by_method': dict(collections.Counter(r['method'] for r in routes)),
    'controllers': len([p for p in paths if str(p).startswith('app/Http/Controllers/') and p.name != 'Controller.php']),
    'models': len([p for p in paths if str(p).startswith('app/Models/')]),
    'migrations': len([p for p in paths if str(p).startswith('database/migrations/')]),
    'views': len(views),
    'controllers_without_routes': unused_controllers,
    'views_without_literal_references_candidates': [str(views[n]) for n, refs in view_refs.items() if not refs],
    'views_not_reachable_by_literal_graph_candidates': [str(views[n]) for n in views if n not in rooted],
    'largest_source_files': sorted([{'file': str(p), 'lines': len(text.splitlines())} for p, text in runtime.items()], key=lambda r: r['lines'], reverse=True)[:15],
    'limitations': 'References are lexical, not proof of dead code. Dynamic views and runtime conditional navigation were manually reviewed separately.',
}
(out / 'inventory.json').write_text(json.dumps(summary, indent=2, ensure_ascii=False) + '\n')

junit = ET.parse(out / 'phpunit.xml')
failures = []
for case in junit.iter('testcase'):
    failure = case.find('failure')
    error = case.find('error')
    node = failure if failure is not None else error
    if node is not None:
        message = (node.text or '').splitlines()
        failures.append({'class': case.get('class'), 'name': case.get('name'), 'file': case.get('file'), 'line': case.get('line'), 'type': node.get('type'), 'summary': '\n'.join(message[:12])})
(out / 'test-failures.json').write_text(json.dumps(failures, indent=2, ensure_ascii=False) + '\n')
print(json.dumps(summary, indent=2, ensure_ascii=False))
