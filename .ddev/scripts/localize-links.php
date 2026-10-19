<?php

/**
 * @file
 * Point a pulled Pantheon site's own links at the local docroot.
 *
 * Public URLs for this site become root-relative paths. Links to other sites are left alone.
 *
 * Run script: drush php:script /var/www/html/.ddev/scripts/localize-links.php
 */

$db = \Drupal::database();
$masking = \Drupal::config('pantheon_domain_masking.settings');
$domain = strtolower(trim((string) $masking->get('domain')));
$subpath = trim((string) $masking->get('subpath'), '/');
$pantheon_site = pantheon_site_name(dirname(__DIR__) . '/providers/pantheon.yaml');

if ($domain === '' && $pantheon_site === '') {
  echo "No Pantheon domain masking settings found; link rewrite skipped.\n";
  return;
}

$patterns = link_patterns($domain, $subpath, $pantheon_site);
if (!$patterns) {
  echo "No public URL patterns to rewrite.\n";
  return;
}

$skip_prefixes = [
  'cache',
  'config',
  'watchdog',
  'sessions',
  'key_value',
  'semaphore',
  'batch',
  'queue',
  'flood',
  'router',
];

$columns = $db->query("SELECT table_name, column_name
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND data_type IN ('varchar', 'text', 'mediumtext', 'longtext', 'tinytext')")->fetchAll();

$tables = 0;
$values = 0;
foreach ($columns as $column) {
  $table = $column->table_name;
  foreach ($skip_prefixes as $prefix) {
    if (str_starts_with($table, $prefix)) {
      continue 2;
    }
  }

  $quoted_table = $db->escapeTable($table);
  $quoted_column = $db->escapeTable($column->column_name);
  $likes = [];
  $args = [];
  foreach ($patterns as $index => $pattern) {
    $args[":needle_$index"] = '%' . $pattern['needle'] . '%';
    $likes[] = "$quoted_column LIKE :needle_$index";
  }
  $sql = "SELECT DISTINCT $quoted_column FROM $quoted_table WHERE " . implode(' OR ', $likes);
  $cells = $db->query($sql, $args)->fetchCol();
  $changed = 0;
  foreach ($cells as $original) {
    if (!is_string($original) || $original === '' || is_serialized_php($original)) {
      continue;
    }
    $rewritten = rewrite_local_links($original, $patterns);
    if ($rewritten === $original) {
      continue;
    }
    $db->query(
      "UPDATE $quoted_table SET $quoted_column = :rewritten WHERE $quoted_column = :original",
      [':rewritten' => $rewritten, ':original' => $original]
    );
    $changed++;
  }
  if ($changed > 0) {
    $tables++;
    $values += $changed;
    echo "Updated $changed value(s) in $table.$column->column_name\n";
  }
}

echo "Localized $values stored value(s) across $tables column(s).\n";
if ($domain !== '') {
  echo "Public site was https://$domain" . ($subpath !== '' ? "/$subpath" : '') . "\n";
}

/**
 * Builds the public URL shapes stored for this site.
 *
 * @return array<int, array{needle: string, replace: \Closure}>
 *   Patterns to apply, in order.
 */
function link_patterns(string $domain, string $subpath, string $pantheon_site): array {
  $patterns = [];
  $hosts = $domain === '' ? [] : [$domain];
  if (str_starts_with($domain, 'www.')) {
    $hosts[] = substr($domain, 4);
  }

  foreach ($hosts as $host) {
    $quoted = preg_quote($host, '#');
    if ($subpath !== '') {
      $quoted_subpath = preg_quote($subpath, '#');
      $patterns[] = [
        'needle' => $host . '/' . $subpath,
        'replace' => static function (string $value) use ($quoted, $quoted_subpath): string {
          $value = preg_replace('#https?://' . $quoted . '/' . $quoted_subpath . '/#i', '/', $value) ?? $value;
          return preg_replace('#https?://' . $quoted . '/' . $quoted_subpath . '(?![A-Za-z0-9_/-])#i', '/', $value) ?? $value;
        },
      ];
    }
    else {
      $patterns[] = [
        'needle' => $host,
        'replace' => static function (string $value) use ($quoted): string {
          $value = preg_replace('#https?://' . $quoted . '/#i', '/', $value) ?? $value;
          return preg_replace('#https?://' . $quoted . '(?![A-Za-z0-9_./-])#i', '/', $value) ?? $value;
        },
      ];
    }

    // Legacy CU Boulder masked paths, for example /p11fb41d7646/node/1.
    $patterns[] = [
      'needle' => $host . '/p1',
      'replace' => static function (string $value) use ($quoted): string {
        return preg_replace('#https?://' . $quoted . '/p1[a-z0-9]{10}#i', '', $value) ?? $value;
      },
    ];
  }

  if ($pantheon_site !== '') {
    $quoted_site = preg_quote($pantheon_site, '#');
    $patterns[] = [
      'needle' => $pantheon_site . '.pantheonsite.io',
      'replace' => static function (string $value) use ($quoted_site): string {
        return preg_replace('#https?://(?:[a-z0-9-]+-)?' . $quoted_site . '\.pantheonsite\.io#i', '', $value) ?? $value;
      },
    ];
  }

  return $patterns;
}

/**
 * @param array<int, array{needle: string, replace: \Closure}> $patterns
 */
function rewrite_local_links(string $value, array $patterns): string {
  foreach ($patterns as $pattern) {
    $value = ($pattern['replace'])($value);
  }
  return $value;
}

/**
 * Site machine name from .ddev/providers/pantheon.yaml project: name.env.
 */
function pantheon_site_name(string $provider_file): string {
  if (!is_file($provider_file)) {
    return '';
  }
  $provider = file_get_contents($provider_file);
  if (!is_string($provider) || !preg_match('/^\s*project:\s*["\']?([A-Za-z0-9._-]+)["\']?\s*$/m', $provider, $matches)) {
    return '';
  }
  $project = $matches[1];
  if (preg_match('/^[0-9a-f-]{36}\./i', $project)) {
    return '';
  }
  return (string) preg_replace('/\.(live|test|dev)$/', '', $project);
}

/**
 * Serialized config must keep its string lengths, so leave those cells alone.
 */
function is_serialized_php(string $value): bool {
  if (!preg_match('/^(a|O|s|i|b|d|N|C):/', $value)) {
    return FALSE;
  }
  return @unserialize($value) !== FALSE || $value === 'b:0;';
}
