# language: fr
Fonctionnalité: L'empreinte d'un échange
  En tant que prospecteur qui a branché une IA sur Prospecter
  Je veux qu'un e-mail ou un message déjà noté ne le soit pas une seconde fois, même supprimé
  Afin que l'IA synchronise ma boîte e-mail ou ma messagerie LinkedIn sans rien doublonner

  Contexte:
    Soit l'organisation "SOLEAN IT" qui utilise Prospecter
    Et l'organisation "ACME" appartient à "SOLEAN IT"
    Et le contact "Ada Lovelace" de "ACME"
    Et je suis connecté en tant que prospecteur de "SOLEAN IT"
    Et nous sommes le 18/11/2026 à 10h00

  Scénario: Un e-mail n'est noté qu'une fois
    Soit l'IA a noté l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h12 de Message-ID "<abc@acme.fr>"
    Quand l'IA note l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h15 de Message-ID "<ABC@acme.fr>"
    Alors c'est déjà noté, avec l'échange existant
    Et "Ada Lovelace" n'a qu'un échange

  Scénario: Un message LinkedIn n'est noté qu'une fois
    Soit l'IA a noté le message LinkedIn reçu de "Ada Lovelace" dans la conversation "2-abc" le 18/11/2026 à 9h12 : "Bonjour Michel !"
    Quand l'IA note le message LinkedIn reçu de "Ada Lovelace" dans la conversation "https://www.linkedin.com/messaging/thread/2-abc/" le 18/11/2026 à 9h12 : "bonjour  Michel"
    Alors c'est déjà noté, avec l'échange existant
    Et "Ada Lovelace" n'a qu'un échange

  Scénario: Deux messages différents de la même minute sont deux échanges
    Soit l'IA a noté le message LinkedIn reçu de "Ada Lovelace" dans la conversation "2-abc" le 18/11/2026 à 9h12 : "Bonjour"
    Quand l'IA note le message LinkedIn reçu de "Ada Lovelace" dans la conversation "2-abc" le 18/11/2026 à 9h12 : "Vous êtes dispo ?"
    Alors ce n'est pas refusé
    Et "Ada Lovelace" a 2 échanges

  Scénario: Un échange supprimé ne revient pas
    Soit l'IA a noté l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h12 de Message-ID "<abc@acme.fr>"
    Et j'ai supprimé l'échange noté par l'IA
    Quand l'IA note l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h12 de Message-ID "<abc@acme.fr>"
    Alors c'est supprimé par moi le 18/11/2026 à 10h00, non renoté
    Et "Ada Lovelace" a 0 échanges

  Scénario: Une référence d'un autre canal est refusée
    Quand l'IA note le SMS reçu de "Ada Lovelace" le 18/11/2026 à 9h12 de Message-ID "<abc@acme.fr>"
    Alors c'est refusé : "Only a message of the channel email has a messageId"
    Et "Ada Lovelace" a 0 échanges

  Scénario: Un échange noté à la main n'est pas renoté par l'IA
    Soit j'ai noté un SMS reçu de "Ada Lovelace" le 18/11/2026 à 9h12
    Quand l'IA note le SMS reçu de "Ada Lovelace" le 18/11/2026 à 9h12
    Alors c'est déjà noté, avec l'échange existant
    Et "Ada Lovelace" n'a qu'un échange

  Scénario: Noter à la main n'est jamais refusé comme doublon
    Soit j'ai noté un SMS reçu de "Ada Lovelace" le 18/11/2026 à 9h12
    Quand je note un SMS reçu de "Ada Lovelace" le 18/11/2026 à 9h12
    Alors ce n'est pas refusé
    Et "Ada Lovelace" a 2 échanges

  Scénario: Un message d'une organisation elle-même n'est noté qu'une fois
    Soit l'IA a noté le message Malt reçu de "ACME" elle-même le 18/11/2026 à 9h12 : "Bonjour"
    Quand l'IA note le message Malt reçu de "ACME" elle-même le 18/11/2026 à 9h12 : "bonjour !"
    Alors c'est déjà noté, avec l'échange existant

  Scénario: L'oubli d'un contact efface les empreintes de ses échanges
    Soit l'IA a noté l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h12 de Message-ID "<abc@acme.fr>"
    Et j'ai supprimé l'échange noté par l'IA
    Et l'IA a noté l'e-mail reçu de "Ada Lovelace" le 18/11/2026 à 9h20 de Message-ID "<def@acme.fr>"
    Quand j'efface le contact "Ada Lovelace"
    Alors le compte ne garde l'empreinte d'aucun échange
