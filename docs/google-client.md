# The Google client of an account

What the administrator of an account sets up in the Google Cloud of their organization so that its members connect Prospecter to Google (ADR 0125), step by step. Every name below in angle brackets is theirs to choose or read in their console; none of them is written in this repository (ADR 0059).

## What it takes

- A **Google Workspace** organization: the client is an *internal* app of it, which only the accounts of the Workspace may connect to, and which Google neither verifies nor audits, whatever its scopes.
- A **gmail.com** address has no organization, so no internal app: its client is an *external* app left in *Testing*, its test users listed by hand (100 at most), and Google makes its tokens expire after **7 days**: the connection of each member is « to reconnect » every week, the calendar included. Publishing an external app asking `gmail.readonly`, a *restricted* scope, takes Google's verification and a yearly security assessment (CASA) by an approved firm: Prospecter does not support it. The import of Gmail is meant for a Workspace.
- In the Google Cloud console, a **project** of the organization, the administrator holding the roles to create a client, enable APIs, and create topics, subscriptions and service accounts in it (Owner of the project does).

## 1. The client (the calendar)

1. Select the project, then *APIs & Services › Library*: enable the **Google Calendar API**.
2. *Google Auth Platform › Branding*: the name of the app (« Prospecter »), a support email; *Audience*: user type **Internal**.
3. *Data Access*: add the scopes `openid`, `.../auth/userinfo.email` and `.../auth/calendar.app.created`.
4. *Clients › Create client*: type **Web application**; under *Authorized redirect URIs*, the redirect URI the card « Client Google du compte » shows (« Adresse de retour à déclarer dans le client ») (the address of the SPA followed by `/settings/connectors/google`).
5. Copy the **client ID** and the **client secret** into Prospecter: *Configuration › Connecteurs*, card « Client Google du compte », as an administrator of the account. The secret is never shown again.

## 2. The import of Gmail

The steps the import of the e-mails asks (ADR 0154).

1. *APIs & Services › Library*: enable the **Gmail API** and the **Cloud Pub/Sub API**.
2. *Google Auth Platform › Data Access*: add the scope `.../auth/gmail.readonly` (read the messages and their bodies; `gmail.metadata` would give no body). The members already connected keep their connection: they give Gmail its consent when they activate the import, which adds it to their calendar.
3. *Pub/Sub › Topics › Create topic*: a topic `<topic>`, without a default subscription. Its **full name** is `projects/<project id>/topics/<topic>`.
4. On that topic, *Permissions › Add principal*: `gmail-api-push@system.gserviceaccount.com`, role **Pub/Sub Publisher**: Gmail publishes there the changes of the mailboxes. An organization policy restricting the domains allowed in IAM (*domain restricted sharing*) refuses that principal: allow it as an exception for this project.
5. *IAM & Admin › Service accounts › Create service account*: `<push service account>`, with no role. Pub/Sub signs with it the token Prospecter checks on each notification. The administrator creating the subscription needs **Service Account User** on it (an Owner of the project has it).
6. *Pub/Sub › Subscriptions › Create subscription*, on the topic:
   - delivery type **Push**, endpoint `<address of the API>/api/accounts/<account id>/gmail-notifications`, the address Prospecter shows on the card of the client (« Adresse des notifications à déclarer dans l’abonnement »);
   - **Enable authentication**, service account `<push service account>`, audience left empty (Pub/Sub takes the endpoint itself, which Prospecter expects);
   - acknowledgement deadline 10 seconds (Prospecter answers at once), retention of 1 day, retry policy *exponential backoff*.
7. In Prospecter, on the card of the Google client, the **full name of the topic** (« Topic Pub/Sub (import Gmail) ») and the **email of the push service account** (« Compte de service des notifications », `<push service account>@<project id>.iam.gserviceaccount.com`).

Each member having the right to manage the personal data then activates the import in *Configuration › Connecteurs*: Google asks their consent for Gmail, and Prospecter watches their mailbox, all but Spam, Trash and Drafts.

## When something goes wrong

- A notification refused by Prospecter (401) is retried by Pub/Sub until the retention ends: check that the subscription authenticates with the service account typed in Prospecter, and that its audience is empty or the endpoint itself.
- Nothing arrives: the topic must grant `gmail-api-push@system.gserviceaccount.com` the role Publisher, in the same project as the client.
- Removing the topic or the subscription stops the import without warning; Prospecter renews the watch of each mailbox every day and resumes from the last e-mail imported once the topic is back.
