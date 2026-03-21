<?php
declare(strict_types=1);

namespace Chamy\Core\Components;

class InstallWizard
{
    /**
     * Render the install wizard HTML.
     * $values: assoc array of current values
     * $errors: assoc array of errors
     * $csrf: string
     */
    public static function render(array $values, array $errors, string $csrf): void
    {
        // Escaper
        $h = function (string $v): string {
            return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
        };

        // Basic step markers and progress; the JS will handle transitions.
        // Prefer admin theme assets from the `default` admin theme. Fall back to packaged assets.
        $projectRoot = dirname(__DIR__, 2);
        $themeCssPath = $projectRoot . '/themes/admin/default/assets/css/admin.css';
        $themeJsPath = $projectRoot . '/themes/admin/default/assets/js/admin.js';
        $useThemeCss = is_file($themeCssPath);
        $useThemeJs = is_file($themeJsPath);

        // Close PHP to emit HTML; theme assets will be conditionally inlined.
        ?>
        <?php
        // Determine logo URL: prefer public assets names
        $logoUrl = null;
        $candidates = [
            '/public/assets/admin-logo.png',
            '/public/assets/logo.png',
            '/public/assets/logo_quadrat.png',
            '/public/assets/logo-collapsed.png',
        ];
        foreach ($candidates as $c) {
            $p = $projectRoot . $c;
            if (is_file($p)) { $logoUrl = str_replace($projectRoot . '/public', '', $p); break; }
        }
        // normalize to web path, fallback to null
        if ($logoUrl !== null) {
            $logoUrl = '/public' . $logoUrl; // ensure leading /public path (server may serve from project root)
            // prefer shorter asset path if possible
            $short = str_replace('\\', '/', $logoUrl);
            $logoUrl = preg_replace('#^/public/#', '/assets/', $short);
        }
        ?>
        <?php if ($useThemeCss): ?>
            <?php $css = @file_get_contents($themeCssPath); ?>
            <?php if ($css !== false): ?>
                <style><?= $css ?></style>
            <?php else: ?>
                <link rel="stylesheet" href="/assets/install-wizard.css">
            <?php endif; ?>
        <?php else: ?>
            <link rel="stylesheet" href="/assets/install-wizard.css">
        <?php endif; ?>

        <?php if ($useThemeJs): ?>
            <?php $js = @file_get_contents($themeJsPath); ?>
            <?php if ($js !== false): ?>
                <script><?= $js ?></script>
            <?php endif; ?>
        <?php endif; ?>

        <?php
        // Inline the wizard JS to avoid incorrect static routing returning HTML.
        $wizardJsPath = $projectRoot . '/public/assets/install-wizard.js';
        if (is_file($wizardJsPath)) {
            $wizJs = @file_get_contents($wizardJsPath);
            if ($wizJs !== false) {
                echo "<script>\n" . $wizJs . "\n</script>";
            } else {
                echo '<script src="/assets/install-wizard.js" defer></script>';
            }
        } else {
            echo '<script src="/assets/install-wizard.js" defer></script>';
        }
        ?>

        <?php if ($errors !== []): ?>
            <div class="card">
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?= $h($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form id="chamy-wizard" method="post" action="/install" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">
            <?php if (($values['__force'] ?? '') === '1'): ?>
                <input type="hidden" name="force" value="1">
            <?php endif; ?>

            <div class="card">
                <div class="wizard-title">
                    <div class="login-logo">
                        <?php if (!empty($logoUrl)): ?>
                            <img src="<?= $h($logoUrl) ?>" class="login-logo-img" alt="logo">
                        <?php else: ?>
                            <div class="login-logo-icon" aria-hidden="true">C</div>
                        <?php endif; ?>
                        <div class="login-logo-title">Chamy Installationsmanager</div>
                    </div>
                    <div class="page-subtitle">Geführte Installation mit Pflichtfeldern, Validierung, Beispielen und automatischer Grundkonfiguration.</div>
                </div>

                <div class="wizard-header">
                    <div class="wizard-steps" data-steps>
                        <div class="step active" data-step="1">1<br><small>Project</small></div>
                        <div class="step" data-step="2">2<br><small>Database</small></div>
                        <div class="step" data-step="3">3<br><small>Admin</small></div>
                        <div class="step" data-step="4">4<br><small>Install</small></div>
                    </div>
                    <div class="wizard-progress" data-progress></div>
                </div>

                <div class="wizard-body">
                    <section class="wizard-panel" data-panel="1">
                        <h2 class="section-title">1) Project & Base</h2>
                        <div class="grid">
                            <div class="field">
                                <label>APP_NAME</label>
                                <input data-summary="true" name="app_name" value="<?= $h($values['app_name'] ?? '') ?>" required>
                                <small>Min 2, Max 80 characters.</small>
                            </div>
                            <div class="field">
                                <label>APP_URL</label>
                                <input data-summary="true" name="app_url" value="<?= $h($values['app_url'] ?? '') ?>" required>
                                <small>Full URL incl. port.</small>
                            </div>
                            <div class="field">
                                <label>APP_ENV</label>
                                <select data-summary="true" name="app_env">
                                    <option value="production" <?= ($values['app_env'] ?? '') === 'production' ? 'selected' : '' ?>>production</option>
                                    <option value="development" <?= ($values['app_env'] ?? '') === 'development' ? 'selected' : '' ?>>development</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>APP_DEBUG</label>
                                <select data-summary="true" name="app_debug">
                                    <option value="false" <?= ($values['app_debug'] ?? '') === 'false' ? 'selected' : '' ?>>false</option>
                                    <option value="true" <?= ($values['app_debug'] ?? '') === 'true' ? 'selected' : '' ?>>true</option>
                                </select>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-panel" data-panel="2" hidden>
                        <h2 class="section-title">2) Database</h2>
                        <div class="help">Provide database connection values.</div>
                        <div class="grid">
                            <div class="field">
                                <label>DB_HOST</label>
                                <input data-summary="true" name="db_host" value="<?= $h($values['db_host'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_PORT</label>
                                <input data-summary="true" name="db_port" type="number" min="1" max="65535" value="<?= $h($values['db_port'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_DATABASE</label>
                                <input data-summary="true" name="db_database" value="<?= $h($values['db_database'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_USERNAME</label>
                                <input data-summary="true" name="db_username" value="<?= $h($values['db_username'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_PASSWORD</label>
                                <input name="db_password" type="password" value="<?= $h($values['db_password'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label>DB_PREFIX (optional)</label>
                                <input data-summary="true" name="db_prefix" value="<?= $h($values['db_prefix'] ?? '') ?>">
                                <small>Leave empty for no prefix.</small>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-panel" data-panel="3" hidden>
                        <h2 class="section-title">3) Admin Account</h2>
                        <div class="grid">
                            <div class="field">
                                <label>Admin Username</label>
                                <input data-summary="true" name="admin_username" value="<?= $h($values['admin_username'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Admin E-Mail</label>
                                <input data-summary="true" name="admin_email" type="email" value="<?= $h($values['admin_email'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Admin Display Name</label>
                                <input data-summary="true" name="admin_display_name" value="<?= $h($values['admin_display_name'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Admin Password</label>
                                <input id="admin_password" name="admin_password" type="password" required minlength="3" maxlength="72">
                                <div class="password-meter" aria-hidden="true">
                                    <div class="password-meter-bar meter-0"></div>
                                </div>
                                <div class="field-error password-error" aria-live="polite" style="display:none;color:var(--danger);margin-top:6px;font-size:13px;"></div>
                            </div>
                            <div class="field full">
                                <label>Confirm Password</label>
                                <input id="admin_password_confirm" name="admin_password_confirm" type="password" required minlength="3" maxlength="72">
                                <div class="field-error password-confirm-error" aria-live="polite" style="display:none;color:var(--danger);margin-top:6px;font-size:13px;"></div>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-panel" data-panel="4" hidden>
                        <h2 class="section-title">4) Ready to install</h2>
                        <p class="help">Review values and start the installation. The installer will run migrations, create admin user and write a <code>.env</code> plus an install lock.</p>
                        <div class="grid">
                            <div class="field full">
                                <label>APP_NAME</label>
                                <div><?= $h($values['app_name'] ?? '') ?></div>
                            </div>
                            <div class="field full">
                                <label>DB_DATABASE</label>
                                <div><?= $h($values['db_database'] ?? '') ?></div>
                            </div>
                            <div class="field full">
                                <label>Admin Username</label>
                                <div><?= $h($values['admin_username'] ?? '') ?></div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="wizard-footer">
                    <button type="button" class="btn btn-secondary" data-prev hidden>Previous</button>
                    <button type="button" class="btn" data-next>Next</button>
                    <button type="submit" class="btn" data-install hidden>Install</button>
                </div>
            </div>
        </form>

        <!-- Confirmation template: used by JS to show a summary before final install -->
        <template id="install-confirm-template">
            <div class="confirm-summary">
                <p>Das System wird nun installiert. Folgende Einstellungen werden verwendet (Passwörter werden aus Sicherheitsgründen nicht angezeigt):</p>
                <ul class="confirm-list"></ul>
                <p class="muted">Hinweis: Die Installation fuehrt Migrationen aus und schreibt Datenbank-Inhalte, <code>.env</code> sowie <code>storage/install.lock</code>.</p>
            </div>
        </template>

        <?php
    }
}
