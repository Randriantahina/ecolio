import 'fake-indexeddb/auto'
import Dexie from 'dexie'
import { afterEach, beforeEach, describe, expect, it } from 'vitest'
import { EcolioDb, type QueuedOperation } from './db'

let db: EcolioDb

function operation(overrides: Partial<QueuedOperation> = {}): QueuedOperation {
  return {
    clientUuid: crypto.randomUUID(),
    type: 'absence.mark',
    payload: { eleve: 'e-1' },
    createdAt: Date.now(),
    status: 'pending',
    attempts: 0,
    ...overrides,
  }
}

beforeEach(async () => {
  db = new EcolioDb(`ecolio-test-${crypto.randomUUID()}`)
  await db.open()
})

afterEach(async () => {
  db.close()
  await Dexie.delete(db.name)
})

describe('EcolioDb', () => {
  it('starts with an empty write queue', async () => {
    expect(await db.queue.count()).toBe(0)
  })

  it('stores an operation and gives it back unchanged', async () => {
    const op = operation({ clientUuid: 'c-1' })
    await db.queue.add(op)

    const stored = await db.queue.where('clientUuid').equals('c-1').first()
    expect(stored?.payload).toEqual({ eleve: 'e-1' })
    expect(stored?.status).toBe('pending')
  })

  it('refuses the same client_uuid twice, mirroring the server idempotency key so a retry never doubles a payment', async () => {
    await db.queue.add(operation({ clientUuid: 'dup' }))

    await expect(db.queue.add(operation({ clientUuid: 'dup' }))).rejects.toThrow()
    expect(await db.queue.count()).toBe(1)
  })

  it('replays in the order the operations were made', async () => {
    await db.queue.bulkAdd([
      operation({ clientUuid: 'third', createdAt: 300 }),
      operation({ clientUuid: 'first', createdAt: 100 }),
      operation({ clientUuid: 'second', createdAt: 200 }),
    ])

    const ordered = await db.queue.orderBy('createdAt').toArray()
    expect(ordered.map((o) => o.clientUuid)).toEqual(['first', 'second', 'third'])
  })

  it('finds what is still waiting to be sent', async () => {
    await db.queue.bulkAdd([
      operation({ clientUuid: 'a', status: 'pending' }),
      operation({ clientUuid: 'b', status: 'conflict' }),
    ])

    const pending = await db.queue.where('status').equals('pending').toArray()
    expect(pending.map((o) => o.clientUuid)).toEqual(['a'])
  })
})
