import { initAdvisor } from './advisor';
import { initCountdowns } from './countdown';
import { initExamTimers } from './exam-timer';
import { initForms } from './forms';
import { initMenus } from './menu';
import { initMotion } from './motion';
import { initPwa } from './pwa';
import { initQuickConvert } from './quick-convert';
import { initReading } from './reading';
import { initSearch } from './search';
import { initTheme } from './theme';
import { initToolMemory } from './tool-memory';

initTheme();
initCountdowns();
initExamTimers();
initMenus();
initForms();
initAdvisor();
initSearch();
initReading();
initQuickConvert();
initPwa();
initToolMemory();
initMotion();

// فرم گام‌به‌گام فقط در صفحه ابزارهای پوسچر؛ با موتور آفلاین جدا بار می‌شود.
const toolSteps = document.querySelector('[data-tool-steps]');
if (toolSteps) {
    import('./tool-steps.js').then(({ initToolSteps }) => initToolSteps(toolSteps));
}
