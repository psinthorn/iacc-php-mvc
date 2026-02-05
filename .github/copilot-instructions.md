# iACC PHP MVC - Project Configuration

## Environment
- **Type**: Docker Compose
- **OS**: macOS
- **PHP Version**: 7.4 (PHP-FPM)
- **MySQL Version**: 5.7
- **Nginx**: Alpine

## Services & URLs
| Service | Container | URL | Port |
|---------|-----------|-----|------|
| Web App | iacc_nginx | http://localhost | 80, 443 |
| PHP-FPM | iacc_php | (internal) | 9000 |
| MySQL | iacc_mysql | localhost:3306 | 3306 |
| phpMyAdmin | iacc_phpmyadmin | http://localhost:8083 | 8083 |
| Ollama AI | iacc_ollama | http://localhost:11434 | 11434 |
| MailHog SMTP | iacc_mailhog_server | localhost:1025 | 1025 |
| MailHog Web | iacc_mailhog_server | http://localhost:8025 | 8025 |

## Database
- **Host (from containers)**: iacc_mysql or mysql
- **Host (from host machine)**: localhost
- **Port**: 3306
- **Database**: iacc
- **User**: root
- **Password**: root
- **Charset**: utf8mb4
- **Collation**: utf8mb4_unicode_ci
- **Timezone**: Asia/Bangkok

## PHP Configuration
- **Max Execution Time**: 300 seconds
- **Memory Limit**: 256M
- **Working Directory**: /var/www/html
- **Upload Directory**: /var/www/html/upload
- **Files Directory**: /var/www/html/file

## Ollama AI Configuration
- **Memory Limit**: 8GB
- **Models Directory**: ollama_models volume
- **API Endpoint**: http://localhost:11434

## MailHog (Email Testing)
- **SMTP Port**: 1025 (use for sending test emails)
- **Web UI**: http://localhost:8025 (view captured emails)

## Key Commands
```bash
# Start all containers
docker compose up -d

# Stop all containers
docker compose down

# Restart specific container
docker compose restart php

# View running containers
docker compose ps

# Copy file to PHP container
docker cp <file> iacc_php:/var/www/html/<file>

# Run MySQL query
docker exec iacc_mysql mysql -uroot -proot -D iacc -e "<query>"

# Import SQL file
docker exec -i iacc_mysql mysql -uroot -proot iacc < file.sql

# Export database
docker exec iacc_mysql mysqldump -uroot -proot iacc > backup.sql

# Check PHP logs
docker logs iacc_php --tail 50

# Check Nginx logs
docker logs iacc_nginx --tail 50

# Check MySQL logs
docker logs iacc_mysql --tail 50

# Run E2E tests
curl -s "http://localhost/test-e2e-crud.php"

# Enter PHP container shell
docker exec -it iacc_php bash

# Enter MySQL container shell
docker exec -it iacc_mysql mysql -uroot -proot iacc

# Check container health
docker inspect --format='{{.State.Health.Status}}' iacc_php
```

## Docker Volumes
| Volume | Purpose |
|--------|---------|
| mysql_data | MySQL database persistence |
| php_uploads | Upload files persistence |
| php_files | General files persistence |
| ollama_models | AI models storage |

## Network
- **Network Name**: iacc-network
- **Type**: Bridge network
- All containers communicate via container names

## Important Files
| File | Purpose |
|------|---------|
| `core-function.php` | Main CRUD handler for all forms |
| `inc/class.hard.php` | Database abstraction layer |
| `inc/class.db.php` | Database connection |
| `test-e2e-crud.php` | E2E test suite (42 tests) |
| `docker-compose.yml` | Development environment |
| `docker-compose.prod.yml` | Production environment |
| `docker/nginx/default.conf` | Nginx configuration |
| `docker/mysql/my.cnf` | MySQL configuration |
| `Dockerfile` | PHP-FPM build configuration |

## Known Patterns & Gotchas
1. **$args array reuse** - Always use **isolated arrays** per DB operation to prevent state leakage
2. **PO versioning** - New PO created on edit, old PO gets `po_id_new` pointing to new one
3. **Form arrays** - Products use indexed arrays: `type[0]`, `price[0]`, `model[0]` etc.
4. **Checkbox handling** - Empty checkboxes need default '0' value handling
5. **Session company** - `$_SESSION['com_id']` contains current user's company ID

### Critical: Shared $args Variable Issue
**The root cause of many bugs in this legacy codebase is the shared `$args` variable.**

The original code pattern reuses `$args` across multiple database operations:
```php
// BAD - causes state leakage
$args['table'] = "po";
$args['columns'] = "col1, col2, col3";
$args['value'] = "...";
$har->insertDbMax($args);

$args['table'] = "product";  // columns still contains PO columns!
$args['value'] = "...";
$har->insertDB($args);  // ERROR: column count mismatch
```

**Always use isolated arrays for each operation:**
```php
// GOOD - isolated arrays
$argsPO = array();
$argsPO['table'] = "po";
$argsPO['columns'] = "col1, col2, col3";
$argsPO['value'] = "...";
$har->insertDbMax($argsPO);

$argsProduct = array();  // Fresh array
$argsProduct['table'] = "product";
$argsProduct['value'] = "...";
$har->insertDB($argsProduct);  // Works correctly
```

**Fixed areas:** PO create (method=A) and PO edit (method=E) in core-function.php
**Potentially affected areas:** Any other switch case in core-function.php that does multiple DB operations

## Testing Checklist
Before declaring production-ready:
1. ✅ Run `test-e2e-crud.php` - all 42 tests pass
2. ⬜ Test company creation from browser
3. ⬜ Test PR creation from browser  
4. ⬜ Test PO/Quotation creation from browser
5. ⬜ Test PO edit and save - verify products preserved
6. ⬜ Test all list pages load without errors
7. ⬜ Test email sending via MailHog

## Production Deployment
For production, use:
```bash
docker compose -f docker-compose.prod.yml up -d
```
Production differences:
- Docker socket proxy (read-only, secure)
- No direct Docker socket mounting
- Container management disabled
