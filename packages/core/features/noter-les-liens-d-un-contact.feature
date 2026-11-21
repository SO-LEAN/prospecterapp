# language: fr
Fonctionnalité: Noter les liens d'un contact
  En tant que prospecteur
  Je veux noter les liens d'un contact vers LinkedIn, Malt ou tout autre outil
  Afin de les ouvrir d'un clic depuis sa fiche, et de ne jamais l'enregistrer deux fois

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Le profil LinkedIn est une référence comme une autre
    Quand j'ajoute à "Ada Lovelace" le lien "fr.linkedin.com/in/Ada-Lovelace/"
    Alors "Ada Lovelace" a la référence LinkedIn "ada-lovelace"
    Et je vois la fiche de "Ada Lovelace" avec :
      | liens | https://fr.linkedin.com/in/Ada-Lovelace/ |

  Scénario: Un profil Malt est une référence
    Quand j'ajoute à "Ada Lovelace" le lien "https://www.malt.fr/profile/AdaLovelace"
    Alors "Ada Lovelace" a la référence Malt "adalovelace"

  Scénario: Une adresse d'un outil inconnu est acceptée
    Quand j'ajoute à "Ada Lovelace" le lien "https://github.com/AdaLovelace"
    Alors "Ada Lovelace" a la référence de source "github.com" et d'identifiant "github.com/adalovelace"

  Scénario: Ce qui n'est pas une adresse est refusé
    Quand j'ajoute à "Ada Lovelace" le lien "Ada sur LinkedIn"
    Alors c'est refusé : ""Ada sur LinkedIn" is not the address of a web page"

  Scénario: La page d'une entreprise ne désigne pas une personne
    Quand j'ajoute à "Ada Lovelace" le lien "https://www.linkedin.com/company/acme-corp"
    Alors c'est refusé : "A page of linkedInCompany designates an organization"

  Scénario: J'enregistre un contact avec ses liens
    Quand j'enregistre le contact "Grace Hopper" pour "ACME" :
      | liens | https://www.linkedin.com/in/grace-hopper https://www.malt.fr/profile/gracehopper |
    Alors "Grace Hopper" a la référence LinkedIn "grace-hopper"
    Et "Grace Hopper" a la référence Malt "gracehopper"

  Scénario: Un contact a cinq liens au plus
    Quand je change les coordonnées de "Ada Lovelace" :
      | liens | https://a.test/ada https://b.test/ada https://c.test/ada https://d.test/ada https://e.test/ada https://f.test/ada |
    Alors c'est refusé : "5 links at most"

  Scénario: Sans le droit, les liens d'un contact sont masqués
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je consulte la fiche de "Ada Lovelace"
    Alors ses liens sont masqués

  Scénario: Une référence ne désigne qu'une personne du compte
    Soit le contact "Grace Hopper" de "ACME"
    Et "Grace Hopper" a noté le lien "https://www.malt.fr/profile/adalovelace"
    Quand j'ajoute à "Ada Lovelace" le lien "https://www.malt.fr/profile/adalovelace"
    Alors c'est refusé : "Link already held by Grace Hopper"

  Scénario: Sans le droit, je n'apprends pas qui a un lien
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et le contact "Grace Hopper" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand j'ajoute à "Grace Hopper" le lien "https://www.linkedin.com/in/ada-lovelace"
    Alors c'est refusé : "Only the person or a member allowed to manage personal data may change them"

  Scénario: J'enregistre un contact avec le lien d'un autre contact
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Quand j'enregistre le contact "Grace Hopper" pour "ACME" :
      | liens | https://fr.linkedin.com/in/ada-lovelace |
    Alors c'est refusé : "Link already held by Ada Lovelace"

  Scénario: Une ancienne référence reconnaît toujours
    Soit "Ada Lovelace" a noté le lien "https://www.malt.fr/profile/adalovelace"
    Et le contact "Grace Hopper" de "ACME"
    Et je retire de "Ada Lovelace" le lien "https://www.malt.fr/profile/adalovelace"
    Quand j'ajoute à "Grace Hopper" le lien "https://www.malt.fr/profile/adalovelace"
    Alors c'est refusé : "Link already held by Ada Lovelace"

  Scénario: Un contact reprend une ancienne référence
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et je retire de "Ada Lovelace" le lien "https://www.linkedin.com/in/ada-lovelace"
    Quand j'ajoute à "Ada Lovelace" le lien "https://www.linkedin.com/in/ada-lovelace"
    Alors "Ada Lovelace" a la référence LinkedIn "ada-lovelace"

  Scénario: Seules les trois dernières anciennes références reconnaissent le contact
    Soit "Ada Lovelace" a noté le lien "https://a.test/ada"
    Et je change les coordonnées de "Ada Lovelace" :
      | liens | https://b.test/ada |
    Et je change les coordonnées de "Ada Lovelace" :
      | liens | https://c.test/ada |
    Et je change les coordonnées de "Ada Lovelace" :
      | liens | https://d.test/ada |
    Et je change les coordonnées de "Ada Lovelace" :
      | liens | https://e.test/ada |
    Et le contact "Grace Hopper" de "ACME"
    Quand j'ajoute à "Grace Hopper" le lien "https://a.test/ada"
    Alors "Grace Hopper" a le lien "https://a.test/ada"

  Scénario: Effacer un contact libère ses références, actuelles et anciennes
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et je retire de "Ada Lovelace" le lien "https://www.linkedin.com/in/ada-lovelace"
    Et "Ada Lovelace" a noté le lien "https://www.malt.fr/profile/adalovelace"
    Et le contact "Grace Hopper" de "ACME"
    Et j'efface la fiche de "Ada Lovelace"
    Quand j'ajoute à "Grace Hopper" le lien "https://www.linkedin.com/in/ada-lovelace"
    Et j'ajoute à "Grace Hopper" le lien "https://www.malt.fr/profile/adalovelace"
    Alors "Grace Hopper" a la référence LinkedIn "ada-lovelace"
    Et "Grace Hopper" a la référence Malt "adalovelace"

  Scénario: Les doublons d'avant la règle ne bloquent rien
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et le contact "Grace Hopper" de "ACME"
    Et "Grace Hopper" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Quand je change les coordonnées de "Grace Hopper" :
      | téléphone | 03 88 00 00 42                           |
      | liens     | https://www.linkedin.com/in/ada-lovelace |
    Alors je vois la fiche de "Grace Hopper" avec :
      | téléphone | +33 3 88 00 00 42                        |
      | liens     | https://www.linkedin.com/in/ada-lovelace |

  Scénario: Le lien d'un membre peut être celui d'un contact ou d'un collègue
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et "Grace" est membre de "SOLEAN IT" avec le profil :
      | liens | https://www.linkedin.com/in/ada-lovelace |
    Quand je change mes coordonnées :
      | liens | https://www.linkedin.com/in/ada-lovelace |
    Alors ce n'est pas refusé

  Scénario: Un autre compte peut noter le même lien
    Soit "Ada Lovelace" a noté le lien "https://www.linkedin.com/in/ada-lovelace"
    Et l'organisation "Initech" qui utilise Prospecter
    Et l'organisation "Globex" appartient à "Initech"
    Et le contact "Grace Hopper" de "Globex"
    Et je suis connecté en tant que prospecteur de "Initech"
    Quand j'ajoute à "Grace Hopper" le lien "https://www.linkedin.com/in/ada-lovelace"
    Alors "Grace Hopper" a la référence LinkedIn "ada-lovelace"
