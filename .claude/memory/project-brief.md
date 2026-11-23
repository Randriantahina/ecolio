# Project brief

**Écolio**: multi-tenant PWA for private-school management, sold by subscription. Owner: the porteur (solo, part-time, 12 to 15 h/week). First client: one directrice (single phone call, hypotheses everywhere, no price agreed).

- Core need (spec 0.1): registry by matricule; checkboxes for tuition paid / absence / departure; auto-generated receipt; Excel import.
- Stack imposed: Laravel API, Vue 3, Tailwind, shadcn-vue, PWA.
- Market: Madagascar (ariary integer, French UI, Malagasy second, unstable network and power).
- Source of truth: `docs/SPEC_ecolio_2026-11-18_v2.md`. `[Hypothèse]` and `[À confirmer]` items are not facts.
- The word "facture" is avoided: the document is a "reçu de paiement" (RG-R6).
