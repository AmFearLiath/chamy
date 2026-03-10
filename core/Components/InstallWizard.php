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
        ?>
        <link rel="stylesheet" href="/assets/install-wizard.css">
        <script defer src="/assets/install-wizard.js"></script>

        <?php if ($errors !== []): ?>
            <div class="card">
                <?php foreach ($errors as $error): ?>
                    <div class="error"><?= $h($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form id="chamy-wizard" method="post" action="/install" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $h($csrf) ?>">

            <div class="card">
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
                                <input name="app_name" value="<?= $h($values['app_name'] ?? '') ?>" required>
                                <small>Min 2, Max 80 characters.</small>
                            </div>
                            <div class="field">
                                <label>APP_URL</label>
                                <input name="app_url" value="<?= $h($values['app_url'] ?? '') ?>" required>
                                <small>Full URL incl. port.</small>
                            </div>
                            <div class="field">
                                <label>APP_ENV</label>
                                <select name="app_env">
                                    <option value="production" <?= ($values['app_env'] ?? '') === 'production' ? 'selected' : '' ?>>production</option>
                                    <option value="development" <?= ($values['app_env'] ?? '') === 'development' ? 'selected' : '' ?>>development</option>
                                </select>
                            </div>
                            <div class="field">
                                <label>APP_DEBUG</label>
                                <select name="app_debug">
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
                                <input name="db_host" value="<?= $h($values['db_host'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_PORT</label>
                                <input name="db_port" type="number" min="1" max="65535" value="<?= $h($values['db_port'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_DATABASE</label>
                                <input name="db_database" value="<?= $h($values['db_database'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_USERNAME</label>
                                <input name="db_username" value="<?= $h($values['db_username'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>DB_PASSWORD</label>
                                <input name="db_password" type="password" value="<?= $h($values['db_password'] ?? '') ?>">
                            </div>
                            <div class="field">
                                <label>DB_PREFIX (optional)</label>
                                <input name="db_prefix" value="<?= $h($values['db_prefix'] ?? '') ?>">
                                <small>Leave empty for no prefix.</small>
                            </div>
                        </div>
                    </section>

                    <section class="wizard-panel" data-panel="3" hidden>
                        <h2 class="section-title">3) Admin Account</h2>
                        <div class="grid">
                            <div class="field">
                                <label>Admin Username</label>
                                <input name="admin_username" value="<?= $h($values['admin_username'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Admin E-Mail</label>
                                <input name="admin_email" type="email" value="<?= $h($values['admin_email'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Admin Display Name</label>
                                <input name="admin_display_name" value="<?= $h($values['admin_display_name'] ?? '') ?>" required>
                            </div>
                            <div class="field">
                                <label>Admin Password</label>
                                <input name="admin_password" type="password" required>
                            </div>
                            <div class="field full">
                                <label>Confirm Password</label>
                                <input name="admin_password_confirm" type="password" required>
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

        <?php
    }
}
