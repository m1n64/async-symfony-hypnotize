# Minimal clean Docker configuration with PHP 8.3+, NGINX 1.20+, PostgreSQL 14.2+ and Symfony 7.2 for development.

- Based on Alpine Linux.
- Doctrine
- Makefile

## Prerequisites

Required requisites:

1. [Git](https://git-scm.com/book/en/Getting-Started-Installing-Git)
2. [Docker](https://docs.docker.com/engine/installation/)
3. [Docker Compose](https://docs.docker.com/compose/install/)
4. [Composer](https://getcomposer.org)

Docker and Docker Compose can be installed with [Docker Desktop](https://www.docker.com/products/docker-desktop/) app.

## Initialization

1. Clone the project:

```
git clone https://github.com/m1n64/symfony-7-docker-template.git
```

2. Go to the project's folder

```
cd /path/to/symfony-7-docker-template
```

3. Update and install Composer packages
```bash
make up
```
```bash
make bash
```

```bash
composer install
```

4. Build and up project with Docker Compose

```
make up
```

5. Open `http://localhost:8080` in your browser, you should see the Symfony's welcome page.

### Using Makefile

To execute Makefile command use `make <command>` from project's folder

List of commands:

| Command | Description |
| ----------- | ----------- |
| up | Up containers |
| down | Down containers |
| build | Build/rebuild continers |
| test | Run PHPUnit tests |
| bash | Use bash in `php` container as `www-data` |
| bash_root | Use bash in `php` container as `root` |

## Mapping

Folders mapped for default Symfony folder structure (assuming local `/` is project's folder):

| Local | Container | Description |
| - | - | - |
| / | /var/www | Project root |
| /public | /var/www/public | Web server document root |
| /logs/nginx | /var/logs/nginx | NGINX logs |

Ports mapped default:

| Local | Container | Description |
| 8080 | 8080 | PHP Server |
| 5432 | 5432 | PostgreSQL port |

## Authors

- [Sergey Volkar (Original Author)](https://github.com/volkar)
- [Original Repo](https://github.com/volkar/docker-symfony-nginx-php-postgres/tree/main)
