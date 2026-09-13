import { calculateAssessment } from './grade-import-data.js';

export function recalcAssessmentRow(row, schema, outcomes) {
    const points = {};
    row.querySelectorAll('input[data-question-index]').forEach(input => {
        const question = schema.find(question => question.index === Number(input.dataset.questionIndex));
        if (!question) return;
        input.type = 'number';
        input.min = '0';
        input.max = String(question.max);
        input.step = 'any';
        input.name = `grades[${row.dataset.studentId}][${question.index}]`;
        points[question.index] = input.value;
    });
    const result = calculateAssessment(points, schema, outcomes);
    const display = (element, value) => {
        const text = value === null ? '' : value.toFixed(2);
        if ('value' in element) {
            element.value = text;
            element.readOnly = true;
        } else element.textContent = text;
    };
    row.querySelectorAll('[data-assessment-score]').forEach(element => display(element, result.score));
    row.querySelectorAll('[data-outcome-score], [data-outcome-earned]').forEach(element => {
        const outcome = result.outcomes.find(outcome => outcome.code === element.dataset.code);
        display(element, outcome?.[element.hasAttribute('data-outcome-earned') ? 'earned' : 'score'] ?? null);
    });
    row.dataset.status = result.status;
    // Update status label with colour
    row.querySelectorAll('[data-assessment-state]').forEach(element => {
        const map = {
            pending:   '<span class="text-muted">Belum lengkap</span>',
            memenuhi:  '<span class="text-emerald-700 font-semibold">Memenuhi target</span>',
            belum:     '<span class="text-danger font-semibold">Belum memenuhi</span>',
        };
        element.innerHTML = map[result.status] ?? '';
    });
    // Update CPMK capaian colour (green = passed, red = failed, neutral = pending)
    row.querySelectorAll('[data-outcome-score]').forEach(element => {
        const outcome = result.outcomes.find(outcome => outcome.code === element.dataset.code);
        if (element.classList) {
            element.classList.remove('text-danger', 'text-emerald-700');
            if (outcome?.passed === false) element.classList.add('text-danger');
            else if (outcome?.passed === true) element.classList.add('text-emerald-700');
        }
    });
    // Update live CPMK contribution text under each input
    row.querySelectorAll('input[data-question-index]').forEach(input => {
        const qIndex = Number(input.dataset.questionIndex);
        const question = schema.find(q => q.index === qIndex);
        const td = typeof input.closest === 'function' ? input.closest('td') : null;
        const contribEl = td && typeof td.querySelector === 'function' ? td.querySelector('.cpmk-contrib') : null;
        if (contribEl && question) {
            const val = parseFloat(input.value);
            const courseWeight = Number(input.dataset.mkWeight || question.course_weight || 0);
            if (!isNaN(val) && val >= 0) {
                const contrib = ((val / Number(question.max || 100)) * courseWeight).toFixed(2);
                contribEl.innerHTML = `Kontribusi: <strong class="text-brand">${contrib}%</strong> MK`;
            } else {
                contribEl.innerHTML = `Kontribusi: <strong class="text-brand">0,00%</strong> MK`;
            }
        }
    });

    // Update total assessment course weight contribution text
    row.querySelectorAll('[data-assessment-course-contrib]').forEach(element => {
        if (!element.dataset || typeof element.dataset.totalCourseWeight === 'undefined') return;
        const totalCourseWeight = Number(element.dataset.totalCourseWeight || 0);
        if (result.score !== null && totalCourseWeight > 0) {
            const totalContrib = ((result.score / 100) * totalCourseWeight).toFixed(2);
            element.textContent = `${totalContrib}% / ${totalCourseWeight.toFixed(1)}% MK`;
        } else {
            element.textContent = `- / ${totalCourseWeight.toFixed(1)}% MK`;
        }
    });

    return result;
}

if (typeof document !== 'undefined') document.querySelectorAll('[data-assessment-editor]').forEach(editor => {
    const schema = JSON.parse(editor.dataset.schema);
    const outcomes = JSON.parse(editor.dataset.outcomes);
    const rows = [...editor.querySelectorAll('.student-row')];
    const search = document.getElementById('student-search');
    const status = document.getElementById('statusfilter');
    const count = document.getElementById('counttext');
    const form = editor.closest('form') || editor.querySelector('form');
    let dirty = false;
    let submitting = false;
    const filter = () => {
        const query = (search?.value || '').trim().toLowerCase();
        let visible = 0;
        rows.forEach(row => {
            row.hidden = !(row.dataset.number.includes(query) || row.dataset.name.includes(query))
                || (!!status?.value && status.value !== 'all' && row.dataset.status !== status.value);
            if (!row.hidden) visible++;
        });
        if (count) count.textContent = `${visible} dari ${rows.length} mahasiswa`;
    };
    const markDirty = () => {
        dirty = true;
        document.querySelectorAll('[data-grading-dirty]').forEach(marker => { marker.hidden = false; });
    };
    editor.addEventListener('input', event => {
        if (!event.target.matches('input[data-question-index]')) return;
        recalcAssessmentRow(event.target.closest('.student-row'), schema, outcomes);
        markDirty();
        filter();
    });
    editor.addEventListener('assessment-grades-changed', () => {
        rows.forEach(row => recalcAssessmentRow(row, schema, outcomes));
        markDirty();
        filter();
    });
    search?.addEventListener('input', filter);
    status?.addEventListener('change', filter);
    form?.addEventListener('submit', event => {
        submitting = true;
        queueMicrotask(() => { if (event.defaultPrevented) submitting = false; });
    });
    window.addEventListener('beforeunload', event => {
        if (!dirty || submitting) return;
        event.preventDefault();
        event.returnValue = '';
    });
    rows.forEach(row => recalcAssessmentRow(row, schema, outcomes));
    filter();
});
