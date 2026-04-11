# ARTIUM – JavaFX Artist (Front-Office) Interfaces – Complete Agent Guide

> This file contains every detail an agent needs to recreate the **Artium Artist front-office** in a JavaFX desktop application, faithfully mirroring the existing Symfony/Twig web version.

---

## 1. Application Context

The **Artist** role is a logged-in user with `role = 'Artiste'`. The artist has a **specialité** which determines which content tabs are available:

| Specialité | Available content tabs |
|---|---|
| Peintre / Sculpteur / Photographe | Collections + Mes Oeuvres + Événements + Réclamations + Statistiques |
| Musicien | Collections + Musiques + Événements + Réclamations + Statistiques |
| Auteur | Collections + Bibliothèque + Événements + Réclamations + Statistiques |

---

## 2. Overall JavaFX Layout for Artist Views

```
Stage
└── Scene
    └── BorderPane (root)
        ├── TOP:    NavbarArtiste   (full-width top bar)
        └── CENTER: ScrollPane
                    └── HBox (container)
                        ├── LEFT (col-lg-8 equiv, ~67%):
                        │     ├── ProfileHeader (cover image + avatar + name + tab nav)
                        │     └── ContentPane (tab-switched FXML views)
                        └── RIGHT (col-lg-4 equiv, ~33%):
                              └── SidebarArtiste (About card + Stats card)
```

---

## 3. Top Navbar (NavbarArtiste)

### Layout
Horizontal `HBox`, fixed at top, white background with bottom border.

### Left section
- **Logo**: `logo2.png` (light mode) / `Colored PNG White Logo.png` (dark mode)
- **Hamburger** toggle (☰) for mobile — collapses the right sidebar

### Center section
- **Search bar**: `TextField` with search icon on the left, placeholder "Recherche…"

### Right section (left to right)
1. **Notifications bell** button (🔔)
   - Blinking animation badge when there are unread notifications
   - Dropdown: list of 4 static-style notifications (placeholder in web; make it live in JavaFX for reclamation replies if needed)
2. **User avatar** (circular, 38×38 px)
   - Shows `photoProfil` image or grey person icon if null
   - **Dropdown** on click:
     - Avatar + `nom prenom` + `specialite.value` (e.g. "Musicien")
     - **Se déconnecter** button → navigate to login screen
     - Divider
     - **Theme toggle**: buttons for Clair (☀) / Sombre (🌙), stored in preferences

---

## 4. Artist Profile Header

Displayed at the top of the main content area (left column). Always visible regardless of the active tab.

### Visual Structure
```
[Cover image banner: 200px tall, bg-image]
[Avatar: 120×120, overlapping bottom of cover, rounded-circle, white border]
[Name: prenom nom]  [✓ verified badge]
[Specialite · Ville · Inscrit le: date]
[Edit Profile button]
[Tab navigation bar]
```

### Tab Navigation (bottom of profile card)
The visible tabs depend on the artist's `specialite`:

**Musicien:**
- Collections  →  `/artiste-collections` (`app_collections_front`)
- Musiques     →  `/musiqueartiste` (`app_musiqueartiste`)
- Événements   →  `/artiste-evenements` (`app_eventsartiste`)
- Réclamations →  `/reclamationsartiste` (`app_reclamationsartiste`)
- Statistiques →  `/artiste-statistiques` (`app_statistiquesartiste`)

**Auteur:**
- Collections  →  `app_collections_front`
- Bibliothèque →  `app_bibliothequeartiste`
- Événements   →  `app_eventsartiste`
- Réclamations →  `app_reclamationsartiste`
- Statistiques →  `app_statistiquesartiste`

**Peintre / Sculpteur / Photographe:**
- Collections  →  `app_collections_front`
- Mes Oeuvres  →  `app_oeuvre_front`
- Événements   →  `app_eventsartiste`
- Réclamations →  `app_reclamationsartiste`
- Statistiques →  `app_statistiquesartiste`

**Edit Profile offcanvas/dialog** fields:
- `nom`, `prenom`, `email`, `num_tel`, `ville`, `biographie`, `specialite` (ComboBox), `photoProfil` (file chooser), `date_naissance`

---

## 5. Right Sidebar (SidebarArtiste)

### "À propos" Card
```
Title: "À propos"
Body:
  - biographie text
  - 📅 Né(e): date_naissance (dd MMM yyyy)
  - 📅 Inscrit le: date_inscription
  - ✉ Email: email
```

### "Statistiques" Card
```
Title: "Statistiques"
List items (label + colored badge):
  - 🖌 Œuvres ajoutées         [blue badge]   → nbOeuvres
  - ⚠ Réclamations faites     [yellow badge]  → nbReclamations
  - 📅 Événements créés        [cyan badge]    → nbEvenements
  - 💬 Commentaires reçus      [green badge]   → nbCommentaires
  - 👍 Likes reçus             [red badge]     → nbLikes
```

**Queries to populate the sidebar:**
```sql
-- nbOeuvres: count oeuvres in all collections owned by this artist
SELECT COUNT(o.id) FROM oeuvre o
JOIN collections c ON o.collection_id = c.id
WHERE c.artiste_id = :userId

-- nbReclamations
SELECT COUNT(*) FROM reclamation WHERE user_id = :userId

-- nbEvenements
SELECT COUNT(*) FROM evenement WHERE artiste_id = :userId

-- nbCommentaires (via oeuvres owned by artist)
-- complex: join commentaire → oeuvre → collections → user
-- Use countByArtist() logic

-- nbLikes (via likes on oeuvres owned by artist)
-- Use countByArtist() logic
```

---

## 6. Views (Content Area – Tab Pages)

### 6.1 Collections View (`app_collections_front`)

**Purpose:** Artist manages their artwork collections and oeuvres inside each.

**Layout:**
- Header row: Search input + "Ajouter Collection" button (opens modal/dialog)
- Accordion list of collections:
  - Each row: `titre` (bold) + `description` (grey) + ⋮ dropdown (Modifier / Supprimer) + chevron toggle
  - Expanded: grid of oeuvre thumbnails (2–4 per row)
    - Each oeuvre: image thumbnail, title below, likes/comments count
    - Click on oeuvre → opens detail modal

**Modal: Ajouter Collection**
```
Fields:
  - titre (TextField)
  - description (TextArea)
Submit: "Créer"
```

**Modal: Modifier Collection**
```
Same fields as Add, pre-filled with existing values
```

**Oeuvre detail modal (per oeuvre):**
- Large image
- titre, description
- type (TypeOeuvre enum badge)
- Like count, comment count

**Related entities:**
- `Collections` (id, titre, description, artiste_id)
- `Oeuvre` (id, titre, description, image, type, collection_id)

---

### 6.2 Mes Oeuvres View (`app_oeuvre_front`) — *Peintre / Sculpteur / Photographe only*

**Purpose:** Browse and manage all oeuvres across all collections.

**Layout:**
- Filter bar: search by titre + filter by TypeOeuvre (ComboBox: Peinture, Sculpture, Photographie)
- Grid of oeuvre cards (3 per row)
  - Image thumbnail
  - Titre, type badge
  - Like count (❤), comment count (💬)
  - ⋮ dropdown: Modifier, Supprimer
- "Ajouter une Œuvre" button → opens slide-out panel/dialog

**Add/Edit Oeuvre form fields:**
```
- titre (TextField)
- description (TextArea)
- type (ComboBox: Peinture, Sculpture, Photographie)
- collection (ComboBox from artist's collections)
- image (FileChooser for BLOB)
```

---

### 6.3 Musiques View (`app_musiqueartiste`) — *Musicien only*

**Purpose:** Artist manages their music tracks.

**Layout:**
- Header: "Mes pièces musicales" + "Ajouter une musique" button (slide-out panel)
- **Filter/Search bar:**
  - Search input: by titre, description (TextField)
  - Sort by: Date d'ajout / Titre (A-Z) / Genre (ComboBox)
  - Sort order toggle button (↑ / ↓)
- Results info: "Trouvé X chanson(s) correspondant à '...'"
- List of music cards (each row):
  - Audio player (using JavaFX MediaPlayer with Plyr-style controls)
  - Titre, genre badge
  - Description (truncated to ~100 chars)
  - Upload date
  - ⋮ dropdown: Modifier, Supprimer

**Add/Edit Musique form (slide-out panel):**
```
- titre (TextField)
- description (TextArea)
- genre (ComboBox: Rock, Jazz, Classique, Pop)
- collection (ComboBox from artist's collections)
- audio file (FileChooser — MP3/WAV)
```

**Audio playback:** Use `javafx.scene.media.MediaPlayer` to play/pause audio files.

**Entities:**
- `Musique` (id, titre, description, genre [GenreMusique], audio [filename], collection_id, updatedAt)
- `GenreMusique` enum: Rock, Jazz, Classique, Pop

---

### 6.4 Bibliothèque View (`app_bibliothequeartiste`) — *Auteur only*

**Purpose:** Artist manages their books (livres) available for rental.

**Layout:**
- Header: "Ma Bibliothèque" + "Ajouter un Livre" button (slide-out panel)
- Grid of book cards (3 per row):
  - Book cover image (140×200 px)
  - Titre (bold)
  - Catégorie: `categorie`
  - Prix: `prix_location €/jour`
  - Status indicator: "En location jusqu'au..." or "Disponible"
  - ⋮ dropdown (top-right of card): Détails, Modifier, Supprimer

**Livre Details popup/dialog:**
```
- Cover image
- Titre, catégorie, description
- Prix: X €/jour
- Nombre de locations actives
- Date de location active (if any)
- Durée (nombre de jours)
- Revenu total estimé
```

**Add/Edit Livre form (slide-out panel):**
```
- titre (TextField)
- description (TextArea)
- categorie (TextField)
- prix_location (NumberField, €/jour)
- collection (ComboBox from artist's collections)
- fichier_pdf (FileChooser — PDF)
- image (FileChooser — JPG/PNG, for cover)
```

**Entities:**
- `Livre` (id, titre, description, categorie, prix_location, fichier_pdf [BLOB], collection_id)
- `LocationLivre` (id, livre_id, user_id, date_location, nombre_jours, prix_total)

---

### 6.5 Événements View (`app_eventsartiste`)

**Purpose:** Artist manages their events.

**Layout:**
- Header: "Mes evenements" + "Ajouter" button → slide-out panel (720 px wide)
- Empty state: calendar icon + "Aucun evenement pour le moment."
- List of event rows:
  - Cover image (96×72 px) or placeholder
  - Titre (bold)
  - Date de début: `dd/MM/yyyy HH:mm` (📅)
  - Type: TypeEvenement badge (🏷)
  - Statut: StatutEvenement badge (⬤)
  - Tickets vendus count (🎫)
  - Description (truncated to 140 chars)
  - Action buttons:
    - "Annuler" (⊗, warning) — only if statut = "À venir" → confirm dialog
    - "Editer" (primary) → slide-out edit panel
    - "Supprimer" (danger) → confirm dialog

**Add/Edit Event form (slide-out panel, 720 px):**
```
Header: "Ajouter un evenement" + AI ticket estimate badge

Fields:
  - titre (TextField)
  - description (TextArea)
  - date_debut (DateTimePicker)
  - date_fin (DateTimePicker)
  - type (ComboBox: Exposition, Concert, Spectacle, Conférence)
  - capacite_max (NumberField)
  - image_couverture (FileChooser — JPG/PNG)
  [Submit: "Creer" or "Modifier"]
```

**AI ticket estimate:** Optional — call an AI service endpoint to estimate ticket demand based on description + type. Show result as a badge "Estimation IA: X".

**Entities:**
- `Evenement` (id, titre, description, date_debut, date_fin, date_creation, type [TypeEvenement], image_couverture [BLOB], statut [StatutEvenement], capacite_max, artiste_id)
- `Ticket` (id, evenement_id, user_id, prix, date_achat)

**Enums:**
- `TypeEvenement`: Exposition, Concert, Spectacle, Conférence
- `StatutEvenement`: À venir, Terminé, Annulé

---

### 6.6 Réclamations View (`app_reclamationsartiste`)

**Purpose:** Artist submits and tracks their reclamations (complaints).

**Layout:**
Two-tab panel:
1. **Tab "Envoyer Réclamation"** (default)
2. **Tab "Mes Réclamations"** (active if search/filter is applied)

**Tab 1 – Soumettre une Réclamation:**
```
Form fields:
  - type (ComboBox: Paiement, Oeuvre, Evènement, Compte)
  - texte (TextArea, 5 rows, placeholder "Décrivez votre problème en détail...")
  - file (FileChooser — PDF, JPG, JPEG, PNG, max 5MB, optional)
Buttons: Réinitialiser | Envoyer la réclamation
```

**Tab 2 – Mes Réclamations (list):**
- Filter row:
  - Search input (by texte)
  - Statut filter ComboBox: Tous / Non traitée / En cours / Traitée / Archivée
  - Type filter ComboBox: Tous / Paiement / Oeuvre / Evènement / Compte
- Table/list of reclamations:
  - Date: `dd/MM/yyyy`
  - Type badge: color by type
  - Texte: first 60 chars
  - Statut badge:
    - Non traitée → yellow (`#FFC107`)
    - En cours → blue (`#0DCAF0`)
    - Traitée → green (`#198754`)
    - Archivée → grey (`#6C757D`)
  - File attachment indicator (if file_name is not null)
  - "Voir détails" → opens detail dialog

**Detail dialog:**
- Full reclamation text
- Date
- Type + Statut badges
- Attached file (download button if present)
- List of **réponses** (admin replies):
  - Each: reply text + date
  - Shown in chronological order

**Entity: Reclamation** (id, user_id, texte, date_creation, statut [StatutReclamation], type [TypeReclamation], file_name)
**Entity: Reponse** (id, reclamation_id, texte, date_creation, admin_id)

---

### 6.7 Statistiques View (`app_statistiquesartiste`)

**Purpose:** Artist views analytics about their content engagement.

**Layout (4 chart sections, stacked vertically):**

#### Section 1 – Répartition par collection (Pie/Doughnut chart)
- Title: "Répartition des [metric] par collection"
- Metric switcher (◀ ▶): Interactions / J'aime / Favoris / Commentaires / Oeuvres
- **PieChart**: one slice per collection, colored from palette
  - `#4f46e5`, `#06b6d4`, `#22c55e`, `#f59e0b`, `#ef4444`, `#ec4899`, `#a855f7`, `#14b8a6`, …
- Legend: collection name + value

**Query:** 
```sql
SELECT c.id AS collectionId, c.titre AS collectionTitle,
  COUNT(DISTINCT o.id) AS oeuvresCount,
  SUM(l.id IS NOT NULL) AS likesCount,
  SUM(f.id IS NOT NULL) AS favorisCount,
  SUM(com.id IS NOT NULL) AS commentairesCount,
  (COUNT(DISTINCT l.id) + COUNT(DISTINCT f.id) + COUNT(DISTINCT com.id)) AS interactionsCount
FROM collections c
LEFT JOIN oeuvre o ON o.collection_id = c.id
LEFT JOIN likes l ON l.oeuvre_id = o.id
LEFT JOIN favoris f ON f.oeuvre_id = o.id
LEFT JOIN commentaire com ON com.oeuvre_id = o.id
WHERE c.artiste_id = :userId
GROUP BY c.id
```

#### Section 2 – Commentaires reçus par jour (Line chart)
- Title: "Commentaires reçus par jour (mois)"
- Month switcher (◀ ▶): available months (e.g. "Avril 2026", "Mars 2026")
- **LineChart**: X = day of month (1–31), Y = comment count
- Max width: 700 px, height: 260 px

**Query:**
```sql
SELECT DAY(c.date_creation) AS day, COUNT(c.id) AS count
FROM commentaire c
JOIN oeuvre o ON c.oeuvre_id = o.id
JOIN collections col ON o.collection_id = col.id
WHERE col.artiste_id = :userId
  AND YEAR(c.date_creation) = :year
  AND MONTH(c.date_creation) = :month
GROUP BY DAY(c.date_creation)
ORDER BY day ASC
```

#### Section 3 – Top 3 œuvres (Bar chart)
- Title: "Top 3 œuvres"
- Metric switcher (◀ ▶): J'aime / Favoris / Commentaires / Interactions
- **BarChart**: 3 bars (top oeuvres by selected metric)
- Height: 300 px

**Query:**
```sql
SELECT o.titre,
  COUNT(DISTINCT l.id) AS likesCount,
  COUNT(DISTINCT f.id) AS favorisCount,
  COUNT(DISTINCT c.id) AS commentsCount
FROM oeuvre o
JOIN collections col ON o.collection_id = col.id
LEFT JOIN likes l ON l.oeuvre_id = o.id
LEFT JOIN favoris f ON f.oeuvre_id = o.id
LEFT JOIN commentaire c ON c.oeuvre_id = o.id
WHERE col.artiste_id = :userId
GROUP BY o.id
ORDER BY [selected_metric] DESC
LIMIT 3
```

#### Section 4 – Top 3 amateurs actifs (Leaderboard list)
- Title: "Top 3 amateurs actifs"
- List of up to 3 amateur users with:
  - Avatar initials circle (colored, 34×34 px)
  - `prenom nom`
  - ❤ X likes / 💬 X commentaires / ⭐ X favoris
- Empty state: "Aucune interaction amateur pour le moment."

---

## 7. Data Model Summary (Artist-Specific Entities)

### Collections
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| artiste_id | int (FK → User) |

### Oeuvre (abstract, subclasses by `classe` discriminator)
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| date_creation | LocalDate |
| image | byte[] (BLOB) |
| type | Enum TypeOeuvre |
| collection_id | int (FK → Collections) |

### Musique (extends Oeuvre, classe='musique')
| Field | Type |
|---|---|
| genre | Enum GenreMusique (Rock, Jazz, Classique, Pop) |
| audio | String (filename) |
| updatedAt | LocalDateTime |

### Livre (extends Oeuvre, classe='livre')
| Field | Type |
|---|---|
| categorie | String |
| prix_location | double |
| fichier_pdf | byte[] (BLOB) |

### Evenement
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| date_debut | LocalDateTime |
| date_fin | LocalDateTime |
| date_creation | LocalDate |
| type | Enum TypeEvenement |
| image_couverture | byte[] (BLOB) |
| statut | Enum StatutEvenement |
| capacite_max | int |
| artiste_id | int (FK → User) |

### Ticket
| Field | Type |
|---|---|
| id | int |
| evenement_id | int |
| user_id | int |
| prix | double |
| date_achat | LocalDateTime |

### LocationLivre
| Field | Type |
|---|---|
| id | int |
| livre_id | int |
| user_id | int |
| date_location | LocalDateTime |
| nombre_jours | int |
| prix_total | double |

### Commentaire
| Field | Type |
|---|---|
| id | int |
| oeuvre_id | int |
| user_id | int |
| texte | String |
| date_creation | LocalDate |

### Reponse
| Field | Type |
|---|---|
| id | int |
| reclamation_id | int |
| texte | String |
| date_creation | LocalDate |
| admin_id | int (FK → User) |

---

## 8. JavaFX Project Structure (Artist module)

```
artium-javafx/
└── src/main/java/com/artium/
    ├── model/
    │   ├── Collections.java
    │   ├── Oeuvre.java
    │   ├── Musique.java        (extends Oeuvre)
    │   ├── Livre.java          (extends Oeuvre)
    │   ├── Evenement.java
    │   ├── Ticket.java
    │   ├── LocationLivre.java
    │   ├── Commentaire.java
    │   ├── Reponse.java
    │   └── enums/
    │       ├── Specialite.java       (Peintre, Sculpteur, Photographe, Musicien, Auteur)
    │       ├── TypeOeuvre.java       (Peinture, Sculpture, Photographie, Musique, Livre)
    │       ├── GenreMusique.java     (Rock, Jazz, Classique, Pop)
    │       ├── TypeEvenement.java    (Exposition, Concert, Spectacle, Conférence)
    │       ├── StatutEvenement.java  (À venir, Terminé, Annulé)
    │       ├── TypeReclamation.java  (Paiement, Oeuvre, Evènement, Compte)
    │       └── StatutReclamation.java (Non traitée, En cours, Traitée, Archivée)
    ├── repository/
    │   ├── CollectionsRepository.java
    │   ├── OeuvreRepository.java
    │   ├── MusiqueRepository.java
    │   ├── LivreRepository.java
    │   ├── EvenementRepository.java
    │   ├── TicketRepository.java
    │   ├── LocationLivreRepository.java
    │   ├── CommentaireRepository.java
    │   └── ReclamationRepository.java
    ├── service/
    │   ├── ArtisteProfileService.java   ← sidebar stats
    │   └── StatistiquesArtisteService.java ← all chart data
    └── controller/
        ├── NavbarArtisteController.java
        ├── ProfileHeaderController.java
        ├── SidebarArtisteController.java
        ├── CollectionsController.java
        ├── MesOeuvresController.java
        ├── MusiquesController.java
        ├── BibliothequArtisteController.java
        ├── EvenementsArtisteController.java
        ├── ReclamationsArtisteController.java
        └── StatistiquesArtisteController.java
```

---

## 9. Route → FXML Mapping

| Web route | Web URL | JavaFX FXML |
|---|---|---|
| app_collections_front | /artiste-collections | artist/Collections.fxml |
| app_oeuvre_front | /artiste-oeuvres | artist/MesOeuvres.fxml |
| app_musiqueartiste | /musiqueartiste | artist/Musiques.fxml |
| app_bibliothequeartiste | /artiste-bibliotheque | artist/Bibliotheque.fxml |
| app_eventsartiste | /artiste-evenements | artist/Evenements.fxml |
| app_reclamationsartiste | /reclamationsartiste | artist/Reclamations.fxml |
| app_statistiquesartiste | /artiste-statistiques | artist/Statistiques.fxml |

---

## 10. CSS / Styling Notes

The artist front-office uses a **light social-network style** (Socialite/Bootstrap 5 "bg-mode" theme), not the dark admin theme.

Key colors:
- Background: `#f9fafb` (light grey page)
- Card background: `white`
- Primary accent: `#0d6efd` (Bootstrap blue)
- Text muted: `#6c757d`
- Success green: `#198754`
- Warning yellow: `#ffc107`
- Info cyan: `#0dcaf0`
- Danger red: `#dc3545`

Badge styles (JavaFX CSS):
```css
.badge-primary  { -fx-background-color: #cfe2ff; -fx-text-fill: #084298; -fx-background-radius: 4; -fx-padding: 2 6; }
.badge-success  { -fx-background-color: #d1e7dd; -fx-text-fill: #0f5132; -fx-background-radius: 4; -fx-padding: 2 6; }
.badge-warning  { -fx-background-color: #fff3cd; -fx-text-fill: #664d03; -fx-background-radius: 4; -fx-padding: 2 6; }
.badge-info     { -fx-background-color: #cff4fc; -fx-text-fill: #055160; -fx-background-radius: 4; -fx-padding: 2 6; }
.badge-danger   { -fx-background-color: #f8d7da; -fx-text-fill: #842029; -fx-background-radius: 4; -fx-padding: 2 6; }
.badge-secondary{ -fx-background-color: #e2e3e5; -fx-text-fill: #41464b; -fx-background-radius: 4; -fx-padding: 2 6; }

.profile-card {
    -fx-background-color: white;
    -fx-background-radius: 12;
    -fx-border-color: #dee2e6;
    -fx-border-radius: 12;
    -fx-effect: dropshadow(gaussian, rgba(0,0,0,0.05), 8, 0, 0, 2);
}

.tab-nav-item {
    -fx-background-color: transparent;
    -fx-text-fill: #6c757d;
    -fx-padding: 8 16;
    -fx-cursor: hand;
    -fx-border-color: transparent transparent #dee2e6 transparent;
    -fx-border-width: 0 0 2 0;
}
.tab-nav-item.active {
    -fx-text-fill: #0d6efd;
    -fx-border-color: transparent transparent #0d6efd transparent;
}

.music-card {
    -fx-background-color: white;
    -fx-background-radius: 8;
    -fx-border-color: #e9ecef;
    -fx-border-radius: 8;
    -fx-padding: 12;
}

.event-row {
    -fx-background-color: white;
    -fx-border-color: #dee2e6;
    -fx-border-radius: 8;
    -fx-padding: 12;
}
```

---

## 11. Key JavaFX Implementation Patterns

### 11.1 Tab switching (profile nav)
```java
// ProfileHeaderController.java
tabCollections.setOnAction(e -> mainController.loadArtistView("/fxml/artist/Collections.fxml"));
tabMusiques.setOnAction(e -> mainController.loadArtistView("/fxml/artist/Musiques.fxml"));
// etc.
// Highlight active tab by toggling CSS class .active
```

### 11.2 Slide-out Panel (offcanvas equivalent)
Use a `DrawerPane` — a custom `VBox` that slides in from the right:
```java
public class DrawerPane extends VBox {
    // slides in from right using TranslateTransition
    public void show() {
        TranslateTransition t = new TranslateTransition(Duration.millis(300), this);
        t.setToX(0);
        t.play();
    }
    public void hide() {
        TranslateTransition t = new TranslateTransition(Duration.millis(300), this);
        t.setToX(getWidth());
        t.play();
    }
}
```

### 11.3 Confirm Dialog
```java
Alert alert = new Alert(Alert.AlertType.CONFIRMATION, "Supprimer cet événement ?", ButtonType.YES, ButtonType.NO);
alert.showAndWait().ifPresent(bt -> { if (bt == ButtonType.YES) { /* delete */ } });
```

### 11.4 Audio Player (Musiques)
```java
Media media = new Media(new File(audioPath).toURI().toString());
MediaPlayer player = new MediaPlayer(media);
// Play/pause toggle button
playBtn.setOnAction(e -> {
    if (player.getStatus() == MediaPlayer.Status.PLAYING) player.pause();
    else player.play();
});
```

### 11.5 Image from BLOB
```java
// Convert byte[] from DB to JavaFX Image
Image img = new Image(new ByteArrayInputStream(imageBytes));
imageView.setImage(img);
```

### 11.6 Statistics Charts (JavaFX built-in)
```java
// Pie chart (collections distribution)
PieChart pieChart = new PieChart();
statsByCollection.forEach(row ->
    pieChart.getData().add(new PieChart.Data(row.collectionTitle, row.interactionsCount))
);

// Line chart (comments per day)
CategoryAxis xAxis = new CategoryAxis();
NumberAxis yAxis = new NumberAxis();
LineChart<String, Number> lineChart = new LineChart<>(xAxis, yAxis);
XYChart.Series<String, Number> series = new XYChart.Series<>();
commentsPerDay.forEach((day, count) ->
    series.getData().add(new XYChart.Data<>(String.valueOf(day), count))
);

// Bar chart (top 3 oeuvres)
CategoryAxis xAxis = new CategoryAxis();
NumberAxis yAxis = new NumberAxis();
BarChart<String, Number> barChart = new BarChart<>(xAxis, yAxis);
XYChart.Series<String, Number> series = new XYChart.Series<>();
top3Oeuvres.forEach(o ->
    series.getData().add(new XYChart.Data<>(o.titre, o.likesCount))
);
```

---

## 12. Summary – What to Build (Phase 1: Artist Interfaces)

1. **NavbarArtiste.fxml** – top bar: logo, search, notifications bell, user avatar dropdown with logout + theme toggle
2. **ProfileHeader.fxml** – cover image, avatar, name, specialite/ville/inscription, Edit Profile button, tab navigation (specialite-conditional)
3. **SidebarArtiste.fxml** – "À propos" card + "Statistiques" mini-card
4. **ArtistMain.fxml** – root layout: NavbarArtiste (top) + HBox(ProfileHeader+Content | SidebarArtiste)
5. **Collections.fxml** – accordion list of collections with oeuvre grid inside each
6. **MesOeuvres.fxml** – oeuvre grid with filters, Add/Edit/Delete (Peintre/Sculpteur/Photographe)
7. **Musiques.fxml** – music list with audio player, search+sort bar, Add/Edit/Delete (Musicien)
8. **Bibliotheque.fxml** – book grid with rental info, Add/Edit/Delete (Auteur)
9. **Evenements.fxml** – event list with status, Add/Edit/Cancel/Delete + AI estimate badge
10. **Reclamations.fxml** – two-tab: submit form + filtered list with replies dialog
11. **Statistiques.fxml** – 4 chart sections: pie (by collection) + line (comments/day) + bar (top oeuvres) + leaderboard (top amateurs)
12. **CSS** – `artist-theme.css` with social-network light styling
13. **Services** – `ArtisteProfileService`, `StatistiquesArtisteService`, repository classes
14. **Edit Profile dialog** – offcanvas-style drawer with all user fields + photo upload
