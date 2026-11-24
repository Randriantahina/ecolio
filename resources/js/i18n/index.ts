import { createI18n } from 'vue-i18n'
import { fr } from './fr'

export function createAppI18n() {
    return createI18n({
        legacy: false,
        locale: 'fr',
        fallbackLocale: 'fr',
        messages: { fr },
        // A missing key renders as the key itself; warnings would flood test output.
        missingWarn: false,
        fallbackWarn: false,
    })
}
