# iFrameBundle für Hvb


Lookup- und Bestellfunktion via Iframe
Benötigt in der paramers.yml Einträge für die Identifizierung der User
`[base-uri]/extern/[username]`, sowie API-Auth-Keys und SwiftMailer.


## Requirements

via `parameters.yml`:
```yaml
    mailer_transport: smtp
    mailer_host: 127.0.0.1
    mailer_user: ~
    mailer_password: ~
    mailer_port: 1025
    mailer_encryption: ~

    iframe_user:
        'is': 'dfj3sd'
        'test': 'empty'
```


