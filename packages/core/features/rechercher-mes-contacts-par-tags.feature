# language: fr
@SearchContactsByTags
Fonctionnalité: Rechercher mes contacts par tags
  En tant que prospecteur
  Je veux retrouver les contacts qui portent tous les tags que je choisis
  Afin de préparer mes campagnes

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et l'organisation "Globex" appartient à "SOLEAN IT", taguée "#EDITEUR"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Grace Hopper" de "ACME"
    Et le contact "Alan Turing" de "Globex" a l'adresse e-mail "alan@globex.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et les tags calculés ont été reconstruits

  Scénario: Un seul tag, les contacts les plus récents d'abord
    Quand je cherche mes contacts tagués "#ESN"
    Alors la liste des contacts affiche :
      | prénom | nom      |
      | Grace  | Hopper   |
      | Ada    | Lovelace |

  Scénario: Plusieurs tags, tous portés
    Quand je cherche mes contacts tagués "#ESN #EMAIL"
    Alors la liste des contacts affiche :
      | prénom | nom      |
      | Ada    | Lovelace |

  Scénario: Un tag porté par le contact lui-même compte comme ceux qu'il hérite
    Soit "Grace Hopper" est tagué "#DECIDEUR"
    Quand je cherche mes contacts tagués "#ESN #DECIDEUR"
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Grace  | Hopper |

  Scénario: Aucun contact ne porte tous les tags
    Quand je cherche mes contacts tagués "#ESN #EDITEUR"
    Alors la liste des contacts est vide

  Scénario: Les contacts d'un autre compte ne sortent jamais
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent", taguée "#ESN"
    Et le contact "Linus Torvalds" de "Initech"
    Et les tags calculés ont été reconstruits
    Quand je cherche mes contacts tagués "#ESN"
    Alors "Linus Torvalds" n'est pas dans la liste

  Scénario: Sans le droit, je cherche par tags et vois leurs noms, leurs coordonnées masquées
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand je cherche mes contacts tagués "#ESN"
    Alors la liste des contacts affiche :
      | prénom | nom      | e-mail   | masquée |
      | Grace  | Hopper   |          | oui     |
      | Ada    | Lovelace | a***@*** | oui     |

  Scénario: Le nombre de contacts trouvés est donné
    Quand je cherche mes contacts tagués "#EMAIL"
    Alors 2 contacts sont trouvés

  Scénario: Les contacts trouvés viennent par tranches, tous comptés
    Quand je cherche mes contacts tagués "#ESN" par tranches de 1
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Grace  | Hopper |
    Et 2 contacts sont trouvés
    Quand je demande la tranche suivante
    Alors la liste des contacts affiche :
      | prénom | nom      |
      | Ada    | Lovelace |
    Et c'est la dernière tranche

  Scénario: Un contact effacé n'est plus trouvé par ses tags, avant même que ses tags soient oubliés
    Soit les réactions aux gestes n'ont pas encore suivi
    Quand j'efface la fiche de "Ada Lovelace"
    Et je cherche mes contacts tagués "#ESN" par tranches de 1
    Alors la liste des contacts affiche :
      | prénom | nom    |
      | Grace  | Hopper |
    Et c'est la dernière tranche
    Et 1 contact est trouvé

  Scénario: Le tag choisi est écrit comme un tag
    Quand je cherche mes contacts tagués "esn"
    Alors 2 contacts sont trouvés

  Scénario: On choisit de 1 à 5 tags
    Quand je cherche mes contacts tagués "#ESN #EMAIL #PHP #CLIENT #DECIDEUR #RH"
    Alors c'est refusé : "Choose from 1 to 5 tags"
