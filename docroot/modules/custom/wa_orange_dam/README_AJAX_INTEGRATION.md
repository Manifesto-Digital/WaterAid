# Orange DAM AJAX Media Integration

This document describes the AJAX-enabled media form that integrates Orange DAM assets with Drupal's media library system.

## Overview

The AJAX media form provides a seamless way to add DAM assets to Drupal's media library through modal dialogs. This integration extends the existing DamWidget functionality to work in AJAX contexts.

## Components Created

### 1. AjaxMediaController (`src/Controller/AjaxMediaController.php`)
- Handles AJAX requests for media form rendering
- Provides endpoints for asset validation and media creation
- Manages communication between DAM API and Drupal media entities

### 2. AjaxMediaForm (`src/Form/AjaxMediaForm.php`)
- AJAX-enabled form for creating DAM media entities
- Real-time asset validation with preview
- Integrates with Orange DAM browser SDK
- Provides user feedback through AJAX callbacks

### 3. JavaScript Integration (`js/OrangeDamAjaxLauncher.js`)
- Handles DAM browser integration in AJAX contexts
- Manages form updates after asset selection
- Provides compatibility with both legacy and new form structures
- Uses Drupal behaviors for proper AJAX attachment

### 4. Routing Configuration (`wa_orange_dam.routing.yml`)
- Defines endpoints for AJAX media form
- Provides routes for asset validation and media creation
- Includes proper permission checks

### 5. Library Configuration (`wa_orange_dam.libraries.yml`)
- Defines `ajax_content_browser` library
- Includes necessary JavaScript and CSS dependencies
- Provides AJAX-specific styling

### 6. CSS Styling (`css/dam-ajax-modal.css`)
- Responsive modal styling
- Asset preview components
- Message display styling
- Form action layout

## Usage

### In Media Library Context

To use the AJAX form in a media library modal:

```php
use Drupal\Core\Url;

$url = Url::fromRoute('wa_orange_dam.ajax_media_form', [
  'media_type' => 'dam_image', // or 'dam_video', 'dam_file'
]);

$link = [
  '#type' => 'link',
  '#title' => t('Browse DAM Assets'),
  '#url' => $url,
  '#attributes' => [
    'class' => ['use-ajax'],
    'data-dialog-type' => 'modal',
    'data-dialog-options' => json_encode([
      'width' => 800,
      'height' => 600,
    ]),
  ],
  '#attached' => [
    'library' => [
      'core/drupal.dialog.ajax',
      'wa_orange_dam/ajax_content_browser',
    ],
  ],
];
```

### Programmatic Form Usage

```php
$form = \Drupal::formBuilder()->getForm(
  'Drupal\wa_orange_dam\Form\AjaxMediaForm',
  'dam_image'
);
```

### JavaScript Events

The system triggers the following JavaScript events:

- `damMediaCreated`: Fired when a media entity is successfully created
- Custom window functions:
  - `window.damMediaCreated(mediaData)`: Global callback for media creation
  - `window.showMessage(message, type)`: Display user messages

## Configuration

### Supported Media Types

The system supports the following DAM media types:
- `dam_image`: Image assets from DAM
- `dam_video`: Video assets from DAM
- `dam_file`: File assets from DAM

### DAM Type Mapping

Media types are mapped to DAM document types:
- `dam_image` → `['Images*']`
- `dam_video` → `['Videos*']`
- `dam_file` → `[]` (all types)

## Integration Points

### With Media Library

The form integrates with Drupal's media library through:
1. Modal dialogs using `core/drupal.dialog.ajax`
2. AJAX form submissions that create media entities
3. JavaScript callbacks that notify the media library of new entities

### With Existing DamWidget

The AJAX form reuses validation logic from the existing DamWidget:
- Same API calls for asset validation
- Identical field data structure
- Compatible metadata extraction

## Permissions

Required permissions:
- `create media`: Needed to access all AJAX endpoints
- Standard media permissions for the specific media types

## Customization

### Extending the Form

To customize the AJAX form behavior:

```php
// In a custom module
function mymodule_form_wa_orange_dam_ajax_media_form_alter(&$form, FormStateInterface $form_state) {
  // Add custom fields or modify behavior
}
```

### Custom JavaScript Integration

```javascript
// Listen for media creation events
$(document).on('damMediaCreated', function(event, mediaData) {
  console.log('New media created:', mediaData);
  // Custom handling
});
```

### Styling Customization

Override CSS classes in your theme:
- `.dam-asset-preview`: Asset preview container
- `.dam-messages`: Message display area
- `.dam-media-library-add-form`: Form wrapper

## Troubleshooting

### Common Issues

1. **Modal not opening**: Ensure `core/drupal.dialog.ajax` library is loaded
2. **DAM browser not working**: Check that Orange DAM SDK is properly loaded
3. **AJAX validation failing**: Verify Orange DAM API connectivity
4. **Media not created**: Check `create media` permissions

### Debug Information

Enable debug logging by checking Drupal logs for `wa_orange_dam` entries.

### Browser Console

The JavaScript logs detailed information about:
- DAM browser initialization
- Asset selection events
- AJAX form interactions
- Media creation results

## Future Enhancements

Potential improvements:
- Batch media creation from multiple selected assets
- Thumbnail preview in asset selection
- Integration with media library views
- Custom field mapping configuration
- Advanced search filters in DAM browser
