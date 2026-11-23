import { z } from 'zod'

/** Mirrors the API's UtilisateurResource. Parsed at the boundary so a drifting API fails loudly here, not in a view. */
export const utilisateurSchema = z.object({
  uuid: z.string(),
  nom: z.string(),
  email: z.string().nullable(),
  telephone: z.string().nullable(),
  langue: z.string(),
  roles: z.array(z.string()),
  // Null for the platform super admin, who belongs to no school.
  ecole: z.object({ uuid: z.string(), nom: z.string() }).nullable(),
})

export const loginResponseSchema = z.object({
  token: z.string(),
  utilisateur: utilisateurSchema,
})

export const meResponseSchema = z.object({ utilisateur: utilisateurSchema })

export type Utilisateur = z.infer<typeof utilisateurSchema>
export type LoginResponse = z.infer<typeof loginResponseSchema>
