# OLX Price Tracker

**OLX Price Tracker** — це веб-додаток, розроблений на Laravel, який дозволяє користувачам відстежувати зміни цін на оголошеннях OLX. Після реєстрації та авторизації користувачі можуть додавати посилання на цікаві їм оголошення. Сервіс автоматично перевіряє ціни цих оголошень за заданим розкладом (наприклад, кожні 15 хвилин). У випадку виявлення зміни ціни або назви, система надсилає сповіщення електронною поштою підписаним користувачам. Важливою особливістю є оптимізація: якщо кілька користувачів відстежують одне й те саме оголошення, його ціна парситься лише один раз, запобігаючи зайвим запитам до OLX.

---

## Схема роботи сервісу

Ось спрощена схема роботи сервісу, що ілюструє взаємодію компонентів.

```mermaid
graph TD
    A[Користувач] --> B(Веб-інтерфейс Laravel);

    B -- Реєстрація/Авторизація --> C{Аутентифікація};
    C -- Успішно --> D[Панель користувача];

    D -- Додати URL оголошення OLX --> E[Контролер Підписок];
    E -- Зберегти OlxAd (якщо новий) --> F[БД: OlxAds];
    E -- Зберегти Subscription --> G[БД: Subscriptions];
    F --> |Запит| OlxScraperService[Сервіс: OlxScraperService];

    subgraph Фоновий процес (Docker Container)
        H[Docker Compose] --> I[Веб-контейнер: Apache + PHP-FPM];
        H --> J[Контейнер БД: MySQL];

        I -- Крон-завдання (кожну хв) --> L[Artisan Scheduler (php artisan schedule:run)];
        L -- Виконує команду --> M[Artisan Command: olx:check-prices];
        M --> F;
        M --> OlxScraperService;
        OlxScraperService -- Парсинг ціни --> N[OLX.ua];
        M -- Якщо ціна змінилась --> O[Відправка Email-повідомлення];
        O --> P[Mail Driver (напр., Mailtrap, SMTP - **Mailpit потрібно додати в docker-compose**)];
    end

    F -- (Відношення) --> G;
    G -- (Відношення) --> Q[БД: Users];
    Q --> B;
    Q --> G;
````

**Пояснення до схеми:**

1.  **Користувач**: Взаємодіє з **Веб-інтерфейсом Laravel**.
2.  **Аутентифікація**: Користувач проходить процес **реєстрації/авторизації**.
3.  **Панель користувача**: Після успішної аутентифікації користувач отримує доступ до особистого кабінету.
4.  **Додавання URL оголошення**: З панелі користувача він додає URL оголошення OLX. Контролер обробляє запит:
    * Перевіряє, чи оголошення вже існує в таблиці `OlxAds`. Якщо ні, створює новий запис.
    * Створює запис про **підписку (Subscription)**, пов'язуючи користувача з оголошенням.
5.  **Бази даних**:
    * `OlxAds`: зберігає інформацію про оголошення (URL, поточну ціну, назву, останню перевірку).
    * `Subscriptions`: пов'язує `Users` з `OlxAds`.
    * `Users`: містить інформацію про користувачів.
6.  **Docker Compose**: Оркеструє всі сервіси у контейнерах:
    * **Веб-контейнер**: Містить Apache для веб-сервера та PHP-FPM для виконання PHP-коду.
    * **Контейнер БД**: MySQL для зберігання даних.
7.  **Крон-завдання**: Системний крон у веб-контейнері щохвилини запускає Laravel Artisan Scheduler (`php artisan schedule:run`).
8.  **Artisan Command**: Планувальник запускає команду `olx:check-prices`, яка:
    * Отримує список унікальних оголошень з `OlxAds`, на які є підписки.
    * Для кожного оголошення викликає **Сервіс: OlxScraperService**.
9.  **OlxScraperService**: Робить HTTP-запит до **OLX.ua**, парсить сторінку (спершу JSON-LD, потім HTML-селектори) для отримання ціни, валюти та назви.
10. **Оновлення та Сповіщення**: Якщо `olx:check-prices` виявляє зміну ціни або назви:
    * Оновлює запис `OlxAd` у базі даних.
    * Генерує email-повідомлення `PriceChangedNotification` для кожного підписаного користувача.
11. **Mail Driver**: Laravel буде використовувати драйвер пошти, налаштований у вашому `.env` (наприклад, `log` або `smtp`), і надсилати листи **безпосередньо** (синхронно).

-----

## Зміст

* [Особливості](#features)
* [Системні вимоги](#requirements)
* [Встановлення та запуск](#install)
    * [Клонування репозиторію](https://www.google.com/search?q=%23%D0%BA%D0%BB%D0%BE%D0%BD%D1%83%D0%B2%D0%B0%D0%BD%D0%BD%D1%8F-%D1%80%D0%B5%D0%BF%D0%BE%D0%B7%D0%B8%D1%82%D0%BE%D1%80%D1%96%D1%8E)
    * [Налаштування змінних оточення](https://www.google.com/search?q=%23%D0%BD%D0%B0%D0%BB%D0%B0%D1%88%D1%82%D1%83%D0%B2%D0%B0%D0%BD%D0%BD%D1%8F-%D0%B7%D0%BC%D1%96%D0%BD%D0%BD%D0%B8%D1%85-%D0%BE%D1%82%D0%BE%D1%87%D0%B5%D0%BD%D0%BD%D1%8F)
    * [Налаштування Docker](https://www.google.com/search?q=%23%D0%BD%D0%B0%D0%BB%D0%B0%D1%88%D1%82%D1%83%D0%B2%D0%B0%D0%BD%D0%BD%D1%8F-docker)
    * [Запуск контейнерів](https://www.google.com/search?q=%23%D0%B7%D0%B0%D0%BF%D1%83%D1%81%D0%BA-%D0%BA%D0%BE%D0%BD%D1%82%D0%B5%D0%B9%D0%BD%D0%B5%D1%80%D1%96%D0%B2)
    * [Виконання міграцій та сидингу](https://www.google.com/search?q=%23%D0%B2%D0%B8%D0%BA%D0%BE%D0%BD%D0%B0%D0%BD%D0%BD%D1%8F-%D0%BC%D1%96%D0%B3%D1%80%D0%B0%D1%86%D1%96%D0%B9-%D1%82%D0%B0-%D1%81%D0%B8%D0%B4%D0%B8%D0%BD%D0%B3%D1%83)
* [Використання](https://www.google.com/search?q=%23%D0%B2%D0%B8%D0%BA%D0%BE%D1%80%D0%B8%D1%81%D1%82%D0%B0%D0%BD%D0%BD%D1%8F)
    * [Веб-інтерфейс](https://www.google.com/search?q=%23%D0%B2%D0%B5%D0%B1-%D1%96%D0%BD%D1%82%D0%B5%D1%80%D1%84%D0%B5%D0%B9%D1%81)
    * [Планувальник (Scheduler)](https://www.google.com/search?q=%23%D0%BF%D0%BB%D0%B0%D0%BD%D1%83%D0%B2%D0%B0%D0%BB%D1%8C%D0%BD%D0%B8%D0%BA-scheduler)
    * [Черги (Queues)](https://www.google.com/search?q=%23%D1%87%D0%B5%D1%80%D0%B3%D0%B8-queues)
    * [Логування помилок PHP](https://www.google.com/search?q=%23%D0%BB%D0%BE%D0%B3%D1%83%D0%B2%D0%B0%D0%BD%D0%BD%D1%8F-%D0%BF%D0%BE%D0%BC%D0%B8%D0%BB%D0%BE%D0%BA-php)
* [Основні технології](https://www.google.com/search?q=%23%D0%BE%D1%81%D0%BD%D0%BE%D0%B2%D0%BD%D1%96-%D1%82%D0%B5%D1%85%D0%BD%D0%BE%D0%BB%D0%BE%D0%B3%D1%96%D1%97)
* [Структура проекту (ключові файли)](https://www.google.com/search?q=%23%D1%81%D1%82%D1%80%D1%83%D0%BA%D1%82%D1%83%D1%80%D0%B0-%D0%BF%D1%80%D0%BE%D0%B5%D0%BA%D1%82%D1%83-%D0%BA%D0%BB%D1%8E%D1%87%D0%BE%D0%B2%D1%96-%D1%84%D0%B0%D0%B9%D0%BB%D0%B8)
* [Внесок](https://www.google.com/search?q=%23%D0%B2%D0%BD%D0%B5%D1%81%D0%BE%D0%BA)
* [Ліцензія](https://www.google.com/search?q=%23%D0%BB%D1%96%D1%86%D0%B5%D0%BD%D0%B7%D1%96%D1%8F)

-----

## Особливості {#features}

* **Аутентифікація користувачів:** Реєстрація, вхід, відновлення пароля.
* **Управління підписками:** Додавання та видалення URL-адрес OLX оголошень для відстеження.
* **Автоматичний парсинг цін:** Регулярна перевірка цін та назв оголошень OLX за розкладом.
* **Сповіщення електронною поштою:** Відправка листів користувачам при зміні ціни або назви.
* **Оптимізація парсингу:** Одне оголошення перевіряється лише один раз, навіть якщо на нього підписано кілька користувачів.
* **Dockerized:** Проєкт повністю контейнеризований для легкої розробки та деплою.

-----

## Системні вимоги {#requirements}

* **Docker Desktop** (для Windows/macOS) або **Docker Engine** та **Docker Compose** (для Linux).
* **Git**

-----

## Встановлення та запуск {#install}

### Клонування репозиторію

```bash
git clone <URL_ВАШОГО_РЕПОЗИТОРІЮ>
cd olx-price-tracker
```

### Налаштування змінних оточення

Створіть файл `.env` на основі `.env.example`:

```bash
cp .env.example .env
```

Відкрийте файл `.env` та налаштуйте наступні змінні:

* **Налаштування Додатка:**

  ```env
  APP_NAME="OLX Price Tracker"
  APP_ENV=local
  APP_KEY= # Залишити порожнім, буде згенеровано Docker'ом
  APP_DEBUG=true
  APP_URL=http://localhost:8078
  OLX_SCRAPER_USER_AGENT="Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36"
  ```

* **Налаштування Бази Даних:**

  ```env
  DB_CONNECTION=mysql
  DB_HOST=mysql_db # Ім'я сервісу з docker-compose.yml
  DB_PORT=3306
  DB_DATABASE=olx_price_tracker
  DB_USERNAME=root
  DB_PASSWORD=root
  ```

* **Налаштування Кешу/Черг:**
  Оскільки черги обробляються синхронно, без окремого воркера.

  ```env
  QUEUE_CONNECTION=sync
  ```

* **Налаштування Email (Mailpit для розробки - якщо будете використовувати):**
  У вашій поточній конфігурації `docker-compose.yml` відсутній сервіс `mailpit`. Якщо ви плануєте його додати для тестування пошти:

    1.  Додайте сервіс `mailpit` до `docker-compose.yml`.
    2.  Налаштуйте `MAIL_HOST` та `MAIL_PORT` згідно з `mailpit`.

  <!-- end list -->

  ```env
  MAIL_MAILER=log # Змінено на log, оскільки Mailpit відсутній у compose
  MAIL_HOST=null
  MAIL_PORT=null
  MAIL_USERNAME=null
  MAIL_PASSWORD=null
  MAIL_ENCRYPTION=null
  MAIL_FROM_ADDRESS="hello@example.com"
  MAIL_FROM_NAME="${APP_NAME}"
  # Якщо додасте Mailpit, змініть на:
  # MAIL_MAILER=smtp
  # MAIL_HOST=mailpit
  # MAIL_PORT=1025
  ```

### Налаштування Docker

Переконайтеся, що ваш `docker-compose.yml` файл правильно налаштований і містить необхідні сервіси: `web` (Apache+php-fpm), `mysql_db`, `phpmyadmin`.

**Ваш оновлений `docker-compose.yml`:**

```yaml
version: '3.8' # It's recommended to use a more recent Docker Compose version

services:
  web:
    build:
      context: .
      dockerfile: ./docker/Dockerfile
    ports:
      - "8078:80" # Maps host port 8078 to container port 80
    container_name: olx-price-tracker-web
    volumes:
      - ./src:/var/www/html:delegated # Mounts your application source code to the container
      - ./docker/config/php.ini:/usr/local/etc/php/php.ini # Mounts a custom PHP configuration file
      - ./docker/config/apache2.conf:/etc/apache2/apache2.conf # Mounts a custom Apache configuration file
      - ./docker/config/envvars:/etc/apache2/envvars # Mounts Apache environment variables
      - ~/.composer/docker-cache/:/root/.composer:cached # Caches Composer dependencies for faster builds
    depends_on:
      - mysql_db # Ensures the database container starts before the web container
    networks:
      - olx_price_tracker
    # For better debugging if the container crashes immediately, you can uncomment these:
    # stdin_open: true
    # tty: true

  mysql_db:
    image: mysql:8.0
    restart: always # Ensures the database container automatically restarts if it stops
    container_name: olx-price-tracker-db
    volumes:
      - ./mysql_db:/var/lib/mysql # Persists MySQL database data to a host volume
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: olx_price_tracker
    command:
      [
        'mysqld',
        '--character-set-server=utf8mb4',
        '--collation-server=utf8mb4_0900_ai_ci',
        '--wait_timeout=28800',
        '--default-authentication-plugin=mysql_native_password'
      ]
    networks:
      - olx_price_tracker

  phpmyadmin:
    image: phpmyadmin/phpmyadmin
    links:
      - mysql_db # Links phpMyAdmin to the MySQL database service for easy connection
    environment:
      PMA_HOST: mysql_db
      PMA_PORT: 3306
      UPLOAD_LIMIT: 300000M
      MEMORY_LIMIT: 300000M
      MAX_EXECUTION_TIME: 300000
    ports:
      - "8048:80" # Maps host port 8048 to container port 80 for the phpMyAdmin UI
    container_name: olx-price-tracker-phpmyadmin
    networks:
      - olx_price_tracker

networks:
  olx_price_tracker:
    # driver: bridge # This explicitly defines the network driver as bridge (optional, as it's the default)
```

### Запуск контейнерів

Зберіть та запустіть Docker-контейнери:

```bash
docker compose build --no-cache # Зберіть образи, --no-cache щоб переконатися, що start_app.sh оновився
docker compose up -d           # Запустіть контейнери у фоновому режимі
```

**Перевірка логів:**
Після запуску контейнерів перевірте логи веб-сервісу, щоб переконатися, що `start_app.sh` відпрацював без помилок:

```bash
docker compose logs -f web
```

Ви повинні побачити повідомлення про `chown`, `composer install`, очищення кешу Laravel, запуск Cron та Apache.

### Виконання міграцій та сидингу

Виконайте міграції бази даних та (за бажанням) заповніть її початковими даними:

```bash
docker exec -it olx-price-tracker-web php artisan migrate --force
docker exec -it olx-price-tracker-web php artisan db:seed # Якщо є сідери
```

*(Замініть `olx-price-tracker-web` на ім'я вашого веб-сервісу в `docker-compose.yml`, якщо воно відрізняється)*

-----

## Використання

Відкрийте ваш браузер та перейдіть за адресою: `http://localhost:8078` (згідно вашого `docker-compose.yml`)

### Веб-інтерфейс

* **Реєстрація/Вхід:** Використовуйте стандартні маршрути `/register` та `/login` для створення облікового запису та входу.
* **Панель користувача:** Після входу ви будете перенаправлені на `/dashboard`, де зможете додавати нові оголошення для відстеження.

### Планувальник (Scheduler)

Планувальник Laravel (`php artisan schedule:run`) запускається Cron'ом кожну хвилину всередині Docker-контейнера. Він відповідає за запуск команди `olx:check-prices`.

Ви можете перевірити список запланованих команд, виконавши:

```bash
docker exec -it olx-price-tracker-web php artisan schedule:list
```

### Черги (Queues)

У вашій поточній конфігурації `docker-compose.yml` відсутні окремі сервіси для черг. Це означає, що Laravel буде використовувати **синхронний драйвер черг (`QUEUE_CONNECTION=sync`)**.
При використанні `sync` драйвера, відправка email-повідомлень буде відбуватися **безпосередньо під час виконання команди `olx:check-prices`**, а не асинхронно. Це може уповільнити виконання команди.

Якщо ви плануєте використовувати черги для асинхронної обробки у майбутньому (наприклад, для production-середовища з великою кількістю даних), вам потрібно буде:

1.  **Додати сервіс брокера черг** (наприклад, Redis, RabbitMQ або базу даних для черг) до вашого `docker-compose.yml`.
2.  **Налаштувати `QUEUE_CONNECTION`** у вашому `.env` файлі відповідно до обраного брокера.
3.  **Запустити воркер черг** (наприклад, в окремому терміналі або за допомогою Supervisor у production):
    ```bash
    docker exec -it olx-price-tracker-web php artisan queue:work --verbose
    ```

### Логування помилок PHP

Усі PHP-помилки записуються у файл:
`storage/logs/php_errors/php_error.log`

Ви можете переглянути його, виконавши команду всередині контейнера:

```bash
docker exec -it olx-price-tracker-web cat storage/logs/php_errors/php_error.log
```

Або безпосередньо на хост-машині у вашому проєкті в директорії `storage/logs/php_errors/`.

-----

## Основні технології

* **PHP 8.3+** (Згідно з Dockerfile)
* **Laravel 10/11+**
* **MySQL 8.0+** (База даних)
* **GuzzleHttp** (HTTP-клієнт для парсингу веб-сторінок)
* **Symfony DomCrawler** (Для парсингу HTML)
* **Docker**
* **Docker Compose**
* **phpMyAdmin** (Для керування БД)

-----

## Структура проекту (ключові файли)

* `app/Console/Commands/CheckOlxPrices.php`: Artisan-команда для перевірки цін та відправки сповіщень.
* `app/Mail/PriceChangedNotification.php`: Mailable-клас для створення email-повідомлень про зміну ціни.
* `app/Models/OlxAd.php`: Eloquent-модель, що представляє OLX оголошення.
* `app/Models/Subscription.php`: Eloquent-модель, що пов'язує користувачів з OLX оголошеннями.
* `app/Models/User.php`: Eloquent-модель, що представляє користувача системи.
* `app/Services/OlxScraperService.php`: Сервіс, відповідальний за безпосередній парсинг OLX сторінок.
* `bootstrap/app.php`: Основний файл ініціалізації Laravel 10/11+, де конфігурується планувальник.
* `config/*`: Директорія з файлами конфігурації Laravel (додатка, бази даних, пошти, черг тощо).
* `database/migrations/*`: Файли міграцій для створення структури бази даних.
* `docker/Dockerfile`: Визначення Docker-образу для веб-сервісу.
* `docker/config/start_app.sh`: Скрипт, що виконується при старті веб-контейнера для налаштування середовища (Composer, Cron, права доступу).
* `docker-compose.yml`: Файл конфігурації для визначення та запуску багатоконтейнерних Docker-додатків.
* `routes/web.php`: Файл, що містить веб-маршрути додатка (для реєстрації, входу, панелі користувача, управління підписками).
* `src/`: Ваша коренева директорія проекту Laravel, яка монтується в контейнер.

-----

## Внесок

Вітаються будь-які внески та пропозиції щодо покращення\! Будь ласка, створіть "Issue" для повідомлення про помилки або пропозицій, або подайте "Pull Request" з вашими змінами.

-----

## Ліцензія

Проект ліцензовано за ліцензією MIT.

```
```