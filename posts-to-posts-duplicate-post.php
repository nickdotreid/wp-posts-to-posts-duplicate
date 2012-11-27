<?php
/*
Plugin Name: Posts to Posts - Duplicate Post
Plugin URI: http://www.foo.com/template-uri
Description: A wordpress plugin to copy any existing posts-to-posts relationships
Version: 0.1
Author: Nick Reid
Author URI: http://nickreid.com
*/

$priority = 10;
add_action( "dp_duplicate_page", "p2pdc_duplicate_connections", $priority, 2);
add_action( "dp_duplicate_post", "p2pdc_duplicate_connections", $priority, 2);
function p2pdc_duplicate_connections($new_post_id, $old_post_object){
	// get all connection types for post type
	$relationships = p2pdc_list_relationships(get_post_type($old_post_object));
	foreach($relationships as $relationship){
		if($relationship->post_type == 'user'){
			$users = get_users(array(
			  'connected_type' => $relationship->name,
			  'connected_items' => $old_post_object,
			));
			foreach($users as $user){
				$connection_meta = array();
				foreach($relationship->fields as $field){
					$connection_meta[$field] = p2p_get_meta( $post->p2p_id, $field, true );
				}
				# add user to field
			}
		}else{
			$connected = new WP_Query( array(
			  'connected_type' => $relationship->name,
			  'connected_items' => $old_post_object,
			  'nopaging' => true,
			) );
			foreach($connected->posts as $post){
				$connection_meta = array();
				foreach($relationship->fields as $field){
					$connection_meta[$field] = p2p_get_meta( $post->p2p_id, $field, true );
				}
				if($relationship->direction == 'to'){
					p2p_type($relationship->name)->connect($new_post_id,$post->ID,$connection_meta);
				}else{
					p2p_type($relationship->name)->connect($post->ID,$new_post_id,$connection_meta);	
				}
			}
		}
		
	}
}

function p2pdc_clone_relationship($connection_type, $from_id, $to_id, $connection_meta){
	p2p_type( $connection_type )->connect( $from_id, $to_id, $connection_meta );
}

function p2pdc_list_relationships($post_type){
	$relationships = array();
	$p2p_relations = P2P_Connection_Type_Factory::get_all_instances();
	foreach($p2p_relations as $p2p){
		$add = array();
		if($post_type == "user"){
			foreach($p2p->object as $side => $obj){
				if($obj == $post_type){
					$add[$side] = true;
				}
			}
		}
		foreach($p2p->side as $side => $obj){
			if(isset($obj->query_vars['post_type']) && is_array($obj->query_vars['post_type'])){
				foreach($obj->query_vars['post_type'] as $pt){
					if($pt == $post_type){
						$add[$side] = true;
					}
				}
			}
		}
		foreach($add as $side => $val){
			$other_side = "from";
			if($side == "from"){
				$other_side = "to";
			}
			if(isset($p2p->side[$other_side]->query_vars['post_type'])){
				$other_type = $p2p->side[$other_side]->query_vars['post_type'];
				if(is_array($other_type) && count($other_type) > 0){
					$other_type = $other_type[0];
				}
			}else{
				$other_type = 'user';
			}
			$fields = array();
			foreach($p2p->fields as $key => $field){
				$fields[] = $key;
			}
			$relationships[] = (object) array(
				"name" => $p2p->name,
				"direction" => $other_side,
				"post_type" => $other_type,
				"fields" => $fields,
			);
		}
	}
	return $relationships;
}

?>