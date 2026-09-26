/**
 * هم‌ارزی فرمول‌های آفلاین با موتور PHP (بخش ۱۸-۱۰).
 *
 * بردارها را خود موتور PHP می‌سازد (`scripts/formula-vectors.php`): موردهای
 * طلایی پکیج و موردهای تصادفی بذرداده. هر نسخه فرمول باید نسخه JS داشته باشد
 * و روی همه موردها همان خروجی، یادداشت و کلید خطا را بدهد.
 */
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import { test } from 'node:test';
import { castFormValue, formatMeasurement, hasFormula, InvalidInput, run } from '../../resources/js/offline/engine.js';

const vectors = JSON.parse(execFileSync('php', ['scripts/formula-vectors.php'], { maxBuffer: 64 * 1024 * 1024 }).toString());

const RELATIVE = 1e-9;
const ABSOLUTE = 1e-9;

function close(actual, expected) {
    if (typeof expected === 'string') {
        const special = { INF: Infinity, '-INF': -Infinity };
        return expected === 'NAN' ? Number.isNaN(actual) : actual === special[expected];
    }

    return Math.abs(actual - expected) <= Math.max(ABSOLUTE, RELATIVE * Math.abs(expected));
}

for (const [id, { inputs: spec, cases }] of Object.entries(vectors.formulas)) {
    test(`${id} has an offline port`, () => {
        assert.ok(hasFormula(id), `فرمول ${id} نسخه آفلاین ندارد.`);
    });

    if (!hasFormula(id)) {
        continue;
    }

    for (const testCase of cases) {
        test(`${id} — ${testCase.name}`, () => {
            let result;

            try {
                result = run(id, spec, testCase.inputs);
            } catch (error) {
                if (!(error instanceof InvalidInput)) {
                    throw error;
                }

                assert.ok(testCase.errors, `JS ورودی را رد کرد ولی PHP پذیرفت: ${error.message}`);
                assert.deepEqual(error.keys(), testCase.errors);
                return;
            }

            assert.ok(!testCase.errors, `PHP ورودی را رد کرد (${testCase.errors}) ولی JS پذیرفت.`);
            assert.deepEqual(Object.keys(result.outputs).sort(), Object.keys(testCase.outputs).sort());

            for (const [key, expected] of Object.entries(testCase.outputs)) {
                assert.ok(close(result.outputs[key], expected), `${key}: JS ${result.outputs[key]} ≠ PHP ${expected}`);
            }

            assert.deepEqual(result.notes, testCase.notes);
        });
    }
}

test('form values are cast like ToolInputCaster', () => {
    assert.equal(castFormValue('۲۵٫۵'), 25.5);
    assert.equal(castFormValue(' 1,250 '), 1250);
    assert.equal(castFormValue(''), null);
    assert.equal(castFormValue('سه'), 'سه');
});

test('measurements are formatted like MeasurementNumber', () => {
    assert.equal(formatMeasurement(28), '28');
    assert.equal(formatMeasurement(0.84213), '0.8421');
    assert.equal(formatMeasurement(Infinity), '—');
});
