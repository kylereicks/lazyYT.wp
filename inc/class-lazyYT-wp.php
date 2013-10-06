<?php
if(!class_exists('LazyYT_WP')){
  class LazyYT_WP{

    private $youtube_params = array(
      'autohide',
      'autoplay',
      'cc_load_policy',
      'color',
      'controls',
      'disablekb',
      'enablejsapi',
      'end',
      'fs',
      'iv_load_policy',
      'list',
      'listType',
      'loop',
      'modestbranding',
      'origin',
      'playerapiid',
      'playlist',
      'rel',
      'showinfo',
      'start',
      'theme'
    );

    public static function get_instance(){
      static $instance;

      if(null === $instance){
        $instance = new self();
      }

      return $instance;
    }

    private function __clone(){
      return null;
    }

    private function __wakeup(){
      return null;
    }

    public static function deactivate(){
      self::clear_options();
    }

    private static function clear_options(){
      global $wpdb;
      $options = $wpdb->get_col('SELECT option_name FROM ' . $wpdb->options . ' WHERE option_name LIKE \'%plugin_name%\'');
      foreach($options as $option){
        delete_option($option);
      }
    }

    private static function t_to_seconds($t){
      $seconds = 0;
      $values = array_filter(preg_split('/\D/', $t));
      $unit = array_filter(preg_split('/\d+/', $t));
      foreach($values as $i => $value){
        switch($unit[$i + 1]){
        case 'h':
          $seconds = $seconds + ($value * 60 * 60);
          break;
        case 'm':
          $seconds = $seconds + ($value * 60);
          break;
        case 's':
          $seconds = $seconds + $value;
          break;
        }
      }
      return $seconds;
    }

    private static function attributes_string($video_atts){
      unset($video_atts['v']);
      $output = '';
      if(!empty($video_atts)){
        foreach($video_atts as $attr => $val){
          $output .= ' data-youtube-' . $attr . '="' . $val . '"';
        }
      }
      return $output;
    }

    public static function allow_empty_tags($html){
      return preg_replace('/(<(iframe)[^<]*?)(?:\/>|\s\/>)/', '$1></$2>', $html);
    }

    public static function fix_empty_attributes($html){
      return str_replace('=""', '', $html);
    }

    // Constructor, add actions and filters
    private function __construct(){
      add_action('init', array($this, 'add_update_hook'));
      add_action('init', array($this, 'handle_oembed'));
      add_action('wp_enqueue_scripts', array($this, 'register_scripts'));
      add_filter('the_content', array($this, 'replace_iframes'));
    }

    public function handle_oembed(){
      wp_oembed_remove_provider( '#https?://(www\.)?youtube\.com/watch.*#i' );
      wp_oembed_remove_provider( 'http://youtu.be/*' );

      wp_embed_register_handler('lazyYT', '#https?://(?:www\.)?youtube\.com/watch.*#i', array($this, 'embed_handler_youtube'));
      wp_embed_register_handler('lazyYTshort', '#http://youtu.be/.*#i', array($this, 'embed_handler_youtube'));
    }

    public function register_scripts(){
      wp_register_script('lazyYT', LAZYYT_WP_URL . 'js/lazyYT.min.js', array(), LAZYYT_JS_VERSION, true);
    }

    public function replace_iframes($html){
      $DOMDocument = new DOMDocument();
      $DOMDocument->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />' . $html);
      $iframes = $DOMDocument->getElementsByTagName('iframe');

      if($iframes->length > 0){
        foreach($iframes as $iframe){
          $src = $iframe->getAttribute('src');
          if(false !== strstr($src, 'youtube.com') || false !== strstr($src, 'youtu.be')){
            $attr = array(
              'width' => $iframe->getAttribute('width'),
              'height' => $iframe->getAttribute('height')
            );
            $original_iframe = self::fix_empty_attributes(self::allow_empty_tags($DOMDocument->saveXML($iframe)));
            $embed = $this->embed_handler_youtube(null, $attr, $src, null);
            $html = str_replace($original_iframe, $embed, $html);
          }
        }
      }
      return $html;
    }

    public function embed_handler_youtube($matches, $attr, $url, $rawattr){
      wp_enqueue_script('lazyYT');
      $url_components = parse_url($url);
      $video_atts = array();
      if(!empty($url_components['query'])){
        parse_str($url_components['query'], $url_attributes);
        foreach($url_attributes as $attribute => $value){
          if('v' === $attribute || 't' !== $attribute && in_array($attribute, $this->youtube_params)){
            $video_atts[$attribute] = $value;
          }elseif('t' === $attribute){
            $video_atts['start'] = self::t_to_seconds($value);
          }
        }
      }
      if('youtu.be' === $url_components['host']){
        $video_atts['v'] = substr($url_components['path'], 1);
      }
      if(null === $matches){
        $video_atts['v'] = substr($url_components['path'], 7);
      }

      $embed = '<div class="lazyYT" data-youtube-id="' . $video_atts['v'] . '" data-youtube-width="' . $attr['width'] . '" data-youtube-height="' . $attr['width'] * .75 . '"' . self::attributes_string($video_atts) . '>loading&hellip;</div>';

      return $embed;
    }

    public function add_update_hook(){
      if(get_option('lazyYT_wp_version') !== LAZYYT_WP_VERSION){
        update_option('lazyYT_wp_update_timestamp', time());
        update_option('lazyYT_wp_version', LAZYYT_WP_VERSION);
        do_action('lazyYT_wp_updated');
      }
    }
  }
}
