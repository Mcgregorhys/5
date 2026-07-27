# Aplikacja katalogu produktów (Symfony 6.4)

Aplikacja webowa do zarządzania katalogiem produktów z panelem administracyjnym, eksportem danych oraz REST API z autoryzacją kluczem API.

## Funkcje

- **Katalog produktów** — dodawanie, edycja, usuwanie, zdjęcia, kategorie, rabaty
- **Eksport** — PDF, Excel (XLSX), CSV
- **Użytkownicy i role** — logowanie, rejestracja, EasyAdmin (`ROLE_USER`, `ROLE_ADMIN`, …)
- **REST API** (`/api/v1/`) — produkty i kategorie w formacie JSON
- **Klucze API** — granularne uprawnienia (`products:read`, `products:write`, …)

## Wymagania

- PHP ≥ 8.1
- Composer
- Docker i Docker Compose (zalecane)
- MySQL 8.0

## Uruchomienie (Docker)

```bash
docker compose up -d
docker exec 5_app composer install
docker exec 5_app php bin/console doctrine:migrations:migrate --no-interaction
docker exec 5_app php bin/console cache:clear
```

Aplikacja dostępna pod adresem: **http://localhost**

| Kontener | Port | Opis |
|----------|------|------|
| `5_nginx` | 80 | Serwer WWW |
| `5_app` | 8000 | PHP-FPM / Symfony |
| `5_db` | 3306 | MySQL |

Dane bazy (Docker): baza `symfony`, użytkownik `symfony`, hasło `symfony`.

> Po zmianie szablonów lub konfiguracji uruchom `cache:clear` w kontenerze — katalog `var/cache` jest w osobnym wolumenie Docker.

## Uruchomienie (lokalne, bez Docker)

```bash
composer install
cp .env .env.local   # dostosuj DATABASE_URL do lokalnej bazy
php bin/console doctrine:migrations:migrate
symfony server:start # lub skonfiguruj nginx/apache
```

## Główne adresy

| Adres | Opis |
|-------|------|
| `/` | Lista produktów |
| `/login` | Logowanie |
| `/register` | Rejestracja |
| `/admin` | Panel EasyAdmin (wymaga `ROLE_ADMIN`) |
| `/api-keys` | Zarządzanie kluczami API (wymaga logowania) |
| `/api/v1/products` | REST API — produkty |
| `/api/v1/categories` | REST API — kategorie |

## REST API

### Endpointy — produkty

| Metoda | URL | Opis |
|--------|-----|------|
| `GET` | `/api/v1/products` | Lista produktów |
| `GET` | `/api/v1/products/{id}` | Szczegóły produktu |
| `POST` | `/api/v1/products` | Nowy produkt |
| `PUT` / `PATCH` | `/api/v1/products/{id}` | Edycja produktu |
| `DELETE` | `/api/v1/products/{id}` | Usunięcie produktu |

### Endpointy — kategorie

| Metoda | URL | Opis |
|--------|-----|------|
| `GET` | `/api/v1/categories` | Lista kategorii |
| `GET` | `/api/v1/categories/{id}` | Szczegóły kategorii |
| `POST` | `/api/v1/categories` | Nowa kategoria |
| `PUT` / `PATCH` | `/api/v1/categories/{id}` | Edycja kategorii |
| `DELETE` | `/api/v1/categories/{id}` | Usunięcie kategorii |

`{id}` to numer rekordu z bazy (np. `5`), nie wpisuj dosłownie `{id}` w adresie.

### Autoryzacja API

Dwa sposoby dostępu:

**1. Sesja przeglądarki** — zaloguj się w aplikacji, potem w tej samej karcie otwórz endpoint (działa tylko dla `GET` z paska adresu).

**2. Klucz API** — nagłówek HTTP przy każdym żądaniu:

```
X-API-Key: sk_twoj_klucz
```

lub:

```
Authorization: Bearer sk_twoj_klucz
```

Klucze tworzysz w panelu **Klucze API** (`/api-keys`). Klucz wyświetlany jest **tylko raz** przy tworzeniu.

### Uprawnienia klucza API

| Uprawnienie | Zakres |
|-------------|--------|
| `products:read` | GET produktów |
| `products:write` | POST, PUT, DELETE produktów |
| `categories:read` | GET kategorii |
| `categories:write` | POST, PUT, DELETE kategorii |

### Przykłady (Windows PowerShell)

```powershell
# Odczyt listy produktów
curl.exe -H "X-API-Key: sk_twoj_klucz" http://localhost/api/v1/products

# Szczegóły produktu ID 5
curl.exe -H "X-API-Key: sk_twoj_klucz" http://localhost/api/v1/products/5

# Edycja produktu (wymaga products:write)
curl.exe -X PUT -H "Content-Type: application/json" -H "X-API-Key: sk_twoj_klucz" -d "{\"nazwaProduktu\":\"Nowa nazwa\"}" http://localhost/api/v1/products/5

# Usunięcie produktu (wymaga products:write)
curl.exe -X DELETE -H "X-API-Key: sk_twoj_klucz" http://localhost/api/v1/products/5
```

> Na Windows używaj `curl.exe`, nie `curl` (alias PowerShell ma inną składnię).  
> Metody `PUT` i `DELETE` nie działają z paska adresu przeglądarki — użyj Postman, curl lub testera API na stronie głównej.

### Przykład body JSON (POST / PUT produktu)

```json
{
  "kod": "123456",
  "nazwaProduktu": "Produkt testowy",
  "cenaNetto": "100",
  "vat": "23",
  "amount": "10",
  "categoryId": 1
}
```

## Role użytkowników

| Rola | Dostęp |
|------|--------|
| `ROLE_USER` | Dodawanie produktów, upusty |
| `ROLE_ADMIN` | Panel `/admin`, kategorie, parametry |
| Zalogowany użytkownik | Pełny dostęp do REST API w przeglądarce |

## Przydatne komendy

```bash
# Migracje bazy
php bin/console doctrine:migrations:migrate

# Czyszczenie cache
php bin/console cache:clear

# Lista tras API
php bin/console debug:router | grep api

# W kontenerze Docker
docker exec 5_app php bin/console cache:clear
```

## Struktura projektu

```
src/
├── Controller/
│   ├── Api/              # REST API (produkty, kategorie)
│   ├── Admin/            # EasyAdmin CRUD
│   └── ApiKeyController.php
├── Entity/               # Product, Category, User, ApiKey
├── Enum/                 # ApiPermission, ShippingOption, ColorsOption
├── Security/             # ApiKeyAuthenticator, ApiKeyUser
├── Service/              # ApiKeyManager, ApiSerializer, FileUploader
└── EventSubscriber/      # ApiPermissionSubscriber, ApiExceptionSubscriber

templates/
├── product/              # Widoki produktów + _api_endpoints.html.twig
└── api_key/              # Panel kluczy API

migrations/               # Migracje Doctrine (w tym tabela api_key)
```

## Technologie

- Symfony 6.4, Doctrine ORM, Twig
- EasyAdmin 4
- MySQL 8, Docker Compose, Nginx
- Webpack Encore, Bootstrap, Stimulus/Turbo
- Dompdf (PDF), PhpSpreadsheet (Excel)

## Integracja PrestaShop

Import produktów z PrestaShop przez Web Service API (REST/JSON).

### Mapowanie pól

| PrestaShop (`ps_product` / `ps_product_lang`) | Pole w aplikacji |
|-----------------------------------------------|------------------|
| `id_product` | `prestashopId` |
| `reference` | `kod` |
| `price` | `cenaNetto` |
| `name` (z `ps_product_lang`) | `nazwaProduktu` |

### Konfiguracja (`.env` lub `.env.local`)

```env
PRESTASHOP_URL=https://twoj-sklep.pl
PRESTASHOP_API_KEY=EUYUFQ6I46CVA9M97HMNC9F83WNUMZ7T
PRESTASHOP_LANG_ID=1
```

`PRESTASHOP_URL` — adres sklepu PrestaShop (bez slasha na końcu).  
`PRESTASHOP_LANG_ID` — ID języka z tabeli `ps_lang` (dla polskiego często `2`).

### Uruchomienie importu

**Z poziomu aplikacji** — przycisk **Import PrestaShop** na liście produktów (wymaga logowania).

**Z konsoli:**

```bash
php bin/console app:prestashop:import
# Docker:
docker exec 5_app php bin/console app:prestashop:import
```

Import aktualizuje istniejące produkty (po `prestashopId` lub `kod`), pozostałe pola (VAT, brutto, rabaty) uzupełnia automatycznie.

## Licencja

Projekt proprietarny.
