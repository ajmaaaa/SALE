import test from 'node:test';
import assert from 'node:assert/strict';
import { parseGradeCsv, validateGradeRows } from '../resources/js/grade-import-data.js';
import { readSheet } from 'read-excel-file/node';
import { strToU8, zipSync } from 'fflate';

const students = new Set(['2024081001', '2024081002', '00123']);
const header = ['NIM', 'CPMK_01', 'CPMK_02'];

test('an XLSX workbook is parsed from the first sheet and validated', async () => {
    const files = {
        '[Content_Types].xml': '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
        '_rels/.rels': '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>',
        'xl/workbook.xml': '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Nilai" sheetId="1" r:id="rId1"/></sheets></workbook>',
        'xl/_rels/workbook.xml.rels': '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>',
        'xl/worksheets/sheet1.xml': '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="inlineStr"><is><t>NIM</t></is></c><c r="B1" t="inlineStr"><is><t>CPMK_01</t></is></c><c r="C1" t="inlineStr"><is><t>CPMK_02</t></is></c></row><row r="2"><c r="A2" t="inlineStr"><is><t>00123</t></is></c><c r="B2"><v>80.5</v></c><c r="C2"><v>90</v></c></row></sheetData></worksheet>',
    };
    const workbook = zipSync(Object.fromEntries(Object.entries(files).map(([path, xml]) => [path, strToU8(xml)])));
    const matrix = await readSheet(Buffer.from(workbook), { sheet: 1 });
    const result = validateGradeRows(matrix, students);
    assert.deepEqual(result.errors, []);
    assert.deepEqual(result.grades, [{ nim: '00123', cpmk1: 80.5, cpmk2: 90, score: 85.3 }]);
});

test('CSV accepts BOM, reordered headers, quoted values, zero and decimal comma', () => {
    const csv = '\uFEFFCPMK_02;NIM;CPMK_01\r\n"80,5";00123;0\r\n100;2024081001;90';
    const result = validateGradeRows(parseGradeCsv(csv), students);
    assert.deepEqual(result.errors, []);
    assert.deepEqual(result.grades[0], { nim: '00123', cpmk1: 0, cpmk2: 80.5, score: 40.3 });
});

test('numeric Excel cells and partial class imports are accepted', () => {
    const result = validateGradeRows([header, [2024081001, 80, 90]], students);
    assert.deepEqual(result.grades, [{ nim: '2024081001', cpmk1: 80, cpmk2: 90, score: 85 }]);
});

test('unknown and duplicate NIM reject the entire import', () => {
    for (const row of [['unknown', 80, 90], ['2024081001', 50, 60]]) {
        const result = validateGradeRows([header, ['2024081001', 80, 90], row], students);
        assert.ok(result.errors.length);
        assert.deepEqual(result.grades, []);
    }
});

test('blank, out of range, formulas, nonnumeric and invalid step scores are rejected', () => {
    for (const value of ['', null, -1, 101, 80.25, '=80+5', 'NaN', 'Infinity', '0x50', '1e2', true]) {
        const result = validateGradeRows([header, ['2024081001', value, 90]], students);
        assert.ok(result.errors.length, String(value));
        assert.deepEqual(result.grades, []);
    }
});

test('empty files, wrong headers, duplicate headers and oversized sheets are rejected', () => {
    for (const matrix of [[], [header], [['NIM', 'NILAI']], [[...header, 'NIM']], [header, ...Array(501).fill(['2024081001', 80, 90])]]) {
        assert.ok(validateGradeRows(matrix, students).errors.length);
    }
});

test('blank rows are ignored and malformed CSV is rejected', () => {
    assert.equal(validateGradeRows([header, ['', '', ''], ['2024081001', 80, 90]], students).grades.length, 1);
    assert.throws(() => parseGradeCsv('NIM,CPMK_01,CPMK_02\n"unfinished,80,90'));
    assert.ok(validateGradeRows(parseGradeCsv('NIM,CPMK_01,CPMK_02\n2024081001,80,5,90'), students).errors.length);
});
