<?php
/*
Plugin Name: WP Easy Slack
Plugin URI: http://smartspc.net/
Description: Easily sends notifications about new wordpress posts to Slack channels
Version: 0.1
Author: Dmytro Perepelytsia
Author URI: http://smartspc.net
License: GPL
*/

class WP_Easy_Slack {

	private $webhook_url;
	private $bot;
	private $post_status;
	private $post_id;
	
	public function __construct()
    {

		register_activation_hook(__FILE__,	array($this, 'activate'));
		register_deactivation_hook(__FILE__,array($this, 'deactivate'));

		add_action( 'save_post', 	array($this, 'notification'), 10, 2);
		add_action( 'admin_menu',   array($this, 'admin_menu'));

		$this->webhook_url	= get_option('es_webhook_url');
		$this->post_status  = get_option('es_post_status');
		$this->bot 			= $_SERVER['SERVER_NAME'];

    }
	public static function activate(){}
	public static function deactivate(){}

	public function notification( $post_id , $post )
	{
		$this->post_id = $post_id; 
		if($post->post_status == $this->post_status){
			$send = ['payload'=>json_encode([
											'text'			=> $this->message(),
											'username'		=> $this->bot,
											'icon_emoji'	=> ':bulb:',
											])];
			$data = wp_remote_request($this->webhook_url, array('method'=>'POST', 'body'=>$send));
		}
	}

    public function admin_menu()
    {
        add_options_page(
            'WP Easy Slack', 
            'WP Easy Slack', 
            'manage_options', 
            'wp-easy-slack-settings', 
            array($this, 'plugin_settings_page')
        );
    }
        
	public function message()
	{
		$string = get_option('es_message');
		$replacements = [
				'%title%'		=> get_the_title($this->post_id),
				'%post_status%' => get_post_status($this->post_id),
				'%post_url%' 	=> get_the_permalink($this->post_id),
				'%edit_url%' 	=> 'http://'.$_SERVER['SERVER_NAME'].'/wp-admin/post.php?post='.$this->post_id.'&action=edit',
				'%site_url%'	=> 'http://'.$_SERVER['SERVER_NAME'].'/',
				'%host%'		=> $_SERVER['SERVER_NAME']
				];

		foreach ( $replacements as $var => $repl ) {
			$string = str_replace( $var, $repl, $string );
		}
		return $string;
	}

    public function plugin_settings_page()
    {
        if(!current_user_can('manage_options')) wp_die(__('You do not have sufficient permissions to access this page.'));
        
        if(isset($_POST)){
        	foreach ($_POST as $key => $value) {
                update_option($key, $value);
            }
        }

        $output	 = 	'<div class="wrap"><h2>WP Easy Slack Settings</h2><form name="df_form" method="post" action=""><input type="hidden" name="oscimp_hidden" value="Y">';
        $output	.=	'<div><label for="es_webhook_url">Webhook URL: <input id="es_webhook_url" type="text" name="es_webhook_url" value="' . get_option('es_webhook_url') . '" size="80"></label></div><hr/>';
        $output	.=	'<div><label for="es_message">Send message: <input id="es_message" type="text" name="es_message" value="' . get_option('es_message') . '" size="60"></label>';
        
        $output	.=	'<label for="es_post_status"> when post gets a status: <select id="es_post_status" name="es_post_status"><option value="pending" ' . (get_option('es_post_status') == 'pending' ? 'selected' : false) . '>pending</option><option value="publish" ' . (get_option('es_post_status') == 'publish' ? 'selected' : false) . '>publish</option></select></label></div>';
        $output	.=	'<h3>Message variables</h3>';
        $output .=	'<div><b>%title%</b> - Post title</div>';
        $output .=	'<div><b>%post_status%</b> - Post status</div>';
        $output .=	'<div><b>%post_url%</b> - Post URL</div>';
        $output .=	'<div><b>%edit_url%</b> - Edit post URL</div>';
        $output .=	'<div><b>%site_url%</b> - Site URL (example: http://'.$_SERVER['SERVER_NAME'].'/)</div>';
        $output .=	'<div><b>%host%</b> - Site domain (example: '.$_SERVER['SERVER_NAME'].')</div>';
        $output .=	'----';
        $output .=	'<div><b>:pencil:</b> - Emoji name between ::</div>';
        $output .=	'<div><b>'.htmlentities('<URL|Anchor>').'</b> - Clickable link</div>';
        $output .=	'<p class="submit"><input type="submit" name="Submit" value="Save Changes" /></p>';
        $output .=	'</form></div>';
        echo $output;
    }
}

$easySlack = new WP_Easy_Slack();