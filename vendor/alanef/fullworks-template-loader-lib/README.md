# Fullworks Template Loader Library

A flexible PHP template loader library for WordPress plugins that allows for loading templates from theme directories with fallback to plugin directories. This library is based on principles similar to the Gamajo Template Loader but with additional improvements.

## Installation

### Composer

Add the package to your project using Composer:

```bash
composer require alanef/fullworks-template-loader-lib
```

Or add it manually to your `composer.json`:

```json
{
    "require": {
        "alanef/fullworks-template-loader-lib": "^1.0"
    }
}
```

Then run `composer install` or `composer update`.

## Usage

### 1. Create a Template Loader Class

Create a class that extends `Fullworks_Template_Loader_Lib\BaseLoader`:

```php
<?php
namespace YourPlugin\Includes;

use Fullworks_Template_Loader_Lib\BaseLoader;

/**
 * Template loader for YourPlugin
 */
class Template_Loader extends BaseLoader {
    /**
     * Prefix for filter names.
     *
     * @var string
     */
    protected $filter_prefix = 'your-plugin';

    /**
     * Directory name where custom templates for this plugin should be found in the theme.
     *
     * @var string
     */
    protected $theme_template_directory = 'your-plugin';

    /**
     * Reference to the root directory path of this plugin.
     *
     * @var string
     */
    protected $plugin_directory = YOUR_PLUGIN_DIR;

    /**
     * Directory name where templates are found in this plugin.
     *
     * @var string
     */
    protected $plugin_template_directory = 'templates';
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize parent constructor
        parent::__construct();
        
        // You can add additional filter hooks here to modify template paths
        add_filter($this->filter_prefix . '_template_paths', array($this, 'add_template_paths'), 10, 1);
    }
    
    /**
     * Add additional template paths
     *
     * @param array $file_paths Existing template paths
     * @return array Modified template paths
     */
    public function add_template_paths($file_paths) {
        // Example of adding a '/parts' subdirectory to templates
        if (isset($file_paths[1])) {
            $file_paths[2] = trailingslashit($file_paths[1]) . 'parts';
        }
        
        $file_paths[11] = trailingslashit($file_paths[10]) . 'parts';
        
        // Add additional custom directories
        $file_paths[50] = YOUR_PLUGIN_DIR . 'additional-templates';
        
        // Always re-sort the file paths by key to maintain priority
        ksort($file_paths);
        
        return $file_paths;
    }
}
```

### 2. Instantiate and Use the Template Loader

```php
// Instantiate the template loader
$template_loader = new \YourPlugin\Includes\Template_Loader();

// Load a template with optional data
$template_loader->get_template_part('content', 'product', array(
    'title' => 'Product Title',
    'price' => 19.99,
    'description' => 'Product description goes here'
));
```

### 3. Template Data

You can pass data to your templates in several ways:

#### Method 1: Direct Data Parameter

```php
$template_loader->get_template_part('content', 'product', array(
    'title' => 'Product Title',
    'price' => 19.99
));
```

The data parameter accepts either arrays or objects. Arrays will be automatically converted to objects for template use while maintaining array access for extraction.

#### Method 2: Set Data Before Loading Template

```php
$template_loader->set_template_data(array(
    'title' => 'Product Title',
    'price' => 19.99
))->get_template_part('content', 'product');
```

This method stores the data for later use, allowing you to call multiple template parts with the same data. Data stored this way will be available to all nested templates.

#### Method 3: Set Data Using Object

```php
// Create a data object
$product = new \stdClass();
$product->title = 'Product Title';
$product->price = 19.99;

// Set the data object
$template_loader->set_template_data($product)->get_template_part('content', 'product');
```

#### Method 4: Set Data With Custom Variable Name

By default, your data is accessible in templates via the `$data` variable. You can change this by specifying a custom variable name:

```php
$template_loader->set_template_data(
    array('title' => 'Product Title', 'price' => 19.99),
    'product' // This makes the data available as $product in templates
)->get_template_part('content', 'product');
```

This feature is maintained for backward compatibility but not recommended as the library now extracts variables automatically.

### 4. Creating Templates

#### Plugin Templates

Create your template files in your plugin's template directory (default: `templates/`):

```
your-plugin/
├── templates/
│   ├── content.php
│   └── content-product.php
```

#### Theme Templates (for overriding)

Users can override templates by creating corresponding files in their theme:

```
theme/
├── your-plugin/
│   ├── content.php
│   └── content-product.php
```

### 5. Using Data Inside Templates

Inside your template files, you can access the data in three ways:

```php
<?php
// Method 1: Data is available as individual variables
echo $title;
echo $price;

// Method 2: Data is available as an object in $data (default variable name)
echo $data->title;
echo $data->price;

// Method 3: If you specified a custom variable name in set_template_data()
// For example: set_template_data($data, 'product')
echo $product->title;
echo $product->price;
?>

<div class="product">
    <h2><?php echo esc_html($title); ?></h2>
    <div class="price"><?php echo esc_html($price); ?></div>
    <div class="description"><?php echo esc_html($description); ?></div>
</div>
```

The library automatically extracts all data keys into individual variables, so the recommended approach is to use direct variable names. This gives you the cleanest template code.

### 6. Loading Nested Templates

You can load nested templates from within a template in several ways:

#### Method 1: Using the $loader Variable

Every template automatically has access to a `$loader` variable that is an instance of the template loader:

```php
// Inside your template file
echo "Main template content";

// Load a nested template with the same data
$loader->get_template_part('nested-part');

// Load a nested template with additional data
$loader->get_template_part('nested-part', null, [
    'additional' => 'This is additional data'
]);
```

#### Method 2: Using the Static Instance Method

For situations where you need to access the template loader from a function or class:

```php
// Inside your template or function
use Fullworks_Template_Loader_Lib\BaseLoader;

$loader = BaseLoader::get_instance();
if ($loader) {
    $loader->get_template_part('nested-part');
}
```

Both methods allow you to load nested templates while maintaining the data context, making it easy to create complex template hierarchies.

## Advanced Usage

### Custom Template Paths

You can add additional template paths using the `{$filter_prefix}_template_paths` filter:

```php
// Add a premium templates directory with higher priority
add_filter('your-plugin_template_paths', function($file_paths) {
    // Add premium templates with higher priority (lower number)
    $file_paths[5] = YOUR_PLUGIN_DIR . 'premium-templates';
    
    // Always re-sort
    ksort($file_paths);
    return $file_paths;
});
```

### Custom Template Filenames

You can modify the template filenames using the `{$filter_prefix}_get_template_part` filter:

```php
add_filter('your-plugin_get_template_part', function($templates, $slug, $name) {
    // Add a premium version of a template with higher priority
    if ('content' == $slug && 'product' == $name) {
        array_unshift($templates, 'premium-product.php');
    }
    
    return $templates;
}, 10, 3);
```

## Template Loading Priority

Templates are loaded in the following order of priority:

1. Child theme directory with a specific template name (`your-plugin/content-product.php`)
2. Child theme directory with a generic template (`your-plugin/content.php`)
3. Parent theme directory with a specific template name (`your-plugin/content-product.php`)
4. Parent theme directory with a generic template (`your-plugin/content.php`)
5. Plugin template directory with a specific template name (`templates/content-product.php`) 
6. Plugin template directory with a generic template (`templates/content.php`)
7. wp-content directory with a specific template name (`your-plugin/content-product.php`)
8. wp-content directory with a generic template (`your-plugin/content.php`)

The priority numbers are:
- Child theme: 1 (highest priority)
- Parent theme: 10
- Plugin: 100
- wp-content: 200 (lowest priority)

Additional paths can be added and prioritized using the `{$filter_prefix}_template_paths` filter.

## License

This package is licensed under the MIT License.