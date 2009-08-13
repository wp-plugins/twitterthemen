<?php
/*
Plugin Name: Twitterthemen for Wordpress
Version: 0.1
Plugin URI: http://twitterthemen.de
Description: Zeigt die Top-Themen der deutschsprachigen Twitterszene
Author: Thomas Pfeiffer
Author URI: http://webevangelisten.de
*/
/* 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('MAGPIE_CACHE_ON', 1); //2.7 Cache Bug
define('MAGPIE_CACHE_AGE', 180);
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

$twitterthemen_options['widget_fields']['title'] = array('label'=>'Titel:', 'type'=>'text', 'default'=>'');
$twitterthemen_options['widget_fields']['subtitle'] = array('label'=>'Untertitel', 'type'=>'text', 'default'=>'Aktuelle Themen (#Hashtags) aus der deutschsprachigen Twittersph&auml;re');
$twitterthemen_options['widget_fields']['num'] = array('label'=>'Anzahl der Hashtags:', 'type'=>'text', 'default'=>'5');
$twitterthemen_options['widget_fields']['encode_utf8'] = array('label'=>'UTF8 Encode:', 'type'=>'checkbox', 'default'=>false);
$twitterthemen_options['widget_fields']['target_blank'] = array('label'=>'Target=_blank:', 'type'=>'checkbox', 'default'=>false);
$twitterthemen_options['widget_fields']['mitdesc'] = array('label'=>'Zeige Beschreibung', 'type'=>'checkbox', 'default'=>false);
$twitterthemen_options['widget_fields']['leere'] = array('label'=>'Zeige auch leere Beschreibungen', 'type'=>'checkbox', 'default'=>false);
$twitterthemen_options['widget_fields']['nurhashtags'] = array('label'=>'Zeige nur Hahstags an', 'type'=>'checkbox', 'default'=>false);

$twitterthemen_options['prefix'] = 'twitterthemen';

// Display Twitter messages
function twitterthemen_messages($num = 5, $encode_utf8 = false, $target_blank=false, $mitdesc=true, $leere=false,$nurhashtags=false){
	global $twitterthemen_options;
	include_once(ABSPATH . WPINC . '/rss.php');
	
	if($nurhashtags) $url='http://twitterthemen.de/rss/';
	else $url='http://twitterthemen.de/rss/themen';
	
	$messages = fetch_rss($url);	
	
	if($target_blank) $target=' target="_blank" ';	
	else $target='';	
		
	echo '<ul class="twitterthemen">';
	if (empty($messages->items)){ echo '<li>Twitterthemen.de/rss ist zurzeit leider nicht erreichbar.</li>';
	}else{
		$i=0;
		foreach ( $messages->items as $message ){			$titel=$message['title'];			if($encode_utf8) $titel=utf8_encode($titel);		
			$msg=$message['description'];
			if($encode_utf8) $msg=utf8_encode($msg);				
					$descr='';			if($mitdesc AND ($leere OR (!$leere AND !preg_match("/@definiere/",$msg)))) $descr="<div class=\"twitterthemen-desc\">$msg</div>";						
				if(preg_match("/@definiere/",$descr)){			
					$descr=preg_replace("/@definiere/","<a href=\"http://twitter.com/home?status=%40definiere+".urlencode($titel)."+\">@definiere</a>",$descr);			}						echo '<li class="twitterthemen-item"><a href="'.$message['link'].'" class="twitterthemen-link" title="'.$msg.'"'.$target.'>'.$titel.'</a>'.$descr.'</li>'; 	
									
			$i++;
			if ($i>=$num ) break;
		}
	}
			
	echo '</ul>';
}	
// Twitter widget stuff
function widget_twitterthemen_init(){
	if ( !function_exists('register_sidebar_widget') ) return;
	
	$check_options = get_option('widget_twitterthemen');
	if ($check_options['number']=='') {
		$check_options['number'] = 5;
		update_option('widget_twitterthemen', $check_options);
	}
  
	function widget_twitterthemen($args, $number = 5) {
		global $twitterthemen_options;
		// $args is an array of strings that help widgets to conform to
		// the active theme: before_widget, before_title, after_widget,
		// and after_title are the array keys. Default tags: li and h2.
		extract($args);

		// Each widget can store its own options. We keep strings here.
		include_once(ABSPATH . WPINC . '/rss.php');
		$options = get_option('widget_twitterthemen');
		
		// fill options with default values if value is not set
		$item=$options[$number];
		foreach($twitterthemen_options['widget_fields'] as $key => $field) {
			if (! isset($item[$key])) {
				$item[$key] = $field['default'];
			}
		}
		
		//$messages = fetch_rss('http://twitterthemen.de/rss/');

		// These lines generate our output.
		echo $before_widget . $before_title . '<a href="http://twitterthemen.de/" class="twitterthemen_title_link">'.$item['title'].'</a>'.$after_title;
		if(strlen($item['subtitle'])>0) echo '<div id="twitterthemen-subtitle">'.$item['subtitle'].'</div>';
			twitterthemen_messages($item['num'],$item['encode_utf8'],$item['target_blank'],$item['mitdesc'],$item['leere'],$item['nurhashtags']);
		echo $after_widget;
				
	}

	// This is the function that outputs the form to let the users edit
	// the widget's title. It's an optional feature that users cry for.
	function widget_twitterthemen_control($number) {		global $twitterthemen_options;
		// Get our options and see if we're handling a form submission.	
		$options = get_option('widget_twitterthemen');
		if ( isset($_POST['twitterthemen-submit'])){			foreach($twitterthemen_options['widget_fields'] as $key => $field) {				$options[$number][$key] = $field['default'];
				$field_name = sprintf('%s_%s_%s', $twitterthemen_options['prefix'], $key, $number);
				if ($field['type'] == 'text') {
					$options[$number][$key] = strip_tags(stripslashes($_POST[$field_name]));
				} elseif ($field['type'] == 'checkbox') {
					$options[$number][$key] = isset($_POST[$field_name]);
				}
			}
			update_option('widget_twitterthemen', $options);
		}		
		foreach($twitterthemen_options['widget_fields'] as $key => $field) {		
			$field_name = sprintf('%s_%s_%s', $twitterthemen_options['prefix'], $key, $number);
			$field_checked = '';
			if ($field['type'] == 'text') {
				$field_value = htmlspecialchars($options[$number][$key], ENT_QUOTES);
			} elseif ($field['type'] == 'checkbox') {
				$field_value = 1;
				if (! empty($options[$number][$key])) {
					$field_checked = 'checked="checked"';
				}
			}
			
			printf('<p style="text-align:right;" class="twitter_field"><label for="%s">%s <input id="%s" name="%s" type="%s" value="%s" class="%s" %s /></label></p>',
				$field_name, __($field['label']), $field_name, $field_name, $field['type'], $field_value, $field['type'], $field_checked);
		}
		echo '<input type="hidden" id="twitterthemen-submit" name="twitterthemen-submit" value="1" />';
	}
	
	function widget_twitterthemen_setup() {
		$options = $newoptions = get_option('widget_twitterthemen');
		
		if ( isset($_POST['twitterthemen-number-submit']) ) {
			$number = (int) $_POST['twitterthemen-number'];
			$newoptions['number'] = $number;
		}
		
		if ( $options != $newoptions ) {
			update_option('widget_twitterthemen', $newoptions);
			widget_twitterthemen_register();
		}
	}
	
	
	function widget_twitterthemen_page() {
		$options = $newoptions = get_option('widget_twitterthemen');
	?>
		<div class="wrap">
			<form method="POST">
				<h2><?php _e('Twitterthemen Widgets'); ?></h2>
				<p style="line-height: 30px;"><?php _e('How many Twitter widgets would you like?'); ?>
				<select id="twitterthemen-number" name="twitterthemen-number" value="<?php echo $options['number']; ?>">
	<?php for ( $i = 1; $i < 10; ++$i ) echo "<option value='$i' ".($options['number']==$i ? "selected='selected'" : '').">$i</option>"; ?>
				</select>
				<span class="submit"><input type="submit" name="twitterthemen-number-submit" id="twitterthemen-number-submit" value="<?php echo attribute_escape(__('Save')); ?>" /></span></p>
			</form>
		</div>
	<?php
	}
	
	
	function widget_twitterthemen_register(){
		$options = get_option('widget_twitterthemen');
		$dims = array('width' => 300, 'height' => 300);
		$class = array('classname' => 'widget_twitterthemen');
		//for ($i = 1; $i <= 2; $i++) {		
		$i=1;
			$name = sprintf(__('Twitterthemen #%d'), $i);
			$id = "twitterthemen-$i"; // Never never never translate an id
			wp_register_sidebar_widget($id, $name, $i <= $options['number'] ? 'widget_twitterthemen' : /* unregister */ '', $class, $i);
			wp_register_widget_control($id, $name, $i <= $options['number'] ? 'widget_twitterthemen_control' : /* unregister */ '', $dims, $i);
		//}
		add_action('sidebar_admin_setup', 'widget_twitterthemen_setup');
		add_action('sidebar_admin_page', 'widget_twitterthemen_page');
	}
	widget_twitterthemen_register();}
	
	function mystyle(){	
	$path = WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)); 	
	echo '<link rel="stylesheet" href="' .$path. 'style.css" type="text/css" />';
	}
	
	
// Run our code later in case this loads prior to any required plugins.
add_action('widgets_init', 'widget_twitterthemen_init');add_action('wp_head', 'mystyle');

?>