# language: fr
@ListCampaignRecipients
Fonctionnalité: Consulter les destinataires d'une campagne
  En tant que prospecteur
  Je veux voir les destinataires d'une campagne et ce que je peux faire de chacun
  Afin de suivre la campagne, et d'en retirer, d'en traiter ou d'en écarter

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT", taguée "#ESN"
    Et l'organisation "Globex" appartient à "SOLEAN IT", taguée "#EDITEUR"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Alan Turing" de "Globex" a l'adresse e-mail "alan@globex.test"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et les tags calculés ont été reconstruits
    Et la campagne "Relance" en brouillon, canal "E-mail", tags "#ESN"

  Scénario: Les destinataires d'une campagne figée, dans l'ordre où ils ont été ajoutés
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Quand je consulte les destinataires de la campagne "Relance"
    Alors les destinataires listés sont :
      | destinataire | statut    | échange |
      | Ada Lovelace | à traiter |         |
      | Alan Turing  | à traiter |         |
    Et sur ses destinataires, je peux :
      | retirer un destinataire |

  Scénario: Une campagne lancée offre de traiter ou d'écarter un destinataire à traiter
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Et la campagne "Relance" est démarrée
    Quand je consulte les destinataires de la campagne "Relance"
    Alors sur ses destinataires, je peux :
      | traiter ou écarter un destinataire |

  Scénario: Un destinataire traité mène à l'échange noté pour lui
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Et la campagne "Relance" est démarrée
    Et "Ada Lovelace" est traitée dans la campagne "Relance"
    Quand je consulte les destinataires de la campagne "Relance"
    Alors les destinataires listés sont :
      | destinataire | statut    | échange |
      | Ada Lovelace | traité    | noté    |
      | Alan Turing  | à traiter |         |

  Scénario: Un brouillon n'a aucun destinataire
    Quand je consulte les destinataires de la campagne "Relance"
    Alors aucun destinataire n'est listé

  Scénario: Un destinataire dont le contact vient d'être effacé n'est plus listé, sauf s'il est gardé comme effacé
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Et les réactions aux gestes n'ont pas encore suivi
    Quand j'efface la fiche de "Alan Turing"
    Et je consulte les destinataires de la campagne "Relance"
    Alors les destinataires listés sont :
      | destinataire | statut    | échange |
      | Ada Lovelace | à traiter |         |

  Scénario: Un destinataire effacé d'une campagne démarrée reste listé, sans son contact
    Soit la campagne "Relance" est figée avec "Ada Lovelace" et "Alan Turing"
    Et la campagne "Relance" est démarrée
    Quand j'efface la fiche de "Alan Turing"
    Et je consulte les destinataires de la campagne "Relance"
    Alors les destinataires listés sont :
      | destinataire   | statut         | échange |
      | Ada Lovelace   | à traiter      |         |
      | Contact effacé | plus à traiter |         |

  Scénario: Les destinataires d'une campagne inconnue sont introuvables
    Quand je consulte les destinataires d'une campagne inconnue
    Alors c'est introuvable, pour la raison "unknownCampaign"
