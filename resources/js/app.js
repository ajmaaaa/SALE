const sidebar = document.querySelector('[data-sidebar]');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
const sidebarToggle = document.querySelector('[data-sidebar-toggle]');

const setSidebar = (open) => {
    if (!sidebar || !sidebarBackdrop || !sidebarToggle) return;

    sidebar.dataset.open = String(open);
    sidebarBackdrop.dataset.open = String(open);
    sidebarToggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('overflow-hidden', open);
};

sidebarToggle?.addEventListener('click', () => setSidebar(sidebar?.dataset.open !== 'true'));
sidebarBackdrop?.addEventListener('click', () => setSidebar(false));

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') setSidebar(false);
});

const editorMount = document.querySelector('[data-code-editor]');
const editorSource = document.querySelector('[data-code-source]');

if (editorMount && editorSource) {
    Promise.all([
        import('codemirror'),
        import('@codemirror/lang-python'),
        import('@codemirror/theme-one-dark'),
    ]).then(([{ basicSetup, EditorView }, { python }, { oneDark }]) => {
        new EditorView({
            doc: editorSource.value,
            extensions: [
                basicSetup,
                python(),
                oneDark,
                EditorView.lineWrapping,
            ],
            parent: editorMount,
        });
    });
}

const settingsLinks = [...document.querySelectorAll('[data-settings-link]')];
const settingsPanels = [...document.querySelectorAll('[data-settings-panel]')];

if (settingsLinks.length && settingsPanels.length) {
    const activateSettings = (hash) => {
        const target = settingsPanels.some((panel) => `#${panel.id}` === hash) ? hash : '#profil';

        settingsLinks.forEach((link) => {
            const active = link.hash === target;
            link.classList.toggle('border-brand', active);
            link.classList.toggle('border-transparent', !active);
            link.classList.toggle('text-brand', active);
            link.classList.toggle('text-muted', !active);
            link.setAttribute('aria-selected', String(active));
            link.tabIndex = active ? 0 : -1;
        });

        settingsPanels.forEach((panel) => panel.classList.toggle('hidden', `#${panel.id}` !== target));
    };

    settingsLinks.forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
            history.replaceState(null, '', link.hash);
            activateSettings(link.hash);
        });
    });

    activateSettings(window.location.hash);
    window.addEventListener('hashchange', () => activateSettings(window.location.hash));
}
