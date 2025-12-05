<?php
namespace Fullworks_Template_Loader_Lib;

class BaseLoader
{
	/**
	 * Prefix for filter names.
	 *
	 * @var string
	 */
	protected $filter_prefix = 'fullworks-base-template-loader';

	/**
	 * Directory name where custom templates for this plugin should be found in the theme.
	 *
	 * @var string
	 */
	protected $theme_template_directory = null;

	/**
	 * Reference to the root directory path of this plugin.
	 *
	 * @var string
	 */
	protected $plugin_directory = null;
	
	/**
	 * Constructor to validate required properties.
	 *
	 * Child classes should set $theme_template_directory and $plugin_directory
	 * in their constructor before calling parent::__construct().
	 */
	public function __construct() {
		if (null === $this->theme_template_directory) {
			_doing_it_wrong(__CLASS__, 'The $theme_template_directory property must be set.', '1.0.0');
		}
		if (null === $this->plugin_directory) {
			_doing_it_wrong(__CLASS__, 'The $plugin_directory property must be set.', '1.0.0');
		}
	}

	/**
	 * Directory name where templates are found in this plugin.
	 *
	 * @var string
	 */
	protected $plugin_template_directory = 'templates';

	/**
	 * Internal cache for template paths
	 *
	 * @var array
	 */
	private $template_path_cache = array();
	/**
	 * Store template data for the current template
	 *
	 * @var mixed
	 */
	private static $current_template_data = null;
	
	/**
	 * Store the current template loader instance for access in templates
	 *
	 * @var self
	 */
	private static $template_loader_instance = null;

	/**
	 * Make custom data available to template.
	 *
	 * This method is kept for backward compatibility with the Gamajo_Template_Loader
	 *
	 * @param mixed  $data     Custom data for the template.
	 * @param string $var_name Optional. Variable under which the custom data is available in the template.
	 *                         Default is 'data'.
	 *
	 * @return BaseLoader
	 */
	public function set_template_data($data, $var_name = 'data') {
		// Store the data for later use in get_template_part
		// If it's an array, convert it to stdClass object for backward compatibility
		if (is_array($data)) {
			$obj_data = new \stdClass();
			foreach ($data as $key => $value) {
				$obj_data->$key = $value;
			}
			self::$current_template_data = $obj_data;
		} else {
			self::$current_template_data = $data;
		}

		return $this;
	}


	/**
	 * Remove access to custom data in template.
	 *
	 * This method is kept for backward compatibility with the Gamajo_Template_Loader
	 * but does nothing in this implementation.
	 *
	 * @return BaseLoader
	 */
	public function unset_template_data() {
		self::$current_template_data = null;
		return $this;
	}


	/**
	 * Retrieve a template part.
	 *
	 * @param string $slug Template slug.
	 * @param string $name Optional. Template variation name. Default null.
	 * @param mixed  $data Optional. Data to pass to template (array or object). Default null to use current template data.
	 * @param bool   $load Optional. Whether to load template. Default true.
	 *
	 * @return string
	 */
	public function get_template_part($slug, $name = null, $data = null, $load = true) {
		// If data is null, use the data set with set_template_data
		if ($data === null && self::$current_template_data !== null) {
			$data = self::$current_template_data;
			// Do NOT reset the current_template_data, so it's available for nested calls
			// self::$current_template_data = null;
		}
		// Execute code for this part.
		do_action('get_template_part_' . $slug, $slug, $name);
		do_action($this->filter_prefix . '_get_template_part_' . $slug, $slug, $name);

		// Get files names of templates, for given slug and name.
		$templates = $this->get_template_file_names($slug, $name);

		// If data is provided, store it for nested template calls
		if ($data !== null) {
			self::$current_template_data = $data;
		} else {
			// Default to empty array if no data is provided
			$data = !empty($data) ? $data : array();
		}

		// Return the part that is found.
		return $this->locate_template($templates, $data, $load, false);
	}

	/**
	 * Given a slug and optional name, create the file names of templates.
	 *
	 * @param string $slug Template slug.
	 * @param string $name Template variation name.
	 *
	 * @return array
	 */
	protected function get_template_file_names($slug, $name) {
		$templates = array();
		if (isset($name)) {
			$templates[] = $slug . '-' . $name . '.php';
		}
		$templates[] = $slug . '.php';

		/**
		 * Allow template choices to be filtered.
		 *
		 * The resulting array should be in the order of most specific first, to least specific last.
		 * e.g. 0 => recipe-instructions.php, 1 => recipe.php
		 *
		 * @param array  $templates Names of template files that should be looked for, for given slug and name.
		 * @param string $slug      Template slug.
		 * @param string $name      Template variation name.
		 */
		return apply_filters($this->filter_prefix . '_get_template_part', $templates, $slug, $name);
	}

	/**
	 * Retrieve the name of the highest priority template file that exists.
	 *
	 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
	 * inherit from a parent theme can just overload one file. If the template is
	 * not found in either of those, it looks in the theme-compat folder last.
	 *
	 * @param string|array $template_names Template file(s) to search for, in order.
	 * @param array        $data           Optional. Data to pass to template. Default empty array.
	 * @param bool         $load           If true the template file will be loaded if it is found.
	 * @param bool         $require_once   Whether to require_once or require. Default true.
	 *                                     Has no effect if $load is false.
	 *
	 * @return string The template filename if one is located.
	 */
	public function locate_template($template_names, $data = array(), $load = false, $require_once = true) {
		// Use $template_names as a cache key - either first element of array or the variable itself if it's a string
		$cache_key = is_array($template_names) ? $template_names[0] : $template_names;

		// If the key is in the cache array, we've already located this file.
		if (isset($this->template_path_cache[$cache_key])) {
			$located = $this->template_path_cache[$cache_key];
		} else {
			// No file found yet.
			$located = false;

			// Remove empty entries.
			$template_names = array_filter((array)$template_names);
			$template_paths = $this->get_template_paths();

			// Try to find a template file.
			foreach ($template_names as $template_name) {
				// Trim off any slashes from the template name.
				$template_name = ltrim($template_name, '/');

				// Try locating this template file by looping through the template paths.
				foreach ($template_paths as $template_path) {
					if (file_exists($template_path . $template_name)) {
						$located = $template_path . $template_name;
						// Store the template path in the cache
						$this->template_path_cache[$cache_key] = $located;
						break 2;
					}
				}
			}
		}

		if ($load && $located) {
			// Handle data based on its type
			if (is_array($data) && !empty($data)) {
				// First, create a $data object for backward compatibility with templates
				// that expect to access properties via $data->property
				$data_obj = new \stdClass();
				foreach ($data as $key => $value) {
					$data_obj->$key = $value;
				}
				$data = $data_obj;

				// Also extract variables for templates that expect individual variables
				extract((array)$data, EXTR_SKIP);
			} elseif (is_object($data)) {
				// If it's an object, make it available as $data
				// This ensures backward compatibility with templates that use $data->property
				// Also extract variables for templates that expect individual variables
				extract((array)$data, EXTR_SKIP);
			}

			// Make template loader available as $loader in templates
			$loader = $this;
			
			// Store current instance for static access if needed
			self::$template_loader_instance = $this;
			
			if ($require_once) {
				require_once $located;
			} else {
				require $located;
			}
		}

		return $located;
	}

	/**
	 * Return a list of paths to check for template locations.
	 *
	 * Default is to check in a child theme (if relevant) before a parent theme, so that themes which inherit from a
	 * parent theme can just overload one file. If the template is not found in either of those, it looks in the
	 * theme-compat folder last.
	 *
	 * @return array
	 */
	protected function get_template_paths() {
		$theme_directory = trailingslashit($this->theme_template_directory);

		$file_paths = array(
			10 => trailingslashit(get_template_directory()) . $theme_directory,
			100 => $this->get_templates_dir(),
			200 => trailingslashit(WP_CONTENT_DIR) . $theme_directory,
		);

		// Only add this conditionally, so non-child themes don't redundantly check active theme twice.
		if (is_child_theme()) {
			$file_paths[1] = trailingslashit(get_stylesheet_directory()) . $theme_directory;
		}

		/**
		 * Allow ordered list of template paths to be amended.
		 *
		 * @param array $var Default is directory in child theme at index 1, parent theme at 10, and plugin at 100.
		 */
		$file_paths = apply_filters($this->filter_prefix . '_template_paths', $file_paths);

		// Sort the file paths based on priority.
		ksort($file_paths, SORT_NUMERIC);

		return array_map('trailingslashit', $file_paths);
	}

	/**
	 * Return the path to the templates directory in this plugin.
	 *
	 * @return string
	 */
	protected function get_templates_dir() {
		return trailingslashit($this->plugin_directory) . $this->plugin_template_directory;
	}

	/**
	 * Get file paths for debugging
	 *
	 * @return array
	 */
	public function get_file_paths() {
		return $this->get_template_paths();
	}
	
	/**
	 * Get the current template loader instance.
	 * 
	 * This can be used from within templates to access the template loader
	 * without having to use $this, which might not be available.
	 *
	 * @return self|null The current template loader instance or null if not set.
	 */
	public static function get_instance() {
		return self::$template_loader_instance;
	}
}