export interface Utilisateur {
    uuid: string
    nom: string
    email: string | null
    telephone: string | null
    langue: string
    roles: string[]
    ecole: { uuid: string; nom: string } | null
}

/** Props shared with every page by HandleInertiaRequests. */
export interface SharedProps {
    [key: string]: unknown
    auth: { utilisateur: Utilisateur | null }
    navigation: string[]
    flash: { succes: string | null; avertissement: string | null }
}
