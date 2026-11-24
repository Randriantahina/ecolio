import { onScopeDispose, ref, type Ref } from 'vue'

/** Reflects the browser's connectivity; the UI must always say whether work is being saved locally or live. */
export function useOnlineStatus(): Ref<boolean> {
    const online = ref(navigator.onLine)
    const setOnline = () => (online.value = true)
    const setOffline = () => (online.value = false)

    window.addEventListener('online', setOnline)
    window.addEventListener('offline', setOffline)
    onScopeDispose(() => {
        window.removeEventListener('online', setOnline)
        window.removeEventListener('offline', setOffline)
    })

    return online
}
