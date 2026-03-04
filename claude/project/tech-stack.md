# Tech Stack Reference

## Runtime
| Layer       | Technology | Version  | Notes                           |
|-------------|-----------|----------|---------------------------------|
| PHP         | PHP       | 8.4      | OC 3.0.5.0 requires >= 7.3     |
| Database    | MySQL     | 8.0      | utf8mb4_general_ci collation    |
| Web server  | Apache    | (Docker) | mod_rewrite enabled             |
| Platform    | OpenCart  | 3.0.5.0  | MVC(L) pattern, Twig templates  |

## Frontend
| Tool        | Version   | Notes                                               |
|-------------|-----------|-----------------------------------------------------|
| jQuery      | 3.7.1     | Loaded by OC — do not load again                   |
| Bootstrap   | 3.x (OC)  | Loaded by OC — we override, not replace             |
| Font Awesome| 4.x (OC)  | Loaded by OC — available via `fa-` classes          |
| Twig        | 1.x (OC)  | OC 3.x ships Twig 1 — no Twig 3 features            |

## Build Tools (run from theme dir)
| Tool          | Package       | Version   | Purpose                       |
|---------------|---------------|-----------|-------------------------------|
| Sass compiler | sass          | ^1.97.3   | SCSS → CSS                    |
| CSS minifier  | clean-css-cli | ^5.6.3    | CSS → .min.css                |
| JS minifier   | uglify-js     | ^3.19.3   | JS → .min.js                  |
| Task runner   | npm-run-all   | ^4.1.5    | Parallel/sequential scripts   |

## Key Constraints
- Twig version is 1.x: no arrow functions, no `filter()` with lambdas
- Bootstrap 3 is already loaded: do not load Bootstrap 4/5
- OC 3 uses `{{ base }}` for asset URL prefix (not relative paths)
- PHP 8.4 in Docker — `display_errors=On` during development
- UglifyJS expects ES5 — write JS without `let`/`const`/arrow functions
