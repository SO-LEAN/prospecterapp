const ESTABLISHMENT_PAGE = 'https://annuaire-entreprises.data.gouv.fr/etablissement/'

/** A SIRET as INSEE writes it: its SIREN by groups of three, then the number of its establishment. */
export const spacedSiret = (siret: string) => siret.replace(/^(\d{3})(\d{3})(\d{3})(\d{5})$/, '$1 $2 $3 $4')

/** The page of the establishment in the public directory of the companies. */
export const establishmentPage = (siret: string) => `${ESTABLISHMENT_PAGE}${siret}`
