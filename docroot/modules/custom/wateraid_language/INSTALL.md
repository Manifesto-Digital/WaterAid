# Installation & Setup Guide

## Prerequisites

- Drupal site with Group module enabled
- Pathauto module enabled
- Multiple languages configured
- Groups created for each site (UK, Japan, India, etc.)

## Installation Steps

### 1. Enable the Module

```bash
cd /Users/danj/Sites/WaterAid
drush en wateraid_language -y
```

### 2. Configure Your Site Mappings

The module comes with example mappings. You need to update them to match your actual group labels.

**Check your existing group labels:**

```bash
drush php:eval "foreach (\Drupal::entityTypeManager()->getStorage('group')->loadMultiple() as \$group) { echo \$group->id() . ': ' . \$group->label() . PHP_EOL; }"
```

**Edit the configuration file to match your groups:**

File: `docroot/modules/custom/wateraid_language/config/install/wateraid_language.site_language_map.yml`

Update the `group_label` values to match exactly what you see from the command above.

### 3. Check Current Languages

```bash
drush language:info
```

Make sure you have all the languages you need (en, ja, hi, sv, etc.). Add any missing languages:

```bash
drush language:add ja
drush language:add hi
```

### 4. Import Configuration

After editing the site mapping file, import the configuration:

```bash
drush cim -y
```

Or if you want to sync only this module's config:

```bash
drush config:import --partial --source=modules/custom/wateraid_language/config/install
```

### 5. Verify Language Negotiation

Check that the custom language negotiation method is enabled:

```bash
drush config:get language.types negotiation
```

You should see `wateraid-site-language` listed with weight -9.

### 6. Regenerate Path Aliases

This is crucial - regenerate all aliases to use the new tokens:

```bash
drush pathauto:aliases-generate --all
```

This will update all existing content to use the new URL structure.

### 7. Clear Caches

```bash
drush cr
```

## Verification

### Test Language Detection

Visit these URLs (adjust based on your configuration):

- `/jp` - Should detect Japanese language
- `/uk` - Should detect English language
- `/in` - Should detect English language
- `/in/hi` - Should detect Hindi language

### Check Generated Aliases

View some content aliases:

```bash
drush sql:query "SELECT * FROM path_alias WHERE alias LIKE '/jp%' OR alias LIKE '/in%' LIMIT 10"
```

You should see aliases like:
- `/jp/blog/article-title`
- `/in/stories/story-title`
- `/in/hi/event/event-title`

### Test Content Access

1. Create a test article in the Japan group (in Japanese)
2. Check its URL - should be `/jp/blog/[title]`
3. Create a test article in India group (in Hindi)
4. Check its URL - should be `/in/hi/blog/[title]`

## Troubleshooting

### Issue: Aliases Not Updating

**Solution:**
```bash
# Delete all existing aliases
drush sql:query "TRUNCATE path_alias"
drush sql:query "TRUNCATE path_alias_revision"

# Regenerate
drush pathauto:aliases-generate --all
drush cr
```

### Issue: Language Not Detected

**Check configuration:**
```bash
drush config:get language.types negotiation
```

**Verify the plugin is registered:**
```bash
drush php:eval "print_r(\Drupal::service('plugin.manager.language_negotiation_method')->getDefinitions());"
```

Look for `wateraid-site-language` in the output.

### Issue: Token Not Working

**Test the token directly:**
```bash
drush php:eval "
\$group = \Drupal::entityTypeManager()->getStorage('group')->load(1);
\$token = \Drupal::token();
echo \$token->replace('[group:url-prefix]', ['group' => \$group]) . PHP_EOL;
"
```

### Issue: Group Label Mismatch

**Update your configuration to match actual labels:**
```bash
# Get actual labels
drush php:eval "foreach (\Drupal::entityTypeManager()->getStorage('group')->loadMultiple() as \$group) { echo \$group->id() . ': ' . \$group->label() . PHP_EOL; }"

# Then update config/install/wateraid_language.site_language_map.yml
# And reimport: drush cim -y
```

## Next Steps

1. **Set up redirects** for old URLs if needed (using Redirect module)
2. **Update any hardcoded URLs** in templates or custom code
3. **Test language switcher** to ensure it generates correct URLs
4. **Update sitemap** if using XML Sitemap module
5. **Test with actual editors** to ensure URL generation works during content creation

## Configuration Management

When deploying to other environments:

1. Export config: `drush cex -y`
2. Commit the changes in `config/sync/`
3. On target environment:
   - `git pull`
   - `drush cim -y`
   - `drush pathauto:aliases-generate --all`
   - `drush cr`

## Rollback

If you need to rollback:

1. Disable module: `drush pmu wateraid_language -y`
2. Revert pathauto patterns: `drush config:delete pathauto.pattern.group_relationship pathauto.pattern.site_groups`
3. Import original config: `drush cim -y`
4. Regenerate aliases: `drush pathauto:aliases-generate --all`
5. Clear cache: `drush cr`
