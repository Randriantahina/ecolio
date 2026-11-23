/**
 * Typed failures so ViewModels branch on meaning (offline, locked, invalid) instead of parsing status codes.
 * Fields are declared explicitly: the project forbids constructor parameter properties (erasableSyntaxOnly).
 */
export class ApiError extends Error {
  readonly status: number

  constructor(message: string, status: number) {
    super(message)
    this.name = new.target.name
    this.status = status
  }
}

export class ValidationError extends ApiError {
  readonly errors: Record<string, string[]>

  constructor(message: string, errors: Record<string, string[]>) {
    super(message, 422)
    this.errors = errors
  }
}

export class UnauthorizedError extends ApiError {
  constructor(message = 'Unauthorized') {
    super(message, 401)
  }
}

export class LockedError extends ApiError {
  constructor(message: string) {
    super(message, 423)
  }
}

/** No answer at all: the device is offline or the server is unreachable. Distinct from a server refusal. */
export class NetworkError extends Error {
  constructor(message = 'Network unreachable') {
    super(message)
    this.name = 'NetworkError'
  }
}
