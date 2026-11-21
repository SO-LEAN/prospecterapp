# language: fr
Fonctionnalité: Reconnaître ce qu'une IA a créé
  En tant que prospecteur qui a branché une IA sur Prospecter
  Je veux voir d'un coup d'œil les organisations, les contacts et les échanges qu'elle a créés pour moi
  Afin de les relire, sans que l'IA soit nommée : Prospecter sait qu'une IA a agi, pas laquelle

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le "2026-11-18 10:00"

  Scénario: Un échange noté par une IA porte le badge
    Soit l'IA a noté le message LinkedIn reçu de "Ada Lovelace" dans la conversation "2-abc" le 18/11/2026 à 9h12 : "Bonjour Michel !"
    Et j'ai noté un e-mail reçu de "Ada Lovelace" le 17/11/2026 à 10h00
    Quand je consulte la timeline de "Ada Lovelace"
    Alors la timeline de "Ada Lovelace" est, du plus récent au plus ancien :
      | canal    | badge |
      | LinkedIn | MCP   |
      | E-mail   |       |

  Scénario: L'échange noté par une IA porte le badge en détail, son membre en est l'auteur
    Soit l'IA a noté le message LinkedIn reçu de "Ada Lovelace" dans la conversation "2-abc" le 18/11/2026 à 9h12 : "Bonjour Michel !"
    Quand je consulte cet échange
    Alors je vois l'échange :
      | badge | MCP                           |
      | noté  | le 18/11/2026 à 10:00 par moi |

  Scénario: Un échange noté par une IA avec l'organisation elle-même porte le badge
    Soit l'IA a noté le message Malt reçu de "ACME" elle-même le 18/11/2026 à 9h12 : "Bonjour"
    Quand je consulte cet échange
    Alors je vois l'échange :
      | badge | MCP |

  Scénario: Un échange planifié par une IA porte le badge
    Soit l'IA a planifié une visio avec "Ada Lovelace" le 20/11/2026 à 14h00
    Quand je consulte cet échange
    Alors je vois l'échange :
      | badge | MCP |

  Scénario: Un échange planifié par une IA avec l'organisation elle-même porte le badge
    Soit l'IA a planifié un appel avec "ACME" le 20/11/2026 à 14h00
    Quand je consulte cet échange
    Alors je vois l'échange :
      | badge | MCP |

  Scénario: Corriger à la main un échange noté par une IA lui garde son badge
    Soit l'IA a noté le message LinkedIn reçu de "Ada Lovelace" dans la conversation "2-abc" le 18/11/2026 à 9h12 : "Bonjour Michel !"
    Et nous sommes le "2026-11-18 11:00"
    Quand je corrige le texte de cet échange en "Bonjour Michel, à demain !"
    Alors je vois l'échange :
      | texte   | Bonjour Michel, à demain !    |
      | badge   | MCP                           |
      | modifié | le 18/11/2026 à 11:00 par moi |
