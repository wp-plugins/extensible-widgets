<h3>Extensible Widgets <cite>by <a href="http://jimisaacs.com" target="wpew_window">Jim Isaacs</a></cite></h3>
<p>First of all, if you have a idea for a widget that you would love to see included with this plugin, please don't be shy. That's what open-source is all about!</p>
<p>The current state of this plugin is targeting mainly developers. Yes, I am aware of how complicated it can get for the average user, but I am not going to eliminate functionality for only this reason... yet. I am a developer that is tired of rewriting the same code over and over again for some common WordPress display. This plugin is meant to help with this problem, and hopefully many others that all you fellow novice and advanced developers may have.</p>
<p>The functionality of this plugin is based on a collection of core widgets that I have built and supplied along with it. Future plans include allowing user generated widgets, built from abstract combinations of custom and core widget views and controls. This is again, future plans, and right now I hope that the core widgets provided with this plugin will be as much help to you as they have been for me.</p>
<p>There is no real hierarchy in widget registration, but there is of course a hierarchy in PHP Class extension.</p>
<h4>The PHP class hierarchy for all core widgets included in this plugin are as follows:</h4>

<table class="widefat" cellspacing="0">
	<thead>
		<tr>
			<th scope="col" id="title" class="manage-column column-title">Widget Name</th>
			<th scope="col">Class *</th>
			<th scope="col">Class *</th>
			<th scope="col">Class View</th>
			<th scope="col">Class Context</th>
			<th scope="col">Class Widget Base</th>
			<th scope="col">Abstract Class</th>
			<th scope="col">WordPress API</th>
		</tr>
	</thead>
	
	<tfoot>
		<tr>
			<th scope="col">Widget Name</th>
			<th scope="col">Class *</th>
			<th scope="col">Class *</th>
			<th scope="col">Class View</th>
			<th scope="col">Class Context</th>
			<th scope="col">Class Widget Base</th>
			<th scope="col">Abstract Class</th>
			<th scope="col">WordPress API</th>
		</tr>
	</tfÒoot>
 	<tbody>
	 	<tr>
	 		<td><strong>Widget Base</strong></td>
	 		<td>&nbsp;</td>
	 		<td>&nbsp;</td>
	 		<td>&nbsp;</td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>Context</strong></td>
	 		<td>&nbsp;</td>
	 		<td>&nbsp;</td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>View</strong></td>
	 		<td>&nbsp;</td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_View</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>Content</strong></td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_Content</td>
	 		<td>wpew_widgets_View</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>Date</strong></td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_Date</td>
	 		<td>wpew_widgets_View</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>Group</strong></td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_Group</td>
	 		<td>wpew_widgets_View</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>Twitter</strong></td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_Twitter</td>
	 		<td>wpew_widgets_View</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>Query Posts</strong></td>
	 		<td>&nbsp;</td>
	 		<td>wpew_widgets_QueryPosts</td>
	 		<td>wpew_widgets_View</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	 	<tr>
	 		<td><strong>QP Extended</strong></td>
	 		<td>wpew_widgets_QueryPostsExtended</td>
	 		<td>wpew_widgets_QueryPosts</td>
	 		<td>wpew_widgets_View</td>
	 		<td>wpew_widgets_Context</td>
	 		<td>wpew_widgets_Widget</td>
	 		<td>wpew_widgets_AWidget</td>
	 		<td>WP_Widget</td>
	 	</tr>
	</tbody>
</table>

<p>Each row of the table above represents a single widget that may be registered separately from all the rest. As you can see the framework does not modify the WordPress API directly. Instead it extends it going off into another abstract branch which is the base of the Extensible Widgets framework.</p>
<p>To create a widget class with all the basic functionality of the Extensible Widgets framework, but starting completely from scratch in terms of controls and settings, you need to extend the Abstract class of <strong>wpew_widgets_AWidget</strong>.</p>

<p>The main difference between using the term "hierarchy" in vanilla PHP versus Extensible Widgets is the use of static methods and the Singleton pattern. Extensible Widgets uses static methods composed around a single widget instance created from the final inherited class. This instance is passed by reference through each static method to make sure each static method is manipulating the same object. The direction the reference traverses is always from top down, stopping when it meets the abstract class.</p>
<p>Using this logic, a class alhough may be extended or inheriting from another class, is still aware of its own views and settings. This is how it is possible to have each widget's class controls render as part of one widget and as part of another, without rewriting any of the same code.</p>

<h3>Modifying the Hierarchy Render Loop for Controls</h3>
<p>Rendering can be modified through the registration page by editing a registered widget. There you may choose to remove a class from the hierarchy loop in terms of rendering, but know that the default settings that are attributed to the removed class within the current widget's hierarchy will still remain as hidden fields, and be serialized where necessary. This means that the settings will still be available, but it is up to you to check if they are serialized, and unserialize them where appropriate.</p>
<ul>
	<li><strong>See:</strong></li>
	<li>wpew_Widgets::isSerialized();</li>
	<li>wpew_Widgtes::unserialize();</li>
</ul>
<p>Yes, I know this is a bit janky doing it on a per setting basis. I would like this functionality to be completely automated by serializing all of the hidden control's settings in one field. I'm sorry to say, that this is still in-the-works.</p>

<h3>Extend!</h3>
<p>The Extensible Widgets framework is meant to be extended, hence the name. Currently there is no admin screen for custom widget class registration. Therefore once you create your custom widget class, you must register it manually via the WordPress API method <strong>register_widget()</strong>. There are also future plans of allowing control over this through more simplified administrative means.</p>
 
<p>Thank You,<br />
Jim Isaacs</p>