# ARTIUM – JavaFX Admin Dashboard & Menu – Complete Agent Guide

> This file contains every detail an agent needs to recreate the **Artium** admin dashboard and sidebar menu in a JavaFX desktop application, faithfully mirroring the existing Symfony/Twig web version.

---

## 1. Application Overview

**Project name:** Artium  
**Language (target):** Java 17+, JavaFX 21  
**Build tool:** Maven (recommended)  
**DB:** MySQL (same schema as the web app)  
**Architecture:** MVC — FXML views, controller classes, service/repository layer using JDBC or Hibernate.

---

## 2. Data Model (Entities)

### 2.1 User
| Field | Type | Notes |
|---|---|---|
| id | int (PK) | auto-increment |
| nom | String | |
| prenom | String | |
| date_naissance | LocalDate | |
| email | String | unique |
| mdp | String | hashed password |
| role | Enum Role | Admin / Artiste / Amateur |
| statut | Enum Statut | Activé / Bloqué |
| date_inscription | LocalDate | |
| num_tel | String | |
| ville | String | |
| biographie | String (nullable) | |
| specialite | Enum Specialite (nullable) | |
| centre_interet | List\<CentreInteret\> (nullable) | |
| photoProfil | String (nullable) | filename |

**Enum Role:** `Admin`, `Artiste`, `Amateur`  
**Enum Statut:** `Activé`, `Bloqué`

### 2.2 Oeuvre
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| date_creation | LocalDate |
| image | byte[] |
| type | Enum TypeOeuvre |
| collection_id | int (FK → Collections) |

### 2.3 Evenement
| Field | Type |
|---|---|
| id | int |
| titre | String |
| description | String |
| date_debut | LocalDateTime |
| date_fin | LocalDateTime |
| date_creation | LocalDate |
| type | Enum TypeEvenement |
| image_couverture | byte[] |
| statut | Enum StatutEvenement |
| capacite_max | int |

### 2.4 Reclamation
| Field | Type |
|---|---|
| id | int |
| user_id | int (FK → User) |
| texte | String |
| date_creation | LocalDate |
| statut | Enum StatutReclamation | Non traitée / En cours / Traitée / Archivée |
| type | Enum TypeReclamation | Paiement / Oeuvre / Evènement / Compte |
| file_name | String (nullable) |
| updated_at | LocalDateTime |

---

## 3. Dashboard Statistics (what to query)

The dashboard shows **6 summary cards** and **4 chart/list sections**.

### 3.1 Summary Cards

| Card | Icon (emoji equiv.) | Color | Query |
|---|---|---|---|
| Utilisateurs | 👥 | Blue (#2196F3 / #E3F2FD bg) | `SELECT COUNT(*) FROM user` |
| Artistes | 🎭 | Purple (#8E24AA / #F3E5F5 bg) | `SELECT COUNT(*) FROM user WHERE role='Artiste'` |
| Amateurs | 😊 | Orange (#FF9800 / #FFF3E0 bg) | `SELECT COUNT(*) FROM user WHERE role='Amateur'` |
| Œuvres | 🖼 | Teal (#43CEA2 / #E0F7FA bg) | `SELECT COUNT(*) FROM oeuvre` |
| Réclamations | ⚠️ | Red (#E53935 / #FFEBEE bg) | `SELECT COUNT(*) FROM reclamation` |
| Événements | 📅 | Indigo (#3949AB / #E8EAF6 bg) | `SELECT COUNT(*) FROM evenement` |

Each card:
- White background, rounded corners (12 px), subtle shadow
- Circular icon badge (38 px) with colored background
- Big bold number (font-size ~28–32 px)
- Label in grey below
- On click → navigate to the corresponding admin list view (route mapping below)

### 3.2 Charts

**Inscriptions chart (line chart, left 2/3 width)**
- X-axis: last 6 months labels (e.g., "janv. 2025")
- Y-axis: count of new users per month
- Query: `SELECT COUNT(*) FROM user WHERE date_inscription >= :start AND date_inscription < :end` for each month window
- Color: blue line (`#007BFF`), light blue fill

**Répartition des rôles (doughnut / pie chart, right 1/3 width)**
- Slices: Admin (blue), Artistes (purple), Amateurs (orange)
- Legend at bottom

### 3.3 Recent Activity Lists

**Inscriptions récentes (24h)** — left half
- Query: `SELECT * FROM user WHERE date_inscription >= NOW() - INTERVAL 1 DAY ORDER BY date_inscription DESC`
- Show: `prenom nom` + `date_inscription`

**Réclamations récentes (24h)** — right half
- Query: `SELECT * FROM reclamation WHERE date_creation >= NOW() - INTERVAL 1 DAY AND statut='Non traitée' ORDER BY date_creation DESC`
- Show: first 40 chars of `texte` + `date_creation`

### 3.4 Top Artistes & Calendar

**Top artistes** — left half
- Find all users with role=Artiste, count their oeuvres (via collections→oeuvres), sort desc, take top 5
- Show: avatar image (photoProfil) or default person icon, `nom prenom`, number of oeuvres

**Calendrier des événements** — right half
- Query all events ordered by `date_debut ASC`
- Show in a month-grid calendar (JavaFX custom calendar or a library)
- Filter dropdown: All / Upcoming / Today
- Today's events highlighted in orange (#FF9800)

---

## 4. Sidebar Menu Structure

```
[LOGO] Artium
├── 🏠  Tableau de bord         → /dashboard (app_admin)
├── 👤  Utilisateurs            [collapsible]
│   ├── Artistes                → /admin/users/artistes (app_user_artistes)
│   └── Amateurs                → /admin/users/amateurs (app_user_amateurs)
├── 🖼   Oeuvres                → /admin/oeuvres (oeuvres)
├── 📖  Bibliothèque            → /admin/livres (livres)
├── 🎵  Musique                 → /admin/musiques (musiques)
├── 📅  Evènements              [collapsible]
│   ├── Evènements              → /admin/evenements (evenements)
│   └── Galeries                → /admin/galeries (galeries)
└── ⚠️  Réclamations            → /admin/reclamations (app_reclamation_admin_index)
```

**Sidebar visual specs:**
- Dark sidebar panel (standard admin-dark or white, matching "Dashui" Bootstrap theme)
- Fixed width ~260 px, full-height
- Logo image at top: `Porcelain PNG Logo.png` (white/light logo)
- Nav items: icon (Feather icon set equivalent) + label text
- Active item highlighted
- Collapsible sub-menus animate open/close
- Uses `simplebar`-style scrollable inner area

---

## 5. Top Navbar

**Left:**
- Hamburger menu toggle (☰) to collapse/expand the sidebar

**Right (from left to right):**
1. **Theme toggle** button (light/dark mode) — sun/moon icon — dropdown with "Clair" / "Sombre"
2. **Notifications bell** (🔔)
   - Badge dot (red) when there are recent unprocessed reclamations
   - Dropdown list: shows last 5 reclamations, each with:
     - Icon by type: 💳 Paiement / 🖼 Oeuvre / 📅 Evènement / 👤 Compte
     - `prenom nom` of the user
     - First 50 chars of `texte`
     - `date_creation` formatted as `dd/MM/yyyy HH:mm`
     - Status badge: green=Traitée, blue=En cours, yellow=Non traitée
   - Trash button to clear the dropdown list (client-side)
3. **User avatar** (circular, 48 px)
   - Shows `photoProfil` image if available, else a grey person icon
   - Dropdown:
     - Avatar + `nom prenom` + `email`
     - Divider
     - 👤 Profil  → /admin/user/{id}
     - Divider
     - ⏻ Se déconnecter → /logout

---

## 6. Layout Structure (JavaFX)

```
Stage
└── Scene
    └── BorderPane (root)
        ├── LEFT:  SidebarView (VBox, 260 px wide)
        │           └── ListView / custom NavItems
        ├── TOP:   NavbarView (HBox, full width)
        └── CENTER: ContentArea (StackPane or ScrollPane)
                    └── DashboardView (currently loaded)
                        ├── HBox (6 stat cards)
                        ├── HBox (line chart + pie chart)
                        ├── HBox (recent signups list + recent reclamations list)
                        └── HBox (top artistes list + events calendar)
```

---

## 7. JavaFX Project Structure

```
artium-admin/
├── pom.xml
└── src/
    └── main/
        ├── java/
        │   └── com/artium/admin/
        │       ├── MainApp.java                  ← entry point
        │       ├── config/
        │       │   └── DatabaseConfig.java       ← JDBC connection
        │       ├── model/
        │       │   ├── User.java
        │       │   ├── Oeuvre.java
        │       │   ├── Evenement.java
        │       │   ├── Reclamation.java
        │       │   └── enums/
        │       │       ├── Role.java
        │       │       ├── Statut.java
        │       │       ├── StatutReclamation.java
        │       │       └── TypeReclamation.java
        │       ├── repository/
        │       │   ├── UserRepository.java
        │       │   ├── OeuvreRepository.java
        │       │   ├── EvenementRepository.java
        │       │   └── ReclamationRepository.java
        │       ├── service/
        │       │   └── DashboardService.java      ← aggregates all stats
        │       └── controller/
        │           ├── MainController.java        ← handles sidebar nav + content swap
        │           ├── DashboardController.java   ← wires dashboard FXML
        │           ├── SidebarController.java
        │           └── NavbarController.java
        └── resources/
            └── com/artium/admin/
                ├── fxml/
                │   ├── Main.fxml                  ← BorderPane root
                │   ├── Sidebar.fxml
                │   ├── Navbar.fxml
                │   └── Dashboard.fxml
                ├── css/
                │   └── admin-theme.css
                └── images/
                    └── logo.png
```

---

## 8. Key JavaFX Implementation Details

### 8.1 MainApp.java
```java
public class MainApp extends Application {
    @Override
    public void start(Stage stage) throws Exception {
        FXMLLoader loader = new FXMLLoader(getClass().getResource("/fxml/Main.fxml"));
        Parent root = loader.load();
        Scene scene = new Scene(root, 1280, 800);
        scene.getStylesheets().add(getClass().getResource("/css/admin-theme.css").toExternalForm());
        stage.setTitle("Artium – Admin");
        stage.setScene(scene);
        stage.show();
    }
}
```

### 8.2 Main.fxml (BorderPane)
```xml
<BorderPane xmlns:fx="http://javafx.com/fxml"
            fx:controller="com.artium.admin.controller.MainController">
    <left>
        <fx:include source="Sidebar.fxml" fx:id="sidebar" />
    </left>
    <top>
        <fx:include source="Navbar.fxml" fx:id="navbar" />
    </top>
    <center>
        <!-- Content loaded dynamically here -->
        <StackPane fx:id="contentArea" />
    </center>
</BorderPane>
```

### 8.3 Content Swapping
```java
// MainController.java
public void loadView(String fxmlPath) {
    FXMLLoader loader = new FXMLLoader(getClass().getResource(fxmlPath));
    Node view = loader.load();
    contentArea.getChildren().setAll(view);
}
// Called on sidebar item click:
// loadView("/fxml/Dashboard.fxml");
// loadView("/fxml/users/UserList.fxml");
// etc.
```

### 8.4 Stat Card (reusable custom component)
Create a `StatCard` class extending `VBox`:
```java
public class StatCard extends VBox {
    public StatCard(String iconUnicode, String colorHex, String bgColorHex,
                    int count, String label, Runnable onClickAction) {
        // Style: white bg, border-radius, shadow
        // Icon in colored circle
        // Big number Label
        // Label text
        // Arrow icon bottom-right
        setOnMouseClicked(e -> onClickAction.run());
    }
}
```

### 8.5 Charts (use JavaFX built-in)
```java
// Line chart – inscriptions
CategoryAxis xAxis = new CategoryAxis();
NumberAxis yAxis = new NumberAxis();
LineChart<String, Number> lineChart = new LineChart<>(xAxis, yAxis);
XYChart.Series<String, Number> series = new XYChart.Series<>();
// Add data points from DashboardService.getMonthlySignups()
lineChart.getData().add(series);

// Pie chart – roles
PieChart pieChart = new PieChart();
pieChart.getData().addAll(
    new PieChart.Data("Admin", adminCount),
    new PieChart.Data("Artistes", artistesCount),
    new PieChart.Data("Amateurs", amateursCount)
);
```

### 8.6 Calendar Widget
Use a `GridPane` to build a simple month calendar:
- Header row: days of week (Lun, Mar, …, Dim)
- Cell rows: day numbers; highlight event days with a colored dot or tooltip
- Navigation buttons (◀ ▶) to change month
- Filter ComboBox: "Tous" / "À venir" / "Aujourd'hui"

### 8.7 Sidebar Navigation Items
```java
// Sidebar items (icons using FontAwesomeFX or emoji text)
String[][] navItems = {
    {"🏠", "Tableau de bord", "/fxml/Dashboard.fxml"},
    {"👤", "Utilisateurs",    null},            // collapsible parent
    {"",   "Artistes",        "/fxml/users/Artistes.fxml"},
    {"",   "Amateurs",        "/fxml/users/Amateurs.fxml"},
    {"🖼", "Oeuvres",         "/fxml/OeuvreList.fxml"},
    {"📖", "Bibliothèque",    "/fxml/BiblioList.fxml"},
    {"🎵", "Musique",         "/fxml/MusiqueList.fxml"},
    {"📅", "Evènements",      null},            // collapsible parent
    {"",   "Evènements",      "/fxml/evenements/EvenementList.fxml"},
    {"",   "Galeries",        "/fxml/evenements/GalerieList.fxml"},
    {"⚠️", "Réclamations",    "/fxml/ReclamationList.fxml"},
};
```

---

## 9. CSS Theme (admin-theme.css)

```css
/* Root layout */
.root { -fx-font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif; }

/* Sidebar */
.sidebar {
    -fx-background-color: #1e2a3b;
    -fx-min-width: 260px;
    -fx-max-width: 260px;
}
.sidebar .nav-item {
    -fx-text-fill: #adb5bd;
    -fx-font-size: 14px;
    -fx-padding: 10 16 10 16;
    -fx-cursor: hand;
}
.sidebar .nav-item:hover,
.sidebar .nav-item.active {
    -fx-background-color: rgba(255,255,255,0.08);
    -fx-text-fill: #ffffff;
}

/* Navbar */
.navbar {
    -fx-background-color: #ffffff;
    -fx-border-color: #e9ecef;
    -fx-border-width: 0 0 1 0;
    -fx-padding: 8 20 8 20;
}

/* Dashboard card */
.dashboard-card {
    -fx-background-color: white;
    -fx-background-radius: 12;
    -fx-border-color: #f0f1f3;
    -fx-border-radius: 12;
    -fx-effect: dropshadow(gaussian, rgba(60,60,60,0.07), 12, 0, 0, 2);
    -fx-padding: 16;
    -fx-min-width: 170px;
    -fx-pref-width: 200px;
    -fx-max-width: 220px;
    -fx-alignment: center;
}
.dashboard-card:hover {
    -fx-effect: dropshadow(gaussian, rgba(60,60,60,0.13), 24, 0, 0, 6);
    -fx-scale-x: 1.02;
    -fx-scale-y: 1.02;
}

/* Dashboard number */
.stat-number {
    -fx-font-size: 32px;
    -fx-font-weight: bold;
    -fx-text-fill: #222222;
}
.stat-label {
    -fx-font-size: 14px;
    -fx-text-fill: #666666;
}

/* Card icon circle */
.icon-circle {
    -fx-background-radius: 50%;
    -fx-min-width: 38px;
    -fx-min-height: 38px;
    -fx-max-width: 38px;
    -fx-max-height: 38px;
    -fx-alignment: center;
}
.icon-users    { -fx-background-color: #e3f2fd; -fx-text-fill: #2196f3; }
.icon-artistes { -fx-background-color: #f3e5f5; -fx-text-fill: #8e24aa; }
.icon-amateurs { -fx-background-color: #fff3e0; -fx-text-fill: #ff9800; }
.icon-oeuvres  { -fx-background-color: #e0f7fa; -fx-text-fill: #43cea2; }
.icon-reclam   { -fx-background-color: #ffebee; -fx-text-fill: #e53935; }
.icon-events   { -fx-background-color: #e8eaf6; -fx-text-fill: #3949ab; }

/* List items */
.list-card {
    -fx-background-color: white;
    -fx-background-radius: 12;
    -fx-border-color: #e9ecef;
    -fx-border-radius: 12;
    -fx-effect: dropshadow(gaussian, rgba(60,60,60,0.07), 12, 0, 0, 2);
}

/* Badge */
.badge-success { -fx-background-color: #198754; -fx-text-fill: white; -fx-background-radius: 4; -fx-padding: 2 6; }
.badge-info    { -fx-background-color: #0dcaf0; -fx-text-fill: white; -fx-background-radius: 4; -fx-padding: 2 6; }
.badge-warning { -fx-background-color: #ffc107; -fx-text-fill: #212529; -fx-background-radius: 4; -fx-padding: 2 6; }
```

---

## 10. Database Service Layer

### DashboardService.java – methods to implement

```java
public class DashboardService {
    // Stat counts
    int countUsers();
    int countArtistes();
    int countAmateurs();
    int countOeuvres();
    int countReclamations();
    int countEvenements();

    // Charts
    List<Integer> getMonthlySignups(int months); // last N months, oldest first
    List<String>  getMonthlyLabels(int months);  // e.g. ["janv. 2025", ...]
    int[] getRolesData(); // [adminCount, artisteCount, amateurCount]

    // Lists
    List<User>        getRecentSignups();        // last 24h
    List<Reclamation> getRecentReclamations();   // last 24h, statut=Non traitée
    List<Map<String,Object>> getTopArtistes(int limit); // {nom, prenom, photoProfil, nbOeuvres}

    // Calendar
    List<Evenement> getAllEvenements();
}
```

---

## 11. Maven pom.xml Dependencies

```xml
<dependencies>
    <!-- JavaFX -->
    <dependency>
        <groupId>org.openjfx</groupId>
        <artifactId>javafx-controls</artifactId>
        <version>21</version>
    </dependency>
    <dependency>
        <groupId>org.openjfx</groupId>
        <artifactId>javafx-fxml</artifactId>
        <version>21</version>
    </dependency>
    <!-- MySQL JDBC -->
    <dependency>
        <groupId>com.mysql</groupId>
        <artifactId>mysql-connector-j</artifactId>
        <version>8.3.0</version>
    </dependency>
    <!-- Optional: ikonli for icons (Feather / FontAwesome) -->
    <dependency>
        <groupId>org.kordamp.ikonli</groupId>
        <artifactId>ikonli-javafx</artifactId>
        <version>12.3.1</version>
    </dependency>
    <dependency>
        <groupId>org.kordamp.ikonli</groupId>
        <artifactId>ikonli-feather-pack</artifactId>
        <version>12.3.1</version>
    </dependency>
</dependencies>
```

---

## 12. Navigation Route Mapping (web → JavaFX view)

| Web route name | Web URL | JavaFX FXML |
|---|---|---|
| app_admin | /dashboard | Dashboard.fxml |
| app_user_index | /admin/users | users/UserList.fxml |
| app_user_artistes | /admin/users/artistes | users/Artistes.fxml |
| app_user_amateurs | /admin/users/amateurs | users/Amateurs.fxml |
| oeuvres | /admin/oeuvres | OeuvreList.fxml |
| livres | /admin/livres | BiblioList.fxml |
| musiques | /admin/musiques | MusiqueList.fxml |
| evenements | /admin/evenements | evenements/EvenementList.fxml |
| galeries | /admin/galeries | evenements/GalerieList.fxml |
| app_reclamation_admin_index | /admin/reclamations | ReclamationList.fxml |
| app_logout | /logout | → clear session, show login screen |

---

## 13. Flash Messages → JavaFX Notifications

The web app shows dismissible flash messages (success/error/warning/info) sliding in from top-right.  
In JavaFX, implement this as:
- A custom `Notification` overlay on the top-right of the Scene (using a `StackPane` overlay layer)
- Auto-dismiss after 5 seconds
- Animate with `TranslateTransition` sliding from right

---

## 14. Connection History Modal → JavaFX Dialog

The dashboard has a "Historique de mes connexions" button that opens a Bootstrap modal.  
In JavaFX, use a `Dialog<Void>` or a custom `Stage` with a `TableView` showing:
- Date/time of each connection
- IP address (if stored)

---

## 15. Sidebar Collapse

The hamburger button (☰) in the navbar toggles the sidebar:
- Expanded: 260 px wide, shows icon + label
- Collapsed: ~60 px wide, shows icon only (tooltip on hover)
- Use a `TranslateTransition` or `Timeline` to animate the width change

---

## 16. Dark Mode

- Store theme preference in a `ThemeManager` singleton
- Switch between two CSS files: `admin-theme.css` and `admin-theme-dark.css`
- Call `scene.getStylesheets().setAll(newThemePath)` on toggle
- Dark sidebar background: `#0f1623`, dark card background: `#1a2235`, text: `#e9ecef`

---

## 17. Summary of what to build (Phase 1: Dashboard + Menu only)

1. **Maven project** setup with JavaFX 21 + MySQL connector + Ikonli icons
2. **DatabaseConfig** – single JDBC connection or connection pool to the existing MySQL DB
3. **Model classes** – User, Oeuvre, Evenement, Reclamation + enums
4. **Repository layer** – raw JDBC queries for all stats
5. **DashboardService** – aggregates all dashboard data
6. **Main.fxml + MainController** – BorderPane root with sidebar + navbar + dynamic content area
7. **Sidebar.fxml + SidebarController** – vertical nav with collapsible groups, logo, active state
8. **Navbar.fxml + NavbarController** – hamburger toggle, theme switch, notifications bell, user avatar dropdown
9. **Dashboard.fxml + DashboardController** – 6 stat cards + 2 charts + 4 list/calendar sections
10. **admin-theme.css** – styling matching the web app's look and feel
11. **StatCard** custom component
12. **CalendarPane** custom component for the events calendar
