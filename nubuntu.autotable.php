<?php

class Autotable{
	
	private $content;
	private $source;
	private $title;
	private $db;

	public function init(){
		global $wpdb;
		$this->db = $wpdb;
		$this->source 	= admin_url( "options-general.php?page=".$_GET["page"] );
		$this->title 	= 'Awesome User Table';
		$this->add_actions();
		$this->trigger();
	}

	protected function add_actions(){
		# add to admin menu
		add_action('admin_menu',[$this,'action_admin_menu']);
		# adding styles and script	
		add_action('admin_enqueue_scripts',[$this,'action_add_scripts']);
	}

	public function action_admin_menu(){
		add_options_page('Autotable Example', 'Autotable', 1, "autotable",[$this,'index']);
	}

	public function action_add_scripts(){
		wp_enqueue_style('bootstrap', AUTOTABLE__PLUGIN_URL. 'jquery_auto_plugin/css/bootstrap.min.css');
		wp_enqueue_style('autocss', AUTOTABLE__PLUGIN_URL. 'jquery_auto_plugin/css/auto.css');
		wp_enqueue_script('jquery_ui', AUTOTABLE__PLUGIN_URL. 'jquery_auto_plugin/jquery-ui/jquery-ui.min.js', array(), '1.0.0', true );		
		wp_enqueue_script('bootstrap_js', AUTOTABLE__PLUGIN_URL. 'jquery_auto_plugin/js/bootstrap.min.js', array(), '1.0.0', true );		
		wp_enqueue_script('jquery_auto_plugin', AUTOTABLE__PLUGIN_URL. 'jquery_auto_plugin/js/jquery.auto.js', array(), '1.0.0', true );		
		wp_enqueue_script('autotable_example_user', $this->source.'&cmd=get_table&table=user', array(), '1.0.0', true );		
	}

	private function parse_variable(){
		preg_match_all('/@{(.*?)}/',$this->content, $matches);
		$pattern = $matches[0];
		$replace = array();
		for($i=0;$i<count($matches[1]);$i++){
			$match 			= $matches[1][$i];
			@eval('$replace[] = '.$match.';');
		}
		$this->content = str_replace($pattern,$replace,$this->content);
	}

	private function show_content(){
		$this->parse_variable();
		echo $this->content;		
	}

	public function index(){
		echo '<div id="main" style="margin:20px;height:100%;"></div>';
	}

	private function trigger(){
		$cmd = $_GET['cmd'];
		if(method_exists($this,$cmd)){
			$this->$cmd();
		}
		$post   = $_POST;
		$get 	= $_GET;
		$post 	= array_merge($get,$post);
		if(isset($post['autotable_action'])){
			$this->trigger_post($post);
		}
	}

	private function trigger_post($post){
		switch($post['autotable_action']){
		    case 'get_records':
		        $page               = $post['autotable_page'];
		        $limit              = $post['autotable_limit'];
		        $start              = ($page-1) * $limit;

		        $where              = '';
		        $sort               = '';
		        # if search 
		        if(isset($post['autotable_search']) && strlen($post['autotable_search'])>=1){
		            $set_option = function($field) use($post){
		                switch($post['autotable_search_option']){
		                    case 'equal':
		                        return 'LOWER('.$field.')="'.strtolower($post['autotable_search']).'"';
		                    break;
		                    case 'contain':
		                        return 'LOWER('.$field.') like "%'.strtolower($post['autotable_search']).'%"';
		                    break;
		                }
		            };
		            for($i=0;$i<count($post['autotable_search_list']);$i++){
		                $field      = $post['autotable_search_list'][$i];
		                $where[]    = $set_option($field);
		            }
		            if(count($where)>=1){
		                $where = 'where '.implode(' or ',$where);                
		            } 
		        }    
		        # end search
		        
		        # if sort
		        if(isset($post['autotable_sort'])){
		            $sort = 'order by '. $post['autotable_sort'];
		        }
		        # end sort
		        
				$q                  = "select * from ".$this->db->prefix."users $where $sort limit $start,$limit";
		        $data['records']    = $this->db->get_results($q);
		        $data['total']      = $this->db->get_var("select count(*) from ".$this->db->prefix."users $where");
		        die(json_encode($data));
		    break;
		    case 'form_save':
		        if($post['ID']==null){
		            # new record
					$user_id = wp_create_user( $post['user_login'], $post['user_pass'], $post['user_email'] );		            
					echo $user_id;
					die();                     
		        }else{
		            # edit record
					$user_id = wp_update_user($post);		            
					echo $user_id;
		            die();                     
		        }
		    break;    
		    case 'form_delete':
		        if($post['ID']!=null){
		        	include ABSPATH. '/wp-admin/includes/user.php';
		           	$result = wp_delete_user( $post['ID'], false);
		            echo $result;
		            die();                     
		        }else{
		            die(0);
		        }    
		    break;
		}		
	}

	private function get_table(){
		$table = $_GET['table'];
		ob_start();
		include AUTOTABLE__PLUGIN_DIR .'jquery_auto_plugin/'.$table.'.js';
		$this->content = ob_get_contents();
		ob_clean();		
		$this->show_content();
		die();
	}


}