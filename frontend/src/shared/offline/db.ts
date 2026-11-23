import Dexie, { type Table } from 'dexie'

export type OperationStatus = 'pending' | 'failed' | 'conflict'

/**
 * A write made while offline (spec 8.3). `clientUuid` is the idempotency key sent to the server:
 * a replay after a network cut must never create a second payment or absence.
 */
export interface QueuedOperation {
  id?: number
  clientUuid: string
  type: string
  payload: unknown
  createdAt: number
  status: OperationStatus
  attempts: number
}

export class EcolioDb extends Dexie {
  // `declare`, not an initialised field: a class field would overwrite the table Dexie installs.
  declare queue: Table<QueuedOperation, number>

  constructor(name = 'ecolio') {
    super(name)
    // `&clientUuid` = unique index; `createdAt` keeps replay in the order the user acted.
    this.version(1).stores({ queue: '++id, &clientUuid, status, createdAt' })
  }
}
