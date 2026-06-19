# Repository Guidelines

## Project Structure & Module Organization

- `src/` — PHP library source (PSR-4: `LLENON\\OltInformation\\` → `src/`).
  - Key areas: `OLT/`, `Connections/`, `Adapters/`, `Console/`, `DTO/`, `Enum/`, `Exceptions/`, `Helpers/`, `config/`.
- `examples/` — usage examples / quick experiments.
- `vendor/` — Composer dependencies (do not edit; generated).
- `docker-compose.yml`, `Dockerfile` — local/dev containers (when used).

## Build, Test, and Development Commands

- `composer install` — install PHP dependencies into `vendor/`.
- `composer dump-autoload` — rebuild autoloader after adding/moving classes under `src/`.
- `php -l path/to/file.php` — quick syntax check for a file.
- Minimal smoke run (from README-style usage): run a small script under `examples/` that instantiates `OLT` + a vendor-specific command class (e.g., `VSolOLTCmd`) and prints results.

## Coding Style & Naming Conventions

- PHP: target `>=8.3` (see `composer.json`); prefer strict typing where already used in nearby code.
- Indentation: 2–4 spaces is acceptable; match the existing file you’re editing.
- Namespaces/classes: PSR-4 under `LLENON\\OltInformation\\...` with one class per file in `src/`.
- Filenames: keep existing vendor-model naming patterns (e.g., `DATACOM.php`, `FIBERHOME.php`) unless refactoring is explicitly required.

## Testing Guidelines

- No repository-wide test harness is currently defined (no `phpunit.xml`, PHPUnit dependency, or CI config).
- When adding tests, prefer PHPUnit and a `tests/` directory with names like `*Test.php`, and document the run command in `README.md`.

## Commit & Pull Request Guidelines

- Commit messages in history are short and descriptive (often lowercase, e.g., “add …”, “modify …”, “bug fix, …”). Follow the same style and keep one change per commit when possible.
- PRs should include: a brief summary, how to reproduce/verify (commands or example snippet), and any OLT model(s) affected.

## Security & Configuration Tips

- Never commit real credentials, IPs, or keys. Use placeholders in examples (`xxx`, `192.168.x.x`).
- Be careful with SSH/telnet connections: document required PHP extensions (e.g., `ext-ssh2`) and keep connection defaults explicit.
- Fiberhome TL1 local credentials may exist in `.env.local`; treat that file as local-only and never commit or echo its secrets.
- For Fiberhome manual checks, prefer the existing example script `examples/FIBERHOME.php` and a local test OLT config under `examples/config/olts`.
