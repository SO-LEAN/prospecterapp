# language: fr
Fonctionnalité: Écrire des observations sur une organisation
  En tant que prospecteur
  Je veux noter ce que je sais d'une organisation
  Afin de préparer mes prochains contacts

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et l'organisation "ACME" fait partie de mes organisations avec :
      | e-mail | contact@acme.test |

  Scénario: Les observations sont enregistrées, le reste ne change pas
    Quand j'écris les observations de "ACME" : "Rappeler en septembre"
    Alors l'organisation "ACME" est enregistrée avec :
      | observations | Rappeler en septembre |
      | e-mail       | contact@acme.test     |

  Scénario: Les observations peuvent être effacées
    Soit j'ai écrit les observations de "ACME" : "Rappeler en septembre"
    Quand j'efface les observations de "ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | observations | |
