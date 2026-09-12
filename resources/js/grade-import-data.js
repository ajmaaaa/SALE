import Papa from 'papaparse';

export function parseGradeCsv(text) {
    const result = Papa.parse(text, { skipEmptyLines: 'greedy' });
    if (result.errors.length) throw new Error('CSV tidak dapat dibaca. Periksa pemisah kolom dan tanda kutip.');
    return result.data;
}

export function validateGradeRows(matrix, studentNumbers) {
    const errors = [];
    const grades = [];
    if (!matrix.length) return { errors: ['File kosong.'], grades };
    const headers = matrix[0].map(value => String(value ?? '').replace(/^\uFEFF/, '').trim().toUpperCase());
    const required = ['NIM', 'CPMK_01', 'CPMK_02'];
    if (required.some(header => headers.filter(value => value === header).length !== 1)) {
        return { errors: ['Header harus memiliki tepat satu kolom NIM, CPMK_01, dan CPMK_02.'], grades };
    }
    if (matrix.length > 501) return { errors: ['Maksimal 500 baris nilai dalam satu file.'], grades };
    const indexes = required.map(header => headers.indexOf(header));
    const seen = new Set();
    matrix.slice(1).forEach((row, index) => {
        if (row.every(value => value == null || String(value).trim() === '')) return;
        const line = index + 2;
        if (row.length > headers.length && row.slice(headers.length).some(value => value != null && String(value).trim() !== '')) {
            errors.push(`Baris ${line}: jumlah kolom melebihi header. Gunakan tanda kutip untuk nilai desimal koma pada CSV.`);
        }
        const nim = String(row[indexes[0]] ?? '').trim();
        if (!studentNumbers.has(nim)) errors.push(`Baris ${line}: NIM ${nim || '(kosong)'} tidak ditemukan pada tabel kelas ini.`);
        if (seen.has(nim)) errors.push(`Baris ${line}: NIM ${nim} muncul lebih dari sekali.`);
        seen.add(nim);
        const scores = indexes.slice(1).map((column, scoreIndex) => {
            const raw = String(row[column] ?? '').trim().replace(',', '.');
            const score = Number(raw);
            if (!/^\d+(\.\d+)?$/.test(raw) || !Number.isFinite(score) || score < 0 || score > 100 || !Number.isInteger(score * 2)) {
                errors.push(`Baris ${line}: ${required[scoreIndex + 1]} harus bernilai 0-100 dengan kelipatan 0,5.`);
            }
            return score;
        });
        grades.push({ nim, cpmk1: scores[0], cpmk2: scores[1], score: Math.round((scores[0] + scores[1]) * 5) / 10 });
    });
    if (!grades.length) errors.push('File tidak berisi baris nilai.');
    return { errors, grades: errors.length ? [] : grades };
}
