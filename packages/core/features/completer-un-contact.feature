# language: fr
Fonctionnalité: Compléter un contact sans rien écraser
  En tant que prospecteur avec le droit de gérer les données personnelles
  Je veux qu'une IA complète ce qui manque à un contact que Prospecter connaît déjà
  Afin de ne jamais perdre ce que j'ai saisi

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Ce qui est vide est complété
    Quand je complète le contact "Ada Lovelace" :
      | fonction  | CTO                                      |
      | e-mail    | ada@acme.fr                              |
      | téléphone | 06 12 34 56 78                           |
      | liens     | https://www.linkedin.com/in/ada-lovelace |
      | Civilité  | Madame                                   |
    Alors je vois la fiche de "Ada Lovelace" avec :
      | Civilité  | Madame                                   |
      | fonction  | CTO                                      |
      | e-mail    | ada@acme.fr                              |
      | téléphone | +33 6 12 34 56 78                        |
      | liens     | https://www.linkedin.com/in/ada-lovelace |

  Scénario: Ce qui est noté reste, un lien de plus s'ajoute aux autres
    Soit la fonction de "Ada Lovelace" est "Directrice technique"
    Et "Ada Lovelace" a la civilité "Madame"
    Et "Ada Lovelace" a noté le lien "https://www.malt.fr/profile/adalovelace"
    Quand je complète le contact "Ada Lovelace" :
      | fonction | CTO                                      |
      | liens    | https://www.linkedin.com/in/ada-lovelace |
      | Civilité | Monsieur                                 |
    Alors je vois la fiche de "Ada Lovelace" avec :
      | Civilité | Madame                                                                            |
      | fonction | Directrice technique                                                              |
      | liens    | https://www.malt.fr/profile/adalovelace https://www.linkedin.com/in/ada-lovelace |

  Scénario: Une adresse e-mail et un téléphone notés restent
    Soit le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@acme.fr"
    Et le contact "Alan Turing" de "ACME" a le téléphone "03 88 00 00 00"
    Quand je complète le contact "Grace Hopper" :
      | e-mail    | grace.hopper@acme.fr |
      | téléphone | 06 12 34 56 78       |
    Et je complète le contact "Alan Turing" :
      | e-mail    | alan@acme.fr   |
      | téléphone | 06 98 76 54 32 |
    Alors je vois la fiche de "Grace Hopper" avec :
      | e-mail    | grace@acme.fr     |
      | téléphone | +33 6 12 34 56 78 |
    Et je vois la fiche de "Alan Turing" avec :
      | e-mail    | alan@acme.fr      |
      | téléphone | +33 3 88 00 00 00 |

  Scénario: Au-delà de cinq liens, les autres ne sont pas notés
    Soit "Ada Lovelace" a noté le lien "https://a.test/ada"
    Et "Ada Lovelace" a noté le lien "https://b.test/ada"
    Et "Ada Lovelace" a noté le lien "https://c.test/ada"
    Et "Ada Lovelace" a noté le lien "https://d.test/ada"
    Quand je complète le contact "Ada Lovelace" :
      | liens | https://e.test/ada https://f.test/ada |
    Alors ce n'est pas refusé
    Et je vois la fiche de "Ada Lovelace" avec :
      | liens | https://a.test/ada https://b.test/ada https://c.test/ada https://d.test/ada https://e.test/ada |

  Scénario: Un lien déjà noté ne prend pas la place d'un nouveau
    Soit "Ada Lovelace" a noté le lien "https://a.test/ada"
    Et "Ada Lovelace" a noté le lien "https://b.test/ada"
    Et "Ada Lovelace" a noté le lien "https://c.test/ada"
    Et "Ada Lovelace" a noté le lien "https://d.test/ada"
    Quand je complète le contact "Ada Lovelace" :
      | liens | http://a.test/ADA https://e.test/ada |
    Alors je vois la fiche de "Ada Lovelace" avec :
      | liens | https://a.test/ada https://b.test/ada https://c.test/ada https://d.test/ada https://e.test/ada |

  Scénario: Sans le droit, je ne complète pas un contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je complète le contact "Ada Lovelace" :
      | fonction | CTO |
    Alors c'est refusé : "Only a member allowed to manage personal data may complete a contact"

  Scénario: Une adresse e-mail d'un autre contact est refusée
    Soit le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@acme.fr"
    Quand je complète le contact "Ada Lovelace" :
      | e-mail | grace@acme.fr |
    Alors c'est refusé : "Email already used"

  Scénario: Un lien d'un autre contact est refusé
    Soit le contact "Grace Hopper" de "ACME"
    Et "Grace Hopper" a noté le lien "https://www.linkedin.com/in/grace-hopper"
    Quand je complète le contact "Ada Lovelace" :
      | liens | https://www.linkedin.com/in/grace-hopper |
    Alors c'est refusé : "Link already held by Grace Hopper"

  Scénario: Le contact complété est publié joignable par ce qu'il a de plus
    Quand je complète le contact "Ada Lovelace" :
      | e-mail | ada@acme.fr |
    Alors le fait publié du contact "Ada Lovelace" le dit joignable par "e-mail"

  Scénario: Rien à compléter, rien ne change
    Soit la fonction de "Ada Lovelace" est "Directrice technique"
    Quand je complète le contact "Ada Lovelace" :
      | fonction | CTO |
    Alors ce n'est pas refusé
    Et aucun fait de contact n'est publié

  Scénario: Avant de compléter, je sais ce qui serait complété et ce qui diffère
    Soit la fonction de "Ada Lovelace" est "Directrice technique"
    Quand je compare le contact "Ada Lovelace" à :
      | fonction | CTO                                      |
      | liens    | https://www.linkedin.com/in/ada-lovelace |
    Alors serait complété : "liens"
    Et diffère : "fonction"

  Scénario: Une même adresse e-mail ne diffère pas, une autre si
    Soit le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@acme.fr"
    Quand je compare le contact "Grace Hopper" à :
      | e-mail    | GRACE@acme.fr  |
      | téléphone | 06 12 34 56 78 |
    Alors serait complété : "téléphone"
    Et diffère : ""

  Scénario: Les liens sans place diffèrent
    Soit "Ada Lovelace" a noté le lien "https://a.test/ada"
    Et "Ada Lovelace" a noté le lien "https://b.test/ada"
    Et "Ada Lovelace" a noté le lien "https://c.test/ada"
    Et "Ada Lovelace" a noté le lien "https://d.test/ada"
    Quand je compare le contact "Ada Lovelace" à :
      | liens | https://a.test/ada https://e.test/ada https://f.test/ada |
    Alors serait complété : "liens"
    Et diffère : "liens"

  Scénario: Sans le droit, je ne compare pas un contact
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je compare le contact "Ada Lovelace" à :
      | fonction | CTO |
    Alors c'est refusé : "Only a member allowed to manage personal data may complete a contact"
