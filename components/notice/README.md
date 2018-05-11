Модуль для отправки уведомлений
===============================
#### ПОДКЛЮЧЕНИЕ
```php
#1. подключение в конфиге :
    'components' => [
        'senderName' => [
            'class' => '\core\components\notice\NoticeSender',
            'apiKey' => ':api-key'
        ],
        ...
    ]
    $sender = Yii::$app->senderName;
#2. вызов напрямую :
    $sender = new \core\components\notice\NoticeSender());
    $sender->setApiKey(":api-key");
```
#### ОТПРАВКА
```php
#Подготовка сообщения
$html = <<<HTML
        <h1>Обертка для нотификатора<h1>
        <ul>
            <li>1</li>
            <li>2</li>
            <li>3</li>
            <li>4</li>
            <li>5</li>
            <li>Вышел эвакадо погулять</li>
        </ul>
HTML;
$message = new \core\components\notice\NoticeMessage();
$message->setFrom('noreply@test.kz')
        ->setTitle('Обертка для нотификатора')
        ->setMessage($html)
        ->setAddresses([
               'test@gmail.com',
               'test1@gmail.com'
            ]);
#Отправка почты
$sender->sendEmail($message) #return bool;
#Отправка пуша
$sender->sendPush($message) #return bool;
```