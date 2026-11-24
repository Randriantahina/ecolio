const ariary = new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 0 })

/** Ariary has no subunit (RG-R3): always whole numbers, grouped for readability. */
export function formatAr(amount: number): string {
  return `${ariary.format(amount).replace(/\s/g, ' ')} Ar`
}

export function formatDate(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  }).format(new Date(`${iso}T00:00:00`))
}

export function formatShortDate(iso: string): string {
  return new Intl.DateTimeFormat('fr-FR', { day: '2-digit', month: 'short' }).format(
    new Date(`${iso}T00:00:00`),
  )
}

/** `2026-09` -> `Septembre 2026` */
export function formatPeriode(periode: string): string {
  if (!/^\d{4}-\d{2}$/.test(periode)) return periode
  const label = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' }).format(
    new Date(`${periode}-01T00:00:00`),
  )
  return label.charAt(0).toUpperCase() + label.slice(1)
}

export function todayIso(): string {
  const d = new Date()
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
}

/** Months of a school year, `YYYY-MM`, first to last inclusive. */
export function monthsBetween(startIso: string, endIso: string): string[] {
  const months: string[] = []
  const [sy, sm] = startIso.split('-').map(Number)
  const [ey, em] = endIso.split('-').map(Number)
  for (let y = sy, m = sm; y < ey || (y === ey && m <= em); m === 12 ? ((m = 1), y++) : m++) {
    months.push(`${y}-${String(m).padStart(2, '0')}`)
  }
  return months
}
