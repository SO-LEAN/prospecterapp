# language: fr
@ConsultOrganizationsOfContacts
Fonctionnalité: Consulter les organisations de mes contacts
  En tant que prospecteur
  Je veux voir d'un coup les organisations de plusieurs contacts
  Afin qu'une liste de contacts nomme l'organisation de chacun

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et l'organisation "Globex" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et le contact "Alan Turing" de "ACME" est aussi contact de "Globex"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"

  Scénario: Chaque contact vient avec ses organisations
    Quand je consulte les organisations de mes contacts :
      | Ada Lovelace |
      | Alan Turing  |
    Alors je vois les organisations de mes contacts :
      | contact      | organisations |
      | Ada Lovelace | ACME          |
      | Alan Turing  | ACME, Globex  |

  Scénario: Le contact d'un autre compte n'a aucune organisation pour moi
    Soit l'organisation "Concurrent" qui utilise Prospecter
    Et l'organisation "Initech" appartient à "Concurrent"
    Et le contact "Grace Hopper" de "Initech"
    Quand je consulte les organisations de mes contacts :
      | Grace Hopper |
    Alors je vois les organisations de mes contacts :
      | contact      | organisations |
      | Grace Hopper |               |
