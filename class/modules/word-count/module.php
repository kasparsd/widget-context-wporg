<?php

// Check for Widget Context plugin
if ( ! class_exists( 'widget_context' ) )
	die;


// Go!
WidgetContextWordCount::instance();


class WidgetContextWordCount {

	private static $instance;
	private $wc;

	var $words_on_page = 0;


	static function instance() {

		if ( ! self::$instance )
			self::$instance = new self();

		return self::$instance;

	}


	private function __construct() {

		$this->wc = widget_context::instance();

		// Check the number of words on page
		add_action( 'wp', array( $this, 'count_words_on_page' ) );

		// Define our context
		add_filter( 'widget_contexts', array( $this, 'add_word_count_context' ) );

		add_filter( 'widget_context_control-word_count', array( $this, 'control_word_count' ), 10, 2 );
		add_filter( 'widget_context_check-word_count', array( $this, 'context_check_word_count' ), 10, 2 );

	}


	function add_word_count_context( $contexts ) {

		$contexts[ 'word_count' ] = array(
				'label' => __( 'Word Count', 'widget-context' ),
				'description' => __( 'Context based on word count on the page.', 'widget-context' ),
				'weight' => 15
			);

		return $contexts;

	}


	function count_words_on_page() {

		global $wp_query;

		if ( empty( $wp_query->posts ) || is_admin() )
			return;

		foreach ( $wp_query->posts as $post_data )
			$this->words_on_page += str_word_count( strip_tags( strip_shortcodes( $post_data->post_content ) ) );

	}


	function context_check_word_count( $check, $settings ) {

		$settings = wp_parse_args( $settings, array(
				'check_wordcount' => false,
				'word_count' => null,
				'check_wordcount_type' => null
			) );

		// Make sure this context check was enabled
		if ( ! $settings['check_wordcount'] )
			return $check;

		$word_count = (int) $settings['word_count'];

		// No word count specified, bail out
		if ( ! $word_count )
			return $check;

		if ( $settings['check_wordcount_type'] == 'less' && $this->words_on_page < $word_count )
			return true;
		elseif ( $settings['check_wordcount_type'] == 'more' && $this->words_on_page > $word_count )
			return true;

		return $check;

	}


	function control_word_count( $control_args ) {

		return sprintf(
				'<p>%s %s %s</p>',
				$this->wc->make_simple_checkbox( $control_args, 'check_wordcount', __('Has', 'widget-context') ),
				$this->wc->make_simple_dropdown( $control_args, 'check_wordcount_type', array( 'less' => __('less', 'widget-context'), 'more' => __('more', 'widget-context') ), null, __('than', 'widget-context') ),
				$this->wc->make_simple_textfield( $control_args, 'word_count', null, __('words', 'widget-context') )
			);

	}


}
