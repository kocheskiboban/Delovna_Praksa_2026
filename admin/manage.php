<?php
require dirname(__DIR__) . '/includes/bootstrap.php';
require APP_ROOT . '/includes/admin.php';
require_admin();

$entities = admin_entities();
$key = input('e', 'events');
if (!isset($entities[$key])) {
    not_found();
}
$E = $entities[$key];
$table = $E['table'];
$action = input('action', 'list');
$id = input_int('id');
$listUrl = static fn(array $p = []) => url('admin/manage.php', ['e' => $key] + $p);
$transFields = array_values(array_filter($E['fields'], static fn($f) => !empty($f['trans'])));
$baseFields = array_values(array_filter($E['fields'], static fn($f) => empty($f['trans'])));
$langs = array_keys(LANGUAGES);

if (is_post()) {
    csrf_check();
    $op = input('op', '', 'post');
    if ($op === 'delete') {
        try {
            db_exec("DELETE FROM `$table` WHERE id = ?", [$id]);
            flash('success', t('admin.deleted'));
        } catch (PDOException $ex) {
            flash('error', t($ex->getCode() === '23000' ? 'admin.err_delete_fk' : 'admin.err_generic'));
        }
        redirect($listUrl());
    }
    if ($op === 'toggle') {
        $field = input('field', '', 'post');
        $allowed = ['status' => ['published', 'draft'], 'is_published' => [1, 0], 'is_active' => [1, 0], 'is_featured' => [1, 0], 'sales_status' => ['open', 'closed']];
        $hasField = (bool) array_filter($E['fields'], static fn($f) => $f['name'] === $field);
        if (isset($allowed[$field]) && $hasField) {
            $cur = db_value("SELECT `$field` FROM `$table` WHERE id = ?", [$id]);
            [$a, $b] = $allowed[$field];
            db_exec("UPDATE `$table` SET `$field` = ? WHERE id = ?", [(string) $cur === (string) $a ? $b : $a, $id]);
            flash('success', t('admin.updated'));
        }
        redirect(safe_return($_POST['return'] ?? '', $listUrl()));
    }
    if ($op === 'save') {
        $errors = [];
        $values = [];
        $tr = (array) ($_POST['tr'] ?? []);
        $firstTitle = '';
        foreach (['mk', 'en'] as $lg) {
            if ($firstTitle === '' && !empty($tr[$lg][$E['title']])) {
                $firstTitle = (string) $tr[$lg][$E['title']];
            }
        }
        foreach ($baseFields as $f) {
            $n = $f['name'];
            $raw = $_POST[$n] ?? null;
            $raw = is_string($raw) ? trim($raw) : $raw;
            switch ($f['type']) {
                case 'performers':
                    continue 2;
                case 'checkbox':
                    $values[$n] = empty($raw) ? 0 : 1;
                    break;
                case 'number':
                    $values[$n] = ($raw === '' || $raw === null) ? null : (is_numeric($raw) ? (int) $raw : 'NaN');
                    break;
                case 'decimal':
                    $raw = is_string($raw) ? str_replace(',', '.', $raw) : $raw;
                    $values[$n] = ($raw === '' || $raw === null) ? null : (is_numeric($raw) ? (float) $raw : 'NaN');
                    break;
                case 'datetime':
                    $ts = $raw ? strtotime((string) $raw) : false;
                    $values[$n] = $ts ? date('Y-m-d H:i:s', $ts) : ($raw ? 'NaN' : null);
                    break;
                case 'date':
                    $ts = $raw ? strtotime((string) $raw) : false;
                    $values[$n] = $ts ? date('Y-m-d', $ts) : ($raw ? 'NaN' : null);
                    break;
                case 'slug':
                    $values[$n] = slugify((string) ($raw !== '' && $raw !== null ? $raw : ($firstTitle ?: ($_POST['name'] ?? ''))));
                    break;
                case 'image':
                    try {
                        $up = admin_handle_upload($n . '_file', $E['image_dir'] ?? 'misc');
                    } catch (Throwable $ex) {
                        $errors[$n] = $ex instanceof InvalidArgumentException ? $ex->getMessage() : 'admin.err_generic';
                        $up = null;
                    }
                    $path = trim((string) ($_POST[$n] ?? ''));
                    if ($path !== '' && !preg_match('#^(assets/img/|uploads/|https?://)[\w./%-]+$#', $path)) {
                        $errors[$n] = 'admin.err_image_path';
                    }
                    $values[$n] = $up ?? ($path !== '' ? $path : null);
                    break;
                case 'select':
                    $opts = is_array($f['options']) ? $f['options'] : admin_options($f['options']);
                    $values[$n] = ($raw === '' || $raw === null) ? null : (array_key_exists($raw, $opts) ? $raw : 'NaN');
                    break;
                case 'url':
                    $values[$n] = $raw ?: null;
                    if ($raw && !filter_var($raw, FILTER_VALIDATE_URL)) {
                        $errors[$n] = 'validation.url';
                    }
                    break;
                case 'email':
                    $values[$n] = $raw ?: null;
                    if ($raw && !is_valid_email($raw)) {
                        $errors[$n] = 'validation.email';
                    }
                    break;
                case 'color':
                    $values[$n] = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $raw) ? $raw : 'NaN';
                    break;
                default:
                    $values[$n] = ($raw === '' || $raw === null) ? null : mb_substr((string) $raw, 0, 255);
                    if (!empty($f['pattern']) && $raw && !preg_match('/^' . $f['pattern'] . '$/', (string) $raw)) {
                        $errors[$n] = 'validation.pattern';
                    }
            }
            if (($values[$n] ?? null) === 'NaN') {
                $errors[$n] = 'validation.invalid';
            } elseif (!empty($f['required']) && ($values[$n] === null || $values[$n] === '')) {
                $errors[$n] = 'validation.required';
            } elseif (isset($f['min']) && $values[$n] !== null && $values[$n] < $f['min']) {
                $errors[$n] = 'validation.invalid';
            } elseif (isset($f['max']) && $values[$n] !== null && $values[$n] > $f['max']) {
                $errors[$n] = 'validation.invalid';
            }
        }
        if (isset($values['slug']) && !isset($errors['slug']) && db_value("SELECT 1 FROM `$table` WHERE slug = ? AND id <> ?", [$values['slug'], $id])) {
            $errors['slug'] = 'admin.err_slug';
        }
        if (isset($values['code']) && db_value("SELECT 1 FROM `$table` WHERE code = ? AND id <> ?", [$values['code'], $id])) {
            $errors['code'] = 'admin.err_slug';
        }
        if ($key === 'editions' && !$errors && $values['end_date'] < $values['start_date']) {
            $errors['end_date'] = 'admin.err_dates';
        }
        if ($key === 'editions' && !$errors && db_value('SELECT 1 FROM festival_editions WHERE year = ? AND id <> ?', [$values['year'], $id])) {
            $errors['year'] = 'admin.err_slug';
        }
        if ($key === 'events' && !$errors) {
            $venue = db_one('SELECT seating_type FROM venues WHERE id = ?', [(int) $values['venue_id']]);
            $mode = $values['seating_mode'] ?: $venue['seating_type'];
            if ($mode === 'general' && $venue['seating_type'] === 'seated' && !$values['capacity']) {
                $errors['capacity'] = 'admin.err_capacity';
            }
            if ($mode === 'seated' && $venue['seating_type'] === 'general') {
                $errors['seating_mode'] = 'admin.err_no_seats';
            }
            if ($values['price_vip'] !== null && $values['price_vip'] < $values['price_standard']) {
                $errors['price_vip'] = 'admin.err_vip';
            }
        }
        $trClean = [];
        foreach ($langs as $lg) {
            $row = [];
            foreach ($transFields as $f) {
                $row[$f['name']] = mb_substr(trim((string) ($tr[$lg][$f['name']] ?? '')), 0, $f['type'] === 'textarea' ? 60000 : 255);
            }
            $filled = array_filter($row, static fn($v) => $v !== '');
            if ($filled) {
                foreach ($transFields as $f) {
                    if (!empty($f['required']) && $row[$f['name']] === '') {
                        $errors['tr_' . $lg . '_' . $f['name']] = 'validation.required';
                    }
                }
                $trClean[$lg] = $row;
            }
        }
        if ($transFields && !isset($trClean['mk']) && !isset($trClean['en'])) {
            $errors['tr_mk_' . $transFields[0]['name']] = 'admin.err_translation';
        }
        if (!$errors) {
            try {
                db_transaction(function () use ($table, $values, $id, $E, $trClean, $langs, $key, &$newId) {
                    $cols = array_keys($values);
                    if ($id) {
                        db_exec("UPDATE `$table` SET " . implode(', ', array_map(static fn($c) => "`$c` = ?", $cols)) . ' WHERE id = ?', array_merge(array_values($values), [$id]));
                        $newId = $id;
                    } else {
                        db_exec("INSERT INTO `$table` (" . implode(',', array_map(static fn($c) => "`$c`", $cols)) . ') VALUES (' . db_placeholders($cols) . ')', array_values($values));
                        $newId = db_insert_id();
                    }
                    foreach ($langs as $lg) {
                        if (!isset($trClean[$lg])) {
                            db_exec("DELETE FROM `{$E['trans']}` WHERE `{$E['fk']}` = ? AND lang = ?", [$newId, $lg]);
                            continue;
                        }
                        $tc = array_keys($trClean[$lg]);
                        db_exec("INSERT INTO `{$E['trans']}` (`{$E['fk']}`, lang, " . implode(',', array_map(static fn($c) => "`$c`", $tc)) . ') VALUES (?, ?, ' . db_placeholders($tc) . ')
                            ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(static fn($c) => "`$c` = VALUES(`$c`)", $tc)),
                            array_merge([$newId, $lg], array_map(static fn($v) => $v === '' ? null : $v, array_values($trClean[$lg]))));
                    }
                    if ($key === 'events') {
                        db_exec('DELETE FROM event_artists WHERE event_id = ?', [$newId]);
                        $roles = ['performer', 'soloist', 'conductor', 'director', 'host'];
                        $sort = 0;
                        foreach ((array) ($_POST['performers'] ?? []) as $aid => $role) {
                            if ($role === '' || !in_array($role, $roles, true)) {
                                continue;
                            }
                            db_exec('INSERT INTO event_artists (event_id, artist_id, role, sort_order) VALUES (?, ?, ?, ?)', [$newId, (int) $aid, $role, $sort++]);
                        }
                    }
                });
                flash('success', t($id ? 'admin.updated' : 'admin.created'));
                redirect(isset($_POST['save_close']) ? $listUrl() : $listUrl(['action' => 'edit', 'id' => $newId]));
            } catch (PDOException $ex) {
                error_log($ex->getMessage());
                $errors['_form'] = 'admin.err_generic';
            }
        }
        $action = $id ? 'edit' : 'new';
        $GLOBALS['form_errors'] = $errors;
        $GLOBALS['form_values'] = $values;
        $GLOBALS['form_tr'] = $tr;
    }
}

[$tSel, $tJoin] = tr_sql($E['trans'], $E['fk'], 'x', array_column($transFields, 'name'));
function admin_cell(array $row, string $col, string $type): string
{
    $v = $row[$col] ?? null;
    switch ($type) {
        case 'image':
            return $v ? '<img class="admin-thumb" src="' . e(img($v)) . '" alt="" loading="lazy" width="64" height="44">' : '—';
        case 'datetime':
            return $v ? e(date('Y-m-d H:i', strtotime($v))) : '—';
        case 'date':
            return $v ? e($v) : '—';
        case 'money':
            return e(money($v));
        case 'percent':
            return (int) $v . '%';
        case 'status':
            return status_badge((string) $v);
        case 'bool':
            return $v ? '<span class="badge badge--success">' . icon('check') . '</span>' : '<span class="badge badge--muted">—</span>';
        case 'color':
            return '<span class="swatch" style="background:' . e($v) . '"></span> ' . e($v);
        case 'text':
        case 'base':
        case 'title':
            return e(str_limit((string) $v, 60));
        default:
            $opts = admin_options($type);
            return e($opts[$v] ?? (string) $v);
    }
}

if ($action === 'list') {
    $q = mb_substr(input('q', '', 'get'), 0, 80);
    $where = [];
    $p = [];
    if ($q !== '') {
        $like = '%' . $q . '%';
        $or = $transFields ? ["EXISTS (SELECT 1 FROM `{$E['trans']}` s WHERE s.`{$E['fk']}` = x.id AND s.`{$E['title']}` LIKE ?)"] : [];
        $p[] = $like;
        if (array_filter($baseFields, static fn($f) => $f['name'] === 'name')) {
            $or[] = 'x.name LIKE ?';
            $p[] = $like;
        }
        if (array_filter($baseFields, static fn($f) => $f['name'] === 'slug')) {
            $or[] = 'x.slug LIKE ?';
            $p[] = $like;
        }
        if (!$or) {
            array_pop($p);
        } else {
            $where[] = '(' . implode(' OR ', $or) . ')';
        }
    }
    foreach ($E['filters'] ?? [] as $fk => $_) {
        $fv = input($fk, '', 'get');
        if ($fv === '') {
            continue;
        }
        if ($fk === 'when') {
            $where[] = $fv === 'past' ? 'x.starts_at < NOW()' : 'x.starts_at >= NOW()';
        } else {
            $where[] = "x.`$fk` = ?";
            $p[] = $fv;
        }
    }
    $whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
    $per = 20;
    $page = current_page();
    $total = (int) db_value("SELECT COUNT(*) FROM `$table` x $whereSql", $p);
    $selectTr = $tSel ? ", $tSel" : '';
    $rows = db_all("SELECT x.* $selectTr FROM `$table` x $tJoin $whereSql ORDER BY {$E['order']} LIMIT $per OFFSET " . (($page - 1) * $per), $p);

    admin_start($key, t($E['label']));
    ?>
    <div class="admin-toolbar">
      <form method="get" action="<?= e(url('admin/manage.php')) ?>" class="admin-filters">
        <input type="hidden" name="e" value="<?= e($key) ?>">
        <?php if (lang() !== config('app.default_lang')): ?><input type="hidden" name="lang" value="<?= e(lang()) ?>"><?php endif; ?>
        <label class="visually-hidden" for="aq"><?= e(t('admin.search')) ?></label>
        <input class="search-input" id="aq" type="search" name="q" value="<?= e($q) ?>" placeholder="<?= e(t('admin.search')) ?>">
        <?php foreach ($E['filters'] ?? [] as $fk => $opts): $opts = is_array($opts) ? $opts : admin_options($opts); ?>
          <label class="visually-hidden" for="af-<?= e($fk) ?>"><?= e($fk) ?></label>
          <div class="select"><select id="af-<?= e($fk) ?>" name="<?= e($fk) ?>" class="search-input"><option value=""><?= e(t('admin.all')) ?></option>
            <?php foreach ($opts as $ov => $ol): if ($ov === '') continue; ?><option value="<?= e((string) $ov) ?>"<?= (string) ($_GET[$fk] ?? '') === (string) $ov ? ' selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?>
          </select><?= icon('chevron-down') ?></div>
        <?php endforeach; ?>
        <button class="btn btn--ghost" type="submit"><?= icon('filter') ?><?= e(t('programme.apply')) ?></button>
      </form>
      <a class="btn btn--primary" href="<?= e($listUrl(['action' => 'new'])) ?>"><?= icon('plus') ?><?= e(t('admin.new')) ?></a>
    </div>
    <p class="muted"><?= e(t('admin.records', ['n' => $total])) ?></p>
    <?php if (!$rows): ?>
      <?= empty_state($E['icon'], t('admin.empty'), '', $listUrl(['action' => 'new']), t('admin.new')) ?>
    <?php else: ?>
    <div class="table-wrap panel" style="padding:0">
      <table class="data-table">
        <thead><tr>
          <?php foreach ($E['list'] as [$col, $type]): ?><th scope="col"><?= e(t('admin.col_' . $col)) ?></th><?php endforeach; ?>
          <th scope="col"><span class="visually-hidden"><?= e(t('admin.actions')) ?></span></th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $r): $editUrl = $listUrl(['action' => 'edit', 'id' => $r['id']]); ?>
          <tr>
            <?php foreach ($E['list'] as [$col, $type]): ?>
              <td><?= in_array($type, ['title', 'base'], true) ? '<a href="' . e($editUrl) . '"><strong>' . admin_cell($r, $col, $type) . '</strong></a>' : admin_cell($r, $col, $type) ?></td>
            <?php endforeach; ?>
            <td class="row-actions">
              <a class="icon-btn" href="<?= e($editUrl) ?>" aria-label="<?= e(t('admin.edit')) ?>" title="<?= e(t('admin.edit')) ?>"><?= icon('edit') ?></a>
              <?php if (!empty($E['public']) && !empty($r['slug'])): ?><a class="icon-btn" href="<?= e(url($E['public'], ['slug' => $r['slug']])) ?>" aria-label="<?= e(t('admin.view')) ?>" title="<?= e(t('admin.view')) ?>"><?= icon('eye') ?></a><?php endif; ?>
              <?php if (!empty($E['public_param'])): ?><a class="icon-btn" href="<?= e(url($E['public_param'][0], [$E['public_param'][1] => $r[$E['public_param'][1]]])) ?>" aria-label="<?= e(t('admin.view')) ?>"><?= icon('eye') ?></a><?php endif; ?>
              <?php if ($key === 'events'): ?><a class="icon-btn" href="<?= e(url('admin/seats.php', ['id' => $r['id']])) ?>" aria-label="<?= e(t('admin.availability')) ?>" title="<?= e(t('admin.availability')) ?>"><?= icon('ticket') ?></a><?php endif; ?>
              <?php foreach (['status', 'is_published', 'is_active'] as $tf): if (!array_key_exists($tf, $r) || ($tf === 'status' && !in_array($r['status'], ['published', 'draft'], true))) continue; ?>
                <form method="post" action="<?= e($listUrl(['id' => $r['id']])) ?>"><?= csrf_field() ?><input type="hidden" name="op" value="toggle"><input type="hidden" name="field" value="<?= e($tf) ?>"><input type="hidden" name="return" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
                  <button class="icon-btn" type="submit" aria-label="<?= e(t('admin.toggle_publish')) ?>" title="<?= e(t('admin.toggle_publish')) ?>"><?= icon('refresh') ?></button></form>
              <?php break; endforeach; ?>
              <form method="post" action="<?= e($listUrl(['id' => $r['id']])) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>"><?= csrf_field() ?><input type="hidden" name="op" value="delete">
                <button class="icon-btn icon-btn--danger" type="submit" aria-label="<?= e(t('admin.delete')) ?>" title="<?= e(t('admin.delete')) ?>"><?= icon('trash') ?></button></form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= pagination($total, $per, $page) ?>
    <?php endif;
    admin_end(['scripts' => ['assets/js/admin.js']]);
    exit;
}

$row = [];
$trRows = [];
if ($action === 'edit') {
    $row = db_one("SELECT * FROM `$table` WHERE id = ?", [$id]);
    if (!$row) {
        not_found();
    }
    foreach (db_all("SELECT * FROM `{$E['trans']}` WHERE `{$E['fk']}` = ?", [$id]) as $tr) {
        $trRows[$tr['lang']] = $tr;
    }
}
$errors = $GLOBALS['form_errors'] ?? [];
if (isset($GLOBALS['form_values'])) {
    $row = array_merge($row, array_filter($GLOBALS['form_values'], static fn($v) => $v !== 'NaN'));
    foreach ($GLOBALS['form_tr'] as $lg => $vals) {
        $trRows[$lg] = (array) $vals;
    }
}
foreach ($baseFields as $f) {
    if (!array_key_exists($f['name'], $row) && isset($f['default'])) {
        $row[$f['name']] = $f['default'];
    }
}
$title = $action === 'edit' ? t('admin.edit') . ': ' . ($trRows['mk'][$E['title']] ?? $trRows['en'][$E['title']] ?? $row['name'] ?? '#' . $id) : t('admin.new') . ' — ' . t($E['label']);

admin_start($key, $title);
?>
<p><a class="link-arrow" href="<?= e($listUrl()) ?>"><?= icon('arrow-left') ?><?= e(t('admin.back_list')) ?></a></p>
<?php if ($errors): ?><?= alert('error', e(t(isset($errors['_form']) ? $errors['_form'] : 'validation.form_errors'))) ?><?php endif; ?>
<form method="post" action="<?= e($listUrl(['id' => $id ?: null])) ?>" enctype="multipart/form-data" class="admin-form" novalidate>
  <?= csrf_field() ?><input type="hidden" name="op" value="save">
  <?php if ($transFields): ?>
  <section class="panel">
    <h2><?= e(t('admin.translations')) ?></h2>
    <p class="muted"><?= e(t('admin.translations_help')) ?></p>
    <div class="tabs" role="tablist" data-tabs>
      <?php foreach ($langs as $i => $lg): $hasErr = (bool) array_filter(array_keys($errors), static fn($k) => str_starts_with($k, 'tr_' . $lg . '_')); ?>
        <button type="button" role="tab" id="tab-<?= $lg ?>" aria-controls="panel-<?= $lg ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"><?= e(LANGUAGES[$lg]['name']) ?><?= $hasErr ? ' ⚠' : '' ?><?= !empty(array_filter((array) ($trRows[$lg] ?? []), static fn($v) => is_string($v) && $v !== '' && strlen($v) > 2)) ? ' ✓' : '' ?></button>
      <?php endforeach; ?>
    </div>
    <?php foreach ($langs as $i => $lg): ?>
      <div role="tabpanel" id="panel-<?= $lg ?>" aria-labelledby="tab-<?= $lg ?>" data-tabpanel <?= $i === 0 ? '' : 'data-inactive' ?> lang="<?= $lg ?>">
        <?php foreach ($transFields as $f):
            $errKey = 'tr_' . $lg . '_' . $f['name']; ?>
          <?= field(['name' => "tr[$lg][{$f['name']}]", 'id' => "tr-$lg-{$f['name']}", 'label' => t($f['label']) . ' (' . strtoupper($lg) . ')',
              'type' => $f['type'] === 'textarea' ? 'textarea' : 'text', 'rows' => $f['rows'] ?? 6, 'value' => $trRows[$lg][$f['name']] ?? '',
              'errors' => isset($errors[$errKey]) ? ["tr[$lg][{$f['name']}]" => $errors[$errKey]] : [], 'hint' => !empty($f['help']) ? t($f['help']) : null]) ?>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </section>
  <?php endif; ?>

  <section class="panel">
    <h2><?= e(t('admin.details')) ?></h2>
    <div class="form-grid">
    <?php foreach ($baseFields as $f):
        $n = $f['name'];
        $val = $row[$n] ?? '';
        $common = ['name' => $n, 'label' => t($f['label']), 'required' => !empty($f['required']), 'errors' => $errors, 'hint' => !empty($f['help']) ? t($f['help']) : null];
        switch ($f['type']):
            case 'performers':
                $current = $id ? array_column(db_all('SELECT artist_id, role FROM event_artists WHERE event_id = ?', [$id]), 'role', 'artist_id') : [];
                if (isset($_POST['performers'])) { $current = array_filter((array) $_POST['performers']); }
                echo '<fieldset class="span-2 performers-field"><legend>' . e(t($f['label'])) . '</legend><p class="field__hint">' . e(t('admin.h_performers')) . '</p><div class="performer-grid">';
                foreach (admin_options('artists') as $aid => $aname) {
                    echo '<label><span>' . e($aname) . '</span><select name="performers[' . (int) $aid . ']"><option value="">—</option>';
                    foreach (['performer', 'soloist', 'conductor', 'director', 'host'] as $role) {
                        echo '<option value="' . $role . '"' . (($current[$aid] ?? '') === $role ? ' selected' : '') . '>' . e(t('role.' . $role)) . '</option>';
                    }
                    echo '</select></label>';
                }
                echo '</div></fieldset>';
                break;
            case 'checkbox':
                echo field(['name' => $n, 'type' => 'checkbox', 'label' => t($f['label']), 'checked' => !empty($val), 'errors' => $errors]);
                break;
            case 'select':
                $opts = is_array($f['options']) ? $f['options'] : admin_options($f['options']);
                if (empty($f['required']) && !array_key_exists('', $opts)) { $opts = ['' => '—'] + $opts; }
                echo field($common + ['type' => 'select', 'value' => (string) $val, 'options' => $opts]);
                break;
            case 'datetime':
                echo field($common + ['type' => 'datetime-local', 'value' => $val ? date('Y-m-d\TH:i', strtotime((string) $val)) : '']);
                break;
            case 'date':
                echo field($common + ['type' => 'date', 'value' => (string) $val]);
                break;
            case 'number':
            case 'decimal':
                echo field($common + ['type' => 'number', 'value' => (string) $val, 'attrs' => ($f['type'] === 'decimal' ? 'step="any"' : 'step="1"') . (isset($f['min']) ? ' min="' . $f['min'] . '"' : '') . (isset($f['max']) ? ' max="' . $f['max'] . '"' : '')]);
                break;
            case 'color':
                echo field($common + ['type' => 'color', 'value' => (string) ($val ?: '#134a66')]);
                break;
            case 'image':
                echo '<div class="span-2 image-field">';
                if ($val) { echo '<img src="' . e(img((string) $val)) . '" alt="" class="image-field__preview" width="200" height="133">'; }
                echo field($common + ['type' => 'text', 'value' => (string) $val, 'hint' => t('admin.h_image_path')]);
                echo '<div class="field"><label for="f-' . e($n) . '-file">' . e(t('admin.upload')) . '</label><input id="f-' . e($n) . '-file" type="file" name="' . e($n) . '_file" accept="image/jpeg,image/png,image/webp,image/gif"><p class="field__hint">' . e(t('admin.h_upload')) . '</p></div></div>';
                break;
            case 'slug':
                echo field($common + ['type' => 'text', 'value' => (string) $val, 'hint' => t('admin.h_slug'), 'attrs' => 'pattern="[a-z0-9-]+"']);
                break;
            default:
                echo field($common + ['type' => in_array($f['type'], ['url', 'email'], true) ? $f['type'] : 'text', 'value' => (string) $val, 'attrs' => !empty($f['pattern']) ? 'pattern="' . e($f['pattern']) . '"' : '']);
        endswitch;
    endforeach; ?>
    </div>
  </section>
  <div class="form-actions admin-sticky-actions">
    <button class="btn btn--primary" type="submit"><?= icon('check') ?><?= e(t('admin.save')) ?></button>
    <button class="btn btn--ghost" type="submit" name="save_close" value="1"><?= e(t('admin.save_close')) ?></button>
    <a class="btn btn--ghost" href="<?= e($listUrl()) ?>"><?= e(t('common.cancel')) ?></a>
    <?php if ($action === 'edit' && $key === 'events'): ?><a class="btn btn--ghost" href="<?= e(url('admin/seats.php', ['id' => $id])) ?>"><?= icon('ticket') ?><?= e(t('admin.availability')) ?></a><?php endif; ?>
    <?php if ($action === 'edit' && !empty($E['public']) && !empty($row['slug'])): ?><a class="btn btn--ghost" href="<?= e(url($E['public'], ['slug' => $row['slug']])) ?>" target="_blank"><?= icon('eye') ?><?= e(t('admin.view')) ?></a><?php endif; ?>
  </div>
</form>
<?php if ($action === 'edit'): ?>
<form method="post" action="<?= e($listUrl(['id' => $id])) ?>" data-confirm="<?= e(t('admin.confirm_delete')) ?>" style="margin-top:var(--s-5)">
  <?= csrf_field() ?><input type="hidden" name="op" value="delete">
  <button class="btn btn--danger btn--sm" type="submit"><?= icon('trash') ?><?= e(t('admin.delete')) ?></button>
</form>
<?php endif; ?>
<?php admin_end(['scripts' => ['assets/js/admin.js']]);
