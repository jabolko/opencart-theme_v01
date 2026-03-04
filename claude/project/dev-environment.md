# Development Environment

## Docker Services
| Service    | Port | URL                      |
|------------|------|--------------------------|
| OpenCart   | 8080 | http://localhost:8080    |
| phpMyAdmin | 8081 | http://localhost:8081    |
| MySQL      | 3306 | localhost:3306           |

## Credentials
- MySQL root password: `root`
- Database name: `opencart_dev`
- phpMyAdmin: `root` / `root`

## Common Commands

```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# View PHP logs
docker logs opencart_web

# Open bash in web container
docker exec -it opencart_web bash

# Start SCSS watch (run from theme directory)
cd opencart/catalog/view/theme/otroskikoticek && npm run dev

# Full production build
cd opencart/catalog/view/theme/otroskikoticek && npm run build
```

## File Volumes
- `./opencart` → `/var/www/html` in container (live sync, no restart needed)
- PHP config: `./docker/php.ini` (Xdebug enabled, `display_errors=On`)
- MySQL config: `./docker/mysql.cnf`

## Xdebug
- Mode: debug + profile
- Port: 9003
- Trigger: `start_with_request=trigger`
- Client host: `host.docker.internal` (works from Mac/Windows host)
- VSCode launch config: see `.vscode/launch.json`

## After Code Changes
- SCSS changes: auto-recompiled when `npm run dev` is running
- Twig changes: visible immediately (no cache during dev)
- PHP changes: visible immediately (Docker volume syncs live)
- JS changes: run `npm run js:minify` manually (not watched)
