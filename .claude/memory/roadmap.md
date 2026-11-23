# Roadmap (source: docs/SPEC_ecolio_2026-11-18_v2.md section 11)

Tick a phase only when its **definition of done** is met: tests green on the 4 families that exist so far (tenant isolation, receipts, grades, import), demo on fictional data, no blocking bug on the main journey. Task detail lives in `phases/`.

- [x] **Bootstrap** · repo, toolchain, CI, rules, memory (done 2026-11-23)
- [ ] **Phase 0 · Fondations** · auth, roles, tenancy, audit, installable PWA skeleton · DoD: a directeur logs in, isolation proven by tests · `phases/phase-0-fondations.md`
- [ ] **Phase 1 · Registre et écolage** · years/levels/classes, students (core), tariffs, échéances, tuition checkboxes, numbered PDF receipts, absences, departures · DoD: what the directrice described (spec 0.1)
- [ ] **Phase 2 · Dynamique et import** · custom fields, saved views, Excel import wizard · DoD: a real school Excel imported with < 10 % manual fixes (real file not yet seen)
- [ ] **Phase 3 · Inscriptions** · tutors, siblings, re-enrolment, class promotion, dashboard · DoD: a simulated school start
- [ ] **Phase 4 · Notes et bulletins** · subjects, evaluations, grades, calculations, report-card PDF · DoD: one full term on one class
- [ ] **Phase 5 · Parents** · communications, parent portal, reminders · DoD: an unpaid-fee reminder sent and traced
- [ ] **Phase 6 · Hors ligne financier** · queue, provisional numbers, conflicts · DoD: a tuition day without network, resynced (may be pulled forward)
- [ ] **Transverse** · Filament back-office, subscriptions, backups, monitoring · DoD: first invoiced subscription

Rules: one phase active at a time; do not start a phase before the previous DoD is met unless the user says so.
