# Workflow: TDD first, every change tested

Applies to all code in this repository.

## Red → Green → Refactor (mandatory)
1. **Red**: write the test first and run it; confirm it fails for the expected reason.
2. **Green**: write the minimum code to pass. No speculative features.
3. **Refactor**: clean up with the suite green, then commit.

Never write production code without a failing test that demands it. Bug fix = first a test that reproduces the bug.

## Every unit of code has a test
- Backend: every class under `app/` (Service, Action, Repository, Policy, FormRequest, Resource, DTO, Value Object, Job, route) has a **Pest Feature or Unit test**.
  - Feature (`tests/Feature/<Context>/`): HTTP endpoints, DB-backed flows, policies, repositories, jobs.
  - Unit (`tests/Unit/<Context>/`): pure logic (value objects, calculators, DTOs, enums, services with mocked repositories).
- Filament: every Resource, Page, Widget and action has a Pest test with `Livewire::test(...)` (render, create/edit through the Service, authorization per role, tenant isolation).
- A change adding a file without its test is incomplete. A task is not done until `pest` is green.

## Test quality
- Test behaviour, not implementation. Names state the business rule (`it('refuses a payment above the remaining balance')`).
- Arrange/Act/Assert, one reason to fail per test, no shared mutable state, no order dependence.
- Use factories/builders; never real student data in fixtures.
- Mock only at architectural boundaries (repository interface, HTTP gateway, clock). Never mock the class under test; never mock the database in Feature tests.
- Freeze time (`Carbon::setTestNow`, `vi.useFakeTimers`) for anything date-dependent.
- Mandatory families (spec section 12): tenant isolation per model, receipt numbering (concurrency, void, grouped, partial, idempotency), grade calculations, import validation.

## Before declaring done
`./vendor/bin/pest` and `./vendor/bin/pint --test`. Report the real output; if something fails or was skipped, say so.
