/**
 * فهرست فرمول‌های آفلاین، با کلید «شناسه@نسخه» همان موتور PHP.
 *
 * هر فرمول { compute(inputs) → {values, notes}, crossCheck?(inputs) → [{key, message}] }
 * است. ورودی‌ها پیش از رسیدن این‌جا اعتبارسنجی شده‌اند (عدد یا فهرست عدد).
 */
import chemical from './chemical.js';
import noise from './noise.js';
import ergonomics from './ergonomics.js';
import others from './others.js';

export const formulas = { ...noise, ...chemical, ...others, ...ergonomics };
