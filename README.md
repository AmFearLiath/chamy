> # Chamy
> 
> Chamy ist ein **PHP Framework**, das eine **einfache und schnelle Entwicklung von Webanwendungen** ermöglicht. Es basiert auf dem **MVC (Model-View-Controller) Muster** und bietet eine Reihe von nützlichen Funktionen wie **Routing, Datenbankabstraktion, Validierung, Authentifizierung** und mehr.
> 
> ## Installation
> 
> Um Chamy zu installieren, benötigen Sie einen Webserver mit **PHP 7.4 oder höher** und eine Datenbank wie MySQL oder SQLite. Sie können Chamy entweder über Composer oder über Git herunterladen.
> 
> ### Composer
> 
> Führen Sie den folgenden Befehl in Ihrem Webroot-Verzeichnis aus, um Chamy als Abhängigkeit zu Ihrem Projekt hinzuzufügen:
> 
> ```bash
> composer require amfearliath/chamy
> ```
> 
> ### Git
> 
> Klone das Repository von GitHub in dein Webroot-Verzeichnis:
> 
> ```bash
> git clone https://github.com/AmFearLiath/chamy.git
> ```
> 
> ## Konfiguration
> 
> Nach der Installation müssen Sie einige Einstellungen in der Datei **config.php** im Hauptverzeichnis von Chamy vornehmen. Dort können Sie unter anderem den Namen Ihrer Anwendung, die Datenbankverbindungsinformationen, den Basis-URL-Pfad und die E-Mail-Einstellungen angeben.
> 
> ## Dokumentation
> 
> Die vollständige Dokumentation von Chamy finden Sie auf der offiziellen Website: [https://chamy.dev/docs](^2^)
> 
> ## Lizenz
> 
> Chamy ist unter der **MIT-Lizenz** veröffentlicht. Siehe die Datei **LICENSE** für weitere Informationen.
>
> ## Features
>
> Chamy bietet Ihnen folgende Features, die Ihnen bei der Entwicklung Ihrer Webanwendungen helfen:
>
>- **Routing**: Chamy ermöglicht es Ihnen, benutzerdefinierte URLs für Ihre Anwendungen zu definieren, die auf bestimmte Controller und Aktionen verweisen. Sie können auch Parameter an Ihre URLs anhängen oder dynamische Segmente verwenden, um flexible Routen zu erstellen.
>- **Datenbankabstraktion**: Chamy verwendet die PDO-Erweiterung, um eine einheitliche Schnittstelle für den Zugriff auf verschiedene Datenbanken zu bieten. Sie können einfache Abfragen mit der query-Methode ausführen oder vorbereitete Anweisungen mit der prepare-Methode verwenden, um SQL-Injection-Angriffe zu vermeiden.
>- **Validierung**: Chamy bietet Ihnen eine Reihe von Validierungsregeln, die Sie auf Ihre Eingabedaten anwenden können, um deren Gültigkeit zu überprüfen. Sie können auch eigene Validierungsregeln erstellen oder mehrere Regeln zu einem Validator zusammenfassen.
>- **Authentifizierung**: Chamy unterstützt die Authentifizierung von Benutzern über verschiedene Methoden wie Cookies, Sitzungen oder HTTP-Basic-Authentifizierung. Sie können auch eigene Authentifizierungsadapter schreiben oder mehrere Adapter zu einer Kette kombinieren.
>- **MVC**: Chamy folgt dem MVC-Muster, das Ihre Anwendung in drei Schichten unterteilt: Model, View und Controller. Das Model ist für die Interaktion mit der Datenbank und die Verwaltung der Geschäftslogik verantwortlich. Die View ist für die Darstellung der Benutzeroberfläche und der Daten verantwortlich. Der Controller ist für die Verarbeitung der Anfragen und die Bereitstellung der Antworten verantwortlich.
