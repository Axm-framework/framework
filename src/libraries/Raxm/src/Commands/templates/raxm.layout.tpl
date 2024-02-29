<!DOCTYPE html>
<html lang="<?= app()->getLocale() ?>">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title><?= config('app.info.title') ?></title>
    </head>
    <body>
        {{content}}
    </body>
</html>
