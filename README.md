# Chamy

Chamy is a modular, theme-driven Content Management Platform designed for long-term extensibility, clear separation of concerns, and practical editorial workflows. This README summarizes the system, architecture, core concepts, and how to get started. For full technical details and background, see the `docs/` directory.

Contents
--------
- Overview
- Key concepts
- Architecture & core components
- Modules & Themes
- Layouts, Components & Content
- Content Types
- Content States, Versioning & Workflows
- API and Integrations
- Marketplace, Security & Validation
- Installation & Quick start
- Development & Contribution
- License & Support

Overview
--------
Chamy is built as a modular platform with a strict separation between system logic (core), functional extensions (modules), visual presentation (themes), and editorial content. The design goals are maintainability, predictable extensibility, multilingual support, and consistent theming across modules.

Key Concepts
------------
- Core: Kernel, manager registry, routing and system services that orchestrate boot and runtime behavior.
- Modules: Feature packages that implement business logic, API endpoints, admin areas, and optional migrations. Modules never embed presentation rules; they provide data and functionality.
- Themes: Responsible for all visual output (frontend + admin). Themes provide templates, components, layouts, assets and may provide safe module overrides.
- Layouts & Components: Structural and reusable units used by the visual system and content editor.
- Content Types: System-registered structured models (e.g. page, article, product) with field definitions, validation and API contracts.
- States & Versions: Content lifecycle with states (draft, review, published, archived, deleted), versioned snapshots and optional workflow processes.

Architecture & Core Components
------------------------------
Chamy follows layered architecture. Important subsystems include:

- Kernel: bootstraps configuration, registers managers, loads modules and themes, and prepares routing.
- Managers: specialized services such as `ModuleManager`, `ThemeManager`, `ContentManager`, `ContentTypeManager`, `ComponentManager`, `HookManager`, `PermissionManager`, `StateManager`, and `VersionManager`.
- Hook system: controlled extension points to let modules and themes augment behavior without breaking core invariants.
- Infrastructure: DB access, caching, filesystem storage, logging and asset handling.

Modules
-------
- Modules are delivered as packages with a manifest describing id, version, compatibility and permissions.
- They may provide migrations, API endpoints, admin UIs and content-type registrations.
- Modules integrate via defined interfaces and hooks; they must not force their own frontend styling — visual output stays theme-controlled.
- The Marketplace validates module structure, manifest, security rules and forbids inline CSS or other theme-escape attempts.

Themes
------
- Themes are the single source of truth for the UI: templates (Twig), components, layouts, language strings, and assets.
- Each theme has a `theme.json` manifest and a canonical folder structure (`templates/`, `components/`, `layouts/`, `assets/`, `module-overrides/`, `languages/`).
- Theme features: dark/light mode, theme inheritance (child themes), safe module overrides placed in `module-overrides/`.
- The `ThemeManager` registers templates, assets and layout definitions during boot.

Layouts, Components & Content
-----------------------------
- Layout System: defines structural page regions (header, nav, content areas, sidebars, footer) and grid systems.
- Component System: reusable UI fragments that can be derived from layout parts and reused in the content editor.
- Content System: stores page data independently from layout and theme. The content editor composes components and structured fields.

Content Types
-------------
- A Content Type is a system-registered model (technical id, label, field definitions, validation rules, API visibility and permissions).
- Typical core types: `page`, `article`, `documentation-entry`, `media-linked-entry`.
- Fields: text, textarea, richtext, number, boolean, date/time, slug, select/multiselect, media, relations, repeater, json, and editor-specific slots.
- The `ContentTypeManager` ensures registration, validation, API mapping and prevents ad-hoc, module-level data-model fragmentation.

Content States, Versioning & Workflow
-------------------------------------
- Every content entry has a state and version history. Standard states include `draft`, `review`, `published`, `archived`, and `deleted`.
- Versions are captured as snapshots to allow restore, diff and audit. The `StateManager` and `VersionManager` control allowed transitions and persistence.
- The system supports soft/hard locking, scheduled publish/unpublish, and optional workflows (author → editor → publisher).

API & Integrations
------------------
- The API system provides internal, module, admin, marketplace and public endpoints.
- Endpoints are versioned (e.g. `/api/v1/...`) and use consistent response envelopes (`success`, `data`, `meta`, `errors`).
- Authentication varies by API area (session-based for admin, API tokens or signed requests for external integrations).
- Modules may register their own endpoints if they comply with versioning and permission rules.

Marketplace, Security & Validation
---------------------------------
- Marketplace handles upload, automatic validation, manual moderation and distribution of modules and themes.
- Automatic checks ensure correct package structure, valid manifests, no inline CSS, and no theme-escape behavior.
- Moderation enforces usability, documentation and security standards before publication.

Installation & Quick Start
-------------------------
Requirements

- PHP 8.x (compatible with your environment)
- Composer (for dependency management if applicable)
- A supported SQL database (MySQL/MariaDB recommended)

Quick steps

1. Clone the repository and install dependencies (if applicable):

```bash
git clone <repo-url> chamy
cd chamy
# install dependencies if Composer/other are used
# composer install
```

2. Prepare environment file (copy example `.env` and edit DB credentials):

```bash
copy .env.example .env
# edit .env with DB credentials and environment settings
```

3. Run database migrations:

```bash
php chamy migrate
```

4. Use the guided web installer by visiting `/install` in your browser (the project includes a guided `public/install.php` that bootstraps admin credentials, runs migrations and locks the installer afterwards).

Notes
- An `install.lock` or similar file in `storage/` prevents re-running the installer in production.
- See `docs/` for advanced setup topics and architecture rationale.

Development
-----------
- Follow the code style and register new managers or hooks through the Kernel.
- Place modules in `/modules/<id>/` with a validated `manifest.json` and optional `migrations/`, `languages/`, and `hooks/`.
- Develop themes under `/themes/<theme-name>/` using Twig templates and the prescribed folder layout.

Contributing
------------
- Please open issues or pull requests on the project repository.
- Follow documented interfaces and register new extension points through the HookManager and Manager registry.
- Module and Theme submissions intended for the Marketplace must pass validation and moderation rules.

Further reading
---------------
See the in-repo documentation for details on each subsystem:

- [01_chamy_overview.md](docs/01_chamy_overview.md)
- [02_system_architecture.md](docs/02_system_architecture.md)
- [03_module_system.md](docs/03_module_system.md)
- [04_theme_system.md](docs/04_theme_system.md)
- [05_layout_component_content_system.md](docs/05_layout_component_content_system.md)
- [06_marketplace_security_and_rules.md](docs/06_marketplace_security_and_rules.md)
- [07_api_system.md](docs/07_api_system.md)
- [08_content_types.md](docs/08_content_types.md)
- [09_content_states_and_versions.md](docs/09_content_states_and_versions.md)

License & Support
-----------------
This repository does not include a specific license file by default. Please add a `LICENSE` file or consult the project owner for licensing and support options.

Contact
-------
For questions, feature requests or contributions, please use the repository issue tracker or reach out to the project maintainer listed in the repository metadata.

--
Generated summary compiled from the repository `docs/` files.
