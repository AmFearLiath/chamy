# Tutorials — Schritt‑für‑Schritt

1) Neues Modul anlegen — Minimalbeispiel

Schritte:

```bash
mkdir -p modules/my_module
cat > modules/my_module/module.php <<'PHP'
<?php
$kernel->modules()->register('my_module', function($kernel){
    $kernel->router()->get('/admin/my-module', [\MyModule\AdminController::class, 'index']);
    $kernel->permissions()->definePermission('my_module.view', 'View My Module');
});
PHP
```

Erstelle Controller, Templates und optional migrations.

2) Google Font lokal installieren (Admin flow)

- Suche im Admin → Font‑Manager nach einer Schriftfamilie
- Wähle Stil(e) und klicke „Install“ → Server lädt CSS & Assets herunter

3) Migration erstellen und laufen lassen

```bash
# Erzeuge Migration unter modules/<modul>/migrations/...
php chamy migrate
```
