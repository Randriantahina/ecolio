# Comments: explain WHY, never HOW

Applies to all code (PHP, TypeScript, Vue, SQL, config). Language of comments: **English**.

## Principle
The code already says *how*. A comment earns its place only by saying what the code cannot: **why** it exists, which business rule or constraint forces it, which trap it avoids, or which decision was made over which alternative.

## Write a comment when
- A **business rule** drives the code: reference the spec (`RG-R1`, `RG-P4`, section 8.3) and state the reason.
- The code looks wrong or odd but is deliberate (workaround, ordering that matters, lock, rounding choice).
- A **non-obvious constraint** applies: concurrency, idempotency, offline replay, tenancy, data-protection.
- A **trade-off or rejected alternative** is worth remembering ("not a DB per school: too costly to operate for a solo owner").
- A **known limit or temporary decision** exists: `TODO(phase-4): ...` with the reason and the trigger to revisit.

## Do not write
- Restating the code: `// increment counter`, `// loop over students`, `// returns the receipt`.
- Section banners, commented-out code, changelog or author notes (git has history).
- Docblocks that only repeat types or parameter names already in the signature. Keep a docblock only for contract info the types cannot express (invariants, thrown domain exceptions, units like "ariary, integer").
- Comments that will rot: if the explanation depends on a line-by-line mechanism, simplify or rename the code instead.

## Before commenting, try first
Rename, extract a well-named function, introduce a value object or enum. Comment only what remains unexplained.

## Examples
Bad (how):
```php
// lock the row then add one to the value
$counter = Compteur::lockForUpdate()->first();
```
Good (why):
```php
// RG-R1: receipt numbers must be gapless per school/year; the row lock serialises
// concurrent cashiers so two payments can never take the same number.
$counter = $this->compteurs->lockedFor($ecoleId, "recu:{$annee}");
```
Good (trap):
```ts
// Absent is not zero (RG-N5): excluding it keeps the average honest for pupils who missed the test.
```

## Tests
Test names carry the rule; add a comment only for non-obvious fixture reasons ("two schools share the same matricule to prove the unique index is composite").
