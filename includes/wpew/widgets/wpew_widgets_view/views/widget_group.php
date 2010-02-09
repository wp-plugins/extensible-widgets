<?php
/*
Template Name:Render Widget Area
*/

// BE CAREFUL OF RECURSON HERE
// IF YOU ARE RENDERING THE GROUP THAT WIDGET RESIDES, YOU WILL HAVE YOURSELF AN INFINITE LOOP
// set as view parameters "area=areaidorname"
if( isset($area) ) $wpew->widgets->renderGroup( $area );
?>