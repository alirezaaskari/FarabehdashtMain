# Vendored design skills

Claude Code loads every skill in this folder in each session (local or cloud).
They are third-party guidance, copied as plain Markdown and reviewed before adding.

**Precedence:** the design-layer rules in `CLAUDE.md` (semantic colour classes only,
logical `ps-*`/`me-*` properties, `data-numeric`, `@fa()`, light-only public pages,
44px touch targets, the `text-*` semantic scale, full-width `px-gutter`, one site shell)
and the product rules always win. Where a skill below disagrees (e.g. suggests dark
mode on public pages, Google Fonts, new npm packages, hex colours or physical
`left`/`right` properties), follow `CLAUDE.md`. All UI is Persian and right-to-left.

| Skill | Source | Version | License | What was kept |
|---|---|---|---|---|
| `impeccable` | [pbakaus/impeccable](https://github.com/pbakaus/impeccable) | 4.4.0 (`9d715cc`) | Apache-2.0 (`LICENSE`, `NOTICE.md`) | `SKILL.md` + `reference/*.md`. Setup step 1 rewritten for this repo. |
| `redesign-existing-projects` | [Leonxlnx/taste-skill](https://github.com/Leonxlnx/taste-skill) `skills/redesign-skill` | `c184364` | MIT (`LICENSE`) | Unchanged. |

## Deliberately left out

- **Impeccable launcher, scripts and hooks.** The launcher downloads a prebuilt binary on
  first run and the plugin runs it after every edit; that is unreviewed code executing in
  our sessions. Without it, `live`, `hooks`, `pin`, `doctor` and the automatic detector do
  not work; every other command (`audit`, `critique`, `polish`, `typeset`, `layout`,
  `harden`, `clarify`, `adapt`, ...) is plain guidance and works.
- **Taste's main `design-taste-frontend` skill.** Written for React/Next.js landing pages,
  mandates dark mode on consumer pages and pushes npm component libraries; it conflicts with
  this stack and `CLAUDE.md`. Only its framework-agnostic redesign audit is kept.
- **Awesome DESIGN.md** ([VoltAgent/awesome-design-md](https://github.com/VoltAgent/awesome-design-md)).
  Those files describe other brands (Notion, Stripe, ...); dropping one in would fight our
  own design system. Our design system lives in `CLAUDE.md` and `resources/css/tokens.css`.
- **img2threejs.** Image to Three.js model; the product has no 3D.
- **Playwright CLI.** Already available: Chromium and Playwright are preinstalled in cloud
  sessions and CI runs `npm run a11y` in a browser.

To update a skill: re-copy the kept files from the upstream commit, re-apply the Impeccable
setup edit, re-read the diff, and bump the table above.
