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
- Datenbank: SQLite (bewusst statt MySQL: kein Server, Repo klonen und starten). Austauschbar halten: DSN aus `config/database.php`, Repositories nur Standard-SQL. Migrationen sind dialektspezifisch.
- `Core/Database`: lazy PDO-Verbindung (`getConnection()` mit `??=`), `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES false`; bei Treiber `sqlite` immer `PRAGMA foreign_keys = ON` (gilt pro Verbindung!).
- Schema: `categories` (`id`, `name` UNIQUE NOCASE) und `transactions` (`category_id` FK RESTRICT, `type` income/expense, `amount` INTEGER in Cent > 0, `description` nullable, `date` TEXT `YYYY-MM-DD`, `created_at`). Alle Tabellen `STRICT`. `type` gehört an die Transaktion, nicht an die Kategorie (z.B. Erstattungen). In PHP später als Backed Enum `TransactionType`.
- Migrationen: nummerierte `.sql`-Dateien in `database/migrations/`, kein `IF NOT EXISTS`. `php bin/migrate.php` führt neue Dateien je in einer Transaktion aus und merkt sie in Tabelle `migrations` (die legt das Script selbst an). Fehler → Rollback, STDERR, Exit-Code 1. Reset: `rm database/budget.sqlite`.
- SQL-Regel: fester SQL-String ohne Werte → `query()`/`exec()` ok; sobald ein Wert ins SQL kommt → immer `prepare()` mit Platzhaltern.
- Dependency Injection: kleiner `Core/Container` mit `set(id, factory)` / `get(id)` (Instanzen gemerkt), Definitionen explizit in `config/container.php`. Router ruft `$container->get($className)` statt `new`. Factories bekommen den Container als Argument; jede Klasse (auch ohne Abhängigkeiten) muss registriert sein. Nur Router und `config/container.php` rufen `get()` auf (kein Service Locator). Wie Slim + PHP-DI, aber ohne Autowiring (Reflection evtl. später als Lernschritt).

## Code-Konventionen
- Code komplett auf Englisch: Bezeichner, Kommentare, Docblocks, Fehlermeldungen. (Erklärungen im Chat bleiben Deutsch.)
- Variablen und Properties in camelCase (`$className`), nicht snake_case.
- Getter mit `get`-Präfix (`getMethod()`, `getStatus()`), wie in PSR-7.
- Unveränderliche Objekte haben keine Setter; Änderungen über `with...()`, das einen Klon zurückgibt (`withHeader()`).

## Git-Konventionen
- Commit-Messages immer auf Englisch, kurz: eine Zeile, Imperativ, max. ~50 Zeichen (z.B. `Add router and front controller`). Body nur, wenn wirklich nötig.

## Umgebung
- Intel-Mac: Homebrew wird nicht mehr unterstützt.
- PHP 8.5 über MacPorts (`/opt/local/bin/php`, `port select --set php php85`), Extensions als Ports (`php85-xdebug`, `php85-sqlite`, …). Eigene Einstellungen nur in `/opt/local/etc/php85/php.ini` (nicht in den generierten `.ini` unter `/opt/local/var/db/php85/`). Composer liegt in `/usr/local/bin/composer`. Node 24 über den offiziellen .pkg-Installer. Herd Lite (`~/.config/herd-lite`) ist nicht mehr im PATH, liegt nur noch als Fallback herum.
- Xdebug ist geladen, aber `xdebug.mode=off` in der `php.ini`. Pro Befehl einschalten: `XDEBUG_MODE=coverage` (macht `composer test:coverage`) bzw. `XDEBUG_MODE=debug` fürs Step-Debugging. Hinweis: `ini_get('xdebug.mode')` zeigt `""`, weil der ini-Parser ungequotetes `off` zu `""` macht; prüfen mit `xdebug_info('mode')`.
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
- [ ] Milestone 3: Datenbank
  - [x] 3.1 Schema-Entwurf (siehe Architektur-Entscheidungen)
  - [x] 3.2a Migration `001_create_categories_and_transactions.sql`
  - [x] 3.2b `Core/Database` + `config/database.php`
  - [x] 3.2c `bin/migrate.php`
  - [x] Seeds: `database/seeds/*.sql` + `bin/seed.php` (alle Dateien in einer Transaktion, kein Tracking; nicht idempotent). `composer migrate` / `composer seed`. Reset: `rm database/budget.sqlite && composer migrate && composer seed`
  - [x] 3.3a `Models/TransactionType` (Backed Enum, Fälle `Income`/`Expense`) + `Models/Transaction` (`final readonly`, private Properties, `fromRow()` DB → Model, `toArray()` für JSON). API-Keys in camelCase, `type` als `->value`.
  - [x] 3.3b `Repositories/TransactionRepository` (`findAll()`, `findById(int $id)`), per CLI getestet + Review
  - [x] 3.4 `Core/Container` + `config/container.php`, Router holt Controller per `$container->get()`
  - [ ] 3.5 `TransactionController::index()` / `show()` + Routen `GET /api/transactions`, `GET /api/transactions/{id}`; `HealthController` bleibt
- [ ] Milestone T: Tests (nach 3.4, vor 3.5). Ziel 100 % Coverage für `src/` (`public/index.php`, `bin/` ausgenommen). Coverage lokal (`composer test:coverage`: Text im Terminal, HTML in `coverage/html/`, `coverage/clover.xml` für die VS-Code-Extension Coverage Gutters) UND in CI (GitHub Actions).
  - [x] T.1 PHPUnit 13 (PHP ≥ 8.4), `phpunit.xml` (Suites Unit/Integration, `requireCoverageMetadata` → jeder Test braucht `#[CoversClass]`, zufällige Reihenfolge), `tests/` spiegelt `src/` (`App\Tests\`), `composer test`
  - [ ] T.2 — **hier geht es weiter**. Unit Tests: `Container`, `Response`, `TransactionType`/`Transaction`, `ErrorHandler`
  - [ ] T.3 `Router`, `Request` (evtl. Umbau wegen `php://input`)
    - Offener Bug: `ErrorHandler` macht aus jeder `JsonException` eine 400. Auch `Response::json()` wirft `JsonException` (z.B. kaputtes UTF-8 aus der DB) → Serverfehler wird als 400 gemeldet. Lösung: eigene Exception in `Request` für ungültige Bodies, `ErrorHandler` mappt nur die auf 400.
  - [ ] T.4 Integration: `Database` + `TransactionRepository` gegen `sqlite::memory:` mit Migration
  - [ ] T.5 GitHub Actions (PHP 8.5, Xdebug oder PCOV) + Coverage-Schwelle
- [ ] Milestone 4: Schreiben (`POST`/`PUT`/`DELETE`) inkl. Validierung → 422; Migration `002` für `updated_at` (SQLite hat kein `ON UPDATE`: im Repository setzen oder Trigger)

## Nächste Sitzung: Einstieg
1. `git status` prüfen: alles committet und gepusht?
2. T.2: `ContainerTest` (User schreibt, Claude beschreibt die Testfälle). Coverage vor dem Commit mit `composer test:coverage` prüfen.
3. Merken für 3.5: Route liefert `string $id`, Repository will `int`. Nicht blind `(int)` casten (`'abc'` → `0`), vorher prüfen (`ctype_digit()` oder Routen-Regex `\d+`).

## Lernnotizen
- Casts wie `(int)` können Fehler verstecken (`(int) ['category_id']` ergab still `1`). Wo gecastet wird, im Review genau hinschauen.

## Arbeitsweise
- User schreibt den Code selbst, Claude beschreibt die Aufgabe (Anforderungen, Fallstricke, curl-Tests) und macht danach ein Review. Boilerplate darf Claude direkt schreiben.
- Tests schreibt Claude (Wunsch des Users), Produktivcode schreibt der User. Braucht ein Test eine Änderung am Produktivcode (z.B. Getter), beschreibt Claude sie und der User setzt sie um.
- Wenn Claude Code des Users anpasst: nur das ändern, was für die Aufgabe nötig ist. Keine Umbenennungen, Umformatierungen oder Umstrukturierungen nebenbei; Stilvorschläge stattdessen im Review nennen.