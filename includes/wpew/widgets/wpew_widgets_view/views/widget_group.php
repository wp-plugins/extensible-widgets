<?php
/*
Template Name:Render Widget Group
*/

// BE CAREFUL OF RECURSON HERE
// IF YOU ARE RENDERING THE GROUP THAT WIDGET RESIDES, YOU ARE IN FOR AN INFINITE LOOP
if( isset($group) ) $wpew->widgets->renderGroup( $group );
?>