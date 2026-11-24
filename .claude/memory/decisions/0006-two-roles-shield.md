# 0006 · Two staff roles, Shield for fine-grained rights

Date: 2026-11-24 · Status: accepted

## Context
The client's school has a direction and teachers, nothing else (no parent accounts, no secretary tier). Teachers will later use a mobile app that only does absences and syncs with the web.

## Decision
Roles are `super_admin` (the owner of the platform), `administration` (the school direction, full school access) and `enseignant` (absences only). `Directeur`, `Secretaire`, `Parent` are removed. Fine-grained rights move to Filament Shield permissions (installed, bezhansalleh/filament-shield 4.3), managed from a roles page and a users page in the Paramètres cluster, like mintizy-v2.

## Why
Fewer roles match the real organisation; Shield lets the school adjust rights without code.

## Consequences
RG-P7 (secretary cannot void) and the director-only matricule/price rules collapse into `administration`. Policies still check roles until the Shield permissions are wired (next step). Parent portal (phase 5) will need its own decision if it returns.

## Rejected
Keeping the five spec roles: the client does not use them.
