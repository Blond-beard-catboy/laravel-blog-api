# Laravel Blog API

Простое API приложение «Блог» на Laravel 10+ с аутентификацией, постами, комментариями (включая вложенные), тегами и фильтрацией.

## Требования

### Локальный запуск (без Docker)
- PHP >= 8.1
- Composer
- SQLite (или PostgreSQL/MySQL)
- Расширения PHP: `pdo_sqlite`, `pdo_pgsql`, `bcmath`, `ctype`, `json`, `mbstring`, `openssl`, `tokenizer`, `xml`

### Запуск через Docker
- Docker
- Docker Compose

## Установка и запуск

### 1. Локальный запуск (без Docker)

```bash
# Клонируйте репозиторий
git clone https://github.com/Blond-beard-catboy/laravel-blog-api.git
cd laravel-blog-api

# Установите зависимости
composer install

# Скопируйте .env.example и настройте подключение к БД
cp .env.example .env
```

Отредактируйте `.env`:
- Для SQLite (по умолчанию): закомментируйте строки `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, установите `DB_CONNECTION=sqlite` и создайте файл `database/database.sqlite`.
- Для PostgreSQL: укажите параметры подключения.

```bash
# Сгенерируйте ключ приложения
php artisan key:generate

# Выполните миграции
php artisan migrate

# Запустите сервер
php artisan serve
```

API будет доступно по адресу: `http://127.0.0.1:8000/api`

### 2. Запуск через Docker

```bash
# Клонируйте репозиторий
git clone https://github.com/Blond-beard-catboy/laravel-blog-api.git
cd laravel-blog-api

# Соберите и запустите контейнеры
docker-compose up -d --build

# Выполните миграции внутри контейнера
docker exec -it laravel_app php artisan migrate
```

API будет доступно по адресу: `http://localhost:8080/api`

> Примечание: в Docker-окружении файл `.env` не обязателен – переменные окружения берутся из `docker-compose.yml`. При желании вы можете создать `.env`, но значения из него переопределят настройки контейнера.

## API эндпоинты

Все запросы к API должны иметь префикс `/api`. Например: `http://127.0.0.1:8000/api/posts`.

### Открытые маршруты (без авторизации)
| Метод | URL | Описание |
|-------|-----|----------|
| POST | `/register` | Регистрация пользователя |
| POST | `/login` | Авторизация (возвращает токен) |
| GET | `/posts` | Список постов (пагинация, фильтр по тегам) |
| GET | `/posts/{id}` | Конкретный пост |
| GET | `/posts/{id}/comments` | Комментарии к посту (с вложенностью) |

### Защищённые маршруты (требуют токен `Authorization: Bearer <token>`)
| Метод | URL | Описание |
|-------|-----|----------|
| POST | `/logout` | Выход (инвалидация токена) |
| POST | `/posts` | Создание поста |
| PUT/PATCH | `/posts/{id}` | Редактирование поста (только автор) |
| DELETE | `/posts/{id}` | Удаление поста (только автор) |
| POST | `/comments` | Создание комментария |
| PUT/PATCH | `/comments/{id}` | Редактирование комментария (только автор) |
| DELETE | `/comments/{id}` | Удаление комментария (только автор) |

## Примеры запросов (curl)

### Регистрация
```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"name":"John","email":"john@example.com","password":"secret"}'
```

### Логин
```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"john@example.com","password":"secret"}'
```
Ответ содержит токен. Далее используйте его как `$TOKEN`.

### Создание поста (с тегами)
```bash
curl -X POST http://127.0.0.1:8000/api/posts \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"title":"Мой пост","content":"Текст","tags":[1,2]}'
```

### Список постов с пагинацией и фильтром по тегам
```bash
curl -X GET "http://127.0.0.1:8000/api/posts?page=1&tags[]=1"
```

### Создание корневого комментария
```bash
curl -X POST http://127.0.0.1:8000/api/comments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"post_id":1,"parent_id":null,"content":"Отличный пост!"}'
```

### Создание вложенного комментария (ответ на комментарий)
```bash
curl -X POST http://127.0.0.1:8000/api/comments \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"post_id":1,"parent_id":3,"content":"Согласен!"}'
```

### Получение комментариев к посту (с вложенностью)
```bash
curl -X GET http://127.0.0.1:8000/api/posts/1/comments
```

### Редактирование поста (только автор)
```bash
curl -X PUT http://127.0.0.1:8000/api/posts/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"title":"Новый заголовок","content":"Новое содержание"}'
```

### Удаление поста
```bash
curl -X DELETE http://127.0.0.1:8000/api/posts/1 \
  -H "Authorization: Bearer $TOKEN"
```

## Тестирование

Вы можете импортировать коллекцию Postman или использовать приведённые выше команды `curl`. Убедитесь, что:
- Неавторизованные пользователи могут только просматривать посты и комментарии.
- Авторизованные пользователи могут создавать посты/комментарии.
- Только автор может редактировать/удалять свой пост или комментарий.
- Работает пагинация (параметр `page`).
- Фильтрация по тегам через `tags[]=id1&tags[]=id2`.
- Вложенные комментарии возвращаются древовидной структурой (поле `children`).

## Структура базы данных (SQLite/PostgreSQL)

- `users` – пользователи.
- `posts` – посты (`user_id`, `title`, `content`).
- `comments` – комментарии (`user_id`, `post_id`, `parent_id`, `content`).
- `tags` – теги (`name`).
- `post_tag` – связь many-to-many между постами и тегами.

## Использованные технологии

- Laravel 11
- Laravel Sanctum (API-токены)
- Eloquent ORM
- API Resources
- Form Requests для валидации
- Policies для авторизации
- PostgreSQL / SQLite
- Docker Compose (опционально)

## Лицензия

Проект создан в рамках тестового задания. Распространяется свободно.
```