/** French is the product language; keys are grouped by feature. Malagasy (mg) is added by the same shape later. */
export const fr = {
  app: { name: 'Écolio' },
  network: { online: 'En ligne', offline: 'Hors ligne' },
  home: { welcome: 'Bonjour, {nom}', school: 'École : {nom}' },
  auth: {
    login: {
      title: 'Connexion',
      subtitle: 'Accédez à la gestion de votre école',
      login: 'E-mail ou téléphone',
      password: 'Mot de passe',
      submit: 'Se connecter',
      submitting: 'Connexion…',
    },
    errors: {
      locked: 'Compte verrouillé temporairement. Réessayez dans quelques minutes.',
      offline: 'Pas de connexion. Vérifiez votre réseau et réessayez.',
      unexpected: 'Une erreur est survenue. Réessayez.',
    },
  },
}

export type MessageSchema = typeof fr
