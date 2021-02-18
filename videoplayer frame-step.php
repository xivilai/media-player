<?php

/** 
 * Plugin Name: videplayer frame-step
 * Version: 1.0.0
 * Description: This is a HTML5 video player with additional controls and features.
 * 
 * Additional controls: 
 *      playback speed 0.5, 0.25, 0.1
 *      stepping onto next/previous video frame
 * 
 * Features:
 *      load video from a URL (provided by user)
 *      load/open video from local hard drive
 * 
 * Author: SlavaLogos (logos.slava@gmail.com)
 */

add_action( 'wp_enqueue_scripts', function() {
	wp_enqueue_script(
		'cd2-media-playback-speed-js',
		plugins_url( 'playback-speed.js', __FILE__ ),
		[],
		'1.1.5',
		true
	);
}, 1, 100);

add_action( 'wp_footer', function(){
	$defaults = array(
		array('rate'=>1,'title'=>__('Playback Speed 1x','media-playback-speed'),'label'=>__('1x','media-playback-speed')),
		array('rate'=>0.5,'title'=>__('Playback Speed 0.5x','media-playback-speed'),'label'=>__('.5x','media-playback-speed')),
		array('rate'=>0.25,'title'=>__('Playback Speed 0.25x','media-playback-speed'),'label'=>__('.25x','media-playback-speed')),
		array('rate'=>0.1,'title'=>__('Playback Speed 0.1x','media-playback-speed'),'label'=>__('.1x','media-playback-speed')),
		// array('rate'=>2.0,'title'=>__('Playback Speed 2x','media-playback-speed'),'label'=>__('2x','media-playback-speed')),
	);
?>
  <style>
	.mejs-button.blank-button>button {
		background: transparent;
		color: #ccc;
		font-size: 1em;
		width: auto;
        display: none;
	}

    .fp-controls .playback-rate-button.active-playback-rate {
        display: initial;
    }

    .mejs-playback-buttons:hover .mejs-button button{
        display: initial;
    }

    .frame-step-button {
        background-color: transparent;
        padding: 5px;
    }

    .frame-step-button:hover {
        text-decoration: none;
        color: gray;
    }

	</style>
	<script type="text/template" id="playback-buttons-template">
		<?php if(apply_filters('media-playback-speed-generate-controls', true)): ?>
			<div class="mejs-playback-buttons">
				<?php foreach(apply_filters('media-playback-speed-data', $defaults) as $item): ?>
					<div class="mejs-button blank-button">
						<button type="button" class="playback-rate-button<?php echo (($item['rate'] == 1) ? ' mejs-active active-playback-rate' : ''); ?>" data-value="<?php echo esc_attr($item['rate']); ?>" title="<?php echo esc_attr($item['title']); ?>" aria-label="<?php echo esc_attr($item['title']); ?>" tabindex="0"><?php echo esc_html($item['label']); ?></button>
					</div>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</script>
    <script type="text/template" id="frame-step-buttons-template">
      <div class="frame-step-buttons">
            <button class="frame-step-button frame-step-previous">&lt</button>
            <button class="frame-step-button frame-step-next">&gt</button>
        </div>
    </script>
<?php
}, 1, 100 );

if (!defined('ABSPATH')) {
    exit;
}
if (!class_exists('EASY_VIDEO_PLAYER')) {

    class EASY_VIDEO_PLAYER {

        var $plugin_version = '1.1.8';
        var $flowplayer_version = '7.2.7';

        function __construct() {
            define('EASY_VIDEO_PLAYER_VERSION', $this->plugin_version);
            $this->plugin_includes();
        }

        function plugin_includes() {
            if (is_admin()) {
                add_filter('plugin_action_links', array($this, 'easy_video_player_plugin_action_links'), 10, 2);
            }
            add_action('plugins_loaded', array($this, 'plugins_loaded_handler'));
            add_action('wp_enqueue_scripts', 'easy_video_player_enqueue_scripts');
            add_action('admin_menu', array($this, 'easy_video_player_add_options_menu'));
            add_action('wp_head', 'easy_video_player_header');
            add_shortcode('evp_embed_video', 'evp_embed_video_handler');
            //allows shortcode execution in the widget, excerpt and content
            add_filter('widget_text', 'do_shortcode');
            add_filter('the_excerpt', 'do_shortcode', 11);
            add_filter('the_content', 'do_shortcode', 11);
        }

        function plugin_url() {
            if ($this->plugin_url)
                return $this->plugin_url;
            return $this->plugin_url = plugins_url(basename(plugin_dir_path(__FILE__)), basename(__FILE__));
        }

        function easy_video_player_plugin_action_links($links, $file) {
            if ($file == plugin_basename(dirname(__FILE__) . '/easy-video-player.php')) {
                $links[] = '<a href="options-general.php?page=easy-video-player-settings">'.__('Settings', 'easy-video-player').'</a>';
            }
            return $links;
        }
        
        function plugins_loaded_handler()
        {
            load_plugin_textdomain('easy-video-player', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/'); 
        }

        function easy_video_player_add_options_menu() {
            if (is_admin()) {
                add_options_page(__('Easy Video Player', 'easy-video-player'), __('Easy Video Player', 'easy-video-player'), 'manage_options', 'easy-video-player-settings', array($this, 'easy_video_player_options_page'));
            }
            add_action('admin_init', array(&$this, 'easy_video_player_add_settings'));
        }

        function easy_video_player_add_settings() {
            register_setting('easy-video-player-settings-group', 'evp_enable_jquery');
        }

        function easy_video_player_options_page() {
            $url = "https://noorsplugin.com/wordpress-video-plugin/";
            $link_text = sprintf(wp_kses(__('For detailed documentation please visit the plugin homepage <a target="_blank" href="%s">here</a>.', 'easy-video-player'), array('a' => array('href' => array(), 'target' => array()))), esc_url($url));          
            ?>
            <div class="wrap">
                <h1>Easy Video Player - v<?php echo $this->plugin_version; ?></h1>
                <div class="update-nag"><?php echo $link_text;?></div>
                    <h2 class="title"><?php _e('General Settings', 'easy-video-player')?></h2>		
                        <form method="post" action="options.php">
                            <?php settings_fields('easy-video-player-settings-group'); ?>
                            <table class="form-table">
                                <tr valign="top">
                                    <th scope="row"><?php _e('Enable jQuery', 'easy-video-player')?></th>
                                    <td><input type="checkbox" id="evp_enable_jquery" name="evp_enable_jquery" value="1" <?php echo checked(1, get_option('evp_enable_jquery'), false) ?> /> 
                                        <p><i><?php _e('By default this option should always be checked.', 'easy-video-player')?></i></p>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
                            </p>		
                        </form>
            </div>
            <?php
        }

    }

    $GLOBALS['easy_video_player'] = new EASY_VIDEO_PLAYER();
}

function easy_video_player_enqueue_scripts() {
    if (!is_admin()) {
        $plugin_url = plugins_url('', __FILE__);
        $enable_jquery = get_option('evp_enable_jquery');
        if ($enable_jquery) {
            wp_enqueue_script('jquery');
        }
        wp_register_script('flowplayer-js', $plugin_url . '/lib/flowplayer.js');
        wp_enqueue_script('flowplayer-js');
        wp_register_style('flowplayer-css', $plugin_url . '/lib/skin/skin.css');
        wp_enqueue_style('flowplayer-css');
    }
}

function easy_video_player_header() {
    if (!is_admin()) {
        $fp_config = '<!-- This content is generated with the Easy Video Player plugin v' . EASY_VIDEO_PLAYER_VERSION . ' - http://noorsplugin.com/wordpress-video-plugin/ -->';
        $fp_config .= '<script>';
        $fp_config .= 'flowplayer.conf.embed = false;';
        $fp_config .= 'flowplayer.conf.keyboard = false;';
        $fp_config .= '</script>';
        $fp_config .= '<!-- Easy Video Player plugin -->';
        echo $fp_config;
    }
}

function evp_embed_video_handler($atts) {
    extract(shortcode_atts(array(
        'url' => '',
        'width' => '',
        'height' => '',
        'ratio' => '0.417',
        'autoplay' => 'false',
        'poster' => '',
        'loop' => '',
        'muted' => '',
        'preload' => 'metadata',
        'share' => 'true',
        'video_id' => '',
        'class' => '',
        'template' => '',
    ), $atts));
    //check if mediaelement template is specified
    if($template=='mediaelement'){
        $attr = array();
        $attr['src'] = $url;
        if(is_numeric($width)){
            $attr['width'] = $width;
        }
        if(is_numeric($height)){
            $attr['height'] = $height;
        }
        if ($autoplay == "true"){
            $attr['autoplay'] = 'on';
        }
        if ($loop == "true"){
            $attr['loop'] = 'on';
        }
        if (!empty($poster)){
            $attr['poster'] = $poster;
        }
        if (!empty($preload)){
            $attr['preload'] = $preload;
        }
        return wp_video_shortcode($attr);
    }
    //custom video id
    if(!empty($video_id)){
        $video_id = ' id="'.$video_id.'"';
    }
    //autoplay
    if ($autoplay == "true") {
        $autoplay = " autoplay";
    } else {
        $autoplay = "";
    }
    //loop
    if ($loop == "true") {
        $loop= " loop";
    }
    else{
        $loop= "";
    }
    //muted
    if($muted == "true"){
        $muted = " muted";
    }
    else{
        $muted = "";
    }
    
    $player = "fp" . uniqid();
    $color = '';
    if (!empty($poster)) {
        $color = 'background: #000 url('.$poster.') 0 0 no-repeat;background-size: 100%;';
    } else {
        $color = 'background-color: #000;';
    }
    $size_attr = "";
    if (!empty($width)) {
        $size_attr = "max-width: {$width}px;max-height: auto;";
    }
    /*
    $class_array = array('flowplayer', 'minimalist');
    if(!empty($class)){
        $shortcode_class_array = array_map('trim', explode(' ', $class));
        $shortcode_class_array = array_filter( $shortcode_class_array, 'strlen' ); //remove NULL, FALSE and Empty Strings (""), but leave values of 0 (zero)
        $shortcode_class_array = array_values($shortcode_class_array);
        if(in_array("functional", $shortcode_class_array) || in_array("playful", $shortcode_class_array)){
            $class_array = array_diff($class_array, array('minimalist'));
        }
        $class_array = array_merge($class_array, $shortcode_class_array);
        $class_array = array_unique($class_array);
        $class_array = array_values($class_array);
    }

    $classes = implode(" ", $class_array);
    */
    
    if(!empty($class)){
        $class = " ".$class;
    }
    
    $styles = <<<EOT
    <style>
        #$player {
            $size_attr
            $color    
        }
    </style>
EOT;
    
    $output = <<<EOT
    <div class="mejs-container">
        <div id="$player" data-ratio="$ratio" data-share="$share" class="flowplayer{$class}">
            <video{$video_id}{$autoplay}{$loop}{$muted}>
               <source type="video/mp4" src="$url"/>
            </video>
        </div>
    </div>
    <div class="load-from-url">
        <input type="text" placeholder="Video URL">
        <button class="load-from-url-btn">Загрузить</button>
    </div>
    <div class="load-from-disk">
        <input type="file">
    </div>
    $styles
EOT;
    return $output;
}

 ?>