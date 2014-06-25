<?php
/**
 * Bpfb shortcode coder/decoder class.
 *
 * Responsible for handling all things shortcode:
 * 1) Resgisters shortcode decoding procedures
 * 2) Decodes shortcodes and creates proper markup on post render
 * 3) Encodes requests into shortcodes on post save
 */
class BpfbCodec {

	/**
	 * Processes link-type shortcode and create proper markup.
	 * Relies on ./forms/link_tag_template.php for markup rendering.
	 */
	function process_link_tag ($atts, $body) {
		extract(shortcode_atts(array(
			'url' => false,
			'title' => false,
			'image' => false,
		), $atts));
		if (!$url) return '';
		ob_start();
		@include(BPFB_PLUGIN_BASE_DIR . '/lib/forms/link_tag_template.php');
		$out = ob_get_clean();
		return $out;
	}

	/**
	 * Creates the proper shortcode tag based on the submitted data.
	 */
	function create_link_tag ($url, $title, $body='', $image='') {
		if (!$url) return '';
		$body = $body ? $body : $title;
		return "[bpfb_link url='${url}' title='{$title}' image='{$image}']{$body}[/bpfb_link]";
	}

	/**
	 * Processes video-type shortcode and create proper markup.
	 * Relies on `wp_oembed_get()` for markup rendering.
	 */
	function process_video_tag ($atts, $content) {
		return wp_oembed_get($content, array('width' => BPFB_OEMBED_WIDTH));
	}

	/**
	 * Creates the proper shortcode tag based on the submitted data.
	 */
	function create_video_tag ($url) {
		if (!$url) return '';

        $url = $this->handleMobileLinks($url);

		return "[bpfb_video]{$url}[/bpfb_video]";
	}

	/**
	 * Processes images-type shortcode and create proper markup.
	 * Relies on ./forms/images_tag_template.php for markup rendering.
	 */
	function process_images_tag ($atts, $content) {
		$images = explode("\n", trim(strip_tags($content)));
		//return var_export($images,1);
		$activity_id = bp_get_activity_id();
		global $blog_id;
		$activity_blog_id = $blog_id;
		$use_thickbox = defined('BPFB_USE_THICKBOX') ? esc_attr(BPFB_USE_THICKBOX) : 'thickbox';
		if ($activity_id) {
			$activity_blog_id = bp_activity_get_meta($activity_id, 'bpfb_blog_id');
		}
		ob_start();
		@include(BPFB_PLUGIN_BASE_DIR . '/lib/forms/images_tag_template.php');
		$out = ob_get_clean();
		return $out;
	}

	/**
	 * Creates the proper shortcode tag based on the submitted data.
	 */
	function create_images_tag ($imgs) {
		if (!$imgs) return '';
		if (!is_array($imgs)) $imgs = array($imgs);
		return "[bpfb_images]\n" . join("\n", $imgs) . "\n[/bpfb_images]";
	}

	/**
	 * Registers shotcode processing procedures.
	 */
	function register () {
		$me = new BpfbCodec;
		add_shortcode('bpfb_link', array($me, 'process_link_tag'));
		add_shortcode('bpfb_video', array($me, 'process_video_tag'));
		add_shortcode('bpfb_images', array($me, 'process_images_tag'));

		// A fix for Ray's "oEmbed for BuddyPress" and similar plugins
		add_filter('bp_get_activity_content_body', 'do_shortcode', 1);
	}

    /**
     * Hanndle mobile links from youtube and vimeo
     *
     * @param $url
     * @return mixed
     */

    protected function handleMobileLinks($url)
    {
        //Check if m.vimeo
        $url = preg_match('/m\.vimeo/', $url) ? preg_replace('/m\.vimeo/', 'vimeo', $url) : $url;
        // Check if m.youtube.com
        $url = preg_match('/m\.youtube/', $url) ? preg_replace('/m\.youtube/', 'youtube', $url) : $url;
        // Check if vimeo.com/m/
        $url = preg_match('/vimeo.com\/m/', $url) ? preg_replace('/vimeo.com\/m/', 'vimeo.com', $url) : $url;
        //Check if youtu.be
        $url = preg_match('/youtu\.be/', $url) ? preg_replace('/youtu\.be\//', 'youtube.com/watch?v=', $url) : $url;
        //Check if https
        $url = preg_match('/https/', $url) ? preg_replace('/https/', 'http', $url) : $url;

        return $url;
    }
}