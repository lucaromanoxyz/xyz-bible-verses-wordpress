<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://lucaromano.xyz
 * @since      1.0.0
 *
 * @package    Xyz_Bible_Verses
 * @subpackage Xyz_Bible_Verses/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Xyz_Bible_Verses
 * @subpackage Xyz_Bible_Verses/public
 * @author     Luca Romano <romano.luca@hotmail.it>
 */
class Xyz_Bible_Verses_Public
{
	private string $url = "https://lucaromano.xyz/api/v1";
	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version The version of this plugin.
	 * @since    1.0.0
	 */
	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;

		// add shortcode
		add_shortcode('xyz_bible_verses', array($this, 'create_shortcode'));
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xyz_Bible_Verses_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xyz_Bible_Verses_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/xyz-bible-verses-public.css', array(), $this->version, 'all');

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts()
	{

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Xyz_Bible_Verses_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Xyz_Bible_Verses_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/xyz-bible-verses-public.js', array('jquery'), $this->version, false);

	}

	/**
	 * Retrieve bible verses from API
	 * @param string $version
	 * @param string $reference
	 * @param bool $notes
	 * @return array|null
	 */
	private function get_verses_by_reference(string $version, string $reference, bool $notes): ?array
	{
		// query parameters
		$parameters = [
			'version' => $version,
			'reference' => $reference,
		];
		if ($notes) {
			$parameters['notes'] = 'true';
		}
		$response = wp_safe_remote_get(
			$this->url . '/references?' . http_build_query($parameters),
			array(
				'timeout' => 30,
			)
		);

		// Se cè un errore termina tutto e manda un risultato negativo
		if (is_wp_error($response)) {
			return null;
		}

		// Decodifico i dati della chiamata
		$apiResponse = wp_remote_retrieve_body($response);

		// Ritorno la risposta del servizio
		return json_decode($apiResponse, true);
	}

	/**
	 * Retrieve bible verses from API
	 * @param string $version
	 * @param string $sentence
	 * @param bool $notes
	 * @return array|null
	 */
	private function get_verses_by_sentence(string $version, string $sentence, bool $notes): ?array
	{
		// query parameters
		$parameters = [
			'version' => $version,
			'sentence' => $sentence,
		];
		if ($notes) {
			$parameters['notes'] = 'true';
		}
		$response = wp_safe_remote_get(
			$this->url . '/sentences?' . http_build_query($parameters),
			array(
				'timeout' => 30,
			)
		);

		// Se cè un errore termina tutto e manda un risultato negativo
		if (is_wp_error($response)) {
			return null;
		}

		// Decodifico i dati della chiamata
		$apiResponse = wp_remote_retrieve_body($response);

		// Ritorno la risposta del servizio
		return json_decode($apiResponse, true);
	}

	/**
	 * Wrap the words in text according the search pattern.
	 * @param string $text
	 * @param string $pattern - string:indexes of item to wrap. Example: hello:1,2,4
	 * @param string $wrapper
	 * @return string
	 */
	private function wrap_substring(string $text, string $pattern, string $wrapper): string
	{
		list($search, $replacements) = explode(":", $pattern);


		if ($replacements) {
			$occourrences = explode(",", $replacements);
		}
		// search range
		$parsed_occourrences = [];
		foreach ($occourrences as $k => $occourrence) {
			if (strpos($occourrence, "...") > 0) {
				// remove occurrence
				unset($occourrences[$k]);
				// add range
				list($min, $max) = explode("...", $occourrence);
				$parsed_occourrences = [...$parsed_occourrences, ...range($min, $max)];
			} else {
				$parsed_occourrences[] = $occourrence;
			}
		}
		// highlight words
		$found = 0;
		return preg_replace_callback('/' . trim($search) . '/', function ($matches) use (&$found, $parsed_occourrences, $wrapper) {
			$found++;
			if (count($parsed_occourrences) == 0 || (count($parsed_occourrences) > 0 && in_array($found, $parsed_occourrences))) {
				return "<{$wrapper}>{$matches[0]}</{$wrapper}>";
			} else {
				return $matches[0];
			}

		}, $text, -1);
	}

	/**
	 * @param array $atts
	 * @return string
	 */
	public function create_shortcode(array $atts)
	{
		$a = shortcode_atts(array(
			'version' => '',
			'reference' => '',
			'sentence' => '',
			'notes' => false,
			'underline' => '',
			'bold' => '',
		), $atts);

		if (!trim($a['version']) || (!trim($a['reference']) && !trim($a['sentence']))) {
			return __('Missing version or reference', XYZ_BIBLE_VERSES_DOMAIN);
		} else {
			if (trim($a['reference'])) {
				// get verses
				$passages = $this->get_verses_by_reference($a['version'], $a['reference'], $a['notes']);
			} else if (trim($a['sentence'])) {
				// get verses
				$passages = $this->get_verses_by_sentence($a['version'], $a['sentence'], $a['notes']);
			}
			$html = "";
			foreach ($passages as $passage) {
				$reference = $passage["reference"];
				$text = $passage["text"];

				// check for underline
				$underline = explode("|", $a['underline']);
				foreach ($underline as $pattern) {
					if (trim($pattern)) {
						$text = $this->wrap_substring($text, $pattern, "u");
					}
				}

				// check for bold
				$bold = explode("|", $a['bold']);
				foreach ($bold as $pattern) {
					if (trim($pattern)) {
						$text = $this->wrap_substring($text, $pattern, "strong");
					}
				}

				$html .= '<' . get_option('xyz_bible_verses_verse_container_tag') . ' class="' . get_option('xyz_bible_verses_verse_container_class') . '">
							<' . get_option('xyz_bible_verses_verse_reference_tag') . ' class="' . get_option('xyz_bible_verses_verse_reference_class') . '">' . $reference . '</' . get_option('xyz_bible_verses_verse_reference_tag') . '>
							<' . get_option('xyz_bible_verses_verse_text_tag') . ' class="' . get_option('xyz_bible_verses_verse_text_class') . '">' . $text . '</' . get_option('xyz_bible_verses_verse_text_tag') . '>
						</' . get_option('xyz_bible_verses_verse_container_tag') . '>';
			}

			return $html;
		}
	}

}
