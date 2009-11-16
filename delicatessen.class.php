<?php

/*
Plugin Name: Delicatessen
Version: 2.0.2
Plugin URI: http://soledadpenades.com/projects/wordpress/delicatessen
Author: Soledad Penadés
Author URI: http://soledadpenades.com
Description: Find out who's linking you in delicious.com

*/

/*  Copyright 2009 Soledad Penades

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
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/
 

if(!class_exists('Delicatessen'))
{
class Delicatessen
{
	protected $base_name;
	protected $cache_dir;
	protected $last_update_filename;
	protected $query_wait_time;
	protected $cache_length;
	
	public function __construct()
	{
		$this->base_name = plugin_basename(__FILE__);
		$this->cache_dir = self::getCacheDir();
		$this->last_update_filename = $this->cache_dir . DIRECTORY_SEPARATOR . 'last_update.txt';
		$options = get_option('delicatessen');
		$this->query_wait_time = $options['query_wait_time'] ;
		$this->cache_length = $options['cache_length'] ;
		
		add_action('loop_start', array(&$this, 'update'), 1);

		if(is_admin())
		{
			add_action('admin_menu', array(&$this, 'settingsPage'));
			add_action('admin_menu', array(&$this, 'resultsPage'));
			$this->checkPluginRequisites();
		}
	}
	
	static function getCacheDir()
	{
		return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cache';
	}

	public function install()
	{
		add_option('delicatessen', array(
				'query_wait_time' =>	20,
				'cache_length'		=>	3600
			));
		$this->ensureCacheDir();
	}
	
	public function update()
	{
		// keep track of single pages only, ignore 'paged' pages (archives, categories, tags...)
		if(is_single() || is_page())
		{
			// If cache dir doesn't exist or is not writable, don't go any further.
			// The user should be warned when visiting the plugin page in the admin area.
			if(!$this->ensureCacheDir()) { return; }
		
			$post_id = get_the_ID();
			$permalink = get_permalink();
			$hash = md5($permalink);
			
			if(0 == strlen($post_id)) { return; }
			
			$bookmarks_cache_file = $post_id . '.txt';
			$bookmarks_url = 'http://feeds.delicious.com/v2/json/url/'.$hash . '?count=100';
			
			$this->updateFeed($bookmarks_cache_file, $bookmarks_url);
		}
	}
	
	protected function updateFeed($file_name, $url)
	{
		$now = time();
		
		$file_path = $this->cache_dir . DIRECTORY_SEPARATOR . $file_name;
		if(!file_exists($file_path) || filemtime($file_path) + $this->cache_length < $now)
		{
			// is it OK to query delicious.com now?
			if(@ filemtime($this->last_update_filename) + $this->query_wait_time < $now)
			{
				$data = file_get_contents($url);
				if(false !== $data)
				{
					file_put_contents($file_path, $data);
				}
				
				touch($this->last_update_filename);
			}
		}
	}
	
	public function settingsPage()
	{
		global $wp_version;
		if(current_user_can('manage_options'))
		{
			add_filter('plugin_action_links_' . $this->base_name, array(&$this, 'pluginActionLinks'));
			if(function_exists('add_submenu_page'))
			{
				add_submenu_page('options-general.php', __('Delicatessen settings', 'delicatessen'), __('Delicatessen', 'delicatessen'), 'manage_options', $this->base_name, array(&$this, 'settingsPageDisplay'));
			}
		}		
	}
	
	public function settingsPageDisplay()
	{
		if(function_exists('current_user_can') && !current_user_can('manage_options'))
		{
			wp_die("Just what do you think you're doing, Dave?");
		}

		if(isset($_POST['submit']))
		{
			$query_wait_time = preg_replace('/[^0-9]/i', '', $_POST['query_wait_time']);
			$cache_length = preg_replace('/[^0-9]/i', '', $_POST['cache_length']);

			update_option('delicatessen', array(
					'query_wait_time'	=>	$query_wait_time,
					'cache_length'		=>	$cache_length
			));

			$this->query_wait_time = $query_wait_time;
			$this->cache_length = $cache_length;
		}

		$s = screen_icon() .
		'<form action="" method="post">
			<p>Generally you should not have to modify these values, unless...<br/>
				<ul style="list-style-type: lower-alpha; list-style-position:inside; margin: 0; padding: 0;">
					<li>you know what you are doing, and</li>
					<li>someone else is using this plug-in in this machine and the IP address is getting banned by delicious.com because of spamming</li>
				</ul>
			</p>
			<p>Therefore, if you experience problems with the plug-in, you should try to <strong>increase</strong> the default values. Results will not be gathered that fast, and they might be a bit old when you read them, but at least you will get results.</p>
			<p>
				<label for="query_wait_time">Minimum time between requests:</label>
				<input name="query_wait_time" type="text" id="query_wait_time" value="'. esc_attr($this->query_wait_time).'" class="small-text" /> seconds<br />
				<em>(A minimum value of <strong>20</strong> is recommended)</em>
			</p>
			<p>
				<label for="cache_length">Cache length:</label>
				<input name="cache_length" type="text" id="cache_length" value="'. esc_attr($this->cache_length).'" class="small-text" /> seconds (for how long should the data of <strong>each page</strong> be cached)<br />
				<em>(A minimum value of <strong>3600</strong> is recommended)</em>
			</p>
			<p class="submit">
				<input type="submit" name="submit" class="button-primary" value="' . esc_attr('Save Changes') .'" />
			</p>

		</form>';

		$this->outputPage('Delicatessen settings', $s);
	}


	
	public function resultsPage()
	{
		if(function_exists('add_submenu_page'))
		{
			add_submenu_page('index.php', __('Delicatessen', 'delicatessen'), __('Delicatessen', 'delicatessen'), 'manage_options', $this->base_name, array(&$this, 'resultsPageDisplay'));
		}
	}
	
	public function resultsPageDisplay()
	{
		$cached = scandir($this->cache_dir);
		$cached_ids = array();

		foreach($cached as $file)
		{
			$file_path = $this->cache_dir . DIRECTORY_SEPARATOR . $file;
			if(preg_match('/^(\d+)\.txt$/', $file, $match) && filesize($file_path) > 2)
			{
				$cached_ids[] = $match[1];
			}
		}

		if(count($cached_ids) == 0)
		{
			$this->outputPage('Delicatessen', 'No bookmarks have been found yet, sorry.');
			return;
		}

		$cached_ids_str = join(',', $cached_ids);
		
		$bookmarked_objects = array();

		foreach(array_merge(
			get_posts(array('include' => $cached_ids_str)),
			get_pages(array('include' => $cached_ids_str))
		) as $item)
		{
			$item->file_path = $this->cache_dir . DIRECTORY_SEPARATOR . $item->ID . '.txt';
			$bookmarked_objects[$item->ID] = $item;
		}

		arsort($bookmarked_objects);
		
		$s = '<table class="widefat">';
        $s.= '<thead><tr><th>ID</th><th>Title</th><th>del.icio.us links</th></tr></thead>';
		
		foreach($bookmarked_objects as $id => $item)
		{
			$permalink = get_permalink($item->ID);
            $post_link = sprintf('<a href="%s">%s</a>', $permalink, $item->post_title);
			
			$encoded = file_get_contents($item->file_path);
			$data = json_decode($encoded);
			if(is_array($data))
			{
				$num_links = count($data);
				$delicious_url = 'http://delicious.com/url/' . md5($permalink);
				$tags = array();
				$comments = array();

				$del_links = '<h3>'.sprintf('<a href="%s">%s</a>', $delicious_url, $num_links).'</h3>';

				foreach($data as $bookmark)
				{
					if(is_array($bookmark->t))
					{
						foreach($bookmark->t as $tag)
						{
							$tags[$tag] = array_key_exists($tag, $tags) ? $tags[$tag]+1 : 1;
						}
					}
					// extract comments+user of the comment
					$comment = trim(strip_tags($bookmark->n));
					$user = trim(strip_tags($bookmark->a));
					if(strlen($comment) && strlen($user))
					{
						$comments[$user] = $comment;
					}
				}

				ksort($tags);

				foreach($tags as $tag => $num)
				{
					$escaped_tag = trim(strip_tags($tag));
					$tag_url = 'http://delicious.com/tag/' . $escaped_tag;
					$tags[$tag] = sprintf('<a href="%s">%s (%d)</a>', $tag_url, $escaped_tag, $num);
				}
				$del_links.= join(', ', $tags);

				foreach($comments as $user => $comment)
				{
					$user_url = 'http://delicious.com/' . $user;
					$comments[$user] = sprintf('<li><q>%s</q>, by <a href="%s">%s</a></li>', $comment, $user_url, $user);
				}
				$del_links.= '<ul>' . join('', $comments) . '</ul>';
				$s.="<tr><td>{$id}</td><td>$post_link</td><td>$del_links</td></tr>";
			}
				
		}

		$s.= "</table>";

		$this->outputPage('Delicatessen', $s);
	}
	
	public function pluginActionLinks($action_links)
	{
		$settings_link = '<a href="options-general.php?page='.$this->base_name.'">' . __('Settings') . '</a>';
		array_unshift( $action_links, $settings_link );

		return $action_links;
	}

	protected function outputPage($title, $contents)
	{
		$s = sprintf('<div class="wrap"><h2>%s</h2>%s</div>', $title, $contents);
		echo $s;
	}

	public static function ensureCacheDir()
	{
		$cache_dir = self::getCacheDir();
		if(!file_exists($cache_dir))
		{
			return @ mkdir($cache_dir);
		}
		else return (is_writable($cache_dir));
	}

	public static function ensureJSON()
	{
		return(function_exists('json_encode') && function_exists('json_decode'));
	}

	public static function ensurePHPVersion()
	{
		return(version_compare(phpversion(),"5.0.0",">"));
	}
	
	public function checkPluginRequisites()
	{
		if(!self::ensurePHPVersion() || !self::ensureCacheDir() || !self::ensureJSON())
		{
			if(!function_exists('delicatessen_warning'))
			{
				function delicatessen_warning()
				{
					$cache_dir = Delicatessen::getCacheDir();
					$parent_dir = dirname($cache_dir);
					$error = '';

					if(!Delicatessen::ensurePHPVersion())
					{
						$error = 'This plug-in requires php5 or higher';
					}
					else if(!file_exists($cache_dir) && !is_writable($parent_dir))
					{
						$error = "The plugin folder is not writable. The plugin needs to be able to create a folder with read and write permissions in <em>$parent_dir</em>.";
					}
					else if(!is_writable($cache_dir))
					{
						$error = "The cache folder is not writable. The plugin needs to be able to create a folder with read and write permissions in <em>$cache_dir</em>.";
					}
					else if(!Delicatessen::ensureJSON())
					{
						$error = "Native php JSON support is required. Please make sure the <strong>json_encode</strong> and <strong>json_decode</strong> functions are available.";
					}

					if(strlen($error))
					{
						echo "<div id='delicatessen_warning' class='updated fade'><p><strong>Delicatessen error: </strong> $error</p></div>";
					}
				}
				add_action('admin_notices', 'delicatessen_warning');
			}
			return false;
		}
		return true;
	}
}

}

// ~~~~~~~~ Hooks are here ~~~~~~~~

if(class_exists('Delicatessen'))
{
	$delicatessen_instance = new Delicatessen();
	register_activation_hook( __FILE__, array(&$delicatessen_instance, 'install'));
}

?>
