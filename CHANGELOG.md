# История изменений

## [NTAI-158](https://jira.ru/NTAI-157)
- env. Добавить параметр 

`MAXBOT_URL_MINI_APP` (ссылка на мини апп)

## [NTAI-101](https://jira.ru/browse/NTAI-101)
1. env. Добавить параметры:
* MAXBOT_ACCESS_TOKEN=
* MAXBOT_API_SECRET=

2. .env. Проверить что проставлены параметры:
* NT_API_URL=
* NT_API_TOKEN=
* NT_API2_URL=
* NT_API2_TOKEN=
* FNS_BASE_URL=
* FNS_USER=
* NETWORK_URL=
* NETWORK_USER=
* NETWORK_PASSWORD=
* AWS_ACCESS_KEY_ID=
* AWS_SECRET_ACCESS_KEY=
* AWS_DEFAULT_REGION=ru-central1
* AWS_BUCKET=
* AWS_URL=
* AWS_ENDPOINT=
* AWS_USE_PATH_STYLE_ENDPOINT=false

* APP_LOCALE=ru

3. Удалить старые webhook из бота:

   `php artisan maxbot:webhook:unsubscribe <OLD URL WEBHOOK>`


4. Установить новый webhook для бота:

   `php artisan maxbot:webhook:subscribe <NEW URL WEBHOOK>/api/max-bot/webhook --secret=<MAXBOT_API_SECRET> --types=message_created --types=message_callback --types=bot_started`


5. Проверить, что webhook установлен:

   `php artisan maxbot:webhook:list"`
