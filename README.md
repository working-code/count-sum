## Тестовое задание на backend-разработчика
Есть список директорий неизвестно насколько большой вложенности.
В директории может быть файл count.
Нужно пройтись по всем директориям и вернуть сумму всех чисел из файла count (файлов count может быть много)

## count-sum
Консольное приложение: обходит дерево каталогов произвольной глубины и возвращает сумму всех чисел, найденных в файлах с именем count

## Требования
php >=8.3

## Установка и запуск
```bash
composer install
php app.php <dir>
```
Пример:
```bash
php app.php /tmp/data
```

## Поведение и допущения
- Файлы читаются построчно
- Каждая строка count — одно число: знак (`+`/`-`), цифры, опц. дробная часть с '.' или ','. Пустые строки пропускаются; строки, не являющиеся числом, логируются как WARN и не входят в сумму (на код выхода это не влияет).
- Символические ссылки:
  - на файлы учитываются, если имя ссылки count;
  - на каталоги не обходятся (защита от циклов по realpath)
- Недоступные каталоги пропускаются, для каждого выводится WARN в stderr
- Недоступный файл count логируется как ERROR и не входит в сумму; если таких файлов хотя бы один, результат считается неполным (код 2)

## Коды выхода
| Код | Значение |
|---|---|
| 0 | Успешное завершение (полный подсчёт) |
| 1 | Ошибка |
| 2 | Подсчёт неполный: часть файлов count была недоступна. Нужно смотреть логи |

## Composer scripts (via Docker)
All commands run inside the `application` service from `docker-compose.yml` (PHP 8.3, Composer included):

```bash
docker compose run --rm application composer <script>
```

| Command | Description |
|---|---|
| `docker compose run --rm application composer test` | run PHPUnit test suite |
| `docker compose run --rm application composer php-cs-fixer-diff` | show code-style violations (dry run, with diff) |
| `docker compose run --rm application composer php-cs-fixer-fix` | fix code style automatically |
| `docker compose run --rm application composer phpstan-full-scan` | run PHPStan static analysis (level 8) on `src/` |
| `docker compose run --rm application composer lint` | run cs-fixer + PHPStan + tests in one command |
