# Phase 1 · Registre et écolage

Goal: what the directrice described (spec 0.1): registry by matricule, tuition/absence/departure checkboxes, numbered receipt. Legend: `[ ]` todo · `[~]` in progress · `[x]` done · `[!]` blocked. Test first, commit on `dev`. Order follows dependencies; copy the auth slice layout (Contract, Eloquent repo, Service, DTO, Request, Resource, Policy).

## Backend
1. [x] `Money` value object (integer ariary, non-negative, plus/minus/min/sum/compare; subtraction below zero throws) (feat, finance, done 2026-11-23)
2. [x] `compteurs` table (unique `ecole_id`+`cle`) + `CompteurRepository::nextValue` (insertOrIgnore, then `lockForUpdate`, increment); gapless incl. rollback; real concurrency NOT provable on SQLite, to verify on PostgreSQL (feat, finance, done 2026-11-23)
3. [x] Années scolaires: `/api/v1/annees-scolaires` list/create/update/`{uuid}/courante`; one current per school (RG-S1) enforced by service in one transaction AND a partial unique index; first year auto-current (my choice, not in spec); staff read, directeur writes, parents 403 (feat, scolarite, done 2026-11-24)
4. [ ] Niveaux and Classes (per year, `effectif_max` warns only, RG-I2) (feat, scolarite)
5. [ ] Matricule format per school (`ecoles.format_matricule`) + generator via compteurs (Strategy) (feat, eleves)
6. [ ] Élève core: model (`BelongsToEcole`, softDeletes), statuses, `UQ(ecole_id, matricule)`, immutable matricule (RG-E1), duplicate warning (RG-E4), list with search/filter/cursor pagination by matricule (feat, eleves)
7. [ ] Inscription: one active per year (RG-I1), class change history (feat, scolarite)
8. [ ] Types de frais + tarifs (per level or class) + échéance generation at enrolment (feat, finance)
9. [ ] Paiement + lignes + échéance status recompute in one transaction; partial (RG-P2), grouped (RG-P3); `client_uuid` idempotency; no line above balance (feat, finance)
10. [ ] Reçu: gapless `YYYY-NNNN` per school/year from compteurs (RG-R1); concurrency test; never edited (RG-R2) (feat, finance)
11. [ ] Annulation: directeur, or secretary with director validation (RG-P7), reason mandatory, receipt marked ANNULÉ, audit (feat, finance)
12. [ ] Registre écolage `GET /classes/{id}/registre-ecolage?periode=` (feat, finance)
13. [ ] Absences: batch `PUT /classes/{id}/absences`, unique (élève, date, période) (RG-P5) (feat, absences)
14. [ ] Départ: `POST /eleves/{id}/sortie`, future unpaid échéances become `annule`, owed stays owed (RG-P6) (feat, eleves)
15. [ ] Receipt PDF A5 (dompdf) with school identity and legal mention (RG-R4), word "reçu" not "facture" (RG-R6) (feat, finance)
16. [ ] Tenant-isolation test for every model above; policies per spec section 1

## Frontend
17. [ ] Settings screens: year, levels, classes, tariffs (directeur)
18. [ ] Élèves table (TanStack Table), search by matricule/name, create/edit form
19. [ ] Registre écolage screen: class + month, one checkbox per student; check = open minimal payment panel (amount prefilled, date, mode) = pay + receipt; 3 interactions max (CA M4)
20. [ ] Absences screen: class + date, "tous présents" then uncheck absentees
21. [ ] Départ action (date, reason, comment)
22. [ ] Receipt view/print/share (PDF)
23. [ ] Void flow with mandatory reason
24. [ ] Playwright journey: "encaisser en 3 interactions"

## Quality
- [ ] Receipt numbering test under concurrency, void, grouped, partial, idempotency (spec 12)
- [ ] `architecture.md` updated; phase DoD checked with the directrice's description (spec 0.1)

Definition of done: see `roadmap.md`. Open spec items to keep parametrable: fees per level, number of instalments, absence granularity (day vs half-day), who may void.
