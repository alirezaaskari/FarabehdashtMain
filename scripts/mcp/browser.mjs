// Launches a browser MCP server (Playwright or Chrome DevTools) for Claude Code.
//
// Locally the servers use the installed Google Chrome. In Claude Code cloud
// sessions there is no Chrome, only Playwright's pre-installed Chromium, and the
// session runs as root, so we point both servers at that Chromium and turn off
// Chrome's sandbox. Never run `playwright install` there.
import { spawn } from 'node:child_process';
import { existsSync } from 'node:fs';

const CLOUD_CHROMIUM = '/opt/pw-browsers/chromium';
const cloud = existsSync(CLOUD_CHROMIUM);

const servers = {
    playwright: {
        pkg: '@playwright/mcp@0.0.82',
        args: ['--isolated', ...(cloud ? ['--headless', '--browser', 'chromium', '--no-sandbox'] : [])],
        env: cloud ? { PLAYWRIGHT_MCP_EXECUTABLE_PATH: CLOUD_CHROMIUM } : {},
    },
    devtools: {
        pkg: 'chrome-devtools-mcp@1.10.1',
        args: [
            '--isolated',
            '--no-usage-statistics',
            '--no-performance-crux',
            ...(cloud ? ['--headless', `--executablePath=${CLOUD_CHROMIUM}`, '--chromeArg=--no-sandbox'] : []),
        ],
        env: {},
    },
};

const server = servers[process.argv[2]];
if (!server) {
    console.error(`Usage: node scripts/mcp/browser.mjs <${Object.keys(servers).join('|')}>`);
    process.exit(2);
}

const child = spawn('npx', ['-y', server.pkg, ...server.args], {
    stdio: 'inherit',
    env: { ...process.env, ...server.env },
    shell: process.platform === 'win32',
});
child.on('exit', (code, signal) => (signal ? process.kill(process.pid, signal) : process.exit(code ?? 1)));
for (const sig of ['SIGINT', 'SIGTERM']) process.on(sig, () => child.kill(sig));
