# ARTIUM – JavaFX Amateur (Front-Office) Interfaces – Complete Agent Guide

> This file contains every detail an agent needs to recreate the **Artium Amateur d'art front-office** in a JavaFX desktop application, faithfully mirroring the existing Symfony/Twig web version.

---

## 1. Application Context

The **Amateur** role is a logged-in user with `role = 'Amateur'`. Unlike the artist, the amateur is a **consumer** of art content: they browse oeuvres (artwork), listen to music, borrow books, attend events, and manage their favourites and reclamations.

Key difference from the artist layout: the amateur has a **left sidebar** (navigation + profile card) and a **central content column**, with no right sidebar.

The amateur profile has a `centreInteret` field (CentreInteret enum: Peinture, Sculpture, Photographie, Musique, Lecture) instead of a `specialite`.

---

## 2. Overall JavaFX Layout for Amateur Views

```
Stage
└── Scene
    └── BorderPane (root)
        ├── TOP:    NavbarAmateur   (full-width top bar)
        └── CENTER: ScrollPane
                    └── HBox (container, max-width container)
                        ├── LEFT (col-lg-3 equiv, ~25%):
                        │     └── SidebarAmateur (sticky on desktop)
                        │           └── Profile card + nav links
                        └── CENTER (col-md-8 col-lg-6 equiv, ~50-66%):
                              └── ContentPane (page-switched FXML views)
```

> Note: The amateur layout is narrower than the artist layout — the central content is `col-md-8 col-lg-6` (~50% width) with a left sidebar of `col-lg-3` (~25%).

**Special: Persistent Mini Audio Player**
A fixed bar at the bottom of the screen (always visible when a song is playing):
```
[⏮] [▶/⏸] [⏭]  |  Title - Artist  |  [seek slider  0:00 ──────── 3:45]  |  [↗ Open Music]
```
- Dark background (`bg-dark`, white text)
- Height: ~72 px
- Saves state to preferences: current track, position, queue, playing/paused
- Hidden when on the Music page itself
- Hidden when no track has been played yet

---

## 3. Top Navbar (NavbarAmateur)

### Layout
Same structure as NavbarArtiste (horizontal `HBox`, fixed top, white background).

### Left section
- **Logo**: `logo2.png` (light mode) / `Colored PNG White Logo.png` (dark mode)
- **Hamburger** toggle for mobile (collapses left sidebar)

### Center section
- **Search bar**: `TextField` with 🔍 icon, placeholder "Recherche…"
- **Navigation links** (top nav, not sidebar):
  - **Oeuvres** — dropdown:
    - Peintures → `app_feed_peintures`
    - Sculptures → `app_feed_sculptures`
    - Photographies → `app_feed_photos`
    - Recommandations → `app_feed_recommandations`
  - **Bibliothèque** → `app_bibliofront`
  - **Musique** → `app_musicfront`
- Active item highlighted (matches current route)

### Right section (left to right)
1. **Notifications bell** (🔔)
   - Badge blinks only when there are notifications
   - Content: **Cancelled event notifications** (live data):
     - Each item: calendar-x icon (red circle) + event title "X a été annulé." + ticket count + date prévue
     - Click → navigate to event detail page
     - Empty state: "Aucune notification pour le moment."
2. **User avatar** (square rounded, 40×40 px)
   - Shows `photoProfil` or grey SVG placeholder
   - **Dropdown** on click:
     - Avatar (circle) + `nom prenom` + "Amateur d'art"
     - **Se déconnecter** button → navigate to login screen
     - Divider
     - **Theme toggle**: Clair (☀) / Sombre (🌙) buttons

---

## 4. Left Sidebar (SidebarAmateur)

### Behaviour
- **Sticky** on desktop (stays visible while scrolling)
- **Offcanvas** on mobile (slide-in from left)
- Mobile toggle button: "≡ A Propos"

### Profile Card (inside sidebar)
```
[Cover image band: 50 px tall, bg-image]
[Avatar: 64×64, rounded, white border, overlapping cover bottom]
  → photoProfil or grey person icon
[nom prenom]
Amateur D'art
[biographie text]
─ Ville  |  Date de naissance ─
```

**Edit Profile** link at bottom of card → opens `offcanvasProfile` drawer (see Section 7)

### Navigation Links (sidebar)
```
🏠 Fil d'actualités    → app_feed
⭐ Favoris             → app_favoris
📅 Evènements          → app_eventsfront
📚 Bibliothèque        → app_bibliofront
⚠ Réclamations        → app_reclamationfront
```

Each link uses an icon image from `assetsfront/images/icon/`:
- `home-outline-filled.svg` (feed)
- `favori.png` (favoris)
- `calendar-outline-filled.svg` (événements)
- `livre-ouvert.png` (bibliothèque)
- `attention.png` (réclamations)

---

## 5. Edit Profile Drawer (offcanvasProfile)

Slide-out panel from the right (same as artist). **For the amateur**, the `centreInteret` field is shown instead of `specialite`.

### Form Fields
```
- photoProfil (FileChooser → image upload)
- nom (TextField)
- prenom (TextField)
- email (TextField)
- numTel (TextField)
- ville (TextField)
- dateNaissance (DatePicker)
- biographie (TextArea)
- centreInteret (ComboBox: Peinture, Sculpture, Photographie, Musique, Lecture) — amateur only
- role (hidden / read-only for non-admin)
- statut (hidden / read-only for non-admin)
```

### Change Password Section (below profile form)
```
- ancien mot de passe (PasswordField)
- nouveau mot de passe (PasswordField)
- confirmation (PasswordField)
[Enregistrer]
```

---

## 6. Views (Content Area – Page Views)

### 6.1 Fil d'actualités / Feed (`app_feed`)

**Purpose:** Main home page — shows all oeuvres (artworks) from all artists as a social feed.

**Route variants:**
- `/feed` — `app_feed` — all oeuvres (default)
- `/feed/peintures` — `app_feed_peintures`
- `/feed/sculptures` — `app_feed_sculptures`
- `/feed/photos` — `app_feed_photos`
- `/feed/recommandations` — `app_feed_recommandations` (AI-powered recommendations based on `centreInteret`)

**Layout:**
Infinite-scroll style list of **oeuvre cards**, 3 loaded initially, more on scroll/button click.

**Each oeuvre card:**
```
┌─────────────────────────────────┐
│ [Avatar story] Artiste nom   ⋮  │  ← card header
│ Specialité · dd Month yyyy      │
├─────────────────────────────────┤
│ Titre de l'oeuvre               │
│ Description... #TypeOeuvre      │
│ #NomCollection                  │
│ [Image, max 600px height]       │
├─────────────────────────────────┤
│ ❤ X likes  💬 X commentaires   │  ← reaction row
│                        ⭐ X favoris│
├─────────────────────────────────┤
│ [User avatar] [Comment textarea ✉]│  ← add comment
│                                 │
│ Comment 1: User • date  ⋮       │  ← existing comments
│   [comment text]                │
│ Comment 2...                    │
│ [Voir X commentaires de plus]   │
└─────────────────────────────────┘
```

**Card header dropdown (⋮):**
- "Ajouter aux Favoris" / "Retirer des favoris" (toggles ⭐ icon + count)

**Like button:** Toggle ❤ (empty/filled), updates count live via AJAX-equivalent API call.

**Comment box:**
- `TextArea` auto-resize (1 row default)
- Submit with send icon button → POST to `oeuvre_commentaire/{id}`

**Comment item actions (own comments only):**
- ⋮ dropdown → Modifier / Supprimer
- Modifier: replace text with editable `TextArea` in place + Enregistrer button
- Show first 3 comments; "Voir X commentaires de plus" expands all

**Data per oeuvre card:**
```java
// Query: all oeuvres, ordered by dateCreation DESC
SELECT o FROM Oeuvre o
LEFT JOIN o.collection c
LEFT JOIN c.artiste a
ORDER BY o.dateCreation DESC
```

**For filtered feeds (peintures/sculptures/photos):**
Add `WHERE o.type = :type` filter.

**For recommandations:**
Uses `RecommendationServiceoeuvre` — filter by user's `centreInteret`.

---

### 6.2 Favoris (`app_favoris`)

**Purpose:** View all saved favourite oeuvres, filterable by type.

**Layout:**
- Card header: "Favoris"
- **Tab navigation** (4 tabs):
  1. Toutes les oeuvres (default)
  2. Peintures
  3. Sculptures
  4. Photographies
- **Grid**: 2 per row on mobile, 4 per row on desktop (col-6 col-lg-3)
  - Each cell: thumbnail image (or placeholder)
  - ✎ pencil button (bottom-right): dropdown → "Retirer des favoris"
  - Click on image → opens **oeuvre detail modal**

**Empty state:** "L'utilisateur n'a pas encore de favoris."

**Remove from favourites:** AJAX call → removes from grid immediately (no page reload).

**Oeuvre detail modal:**
- Large image
- Titre, type, collection, artist name
- Description
- Likes count, comments count
- Close button

**Data:**
```java
// user.getFavUser() — ManyToMany with Oeuvre
// Filtered per type client-side (JavaFX tab switching on cached list)
```

---

### 6.3 Événements (`app_eventsfront`)

**Purpose:** Browse all available events, filter by type, search by keyword.

**Layout:**
- Card header: "Découvrir les évènements"
- **Search bar** (top): TextField + "Rechercher" button
  - Searches by keyword via `app_eventsfrontkeyword` route
  - AI-scored results: badge "X/10" on matching events
- **Tab navigation**: Tous | Exposition | Concert | Spectacle | Conférence
- **Event grid** (3 per row):
  - Cover image (190 px height, or placeholder)
  - Type badge (dark, top-left of image)
  - Titre + optional AI score badge (green)
  - 📅 Date début: `Ddd, dd MMM yyyy HH:mm`
  - 📍 Localisation galerie (or "Lieu à définir")
  - 👥 `capaciteMax` places | Price: `X TND`
  - **Acheter ticket** button (🎫) → POST to `app_payment_checkout`
  - Click on card → navigate to event detail page

**Empty state:** "Aucun evenement pour le moment."

**Data fields (Evenement entity):**
- `id`, `titre`, `description`
- `dateDebut` (LocalDateTime), `dateFin`
- `type` (TypeEvenement: Exposition, Concert, Spectacle, Conférence)
- `statut` (StatutEvenement: À venir, Terminé, Annulé)
- `imageCouverture` (byte[] BLOB)
- `prixTicket` (double, TND)
- `capaciteMax` (int)
- `galerie` → `Galerie.nom`, `Galerie.localisation`

---

### 6.4 Event Detail (`app_eventdetails`)

**Purpose:** Full details of a single event + ticket purchase.

**Layout:**
- Cover image (max 400 px height, full width) or placeholder
- Titre + statut badge + type badge
- **Acheter ticket** button (prominent, primary)
- Description
- `<hr>`
- Info grid (4 columns):
  - ⏰ Horaires: date début, heure début, date fin, heure fin
  - 💶 Prix d'entrée: `X DT` (green, h4)
  - 👥 Capacité: `X Personnes`
  - 🏛 Galerie: `galerie.nom` (or "Non spécifié")

**Ticket purchase flow:**
1. Click "Acheter ticket" → POST to `/payment/checkout/{id}`
2. Redirects to Stripe Checkout (external browser/WebView)
3. On success → `/payment/success?session_id=xxx`

---

### 6.5 Payment Success (`app_payment_success`)

**Purpose:** Confirm ticket purchase, allow QR code display and PDF download.

**Layout:**
- Centered card (max 480 px wide)
- ✅ Green checkmark circle (100×100)
- Title: "Paiement réussi !"
- Message: "Votre ticket pour **[event title]** a été acheté avec succès !"
- Info alert (blue): instructions (QR code at entrance, check email, etc.)
- **Buttons:**
  - "Afficher le QR Code" → `app_ticket_qr/{id}` (new window)
  - "Télécharger le ticket PDF" → `app_ticket_download/{id}`
  - "Retour à l'événement" → `app_eventdetails/{id}`
  - "Tous les événements" → `app_eventsfront`
- Transaction reference: `session_id` (small, bottom)

---

### 6.6 Bibliothèque Numérique (`app_bibliofront`)

**Purpose:** Browse and rent digital books (livres numériques).

**Layout:**
- Card header: "Bibliotheque numerique"
- **Search form** (right of header):
  - Search input: titre / catégorie
  - Category ComboBox (all categories from DB)
  - "Recherche" button
- Badges row: "Livres numériques" | "Accès immédiat" | "Louer & Lire"

**Recommendation Section** (shown if user has reading history):
```
🤖 Système de Recommandation
"Basé sur vos habitudes de lecture [user profile key]."
3-column grid of recommended books (cover image, titre, categorie)
```

**Book Grid** (3 per row):
Each book card:
```
┌───────────────────────────────┐
│ [Cover image 220px height]    │
│ ┌─── Status badge (top-right) │
│ │ "Non loué"      → grey      │
│ │ "Loué"          → green     │
│ │ "Loué (toi)"    → yellow    │
│ └───────────────────────────── │
├───────────────────────────────┤
│ Titre                         │
│ Par [artiste nom prenom]      │
│ [categorie badge]             │
│ Description (truncated ~200ch)│
│ Prix de location: X,XX€       │
│                               │
│ [Rental progress bar]         │  ← if rented by this user
│ Expire dans: X jours          │
│                               │
│ [Louer] or [Lire] buttons     │
└───────────────────────────────┘
```

**Rental progress bar** (only shown if `rentedByCurrentUser`):
- Full-width progress bar showing days remaining / total rental days
- Text: "Expire dans X jours" (yellow badge if ≤ 3 days)

**Buttons per book:**
- If `available` → **Louer** (primary) → opens rental dialog
- If `rented_by_you` → **Lire** (success) + **Prolonger** (outline-secondary) + optional countdown
- If `rented` (by someone else) → **Non disponible** (disabled)

**Rental dialog:**
```
Title: "Louer [book titre]"
Fields:
  - Durée (days) (NumberField, min 1)
  - Prix total: auto-calculated = prix_location × jours (shown live)
  - [Confirmer la location]
```
Payment flow: uses Stripe (same as events) or direct DB entry for free/testing.

**Book Reader** (separate view, `book/read`):
```
Title: [livre.titre]
[Previous] [Next]  Page: X / Y
[PDF canvas rendered via PDFBox / Apache PDFRenderer]
```
Implementation: Use Apache PDFRenderer library to render PDF pages onto a JavaFX Canvas.

**Data fields (Livre):**
- `id`, `titre`, `description`, `categorie`
- `prixLocation` (double, €/jour)
- `fichierPdf` (byte[] BLOB)
- `image` (byte[] BLOB — cover)
- `collection_id` → `Collections` → `artiste`

**LocationLivre entity:**
- `id`, `dateDebut`, `etat` (EtatLocation: Active, Expirée), `user_id`, `livre_id`, `nombreDeJours`

**Recommendation service:**
```java
// RecommendationService uses user's reading history (LocationLivre)
// and current user's centreInteret = Lecture
// Returns list of Book IDs sorted by similarity score
```

---

### 6.7 Musique (`app_musicfront`)

**Purpose:** Browse and listen to all music tracks, manage playlists.

**This is the only page where the mini player bar is HIDDEN** (music is played directly on this page instead).

**Layout:**
- Card (dark theme: `bg-dark text-white`)
- Header: "Découvrir Musique" + search+sort form

**Search & Sort bar:**
- Search input: titre, description, artiste name
- Sort by: Date / Titre (A-Z) / Genre (ComboBox)
- Sort order toggle: ↑ / ↓ (Asc/Desc)
- Results info: "Resultats: X musique(s) pour '...'"

**Tabs** (genre filter):
- Tous les genres (default)
- Rock | Jazz | Classique | Pop
- **Playlists** (user's own playlists + create new)

**Music Card** (per track, dark card, 3 per row):
```
[Oeuvre image (200px min) or grey placeholder]
[Genre badge top-right: blue]
─────────────────────────────────────────────
[Genre tag button: blue, small]
Titre
👤 Artiste nom prenom
Description (truncated ~50 chars)
📅 Date (MMM d, yyyy)
─────────────────────────────────────────────
[▶ Jouer]  [🎵 Lyrics]  [♡]  [➕ Playlist]
```

**"Jouer" button:** Adds track to the global audio queue and starts playback.
- Updates the mini player bar (at bottom of screen on all other pages)
- State persisted in JavaFX Preferences (equivalent of localStorage)

**"Lyrics" button:** Opens a dialog with AI-generated lyrics for the track (via Groq API or similar).

**"♡" button:** Adds to favourites (music-specific favourites, if implemented).

**"➕ Playlist" button:** Opens "Add to playlist" dialog:
```
[Select playlist ComboBox: [playlist names]]
or "Créer une nouvelle playlist"
[Ajouter]
```

---

#### Playlists Tab

**Existing playlists:**
Each playlist card (dark):
- Playlist cover image (BLOB) or gradient placeholder
- Nom
- Description (truncated)
- Date création
- Track count
- "Voir la playlist" → opens playlist detail
- ⋮ dropdown: Renommer, Supprimer

**"Créer une playlist" card** (first in grid, always shown):
```
[+ circle icon, gradient background]
"Créer une playlist"
"Vous pouvez créer une playlist vide, puis ajouter des musiques."
[Ajouter] button → opens inline form:
  - Nom playlist (TextField)
  - Description (TextArea, optional)
  [Créer la playlist]
```

**"🌟 AI Playlist" button** (next to Ajouter):
```
Opens AI playlist form:
  - Describe your mood / genre preferences (TextArea)
  - [Générer] → calls GroqPlaylistService
  - Returns a playlist pre-populated with matching tracks
```

**Global Audio Queue:**
The JavaFX audio system maintains:
```java
public class AudioQueue {
    private List<TrackInfo> queue;       // current track list
    private int currentIndex;            // current track position
    private MediaPlayer player;
    private double currentTime;
    private boolean isPlaying;
    
    // Persisted across pages via Preferences or static singleton
    public void playTrack(TrackInfo track);
    public void playNext();
    public void playPrev();
    public void seekTo(double progress);  // 0.0 to 1.0
}

public class TrackInfo {
    String id;
    String title;
    String artist;
    String audioUrl;   // /musiqueartiste/{id}/audio
    String imageUrl;   // /musiqueartiste/{id}/image
}
```

**Entities:**
- `Musique` (id, titre, description, genre, audio [filename], collection_id, dateCreation)
- `Playlist` (id, nom, description, date_creation, image [BLOB], user_id)
- Playlist ↔ Musique: ManyToMany join table

---

### 6.8 Réclamations (`app_reclamationfront`)

**Purpose:** Submit and track reclamations (same as artist, but using the amateur layout).

**Layout:** Identical to the artist reclamations view but embedded in the amateur base layout (left sidebar instead of right sidebar + profile header).

**Two-tab panel:**

**Tab 1 — Envoyer Réclamation** (default):
```
Card: "Soumettre une Réclamation"
Form fields:
  - type (ComboBox: Paiement, Oeuvre, Evènement, Compte)
  - texte (TextArea, 5 rows, placeholder: "Décrivez votre problème en détail...")
  - file (FileChooser: PDF/JPG/JPEG/PNG, max 5MB, optional)
Buttons: [Réinitialiser]  [Envoyer la réclamation]
```

**Tab 2 — Mes Réclamations:**
- **Filters row** (auto-submit on change):
  - Search input (TextField — search by texte)
  - Statut filter (ComboBox: Tous / Traitée / Non traitée / Archivée)
  - Type filter (ComboBox: Tous / Paiement / Oeuvre / Evènement / Compte)
  - Date from (DatePicker)
- **Reclamation list** (table or card list):
  - Each row:
    - Date: `dd/MM/yyyy`
    - Type badge
    - Statut badge:
      - Non traitée → yellow (`#FFC107`)
      - En cours → cyan (`#0DCAF0`)
      - Traitée → green (`#198754`)
      - Archivée → grey (`#6C757D`)
    - Texte (truncated to ~80 chars)
    - File attachment icon (if file_name is not null)
    - "Voir détails" → navigate to reclamation detail page

**Reclamation Detail page** (`reclamation/show`):
```
Header: "Reclamation #[id]"  [← Retour button]
─────────────────────────────────────────────────
[Statut badge]  Type: X  Date: dd/MM/yyyy
[Background box]: full reclamation text
─────────────────────────────────────────────────
💬 Réponses de l'administration  [N badge]

For each réponse:
  Card:
    [Avatar circle with person icon] Administrateur
    [Admin nom prenom if available]
    Date: dd/MM/yyyy à HH:mm
    [Admin badge: green "Admin"]
    [Reply text]
```

---

## 7. Data Model Summary (Amateur-Relevant Entities)

### User (amateur-specific fields)
| Field | Type |
|---|---|
| id | int |
| nom | String |
| prenom | String |
| email | String |
| numTel | String |
| ville | String |
| biographie | String (nullable) |
| dateNaissance | LocalDate |
| dateInscription | LocalDate |
| photoProfil | String (filename in uploads/) |
| role | Enum Role (AMATEUR) |
| statut | Enum Statut |
| centreInteret | Enum CentreInteret (nullable) |
| favUser | ManyToMany → Oeuvre |

### CentreInteret enum
`Peinture, Sculpture, Photographie, Musique, Lecture`

### Oeuvre
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| dateCreation | LocalDate |
| image | byte[] (BLOB) |
| type | Enum TypeOeuvre |
| collection_id | FK → Collections → artiste |
| likes | OneToMany → Like |
| userFav | ManyToMany → User (favUser) |

### Commentaire
| Field | Type |
|---|---|
| id | int |
| oeuvre_id | FK → Oeuvre |
| user_id | FK → User |
| texte | String |
| dateCommentaire | LocalDate |

### Like
| Field | Type |
|---|---|
| id | int |
| oeuvre_id | FK → Oeuvre |
| user_id | FK → User |

### Evenement
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| dateDebut | LocalDateTime |
| dateFin | LocalDateTime |
| dateCreation | LocalDate |
| type | Enum TypeEvenement |
| imageCouverture | byte[] (BLOB) |
| statut | Enum StatutEvenement |
| prixTicket | double (TND) |
| capaciteMax | int |
| artiste_id | FK → User |
| galerie_id | FK → Galerie (nullable) |

### Galerie
| Field | Type |
|---|---|
| id | int |
| nom | String |
| localisation | String |

### Ticket
| Field | Type |
|---|---|
| id | int |
| codeQr | byte[] (BLOB — QR code image) |
| dateAchat | LocalDate |
| evenement_id | FK → Evenement |
| user_id | FK → User |

### Livre
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| categorie | String |
| prixLocation | double (€/jour) |
| fichierPdf | byte[] (BLOB) |
| image | byte[] (BLOB — cover) |
| collection_id | FK → Collections |

### LocationLivre
| Field | Type |
|---|---|
| id | int |
| dateDebut | LocalDateTime |
| etat | Enum EtatLocation (Active, Expirée) |
| nombreDeJours | int |
| user_id | FK → User |
| livre_id | FK → Livre |

### Musique
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| genre | Enum GenreMusique (Rock, Jazz, Classique, Pop) |
| audio | String (filename) |
| dateCreation | LocalDate |
| collection_id | FK → Collections |

### Playlist
| Field | Type |
|---|---|
| id | int |
| nom | String |
| description | String (nullable) |
| date_creation | LocalDate |
| image | byte[] (BLOB, nullable) |
| user_id | FK → User |
| musiques | ManyToMany → Musique |

### Reclamation
| Field | Type |
|---|---|
| id | int |
| texte | String |
| dateCreation | LocalDate |
| statut | Enum StatutReclamation |
| type | Enum TypeReclamation |
| file_name | String (nullable) |
| user_id | FK → User |

### Reponse
| Field | Type |
|---|---|
| id | int |
| reclamation_id | FK → Reclamation |
| texte | String |
| dateReponse | LocalDateTime |
| userAdmin_id | FK → User (admin) |

---

## 8. JavaFX Project Structure (Amateur module)

```
artium-javafx/
└── src/main/java/com/artium/
    ├── model/
    │   ├── Oeuvre.java
    │   ├── Commentaire.java
    │   ├── Like.java
    │   ├── Evenement.java
    │   ├── Galerie.java
    │   ├── Ticket.java
    │   ├── Livre.java
    │   ├── LocationLivre.java
    │   ├── Musique.java
    │   ├── Playlist.java
    │   ├── Reclamation.java
    │   ├── Reponse.java
    │   └── enums/
    │       ├── CentreInteret.java      (Peinture, Sculpture, Photographie, Musique, Lecture)
    │       ├── EtatLocation.java       (Active, Expirée)
    │       ├── TypeReclamation.java    (Paiement, Oeuvre, Evènement, Compte)
    │       └── StatutReclamation.java  (Traitée, Non traitée, En cours, Archivée)
    ├── repository/
    │   ├── OeuvreRepository.java
    │   ├── CommentaireRepository.java
    │   ├── LikeRepository.java
    │   ├── EvenementRepository.java
    │   ├── TicketRepository.java
    │   ├── LivreRepository.java
    │   ├── LocationLivreRepository.java
    │   ├── MusiqueRepository.java
    │   ├── PlaylistRepository.java
    │   └── ReclamationRepository.java
    ├── service/
    │   ├── RecommendationServiceOeuvre.java  ← oeuvre recommendations by centreInteret
    │   ├── RecommendationServiceLivre.java   ← book recommendations
    │   ├── AudioQueueService.java             ← global music player state
    │   └── GroqPlaylistService.java           ← AI playlist generation
    └── controller/
        ├── NavbarAmateurController.java
        ├── SidebarAmateurController.java
        ├── EditProfileController.java
        ├── FeedController.java
        ├── FavorisController.java
        ├── EventsfrontController.java
        ├── EventDetailController.java
        ├── PaymentController.java
        ├── BibliofrontController.java
        ├── BookReaderController.java
        ├── MusicfrontController.java
        ├── PlaylistController.java
        ├── MiniAudioPlayerController.java
        └── ReclamationController.java
```

---

## 9. Route → FXML Mapping

| Web route | Web URL | JavaFX FXML |
|---|---|---|
| app_feed | /feed | amateur/Feed.fxml |
| app_feed_peintures | /feed/peintures | amateur/Feed.fxml (filtered) |
| app_feed_sculptures | /feed/sculptures | amateur/Feed.fxml (filtered) |
| app_feed_photos | /feed/photos | amateur/Feed.fxml (filtered) |
| app_feed_recommandations | /feed/recommandations | amateur/FeedReco.fxml |
| app_favoris | /favoris | amateur/Favoris.fxml |
| app_eventsfront | /user-evenements | amateur/Evenements.fxml |
| app_eventdetails | /eventdetails/{id} | amateur/EventDetail.fxml |
| app_payment_success | /payment/success | amateur/PaymentSuccess.fxml |
| app_bibliofront | /user-bibliotheque | amateur/Bibliotheque.fxml |
| book/read | /book/read/{id} | amateur/BookReader.fxml |
| app_musicfront | /user-musiques | amateur/Musique.fxml |
| app_reclamationfront | /reclamationfront | amateur/Reclamations.fxml |
| reclamation/show | /reclamation/{id} | amateur/ReclamationDetail.fxml |

---

## 10. CSS / Styling Notes

The amateur front-office uses the same **light social-network style** as the artist. Key differences:

- Body: `padding-bottom: 95px` when mini player is active (`page-with-player` class)
- Mini audio player: fixed bottom, `bg-dark text-white`, height ~72 px, full width, `z-index: 1050`
- Sidebar sticky: `position: sticky; top: calc(72px + 1rem); align-self: flex-start;` on desktop
- Music page: dark card (`bg-dark text-white border-0`)

```css
/* Left sidebar */
.amateur-left-sidebar {
    -fx-background-color: white;
    -fx-padding: 0;
}
.sidebar-card {
    -fx-background-color: white;
    -fx-background-radius: 12;
    -fx-border-color: #dee2e6;
    -fx-border-radius: 12;
    -fx-effect: dropshadow(gaussian, rgba(0,0,0,0.05), 8, 0, 0, 2);
}

/* Feed card */
.feed-card {
    -fx-background-color: white;
    -fx-background-radius: 8;
    -fx-border-color: #dee2e6;
    -fx-border-radius: 8;
    -fx-padding: 0;
    -fx-effect: dropshadow(gaussian, rgba(0,0,0,0.04), 6, 0, 0, 1);
}
.feed-card-header {
    -fx-padding: 12 16;
    -fx-border-color: transparent transparent #f1f3f5 transparent;
    -fx-border-width: 0 0 1 0;
}

/* Like/comment reaction row */
.reaction-row {
    -fx-padding: 8 16;
    -fx-border-color: #f1f3f5 transparent transparent transparent;
    -fx-border-width: 1 0 0 0;
}
.like-button-liked { -fx-text-fill: #dc3545; }
.like-button-unliked { -fx-text-fill: #6c757d; }

/* Mini audio player (fixed bottom) */
.mini-audio-player {
    -fx-background-color: #212529;
    -fx-padding: 8 16;
    -fx-border-color: #343a40 transparent transparent transparent;
    -fx-border-width: 1 0 0 0;
}
.mini-audio-title {
    -fx-text-fill: white;
    -fx-font-weight: bold;
    -fx-max-width: 260;
}
.mini-audio-artist {
    -fx-text-fill: #adb5bd;
    -fx-font-size: 11;
}
.mini-seek-slider .track {
    -fx-background-color: #495057;
}
.mini-seek-slider .thumb {
    -fx-background-color: #198754;
}

/* Music page dark theme */
.music-card {
    -fx-background-color: #212529;
    -fx-border-color: #495057;
    -fx-border-radius: 8;
    -fx-background-radius: 8;
}
.music-card Label { -fx-text-fill: white; }

/* Book card */
.library-card {
    -fx-background-color: white;
    -fx-border-color: #dee2e6;
    -fx-border-radius: 8;
    -fx-background-radius: 8;
}
.library-cover {
    -fx-min-height: 220;
    -fx-max-height: 220;
}
```

---

## 11. Key JavaFX Implementation Patterns

### 11.1 Infinite Scroll / Load More (Feed)
```java
// FeedController.java
private int displayedCount = 3;
private List<Oeuvre> allOeuvres;

public void loadMore() {
    int nextBatch = Math.min(displayedCount + 5, allOeuvres.size());
    for (int i = displayedCount; i < nextBatch; i++) {
        feedVBox.getChildren().add(buildOeuvreCard(allOeuvres.get(i)));
    }
    displayedCount = nextBatch;
    loadMoreBtn.setVisible(displayedCount < allOeuvres.size());
}
```

### 11.2 Inline Like Toggle (Feed)
```java
likeBtn.setOnAction(e -> {
    boolean nowLiked = !isCurrentlyLiked;
    // Call API: POST /oeuvre/{id}/like or DELETE
    isCurrentlyLiked = nowLiked;
    likeIcon.setStyleClass(nowLiked ? "like-button-liked" : "like-button-unliked");
    likeCountLabel.setText(String.valueOf(nowLiked ? likeCount + 1 : likeCount - 1));
});
```

### 11.3 Favourite Toggle
```java
favBtn.setOnAction(e -> {
    // Call API: POST /oeuvre/{id}/favorite or DELETE
    boolean isFav = !currentlyFav;
    favIcon.setImage(isFav ? starFillImg : starEmptyImg);
    favCountLabel.setText(String.valueOf(isFav ? favCount + 1 : favCount - 1));
});
```

### 11.4 Persistent Global Audio Queue (Mini Player)
```java
public class AudioQueueService {
    private static final AudioQueueService INSTANCE = new AudioQueueService();
    
    private MediaPlayer player;
    private List<TrackInfo> queue = new ArrayList<>();
    private int currentIndex = -1;
    
    public static AudioQueueService get() { return INSTANCE; }
    
    public void setQueue(List<TrackInfo> tracks, int startIndex) {
        this.queue = tracks;
        playAt(startIndex);
    }
    
    public void playAt(int index) {
        currentIndex = index;
        TrackInfo track = queue.get(index);
        if (player != null) player.stop();
        Media media = new Media(track.audioUrl);
        player = new MediaPlayer(media);
        player.setOnEndOfMedia(this::playNext);
        player.play();
        saveState();
    }
    
    public void playNext() {
        if (queue.isEmpty()) return;
        playAt((currentIndex + 1) % queue.size());
    }
    
    public void playPrev() {
        if (queue.isEmpty()) return;
        playAt((currentIndex - 1 + queue.size()) % queue.size());
    }
    
    private void saveState() {
        Preferences prefs = Preferences.userNodeForPackage(AudioQueueService.class);
        prefs.put("currentTitle", queue.get(currentIndex).title);
        prefs.put("currentArtist", queue.get(currentIndex).artist);
        prefs.putInt("currentIndex", currentIndex);
        // serialize queue to JSON and save
    }
}
```

### 11.5 Mini Player Bar (persistent bottom bar)
```java
// MiniAudioPlayerController.java
// Shown on all pages EXCEPT the music page

public void init() {
    AudioQueueService audio = AudioQueueService.get();
    
    // Bind title/artist labels
    audio.currentTrackProperty().addListener((obs, old, track) -> {
        if (track != null) {
            titleLabel.setText(track.title);
            artistLabel.setText(track.artist);
            playerBar.setVisible(true);
        }
    });
    
    // Seek slider
    audio.progressProperty().addListener((obs, old, progress) -> {
        if (!isUserSeeking) seekSlider.setValue(progress.doubleValue() * 100);
    });
    
    // Time labels
    audio.currentTimeProperty().addListener((obs, old, t) ->
        currentTimeLabel.setText(formatTime(t.doubleValue()))
    );
    
    toggleBtn.setOnAction(e -> {
        if (audio.isPlaying()) audio.pause(); else audio.play();
    });
    
    prevBtn.setOnAction(e -> audio.playPrev());
    nextBtn.setOnAction(e -> audio.playNext());
    
    openMusicBtn.setOnAction(e -> mainController.navigateTo("/fxml/amateur/Musique.fxml"));
}
```

### 11.6 PDF Reader (Book Reader)
```java
// BookReaderController.java
// Uses Apache PDFBox or PDFRenderer library

PDDocument pdfDoc;
int currentPage = 0;
int totalPages;

public void loadPdf(byte[] pdfBytes) {
    pdfDoc = PDDocument.load(pdfBytes);
    totalPages = pdfDoc.getNumberOfPages();
    renderPage(0);
}

private void renderPage(int pageIndex) {
    PDFRenderer renderer = new PDFRenderer(pdfDoc);
    BufferedImage bi = renderer.renderImageWithDPI(pageIndex, 150);
    // Convert BufferedImage to JavaFX Image
    WritableImage fxImage = SwingFXUtils.toFXImage(bi, null);
    pdfCanvas.setImage(fxImage);
    pageNumLabel.setText((pageIndex + 1) + " / " + totalPages);
    currentPage = pageIndex;
    prevBtn.setDisable(currentPage == 0);
    nextBtn.setDisable(currentPage == totalPages - 1);
}
```

### 11.7 Payment via Stripe (WebView)
```java
// PaymentController.java — open Stripe checkout URL in JavaFX WebView
WebView webView = new WebView();
WebEngine engine = webView.getEngine();
engine.load(stripeCheckoutUrl);

// Listen for redirect back to success URL
engine.locationProperty().addListener((obs, old, url) -> {
    if (url.contains("/payment/success")) {
        // Extract session_id from URL, then navigate to PaymentSuccess view
        String sessionId = extractSessionId(url);
        Platform.runLater(() -> navigateToSuccess(sessionId));
    }
});
```

### 11.8 Reclamation Filters (auto-apply on change)
```java
// ReclamationController.java
statutComboBox.setOnAction(e -> applyFilters());
typeComboBox.setOnAction(e -> applyFilters());
searchField.textProperty().addListener((obs, old, val) -> applyFilters());
datePicker.setOnAction(e -> applyFilters());

private void applyFilters() {
    String q = searchField.getText().toLowerCase();
    String statut = statutComboBox.getValue();
    String type = typeComboBox.getValue();
    LocalDate dateFrom = datePicker.getValue();
    
    List<Reclamation> filtered = allReclamations.stream()
        .filter(r -> q.isEmpty() || r.texte.toLowerCase().contains(q))
        .filter(r -> statut == null || statut.isEmpty() || r.statut.value.equals(statut))
        .filter(r -> type == null || type.isEmpty() || r.type.value.equals(type))
        .filter(r -> dateFrom == null || !r.dateCreation.isBefore(dateFrom))
        .collect(Collectors.toList());
    
    rebuildReclamationList(filtered);
}
```

---

## 12. Notification System (Cancelled Events)

The navbar notification bell shows cancelled-event notifications for the current user.

**Query:**
```sql
SELECT e, COUNT(t.id) AS ticket_count
FROM evenement e
JOIN ticket t ON t.evenement_id = e.id
WHERE t.user_id = :userId
  AND e.statut = 'Annulé'
ORDER BY e.date_debut DESC
```

Each notification item shows:
- 🗓 Red circle with calendar-x icon
- Event title + "a été annulé"
- Ticket count: "X ticket(s)"
- Date prévue: `dd/MM/yyyy`

**Badge:** Red blinking dot on bell icon, shown only when `count > 0`.

---

## 13. Summary – What to Build (Phase 2: Amateur Interfaces)

1. **NavbarAmateur.fxml** – logo, search, nav links (Oeuvres dropdown + Bibliothèque + Musique), notifications bell with live cancelled-event data, user avatar dropdown
2. **SidebarAmateur.fxml** – profile card (avatar, name, bio, ville, date) + navigation links (5 items) + Edit Profile link
3. **AmateurMain.fxml** – root layout: NavbarAmateur (top) + HBox(SidebarAmateur | ContentPane) + MiniAudioPlayer (bottom)
4. **EditProfile drawer** – slide-out: profile fields + centreInteret ComboBox + password change section
5. **Feed.fxml** – infinite scroll list of oeuvre cards with likes, comments (inline add/edit/delete), favourites toggle, load more button
6. **FeedReco.fxml** – same card list but filtered by centreInteret via recommendation service
7. **Favoris.fxml** – tab grid (Toutes / Peintures / Sculptures / Photographies), thumbnail grid, remove button, oeuvre detail modal
8. **Evenements.fxml** – event grid with type tabs, keyword search, AI score badge, buy ticket button (Stripe)
9. **EventDetail.fxml** – full event details: cover, info grid (Horaires/Prix/Capacité/Galerie), buy ticket
10. **PaymentSuccess.fxml** – success card with QR code + PDF download buttons
11. **Bibliotheque.fxml** – search+filter header, recommendation section, book grid with rental status, rental dialog, progress bar
12. **BookReader.fxml** – PDF rendering with prev/next navigation (Apache PDFBox)
13. **Musique.fxml** – dark-themed music grid with search+sort, genre tabs, Playlists tab (create + AI), per-card audio controls
14. **Reclamations.fxml** – two-tab: submit form + filtered list
15. **ReclamationDetail.fxml** – reclamation details + admin replies thread
16. **MiniAudioPlayerBar.fxml** – persistent bottom player: ⏮ ▶/⏸ ⏭, title/artist, seek slider, timestamps, open-music link
17. **AudioQueueService.java** – singleton audio state manager, persisted across page navigation
18. **CSS** – `amateur-theme.css`: light social-network style + dark music card overrides + mini player styles
