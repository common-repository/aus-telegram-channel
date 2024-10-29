<?php 
/***************************************************************************
Plugin Name:  AUS Telegram Bot Notifier
Plugin URI:   http://wp.ulugov.uz
Description:  Sends Wordpress Posts to Telegram channel via Telegram Bot
Version:      1.0.7
Author:       Anvar Ulugov
Author URI:   http://anvar.ulugov.uz
License:      GPLv2 or later
**************************************************************************/

defined( 'ABSPATH' ) or die( "No script kiddies please!" );
/*
 * Define plugin absolute path and url
 */
define( 'AUSTB_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );
define( 'AUSTB_URL', rtrim( plugin_dir_url( __FILE__ ), '/' ) );
include( AUSTB_DIR . '/class-options.php' );

class AUS_Telegram_Bot {

	private $options;
	private $last_send;

	function __construct() {

		$options_configs = array(
			'options' => 'aus-telegram-bot_plugin_options',
			'plugin_name' => 'AUS Telegram Bot',
			'plugin_slug' => 'aus-telegram-bot',
		);
		$AUS_tb_options = new AUS_tb_options( $options_configs );
		// Initialize
		$this->init();
		// Add action to schedule telegram_send function
		add_action( 'aus_telegram_bot_schedule', array( $this, 'telegram_send') );
		$this->telegram_send_scheduler();

		// Check if changed the recurrence of scheduled event. If yes unschedule the current and schedule new one.
		$recurrence = wp_get_schedule( 'aus_telegram_bot_schedule' );

		if ( $recurrence != $this->options['recurrence'] ) {
			wp_clear_scheduled_hook( 'aus_telegram_bot_schedule' );
			wp_schedule_event( time(), $this->options['recurrence'], 'aus_telegram_bot_schedule' );
		}

		// Adding ajax action for seding instant messages
		add_action( 'wp_ajax_aus_telegram_im', array( $this, 'telegram_send_im' ) );
		add_action( 'wp_ajax_nopriv_aus_telegram_im', array( $this, 'telegram_send_im' ) );

	}

	/*
	 * Class init function
	 */
	public function init() {
		$this->options 	= get_option( 'aus-telegram-bot_plugin_options' );
		$this->last_send = get_option( 'aus_telegram_bot_last_send' );
	}

	public function telegram_send_im() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'aus_telegram_im' ) ) {
			die('nonce failed');
		} else {
			if ( isset( $_POST['content'] ) && ! empty( $_POST['content'] ) ) {
				$message = strip_tags( $_POST['content'], '<b><strong><a><i><em><code><pre>' );
				$message = str_replace('\"', '"', $message);
				$data = array(
					'chat_id' => $this->options['channelname'],
					'parse_mode' => 'HTML',
					'disable_web_page_preview' => 'false',
					'text' => $message,
				);

				$ch = curl_init();
				curl_setopt( $ch, CURLOPT_URL, "https://api.telegram.org/bot" . $this->options['bot_token'] . "/sendMessage" );

				curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );

				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				$output = curl_exec( $ch );
				curl_close( $ch );
				print_r($output);
			}
		}
		die();
	}

	public function telegram_send( $new_status = null, $old_status = null, $post = null ) {

		if ( $old_status == 'publish' && $new_status == 'publish' ) return;

		$post = $this->get_post( $post->ID );
		if ( $post ) {

			$data = array(
				'chat_id' => $this->options['channelname'],
				'parse_mode' => 'HTML',
				'disable_web_page_preview' => 'false',
				'text' => $this->options['before_text'] . 
							"\n" . 
							"<a href='{$post['url']}'>{$post['title']}</a>\n" . 
							( $this->options['cat_as_hashtag'] ? '#' : '<b>' ) . 
							"{$post['category']}" . 
							( $this->options['cat_as_hashtag'] ? '' : '</b>' ) . 
							" | <i>{$post['date']}</i>\n{$post['text']}\n" . 
							$this->options['after_text'],
			);

			$ch = curl_init();
			curl_setopt( $ch, CURLOPT_URL, "https://api.telegram.org/bot" . $this->options['bot_token'] . "/sendMessage" );

			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query( $data ) );

			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
			$output = curl_exec( $ch );
			curl_close( $ch );

			update_post_meta( $post['id'], 'aus_telegram_sent', 1 );
			update_option( 'aus_telegram_bot_last_send', date( 'Y-m-d H:i:s' ) );
		}
		
	}

	private function get_post( $post_id = null ) {
		if ( isset( $this->options['text_limit'] ) && ! empty( $this->options['text_limit'] ) ) {
			$limit = $this->options['text_limit'];
		} else {
			$limit = 100;
		}
		$start_date = date( 'F jS, Y', strtotime( $this->options['start_date'] ) );
		$getPost = new WP_Query( array(
			'p' => $post_id,
			'cat' => implode( ',', $this->options['categories'] ),
			'date_query' => array(
				array(
					'after'	=> $start_date,
				),
			),
			'orderby' => 'date',
			'post_status' => 'publish',
			'order' => 'ASC',
			'meta_query' => array(
			   'relation' => 'OR',
				array(
					'key' => 'aus_telegram_sent',
					'compare' => 'NOT EXISTS', // works!
					'value' => '' // This is ignored, but is necessary...
				)
			),
			'posts_per_page' => 1
		) );
		if ( isset( $getPost->posts[0] ) ) {
			$getPost = $getPost->posts[0];
			$text = $getPost->post_content;
			$text = strip_shortcodes($text);
			$text = preg_replace('/[\r\n]+/', " ", $text);
			$text = preg_replace('/\s+/', ' ', $text);
			$text = trim( $text );
			$text = strip_tags($text);
			$text = $this->limit( $text, $limit );
			$cat_ids = wp_get_post_categories( $getPost->ID );
			if ( ! empty( $cat_ids ) ) {
				$category = get_category( $cat_ids[0] )->name;
			} else {
				$category = '';
			}
			$post = array(
				'id' => $getPost->ID,
				'title' => $getPost->post_title,
				'url' => $getPost->guid,
				'date' => date( 'd.m.Y', strtotime( $getPost->post_date ) ),
				'category' => $category,
				'text' => $text,
			);
			wp_reset_query();
			return $post;
		} else {
			return false;
		}
		
	}

	private function limit( $text, $limit = 100 ) {

		$cyr_chars = 'АаБбВвГгДдЕеЁёЖжЗзИиЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЪъЫыЬьЭэЮюЯяЎўҚқҒғҲҳ';

		if ( str_word_count( $text, 0, $cyr_chars ) >= $limit ) {
			$words = str_word_count( $text, 2, $cyr_chars );
			$pos = array_keys( $words );
			$text = substr( $text, 0, $pos[ $limit ] );
		}

		return $text;

	}

	public function telegram_send_scheduler() {

		if ( $this->options['recurrence'] == 'new_post' ) {

			add_action( 'transition_post_status', array( $this, 'telegram_send' ), 10, 3 );

		} else {

			if ( ! wp_next_scheduled( 'aus_telegram_bot_schedule' ) ) {
				if ( ! isset( $this->options['recurrence'] ) or $this->options['recurrence'] == '' || $this->options['recurrence'] != 'new_post' ) {
					$this->options['recurrence'] = 'hourly';
				}
				wp_schedule_event( time(), $this->options['recurrence'], 'aus_telegram_bot_schedule' );
			}

		}

	}
}
$AUS_Telegram_Bot = new AUS_Telegram_Bot();