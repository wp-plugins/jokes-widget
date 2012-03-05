<?php
/*
Plugin Name: Jokes Widget
Plugin URI: http://fun-humor-witze.de/
Description: A widget that will display german jokes in a widget. You can select how many jokes should be displayed and you can select jokes from different categroies.
Author: Michael Jentsch
Version: 0.1
Author URI: http://m-software.de/
License: GPL2

    Copyright 2009  Michael Jentsch (email : m.jentsch@web.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation. 
    
    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    The license for this software can likely be found here: 
    http://www.gnu.org/licenses/gpl-2.0.html
    
*/

class Jokes_Widget extends WP_Widget {
	
	function curl_file_get_contents ($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	function Jokes_Widget() {
		$widget_ops = array('classname' => 'widget_jokes', 'description' => __('Witze Widget'));
		$control_ops = array('width' => 300, 'height' => 550);
		$this->WP_Widget('jokes', __('Witze'), $widget_ops, $control_ops);
	}

	function page_URL() {
 		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	function get_url_params ($instance)
	{
		$ret = "?";
		$url = $this->page_URL(); // Needed fpr func = 3
		foreach ($instance as $key => $value)
		{
			$ret .= urlencode ($key) . "=" . urlencode ($value) . "&";
		}
		$ret .= "lang=" . urlencode(WPLANG) . "&";
		$ret .= "url=" . urlencode($url);
		return $ret;
	}
	
	function get_jokes_data ($instance)
	{
		// TODO: Cache (V1.1)
		$server = "api.fun-humor-witze.de";
		$url = "http://" . $server . "/rest/index.php" . $this->get_url_params ($instance);
		$data = $this->curl_file_get_contents ($url);
		$result = json_decode ($data, true);
		return $result;
	}
	
	function get_jokes_content ($data)
	{
		$content = "";
		foreach ($data['results'] as $joke)
		{
			$content .= "<p>" . $joke . "</p>";
		}
		return $content;
	}

	/* Show Widget */
	function widget( $args, $instance ) {
		$img = plugins_url( 'images/jokes.png', __FILE__ );	
		extract($args);
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance );
		$anz   = apply_filters( 'widget_anz', $instance['anz'], $instance );
		$data = $this->get_jokes_data ($instance);
		$text  = $this->get_jokes_content ($data);

		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 
		ob_start();
		eval('?>'.$text);
		$text = ob_get_contents();
		ob_end_clean();
		?>			
		<div class="jokeswidget">
		<?php echo $instance['filter'] ? wpautop($text) : $text; ?>
		</div>
		<?php
		echo "<a id='jokes-a' href='http://www.fun-humor-witze.de/' title='" . $data['info'] . "' target='jokes'>";
		echo "<img id='jokes-img' alt='" . $data['info'] . "' src='$img' border='0'></a>";
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']  = strip_tags($new_instance['title']);
		$instance['filter'] = isset($new_instance['filter']);
		$instance['anz']    = intval($new_instance['anz']);
		$instance['func']   = intval($new_instance['func']);
		for ($i = 1; $i < 24; $i++)
		{
			$instance['cat' . $i]    = intval ($new_instance['cat' . $i]);
		}

		return $instance;
	}

	function form( $instance ) {
		$myargs = array( 'title' => '', 'filter' => '', 'anz' => '');
		for ($i = 1; $i < 24; $i++)
		{
			$myargs['cat' . $i] = "";
		}
		$instance = wp_parse_args( (array) $instance, $myargs );
		$title  = strip_tags($instance['title']);
		$text   = format_to_edit($instance['text']);
		$anz    = intval ($instance['anz']); 
		$func   = intval ($instance['func']); 
		$filter = intval ($instance['filter']); 
		for ($i = 1; $i < 24; $i++)
		{
			$cat[$i]    = $instance['cat' . $i];
			if ($cat[$i] == 1) {
				$selcat[$i] = "checked";
			} else {
				$selcat[$i] = "";
			}
		}
		 

		$intitleid = $this->get_field_id('title');
		$intitlename = $this->get_field_name('title');
		if (strlen (esc_attr($title)) < 1)
		{
			$intitlevalue = "Fun Humor & Witze"; // Default Text
		} else {
			$intitlevalue = esc_attr($title); 
		}
		$inanzid = $this->get_field_id('anz');
		$inanzname = $this->get_field_name('anz');
		$sel01 = "";
		$sel02 = "";
		$sel05 = "";
		$sel10 = "";
		$sel20 = "";
		switch ($anz) {
			case  1: $sel01 = "selected"; break;
			case  2: $sel02 = "selected"; break;
			case  5: $sel05 = "selected"; break;
			case 10: $sel10 = "selected"; break;
			case 20: $sel20 = "selected"; break;
			default: $sel05 = "selected"; break;
		}

		$infilterid = $this->get_field_id('filter');
		$infiltername = $this->get_field_name('filter');
		$selfilter = "";
		if ($filter > 0)
		{
			$selfilter = "checked";
		}

		$infuncid = $this->get_field_id('func');
		$infuncname = $this->get_field_name('func');
		$func1 = "";
		$func2 = "";
		$func3 = "";
		switch ($func) {
			case 1:  $func1 = "selected"; break;
			case 2:  $func2 = "selected"; break;
			case 3:  $func3 = "selected"; break;
			default: $func1 = "selected"; break;
		}

		for ($i = 1; $i < 24; $i++)
		{
			$incatid[$i] = $this->get_field_id('cat' . $i);
			$incatname[$i] = $this->get_field_name('cat' . $i);
		}

?>
<SCRIPT LANGUAGE="JavaScript">
// <!--
function check(name)
{
	field = document.getElementsByClassName(name);
	for (i = 0; i < field.length; i++) field[i].checked = true ;
}

function uncheck(name)
{
	field = document.getElementsByClassName(name);
	for (i = 0; i < field.length; i++) field[i].checked = false ;
}
//  -->
</script>
		<p>
<?
	if (!function_exists ("curl_version"))
	{
?>
		<center>
		<h3 style='color:red;'>You need to enable curl first.</h3>
		<a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl Info</a><br>
		</center>
<?
	}
?>
		<label for="<?=$intitleid?>"><?php _e('Title:'); ?></label>
		<input  class="widefat" id="<?=$intitleid?>" 
			name="<?=$intitlename?>" type="text" value="<?=$intitlevalue?>" />
		</p>

		<p>
		<label for="<?=$inanzid?>"><?php _e('How many items would you like to display?'); ?></label><br>
		<select name="<?=$inanzname?>" id="<?=$inanzid?>" size="1">
			<option <?=$sel01?> value="1" > 1 <?=_e('items')?></option>
			<option <?=$sel02?> value="2" > 2 <?=_e('items')?></option>
			<option <?=$sel05?> value="5" > 5 <?=_e('items')?></option>
			<option <?=$sel10?> value="10">10 <?=_e('items')?></option>
			<option <?=$sel20?> value="20">20 <?=_e('items')?></option>
		</select>
		</p>

		<p>
		<label for="<?=$infuncid?>"><?php _e('Feature Filter'); ?></label><br>
		<select name="<?=$infuncname?>" id="<?=$infuncid?>" size="1">
			<option <?=$func1?> value="1">Random</option>
			<option <?=$func2?> value="2">Random (Dayly refreshed)</option>
			<option <?=$func3?> value="3">Per Page (Never chenged)</option>
		</select>
		</p>

		<label><?php _e('Category'); ?></label><br>
		<a onclick="check('jokekat')" style='cursor:pointer; text-decoration:underline;'>Select all</a> 
		<a onclick="uncheck('jokekat')" style='cursor:pointer; text-decoration:underline;'>Deselect all</a>
		<div style="width:200px; height:100px; overflow:auto; border:1px solid silver;">


	<input class="jokekat" type="checkbox" name="<?=$incatname['1']?>" id="<?=$incatid['1']?>" <?=$selcat['1']?> value="1">Ärzte Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['2']?>" id="<?=$incatid['2']?>" <?=$selcat['2']?> value="1">Akademikerwitze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['3']?>" id="<?=$incatid['3']?>" <?=$selcat['3']?> value="1">A-Klasse Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['4']?>" id="<?=$incatid['4']?>" <?=$selcat['4']?> value="1">Anrufbeantworter Sprüche<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['5']?>" id="<?=$incatid['5']?>" <?=$selcat['5']?> value="1">Anti Frauen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['6']?>" id="<?=$incatid['6']?>" <?=$selcat['6']?> value="1">Anti Männer Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['7']?>" id="<?=$incatid['7']?>" <?=$selcat['7']?> value="1">Al Bundy Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['8']?>" id="<?=$incatid['8']?>" <?=$selcat['8']?> value="1">anti_mann.php<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['9']?>" id="<?=$incatid['9']?>" <?=$selcat['9']?> value="1">Beamten Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['10']?>" id="<?=$incatid['10']?>" <?=$selcat['10']?> value="1">Berufe<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['11']?>" id="<?=$incatid['11']?>" <?=$selcat['11']?> value="1">Zungenbrecher<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['12']?>" id="<?=$incatid['12']?>" <?=$selcat['12']?> value="1">Weicheier Sprüche<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['13']?>" id="<?=$incatid['13']?>" <?=$selcat['13']?> value="1">Lieber Als<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['14']?>" id="<?=$incatid['14']?>" <?=$selcat['14']?> value="1">Autokennzeichen<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['15']?>" id="<?=$incatid['15']?>" <?=$selcat['15']?> value="1">SMS Sprueche<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['16']?>" id="<?=$incatid['16']?>" <?=$selcat['16']?> value="1">Bauern Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['17']?>" id="<?=$incatid['17']?>" <?=$selcat['17']?> value="1">Manta Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['18']?>" id="<?=$incatid['18']?>" <?=$selcat['18']?> value="1">Polizei Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['19']?>" id="<?=$incatid['19']?>" <?=$selcat['19']?> value="1">Blondinen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['20']?>" id="<?=$incatid['20']?>" <?=$selcat['20']?> value="1">Harteier Sprüche<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['21']?>" id="<?=$incatid['21']?>" <?=$selcat['21']?> value="1">Zitate<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['22']?>" id="<?=$incatid['22']?>" <?=$selcat['22']?> value="1">Viagra Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['23']?>" id="<?=$incatid['23']?>" <?=$selcat['23']?> value="1">Versautes<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['24']?>" id="<?=$incatid['24']?>" <?=$selcat['24']?> value="1">Urlaub<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['25']?>" id="<?=$incatid['25']?>" <?=$selcat['25']?> value="1">Trabi Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['26']?>" id="<?=$incatid['26']?>" <?=$selcat['26']?> value="1">Toilettensprueche<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['27']?>" id="<?=$incatid['27']?>" <?=$selcat['27']?> value="1">Tier Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['28']?>" id="<?=$incatid['28']?>" <?=$selcat['28']?> value="1">Studenten Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['29']?>" id="<?=$incatid['29']?>" <?=$selcat['29']?> value="1">Sprueche<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['30']?>" id="<?=$incatid['30']?>" <?=$selcat['30']?> value="1">Sport Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['31']?>" id="<?=$incatid['31']?>" <?=$selcat['31']?> value="1">Schwule Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['32']?>" id="<?=$incatid['32']?>" <?=$selcat['32']?> value="1">Politiker Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['33']?>" id="<?=$incatid['33']?>" <?=$selcat['33']?> value="1">Polen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['34']?>" id="<?=$incatid['34']?>" <?=$selcat['34']?> value="1">Ostfriesen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['35']?>" id="<?=$incatid['35']?>" <?=$selcat['35']?> value="1">Österreicher Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['36']?>" id="<?=$incatid['36']?>" <?=$selcat['36']?> value="1">Internationale Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['37']?>" id="<?=$incatid['37']?>" <?=$selcat['37']?> value="1">Mutproben<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['38']?>" id="<?=$incatid['38']?>" <?=$selcat['38']?> value="1">Musiker Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['39']?>" id="<?=$incatid['39']?>" <?=$selcat['39']?> value="1">Kneipen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['40']?>" id="<?=$incatid['40']?>" <?=$selcat['40']?> value="1">Kirchen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['41']?>" id="<?=$incatid['41']?>" <?=$selcat['41']?> value="1">Harald<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['42']?>" id="<?=$incatid['42']?>" <?=$selcat['42']?> value="1">Kkinder Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['43']?>" id="<?=$incatid['43']?>" <?=$selcat['43']?> value="1">Kelly Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['44']?>" id="<?=$incatid['44']?>" <?=$selcat['44']?> value="1">Kellner Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['45']?>" id="<?=$incatid['45']?>" <?=$selcat['45']?> value="1">Kannibalen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['46']?>" id="<?=$incatid['46']?>" <?=$selcat['46']?> value="1">Juristen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['47']?>" id="<?=$incatid['47']?>" <?=$selcat['47']?> value="1">Geschichte<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['48']?>" id="<?=$incatid['48']?>" <?=$selcat['48']?> value="1">Fussball Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['49']?>" id="<?=$incatid['49']?>" <?=$selcat['49']?> value="1">Fiese Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['50']?>" id="<?=$incatid['50']?>" <?=$selcat['50']?> value="1">Fieses<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['51']?>" id="<?=$incatid['51']?>" <?=$selcat['51']?> value="1">Elefanten Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['52']?>" id="<?=$incatid['52']?>" <?=$selcat['52']?> value="1">Drogen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['53']?>" id="<?=$incatid['53']?>" <?=$selcat['53']?> value="1">DDR Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['54']?>" id="<?=$incatid['54']?>" <?=$selcat['54']?> value="1">Computer Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['55']?>" id="<?=$incatid['55']?>" <?=$selcat['55']?> value="1">Bundeswehr Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['56']?>" id="<?=$incatid['56']?>" <?=$selcat['56']?> value="1">Buero Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['57']?>" id="<?=$incatid['57']?>" <?=$selcat['57']?> value="1">Börsen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['58']?>" id="<?=$incatid['58']?>" <?=$selcat['58']?> value="1">Bill Clinton Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['59']?>" id="<?=$incatid['59']?>" <?=$selcat['59']?> value="1">Bier Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['60']?>" id="<?=$incatid['60']?>" <?=$selcat['60']?> value="1">Aethiopier Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['61']?>" id="<?=$incatid['61']?>" <?=$selcat['61']?> value="1">Anti Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['62']?>" id="<?=$incatid['62']?>" <?=$selcat['62']?> value="1">Anwaltswitze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['63']?>" id="<?=$incatid['63']?>" <?=$selcat['63']?> value="1">Fritzchen Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['64']?>" id="<?=$incatid['64']?>" <?=$selcat['64']?> value="1">Himmel Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['65']?>" id="<?=$incatid['65']?>" <?=$selcat['65']?> value="1">Jäger Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['66']?>" id="<?=$incatid['66']?>" <?=$selcat['66']?> value="1">Länder Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['67']?>" id="<?=$incatid['67']?>" <?=$selcat['67']?> value="1">Klo Witze<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['68']?>" id="<?=$incatid['68']?>" <?=$selcat['68']?> value="1">Letzte Worte<br>
	<input class="jokekat" type="checkbox" name="<?=$incatname['69']?>" id="<?=$incatid['69']?>" <?=$selcat['69']?> value="1">Abkürzungen<br>
	</div>
	<br>

<?php
	}
}

/* Register Widget */
add_action('widgets_init', create_function('', 'return register_widget("Jokes_Widget");'));

?>
