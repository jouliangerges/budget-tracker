# Portfolio-Projekt: Smart Budget Tracker
Tech-Stack: Pure PHP (Backend), Vanilla JavaScript, HTML, SCSS, SQLite.

## Mentor-Richtlinien für Claude Code:
1. Ich habe 4 Jahre Arbeitserfahrung mit PHP/JS/HTML/SCSS. Erkläre mir die Architektur (z.B. API-Endpunkte, MVC-Ansatz), statt nur Code zu schreiben.
2. Brich alle Aufgaben in kleine Teilschritte (Milestones) herunter. Ändere Code erst, wenn wir den Schritt besprochen haben.
3. Achte auf Best Practices (Schutz vor SQL-Injections, saubere Trennung von Logik und Design).

## Architektur-Entscheidungen
- Backend ist eine reine JSON-API (`/api/...`), Frontend holt Daten per `fetch()`.
- Front-Controller-Pattern: jeder Request läuft über `public/index.php` → Router → Controller.
- Schichten in `src/`: `Core/` (Router, Database, Request, Response), `Controllers/`, `Models/`, `Repositories/` (einziger Ort für SQL, nur Prepared Statements). `Services/` erst bei Bedarf.
- `public/` ist der Document Root; `src/`, `config/`, `database/` liegen bewusst außerhalb.
- Autoloading: Composer PSR-4, Namespace `App\` → `src/`. Kein Framework.
- SCSS: Quellen in `resources/scss/`, kompiliert mit dart-sass (`npm run scss:watch` / `scss:build`) nach `public/assets/css/`.
- JS: native ES-Module direkt in `public/assets/js/`, kein Bundler.
- Lokaler Server: `php -S localhost:8000 -t public public/index.php`

## Code-Konventionen
- Getter mit `get`-Präfix (`getMethod()`, `getStatus()`), wie in PSR-7.
- Unveränderliche Objekte haben keine Setter; Änderungen über `with...()`, das einen Klon zurückgibt (`withHeader()`).

## Git-Konventionen
- Commit-Messages immer auf Englisch, kurz: eine Zeile, Imperativ, max. ~50 Zeichen (z.B. `Add router and front controller`). Body nur, wenn wirklich nötig.

## Umgebung
- Intel-Mac: Homebrew wird nicht mehr unterstützt.
- PHP 8.5 + Composer über php.new (Herd Lite, `~/.config/herd-lite/bin`), Node 24 über den offiziellen .pkg-Installer.

## Projektstand
- [x] Milestone 1: Ordnerstruktur, `composer.json`, `package.json`, `.gitignore`, `git init` (noch kein Commit)
- [x] `composer install` + `npm install`, erster Commit
- [ ] Milestone 2: Front Controller + Router (Request-Lebenszyklus von `public/index.php` bis zum Controller)