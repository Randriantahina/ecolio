/** The post-login target comes from the URL, so it must stay inside the app or phishing links could bounce users out. */
export function safeRedirect(value: string | null | undefined): string {
  if (typeof value !== 'string') return '/'
  const internal = value.startsWith('/') && !value.startsWith('//') && !value.includes('\\')
  return internal ? value : '/'
}
