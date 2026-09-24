#!/usr/bin/env python3
"""Run the locally installed Strix against a disposable source snapshot."""
import argparse
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
from datetime import datetime, timezone

ROOT = Path(__file__).resolve().parents[1]
LOCAL = ROOT / '.security' / 'strix'
CONFIG = LOCAL / 'config.json'
BIN = LOCAL / 'venv' / 'bin' / 'strix'


def fail(message):
    sys.exit(message)


def configuration():
    if not CONFIG.exists():
        fail('Konfigurasi belum ada: salin docs/security/strix-config.example.json ke .security/strix/config.json')
    try:
        value = json.loads(CONFIG.read_text())
    except (ValueError, OSError):
        fail('config.json tidak dapat dibaca atau JSON tidak valid. Isi key di file lokal, bukan di command line.')
    if not isinstance(value, dict) or not all(isinstance(value.get(k), str) for k in ('model', 'gemini_api_key')):
        fail('config.json harus berisi model dan gemini_api_key bertipe string.')
    return value


def snapshot():
    stamp = datetime.now(timezone.utc).strftime('%Y%m%dT%H%M%S%fZ')
    target = LOCAL / 'targets' / stamp
    target.mkdir(parents=True)
    # Include safe, non-ignored untracked source files as well. Security scans must
    # cover the working tree being reviewed, not only the last Git index state.
    tracked = subprocess.check_output(
        ['git', 'ls-files', '-z', '--cached', '--others', '--exclude-standard'],
        cwd=ROOT,
    ).decode().split('\0')
    directories = {'app', 'bootstrap', 'config', 'database', 'resources', 'routes', 'tests'}
    files = {'artisan', 'composer.json', 'composer.lock', 'package.json', 'package-lock.json',
             'phpunit.xml', 'vite.config.js', '.env.example', '.env.testing', '.nvmrc', 'README.md',
             'public/index.php', 'scripts/setup.php', 'scripts/prepare-python.mjs'}
    count = 0
    for name in tracked:
        if not name:
            continue
        path = Path(name)
        if path.parts[0] not in directories and name not in files:
            continue
        source = ROOT / path
        if source.is_symlink() or not source.is_file() or source.resolve() != source.absolute():
            continue
        if path.suffix == '.db' or 'cache' in path.parts:
            continue
        destination = target / path
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(source, destination)
        count += 1
    print(f'Snapshot: {target} ({count} file; perubahan tracked dan source baru yang belum commit ikut disalin).', flush=True)
    return target


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('action', choices=['check', 'prepare', 'scan'])
    parser.add_argument('--budget', type=float, help='Estimasi batas biaya USD wajib untuk scan; dapat terlampaui oleh request aktif.')
    args = parser.parse_args()
    if args.action == 'prepare':
        snapshot()
        return
    if not BIN.exists():
        fail('Strix belum terpasang di .security/strix/venv.')
    config = configuration()
    key = config.get('gemini_api_key', '').strip()
    model = config.get('model', '').strip()
    if not model.startswith('gemini/'):
        fail('Model harus memakai prefix gemini/ untuk API Google AI Studio.')
    docker = subprocess.run(['docker', 'info', '--format', '{{.ServerVersion}}'], capture_output=True, text=True)
    print('Strix: terpasang', flush=True)
    print('Docker: ' + (docker.stdout.strip() if docker.returncode == 0 else 'tidak dapat diakses'), flush=True)
    print('Gemini API key: ' + ('sudah diisi (belum diuji)' if key else 'belum diisi'), flush=True)
    print('Model: ' + model, flush=True)
    if args.action == 'check':
        if docker.returncode:
            fail('Jalankan pemeriksaan dari terminal yang memiliki akses Docker.')
        return
    if not key:
        fail('Isi gemini_api_key pada .security/strix/config.json dahulu.')
    if docker.returncode:
        fail('Docker belum dapat diakses.')
    if args.budget is None or not 0 < args.budget < float('inf'):
        fail('Tentukan --budget dengan angka USD positif, misalnya --budget 2.')
    target = snapshot()
    env = os.environ.copy()
    for name in ('LLM_API_BASE', 'OPENAI_API_BASE', 'OPENAI_BASE_URL', 'LITELLM_BASE_URL', 'OLLAMA_API_BASE',
                 'LLM_EXTRA_HEADERS', 'STRIX_DEDUPE_MODEL', 'DEDUPE_LLM_API_KEY',
                 'DEDUPE_LLM_API_BASE', 'DEDUPE_LLM_EXTRA_HEADERS'):
        env.pop(name, None)
    env.update(
        STRIX_LLM=model,
        LLM_API_KEY=key,
        GEMINI_API_KEY=key,
        STRIX_LLM_MAX_RETRIES='2',
        STRIX_DOCKER_SANDBOX_NETWORK='host',
    )
    print('Memulai scan Gemini pada salinan source. Output disimpan di .security/strix/strix_runs.', flush=True)
    result = subprocess.run([
        str(BIN), '--target', str(target), '--non-interactive', '--scan-mode', 'quick',
        '--scope-mode', 'full', '--max-budget', str(args.budget), '--max-turns', '60',
        '--instruction-file', str(ROOT / 'docs/security/strix-instructions.md'),
    ], cwd=LOCAL, env=env)
    sys.exit(result.returncode)


if __name__ == '__main__':
    main()
