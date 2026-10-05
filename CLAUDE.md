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
- Request-Lebenszyklus: `index.php` → `Request::fromGlobals()` → `Router::dispatch()` → Controller gibt `Response` zurück → `send()` nur einmal am Ende von `index.php`. Controller senden nie selbst.
- Routen in `config/routes.php` (gibt `function (Router $router)` zurück). Platzhalter `{id}` werden als benannte Argumente übergeben: Controller-Parameter muss gleich heißen (`string $id`, immer string).
- Fehlerbehandlung: `try/catch (Throwable)` in `index.php` → `ErrorHandler::toResponse()`. `JsonException` → 400, alles andere → 500 (wird geloggt). Debug-Infos nur bei `config/app.php` `debug => true`.

## Code-Konventionen
- Code komplett auf Englisch: Bezeichner, Kommentare, Docblocks, Fehlermeldungen. (Erklärungen im Chat bleiben Deutsch.)
- Variablen und Properties in camelCase (`$className`), nicht snake_case.
- Getter mit `get`-Präfix (`getMethod()`, `getStatus()`), wie in PSR-7.
- Unveränderliche Objekte haben keine Setter; Änderungen über `with...()`, das einen Klon zurückgibt (`withHeader()`).

## Git-Konventionen
- Commit-Messages immer auf Englisch, kurz: eine Zeile, Imperativ, max. ~50 Zeichen (z.B. `Add router and front controller`). Body nur, wenn wirklich nötig.

## Umgebung
- Intel-Mac: Homebrew wird nicht mehr unterstützt.
- PHP 8.5 + Composer über php.new (Herd Lite, `~/.config/herd-lite/bin`), Node 24 über den offiziellen .pkg-Installer.
- PHP ist statisch gebaut: kein Xdebug möglich. Debugging über `error_log()` (erscheint im `php -S`-Terminal), Debug-JSON und curl.
- VS Code mit Intelephense (Formatter, Find References). Projekt-Settings in `.vscode/settings.json` (nicht committet): Ruler bei 120, Inline-KI-Vorschläge aus.
- zsh: in curl-Beispielen keine `#`-Kommentare hinter Befehlen; URLs mit `?` quoten.
- GitHub: https://github.com/jouliangerges/budget-tracker (Commits mit GitHub-No-Reply-Adresse).

## Projektstand
- [x] Milestone 1: Ordnerstruktur, `composer.json`, `package.json`, `.gitignore`, `git init`
- [x] `composer install` + `npm install`, erster Commit
- [x] Milestone 2: Front Controller + Router (Request-Lebenszyklus von `public/index.php` bis zum Controller)
  - [x] 2.1 Front Controller `public/index.php`
  - [x] 2.2 `Core/Response` (immutable, `json()`, `noContent()`, `withHeader()`)
  - [x] 2.3 `Core/Request` (`fromGlobals()`, JSON-Body, wirft `JsonException`)
  - [x] 2.4 `Core/Router` (flache Routen-Liste, 404), `config/routes.php`, `HealthController`
  - [x] 2.5 Platzhalter `{id}` per Regex + 405 bei falscher Methode
  - [x] 2.6 `Core/ErrorHandler` + try/catch in `index.php`, `set_error_handler` (Warnungen → `ErrorException`), `config/app.php` (`debug`)
- [ ] Milestone 3: Datenbank — **hier geht es weiter**

## Nächste Sitzung: Einstieg
1. `git status` prüfen: ist der Stand von 2.6 committet und gepusht?
2. Milestone 3 mit dem User besprechen — **Vorschlag, noch nicht abgestimmt**:
   - 3.1 Schema entwerfen: Tabelle `transactions` (z.B. `id`, `amount` als Integer in Cent statt Float, `type` income/expense, `category`, `description`, `date`, `created_at`). Erst besprechen, dann SQL.
   - 3.2 Migration: SQL-Datei in `database/migrations/` + kleines CLI-Script, das sie ausführt; DB-Datei `database/budget.sqlite` (gitignored).
   - 3.3 `Core/Database`: PDO-Verbindung zu SQLite (`ERRMODE_EXCEPTION`, `FETCH_ASSOC`), Pfad aus `config/`.
   - 3.4 `Models/Transaction` + `Repositories/TransactionRepository` (`findAll()`, `findById()`), nur Prepared Statements.
   - 3.5 `TransactionController::index()` / `show()` + Routen `GET /api/transactions`, `GET /api/transactions/{id}`; `HealthController` bleibt.
   - Danach (Milestone 4): Schreiben (`POST`/`PUT`/`DELETE`) inkl. Validierung → 422.
3. Offene Fragen an den User: Kategorien fest oder eigene Tabelle? Wie werden Controller an das Repository kommen (manuell im Router vs. kleiner Container)?

## Arbeitsweise
- User schreibt den Code selbst, Claude beschreibt die Aufgabe (Anforderungen, Fallstricke, curl-Tests) und macht danach ein Review. Boilerplate darf Claude direkt schreiben.
- Wenn Claude Code des Users anpasst: nur das ändern, was für die Aufgabe nötig ist. Keine Umbenennungen, Umformatierungen oder Umstrukturierungen nebenbei; Stilvorschläge stattdessen im Review nennen.