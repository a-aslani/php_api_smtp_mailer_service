## SENDING SMTP MAIL WITH API SERVICE

Some service providers may have closed the ports related to sending emails of their services and you will not be able to send emails through their servers! The easiest option to solve this problem is to use shared hosting and an email sending service through API, which you can use in your main service. I am sharing the codes of this email sending service with you for this purpose.

## How to use


```
composer install
```

```
php -S 127.0.0.1:8000 -t public
```

```
curl --location 'http://localhost:8000/mail' \
--header 'Content-Type: application/json' \
--data-raw '{
    "host": "<your-smtp-host>",
    "port": 465,
    "username": "<your-smtp-username>",
    "password": "<your-smtp-password>",
    "sender_name": "Your barnd name",
    "to": "target@gmail.com",
    "subject": "Your subject message",
    "message": "<p>Your html message body</p>"
}'
```