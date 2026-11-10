# language: fr
Fonctionnalité: Arrêter de prospecter une organisation
  En tant que prospecteur
  Je veux retirer une organisation de la base de prospection de mon compte
  Afin de ne garder que les organisations que nous prospectons, et pas les données de leurs contacts

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et l'organisation "Groupe ACME" appartient à "SOLEAN IT"

  Scénario: L'organisation n'est plus prospectée
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Quand j'arrête de prospecter "ACME"
    Et je consulte l'organisation "ACME"
    Alors c'est introuvable

  Scénario: Ses filiales n'ont plus de holding
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et "ACME" est rattachée à la holding "Groupe ACME"
    Quand j'arrête de prospecter "Groupe ACME"
    Alors l'organisation "ACME" est enregistrée avec :
      | holding | |

  Scénario: Ses contacts sans autre organisation sont effacés, les autres restent
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME" a l'adresse e-mail "ada@acme.test"
    Et le contact "Grace Hopper" de "ACME" a l'adresse e-mail "grace@navy.test"
    Et "Grace Hopper" est un contact de "Groupe ACME"
    Quand j'arrête de prospecter "ACME"
    Et je consulte la fiche de "Ada Lovelace"
    Alors c'est introuvable
    Quand je consulte la fiche de "Grace Hopper"
    Alors je vois la fiche :
      | e-mail | grace@navy.test |

  Scénario: Sans le droit, une organisation sans contact s'arrête quand même
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT" sans le droit aux données personnelles
    Quand j'arrête de prospecter "ACME"
    Alors ce n'est pas refusé

  Scénario: Chaque échange est effacé par sa propre réaction
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté 3 échanges avec "ACME" elle-même
    Quand j'arrête de prospecter "ACME"
    Alors 3 échanges à effacer sont publiés
    Et ses 3 échanges sont effacés

  Scénario: Un échange déjà effacé ne fait pas échouer la réaction
    Soit je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et j'ai noté un appel reçu de "ACME" elle-même
    Et j'arrête de prospecter "ACME"
    Quand l'échange à effacer est publié de nouveau
    Alors ce n'est pas refusé
    Et rien de plus n'est écrit
