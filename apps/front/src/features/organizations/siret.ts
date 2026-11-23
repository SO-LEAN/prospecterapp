const ESTABLISHMENT_PAGE = 'https://annuaire-entreprises.data.gouv.fr/etablissement/'

/** A SIRET as INSEE writes it: its SIREN in groups of three, then its establishment's number. */
export const spacedSiret = (siret: string) => siret.replace(/^(\d{3})(\d{3})(\d{3})(\d{5})$/, '$1 $2 $3 $4')

/** The establishment's page in the public company directory. */
export const establishmentPage = (siret: string) => `${ESTABLISHMENT_PAGE}${siret}`
