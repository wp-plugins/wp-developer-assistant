<?php

/*
Plugin Name: WP Developer Assistant
Plugin URI: http://blog.realthemes.com/wp-developer-assistant/
Description: A plugin by a WordPress developer for WordPress developers.
Version: 1.0.3
Author: Chris Jean
Author URI: http://realthemes.com
*/

/*
Installation

1. Download and unzip the latest release zip file
2. Upload the entire wp-developer-assistant directory to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
*/

/*
Version History

1.0.1 - 2008-06-26
	Initial release version
1.0.2 - 2008-06-26
	Slight modification that required a new version
1.0.3 - 2008-07-01
	Added support for PHP 4
*/

/*
Completed

- add option to enable error display and which errors are displayed
- show defines
- actions / filters
	view all the hook registrations
	view all the hook calls
	show information about what source file contains the call and what subroutine it is contained in
- options page
	view all options
	view serialized data
	modify options
- run sql queries
- phpInfo()
- show GLOBALS variables
- upload files
- automatically extract uploaded archive files
*/

/*
WIP

- options page
	add new options
- search files
*/

/*
To Do

- restructure code format to place different "pages" in seperate files for easier maintenance
- md5sums of core files to verify that the files are consistent with the core
- show available plugin information / known incompatibilities
- verify database structure with core
- access levels (show available and current users' levels)
- add the ability to view the site with different themes
- scan theme files for problems, ex: calling functions without checking for function existence first
- add option to exclude this plugin's variables from output.
- html validation? (not while in wp-admin)
- replace current query tool with embedded phpMyAdmin
- provide a database backup/restore tool
- create auto backups before potentially dangerous operations
- create an easy method of restoring backups even if WordPress isn't functional
- speed up some of the functions (Options, Hooks)
- clean up display of different information
	- better display of data on the hooks page
	- ability to capture error data and show it serparate from the page layout (if possible)
	- find a better place to put variable output
- create rudimentary file management options
	- edit files (since current theme and plugin editors only allow for specific files to be modified)
	- remove / rename files
- add a function to modify saved sidebar settings
	- remove individual items (broken widgets) from a specific sidebar
	- clear sidebars
*/

/*
Notes:

- It is still possible that I'm exposing too much data unecessarily.
  If you see a security risk with exposing some data, please let me
  know.
- The security for the plugin itself should be tight. Only users
  with "manage_options" permissions can even initialize the plugin.
*/

/*
Browser Testing

Functions properly:
- Firefox 2.0.0.14
- Firefox 3.0
- Flock 1.1.4
- Safari for Windows 3.0.3
- Internet Explorer 7

Problems:
- Internet Explorer 8 doesn't expand div height when serialized data is expanded
*/

/*
Copyright 2008 Chris Jean (email: chris@realthemes.com)

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


if ( !class_exists( 'WPDeveloperAssistant' ) ) {
	class WPDeveloperAssistant {
		var $_var = "wp-developer-assistant";
		var $_name = "WP Developer Assistant";
		var $_class = '';
		var $_initialized = false;
		var $_options = array();
		var $_userID = 0;
		var $_usedInputs = array();
		
		
		
		function WPDeveloperAssistant() {
			add_action( 'plugins_loaded', array( $this, 'init' ), -10 );
		}
		
		function init() {
			// The current user must have the ability to manage options
			// (Administrator level when default capabilities and roles
			// are used) in order to use this plugin. This step most
			// likely isn't necessary, but I want to be sure since this
			// plugin has a lot of power.
			
			if ( current_user_can('manage_options') ) {
				if( ! $this->_initialized ) {
					$this->_class = get_class( $this );
					
					$user = wp_get_current_user();
					$this->_userID = $user->ID;
					
					$this->load();
					
					add_action( 'admin_head', array( $this, 'showSelectedMessages' ) );
					
					add_action( 'admin_menu', array( $this, 'addPages' ) );
					
					$this->_initialized = true;
				}
			}
		}
		
		function addPages() {
			if ( function_exists( 'add_menu_page' ) )
				add_menu_page( 'WP Developer Assistant Settings', 'Developer', 'manage_options', __FILE__, array( $this, 'mainPage' ) );
			
			if ( function_exists( 'add_submenu_page' ) ) {
				add_submenu_page( __FILE__, 'Options Table', 'Options', 'manage_options', 'options', array( $this, 'optionsPage' ) );
				add_submenu_page( __FILE__, 'Hooks: Actions and Filters', 'Hooks', 'manage_options', 'hooks', array( $this, 'hooksPage' ) );
//				add_submenu_page( __FILE__, 'Verify Core Files and Tables', 'Verify', 'manage_options', 'verify', array( $this, 'verifyPage' ) );
				add_submenu_page( __FILE__, 'Run Query', 'Run Query', 'manage_options', 'query', array( $this, 'queryPage' ) );
				add_submenu_page( __FILE__, 'PHP Info', 'PHP Info', 'manage_options', 'php-info', array( $this, 'phpInfoPage' ) );
				add_submenu_page( __FILE__, 'Defines', 'Defines', 'manage_options', 'defines', array( $this, 'definesPage' ) );
				add_submenu_page( __FILE__, 'Upload Files', 'Upload Files', 'manage_options', 'upload-files', array( $this, 'uploadsPage' ) );
//				add_submenu_page( __FILE__, 'Search Files', 'Search Files', 'manage_options', 'search-files', array( $this, 'searchFiles' ) );
			}
		}
		
		
		// Options Storage ////////////////////////////
		
		function initializeOptions() {
			$this->_options['showDocRef'] = 1;
			$this->_options['showMemLeask'] = 1;
			
			$this->_options['showError'] = 1;
			$this->_options['showWarning'] = 1;
			$this->_options['showParse'] = 1;
			$this->_options['showCoreError'] = 1;
			$this->_options['showCoreWarning'] = 1;
			$this->_options['showCompileError'] = 1;
			$this->_options['showCompileWarning'] = 1;
			$this->_options['showUserError'] = 1;
			$this->_options['showUserWarning'] = 1;
			$this->_options['showUserNotice'] = 1;
			$this->_options['showStrict'] = version_compare( PHP_VERSION, '6.0.0', '>=' ) ? 1 : '';
			$this->_options['showRecoverableError'] = version_compare( PHP_VERSION, '5.2.0', '>=' ) ? 1 : '';
			
			
			$this->save();
		}
		
		function save() {
			$data = @get_option( $this->_var );
			
			if ( isset( $data[$this->_userID] ) && ( $data[$this->_userID] === $this->_options ) )
				return true;
			
			$data[$this->_userID] = $this->_options;
			
			return update_option( $this->_var, $data );
		}
		
		function load() {
			$data = @get_option( $this->_var );
			
			if ( is_array( $data ) && is_array( $data[$this->_userID] ) )
				$this->_options = $data[$this->_userID];
			else
				$this->initializeOptions();
		}
		
		
		// Pages //////////////////////////////////////
		
		function mainPage() {
			$this->saveFormOptions();
			
?>
	
	<div class="wrap">
		<h2>WP Developer Assistant Settings</h2>
		
		<form method="post" action="<?php echo $this->getBackLink() ?>">
			<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Error Reporting</th>
					<td>
						<?php $this->addCheckBox( $this->_var, 'enableDisplayErrors', 1, 'Enable display of errors on all pages for this user' ); ?><br />
						<?php $this->addCheckBox( $this->_var, 'showDocRef', 1, 'Include PHP documentation reference links for function errors (also enables HTML error messages)' ); ?><br />
						<?php $this->addCheckBox( $this->_var, 'showMemLeaks', 1, 'Show memory leaks' ); ?><br />
						<div style="margin-left:18px;">
							<?php $this->addShowHideLink( 'errorReportingSettings', 'Error Reporting Settings', true ); ?><br />
							<div id="errorReportingSettings" style="display:none;">
								<br />
								<a href="http://us2.php.net/manual/en/errorfunc.constants.php" target="errorlevels">Settings description</a><br />
								<b>Bold</b> are default options<br />
								<?php $this->addCheckBox( $this->_var, 'showError', 1, '<b>E_ERROR (1)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showWarning', 1, '<b>E_WARNING (2)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showParse', 1, '<b>E_PARSE (4)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showNotice', 1, 'E_NOTICE (8)' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showCoreError', 1, '<b>E_CORE_ERROR (16)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showCoreWarning', 1, '<b>E_CORE_WARNING (32)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showCompileError', 1, '<b>E_COMPILE_ERROR (64)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showCompileWarning', 1, '<b>E_COMPILE_WARNING (128)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showUserError', 1, '<b>E_USER_ERROR (256)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showUserWarning', 1, '<b>E_USER_WARNING (512)</b>' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showUserNotice', 1, '<b>E_USER_NOTICE (1024)</b>' ); ?><br />
								<?php
									if ( version_compare( PHP_VERSION, '6.0.0', '>=' ) )
										$this->addCheckBox( $this->_var, 'showStrict', 1, '<b>E_STRICT (2048)</b>' );
									elseif ( version_compare( PHP_VERSION, '5.0.0', '>=' ) )
										$this->addCheckBox( $this->_var, 'showStrict', 1, 'E_STRICT (2048)' );
									else
										$this->addCheckBox( $this->_var, 'showStrict', 1, 'E_STRICT (2048) <b>Not available due to PHP version &lt; 5.0.0</b>', true );
									
									echo '<br />';
									
									if ( version_compare( PHP_VERSION, '5.2.0', '>=' ) )
										$this->addCheckBox( $this->_var, 'showRecoverableError', 1, '<b>E_RECOVERABLE_ERROR (4096)</b>' );
									else
										$this->addCheckBox( $this->_var, 'showRecoverableError', 1, 'E_RECOVERABLE_ERROR (4096) <b>Not available due to PHP version &lt; 5.2.0</b>', true );
								?>
							</div>
						</div>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">PHP Info Variables</th>
					<td>
						<?php $this->addCheckBox( $this->_var, 'showPHPInfoVariables', 1, 'Show <a href="http://us2.php.net/manual/en/language.variables.predefined.php" target="phpInfoVariables">PHP Predefined Variables</a>' ); ?>
						<div style="margin-left:18px;">
							<?php $this->addShowHideLink( 'showPHPInfoVariablesSettings', 'Variables to Output', true ); ?><br />
							
							<div id="showPHPInfoVariablesSettings" style="display:none;">
								<br />
								<?php $this->addCheckBox( $this->_var, 'showVariableCookie', 1, '_COOKIE' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariableEnv', 1, '_ENV' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariableFiles', 1, '_FILES' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariableGet', 1, '_GET' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariablePHPSelf', 1, 'PHP_SELF' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariablePost', 1, '_POST' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariableRequest', 1, '_REQUEST' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariableServer', 1, '_SERVER' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'showVariableSession', 1, '_SESSION' ); ?><br />
								<br />
								<?php $this->addCheckBox( $this->_var, 'showVariableGlobals', 1, 'GLOBALS (Outputs all globals, lots of data)' ); ?><br />
							</div>
						</div>
					</td>
				</tr>
			</table>
			
			<?php $this->addSubmitButton( 'save', 'Save Changes' ); ?>
			
			<?php $this->addHidden( 'refreshShowSelectedMessages', '1' ); ?>
			<?php $this->addUsedInputs(); ?>
		</form>
	</div>
	
<?php
		}
		
		function optionsPage() {
			global $wpdb;
			
			
			$updated = false;
			
			if ( ! empty( $_POST['page_options'] ) ) {
				check_admin_referer( $this->_var . '-nonce' );
				
				$options = explode( ',', stripslashes( $_POST['page_options'] ) );
				
				if ( is_array( $options ) ) {
					foreach ( (array) $options as $option ) {
						$option = trim($option);
						
						$value = $_POST[$option];
						if ( ! is_array( $value ) )
							$value = trim( $value );
						$value = stripslashes_deep( $value );
						
						$this->update_option_no_serialize( $option, $value );
						
						$updated = true;
					}
				}
			}
			
			if ( $updated )
				$this->showStatusMessage( 'Options updated' );
			
?>
	<div class="wrap">
		<h2>Options</h2>
		
		<p>This page should only be used by experienced developers. Making a mistake with one of these options can
		cause WordPress to stop functioning. If you don't know how to modify a serialized variable, I recommend
		that you don't touch them.</p>
		
		<form method="post" action="<?php echo $this->getBackLink() ?>">
			<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
			<table class="form-table">
			
<?php
			
			$options = $wpdb->get_results( "SELECT * FROM $wpdb->options ORDER BY option_name" );

			foreach ( (array) $options as $option ) :
				$serialized = false;
				$class = '';

				$option->option_name = $this->esc_attr( $option->option_name );
				if ( is_serialized( $option->option_value ) ) {
					$serialized = true;
					$value = $option->option_value;
					$options_to_update[] = $option->option_name;
//					$class = 'all-options';
				}
				else {
					$value = $option->option_value;
					$options_to_update[] = $option->option_name;
//					$class = 'all-options';
				}

?>
					<tr valign="top">
						<th scope="row"><?php echo $option->option_name; ?></th>
						<td>
							<?php if ( $serialized ) : ?>
								<i><?php $this->addShowHideLink( $option->option_name, 'Serialized Data', true ); ?></i><br />
								<textarea style="display:none;" class="<?php echo $class; ?>" name="<?php echo $option->option_name; ?>" id="<?php echo $option->option_name; ?>" cols="60" rows="10"><?php echo $value ?></textarea>
							<?php elseif ( strpos( $value, "\n" ) !== false ) : ?>
								<textarea class="<?php echo $class; ?>" name="<?php echo $option->option_name; ?>" id="<?php echo $option->option_name; ?>" cols="60" rows="10"><?php echo $this->esc_html( $value ); ?></textarea>
							<?php else : ?>
								<input class="<?php echo $class; ?>" type="text" name="<?php echo $option->option_name; ?>" id="<?php echo $option->option_name; ?>" size="30" value="<?php echo $this->esc_attr( $value ); ?>" />
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</table>
			
			<?php $this->addSubmitButton( 'save', 'Save Changes' ); ?>
			<?php $this->addHidden( 'page_options', $options_to_update ); ?>
		</form>
	</div>
	
<?php
		}
		
		function hooksPage() {
?>
	<div class="wrap">
		<h2>Hooks: Actions and Filters</h2>
		
		<?php
			$addActions = $this->findFilesWithMatch( '/(^|\W)add_action\s*\(\s*[\'"]?([\w{}\$]+)[\'"]?\s*,\s*[\'"]?(.+?)[\'"]?(\s*,\s*[\'"]?(\-?\w+)[\'"]?)?(\s*,\s*[\'"]?(\w+)[\'"]?)?\s*\)\s*;/' );
			$doActions = $this->findFilesWithMatch( '/(^|\W)do_action\s*\(\s*[\'"]?([\w{}\$]+)[\'"]?\s*,\s*(.+?)\s*\)\s*;/' );
			$addFilters = $this->findFilesWithMatch( '/(^|\W)add_filter\s*\(\s*[\'"]?([\w{}\$]+)[\'"]?\s*,\s*[\'"]?(.+?)[\'"]?(\s*,\s*[\'"]?(\-?\w+)[\'"]?)?(\s*,\s*[\'"]?(\w+)[\'"]?)?\s*\)\s*;/' );
			$applyFilters = $this->findFilesWithMatch( '/(^|\W)apply_filters\s*\(\s*[\'"]?([\w{}\$]+)[\'"]?\s*,\s*(.+?)\s*\)\s*;/' );
		?>
		
		
		<p>Click on one of the links below to show the information for that type of hook.</p>
		<p>Lines with a light background are for setting hooks (add_filter / add_action) and lines with
		a dark background are for activating hooks (apply_filters / do_action). If a Priority or
		Accepted Args value is in italics, the value wasn't specified and the default is shown.</p>
		
		
		<h3><?php $this->addShowHideLink( 'actionsTable', 'Actions', true ); ?></h3>
		
		<table cellpadding="5" cellspacing="0" id="actionsTable" style="display:none;">
			<tr><th>Called Function</th><th>Name</th><th>Passed Attributes / Function</th><th>Priority</th><th>Accepted Args</th><th>File</th><th>Line Number</th></tr>
			
			<?php
				$actions = array_keys( array_merge( $addActions, $doActions ) );
				sort( $actions );
				
				foreach ( (array) $actions as $action ) {
					if ( isset( $doActions[$action] ) && is_array( $doActions[$action] ) ) {
						$first = true;
						
						foreach ( (array) $doActions[$action] as $match ) {
							foreach ( (array) $match['matches'] as $key => $val ) {
								$match['matches'][$key] = $this->esc_html( $val );
								$match['matches'][$key] = preg_replace( "/\n/", "<br />\n", $val );
							}
							
							$match['file'] = preg_replace( '[' . ABSPATH . ']', '', $match['file'] );
							
							if ( $first ) {
								echo '<tr style="vertical-align:top; background-color:#CCC;"><td style="border-top:1px solid black;">do_action()</td><td style="border-top:1px solid black;">' . $action . '</td><td colspan="3" style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
								
								$first = false;
							}
							else
								echo '<tr style="vertical-align:top; background-color:#CCC;"><td colspan="2">&nbsp;</td><td colspan="3" style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
						}
					}
					
					if ( isset( $addActions[$action] ) && is_array( $addActions[$action] ) ) {
						$first = true;
						
						foreach ( (array) $addActions[$action] as $match ) {
							foreach ( (array) $match['matches'] as $key => $val ) {
								$match['matches'][$key] = $this->esc_html( $val );
								$match['matches'][$key] = preg_replace( "/\n/", "<br />\n", $val );
							}
							
							$match['file'] = preg_replace( '[' . ABSPATH . ']', '', $match['file'] );
							
							if ( empty( $match['matches'][5] ) )
								$match['matches'][5] = '<i>10</i>';
							
							if ( empty( $match['matches'][7] ) )
								$match['matches'][7] = '<i>1</i>';
							
							if ( $first ) {
								echo '<tr style="vertical-align:top;"><td style="border-top:1px solid black;">add_action()</td><td style="border-top:1px solid black;">' . $action . '</td><td style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['matches'][5] . '</td><td style="border-top:1px solid black;">' . $match['matches'][7] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
								
								$first = false;
							}
							else
								echo '<tr style="vertical-align:top;"><td colspan="2">&nbsp;</td><td style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['matches'][5] . '</td><td style="border-top:1px solid black;">' . $match['matches'][7] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
						}
					}
				}
			?>
		</table>
		
		
		<h3><?php $this->addShowHideLink( 'filtersTable', 'Filters', true ); ?></h3>
		
		<table cellpadding="5" cellspacing="0" id="filtersTable" style="display:none;">
			<tr><th>Called Function</th><th>Name</th><th>Passed Attributes / Function</th><th>Priority</th><th>Accepted Args</th><th>File</th><th>Line Number</th></tr>
			
			<?php
				$filters = array_keys( array_merge( $addFilters, $applyFilters ) );
				sort( $filters );
				
				foreach ( $filters as $filter ) {

					if ( isset( $applyFilters[$filter] ) && is_array( $applyFilters[$filter] ) ) {
						$first = true;
						
						foreach ( (array) $applyFilters[$filter] as $match ) {
							foreach ( (array) $match['matches'] as $key => $val ) {
								$match['matches'][$key] = $this->esc_html( $val );
								$match['matches'][$key] = preg_replace( "/\n/", "<br />\n", $val );
							}
							
							$match['file'] = preg_replace( '[' . ABSPATH . ']', '', $match['file'] );
							
							if ( preg_match( '/\)$/', $match['matches'][3] ) && ! preg_match( '/\(/', $match['matches'][3] ) )
								$match['matches'][3] = preg_replace( '/\)$/', '', $match['matches'][3] );
							
							$match['matches'][3] = preg_replace( '/</', '&lt;', $match['matches'][3] );
							$match['matches'][3] = preg_replace( '/>/', '&gt;', $match['matches'][3] );
							
							if ( $first ) {
								echo '<tr style="vertical-align:top; background-color:#CCC;"><td style="border-top:1px solid black;">apply_filters()</td><td style="border-top:1px solid black;">' . $filter . '</td><td colspan="3" style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
								
								$first = false;
							}
							else
								echo '<tr style="vertical-align:top; background-color:#CCC;"><td colspan="2">&nbsp;</td><td colspan="3" style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
						}
					}

					if ( isset( $addFilters[$filter] ) && is_array( $addFilters[$filter] ) ) {
						$first = true;
						
						foreach ( (array) $addFilters[$filter] as $match ) {
							foreach ( (array) $match['matches'] as $key => $val ) {
								$match['matches'][$key] = $this->esc_html( $val );
								$match['matches'][$key] = preg_replace( "/\n/", "<br />\n", $val );
							}
							
							$match['file'] = preg_replace( '[' . ABSPATH . ']', '', $match['file'] );
							
							if ( empty( $match['matches'][5] ) )
								$match['matches'][5] = '<i>10</i>';
							
							if ( empty( $match['matches'][7] ) )
								$match['matches'][7] = '<i>1</i>';
							
							if ( $first ) {
								echo '<tr style="vertical-align:top;"><td style="border-top:1px solid black;">add_filter()</td><td style="border-top:1px solid black;">' . $filter . '</td><td style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['matches'][5] . '</td><td style="border-top:1px solid black;">' . $match['matches'][7] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
								
								$first = false;
							}
							else
								echo '<tr style="vertical-align:top;"><td colspan="2">&nbsp;</td><td style="border-top:1px solid black;">' . $match['matches'][3] . '</td><td style="border-top:1px solid black;">' . $match['matches'][5] . '</td><td style="border-top:1px solid black;">' . $match['matches'][7] . '</td><td style="border-top:1px solid black;">' . $match['file'] . '</td><td style="border-top:1px solid black;">' . $match['line'] . "</td></tr>\n";
						}
					}
				}
			?>
		</table>
	</div>
<?php
		}
		
		function verifyPage() {
			echo "<h2>Verify Core Files and Tables</h2>\n";
		}
		
		function queryPage() {
			global $table_prefix;
			
			
			$tables = '';
			
			$result = mysql_query( "SHOW TABLES LIKE '$table_prefix%'" );
			
			while ( $row = mysql_fetch_assoc( $result ) ) {
				if ( ! preg_match( "/^$table_prefix/", $row['Tables_in_' . DB_NAME . ' (' . $table_prefix . '%)'] ) )
					continue;
				
				if ( $tables != '' )
					$tables .= '<br />';
				
				$tables .= $row['Tables_in_' . DB_NAME . ' (' . $table_prefix . '%)'];
			}
			
			$tables = '<div id="wpTablesDiv" style="display:none;">' . $tables . '</div>';
			
			$link = $this->getShowHideLink( 'wpTablesDiv', 'Tables', true );
			
?>
	<div class="wrap">
		<h2>Run Query</h2>
		
		<form method="post" action="<?php echo $this->getBackLink() ?>">
			<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Table Prefix</th>
					<td><?php echo $table_prefix; ?></td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php echo $link; ?></th>
					<td><?php echo $tables; ?></td>
				</tr>
				<tr valign="top">
					<th scope="row">Query</th>
					<td><textarea name="<?php echo $this->_var; ?>[query]" rows="5" cols="50"><?php echo $_REQUEST[$this->_var]['query']; ?></textarea></td>
				</tr>
<?php
			
			if ( ! empty( $_POST['runQuery'] ) ) {
				
?>
				<tr valign="top">
					<th scope="row">Results</th>
					<td>
<?php
				
				$nonResultQuery = ( preg_match( '/^UPDATE|^DELETE|^DROP/i', $_REQUEST[$this->_var]['query'] ) ) ? true : false;
				
				if ( $result = mysql_query( $_REQUEST[$this->_var]['query'] ) ) {
					if ( $nonResultQuery ) {
						echo "Query successful.";
					}
					else {
						$num = mysql_num_rows( $result );
						$row = ( $num == 1 ) ? 'row' : 'rows';
						echo "<p>$num $row returned.</p>\n";

						$retval = '';
						while ( $row = mysql_fetch_assoc( $result ) ) {
							if ( ! isset( $retval ) ) {
								foreach ( (array) array_keys( $row ) as $val ) {
									$val = $this->esc_html( $val );
									
									$retval .= "<th>$val</th>";
								}
								
								$retval = "<tr>$retval</tr>\n";
							}
							
							$retval .= "<tr>";
							
							foreach ( (array) array_values( $row ) as $val ) {
								$val = $this->esc_html( $val );
								$val = preg_replace( "/\n/", "<br />\n", $val );
								
								$retval .= "<td>$val</td>";
							}
							
							$retval .= "</tr>\n";
						}
						
						echo "<table>$retval</table>\n";
					}
				}
				else {
					if ( $nonResultQuery )
						echo "Query failed.";
					else
						echo "No results or an error occurred.";
				}
			}
			
?>
					</td>
				</tr>
			</table>
			
			<?php $this->addSubmitButton( 'runQuery', 'Run Query' ); ?>
		</form>
	</div>
<?php
			
		}
		
		function phpInfoPage() {
			if ( function_exists( 'phpinfo' ) ) {
				phpinfo() ;
			}
			else {
				echo "Function phpinfo() unavailable.";
			}
		}
		
		function definesPage() {
			if ( function_exists( 'get_defined_constants' ) ) {
				$defines = get_defined_constants();
				ksort( $defines );
				
				$hiddens[] = "AUTH_COOKIE";
				$hiddens[] = "COOKIEHASH";
				$hiddens[] = "DB_HOST";
				$hiddens[] = "DB_NAME";
				$hiddens[] = "DB_PASSWORD";
				$hiddens[] = "DB_USER";
				$hiddens[] = "PASS_COOKIE";
				$hiddens[] = "SECRET_KEY";
				$hiddens[] = "USER_COOKIE";
				
?>
	<div class="wrap">
		<h2>Defined Named Constants</h2>
		
		<form method="post" action="<?php echo $this->getBackLink() ?>">
			<p>For security reasons (unencrypted communications), some of the values are masked. The
			masked values have a value of <i>hidden for security reasons</i>. If you see any variable
			values that should be masked but are not, please notify me at chris@realthemes.com.</p>
			
			<p><i>Note:</i> For the WordPress defines, only the WordPress installation, WPINC,
			wp-content, PLUGINDIR, and wp-admin directories are searched for define() calls. All
			directories except the installation directory are searched recursively.</p>
			
			<?php if ( ! empty( $_POST[$this->_var]['showAllDefines'] ) ) : ?>
				<p><input type="submit" name="<?php echo $this->_var; ?>[default]" value="Show Only WordPress Defines" /></p>
			<?php else : ?>
				<p><input type="submit" name="<?php echo $this->_var; ?>[showAllDefines]" value="Show All Defines" /></p>
			<?php endif; ?>
			
			<table cellpadding="5" width="100%">
				<?php $defines = $this->findFilesWithMatch( '/(^define|\Wdefine)\s*\(\s*[\'"]?(\w+)[\'"]?\s*,\s*[\'"]?(\w+)[\'"]?/' ); ?>
				
				<?php
					if ( ! empty( $_POST[$this->_var]['showAllDefines'] ) ) {
						$curValues = get_defined_constants();
						
						foreach ( (array) $curValues as $define => $val )
							if ( ! isset( $defines[$define] ) )
								$defines[$define][] = array( 'file' => '<i>Not defined in WordPress</i>', 'line' => '&nbsp;', 'matches' => array( 3 => $curValues[$define] ) );
					}
				?>
				
				<tr><th>Name</th><th>Current Value</th><th>Value</th><th>File</th><th>Line Number</th></tr>
				
				<?php
					ksort( $defines );
					
					$curValues = get_defined_constants();
					
					foreach ( (array) $defines as $define => $matches ) {
						if ( in_array( $define, (array) $hiddens ) )
							$curValues[$define] = '<i>hidden for security reasons</i>';
						
						echo '<tr style="vertical-align:top;"><td>' . $define . '</td><td>' . ( isset( $curValues[$define] ) ? $curValues[$define] : 'not set' ) . '</td>';
						
						$first = true;
						
						foreach ( (array) $matches as $match ) {
							$match['matches'][3] = $this->esc_html( $match['matches'][3] );
							if ( in_array( $define, (array) $hiddens ) )
								$match['matches'][3] = '<i>hidden for security reasons</i>';
							$match['matches'][3] = preg_replace( "/\n/", "<br />\n", $match['matches'][3] );
							
							$match['file'] = preg_replace( '[' . ABSPATH . ']', '', $match['file'] );
							
							if ( $first ) {
								echo '<td>' . $match['matches'][3] . '</td><td>' . $match['file'] . '</td><td>' . $match['line'] . "</td></tr>\n";
								
								$first = false;
							}
							else
								echo '<tr style="vertical-align:top;"><td colspan="2">&nbsp;</td><td>' . $match['matches'][3] . '</td><td>' . $match['file'] . '</td><td>' . $match['line'] . "</td></tr>\n";
						}
					}
				?>
			</table>
		</form>
	</div>
<?php
				
			}
			else
				echo "Function get_defined_constants() unavailble.";
		}
		
		function uploadsPage() {
			if ( ! empty( $_POST['upload'] ) ) {
				check_admin_referer( $this->_var . '-nonce' );
				
				
				$uploads = array();
				$file = array();
				
				if ( 'plugin' == $_POST[$this->_var]['destinationSelection'] )
					$uploads = array( 'path' => path_join( ABSPATH, PLUGINDIR ), 'url' => trailingslashit( get_option( 'siteurl' ) ) . PLUGINDIR, 'subdir' => '', 'error' => false );
				elseif ( 'theme' == $_POST[$this->_var]['destinationSelection'] )
					$uploads = array( 'path' => get_theme_root(), 'url' => get_theme_root_uri(), 'subdir' => '', 'error' => false );
				elseif ( 'manual' == $_POST[$this->_var]['destinationSelection'] ) {
					if ( preg_match( '/^[\/\\\\]/', $_POST[$this->_var]['destinationPath'] ) )
						$file['error'] = "The Manual Path must be relative (cannot begin with \ or /).";
					elseif ( preg_match( '/\.\./', $_POST[$this->_var]['destinationPath'] ) )
						$file['error'] = "Previous directory paths (..) are not permitted in the Manual Path.";
					else {
						if ( empty( $_POST[$this->_var]['destinationPath'] ) ) {
							$path = ABSPATH;
							$url = get_option( 'siteurl' );
						}
						else {
							$path = path_join( ABSPATH, $_POST[$this->_var]['destinationPath'] );
							$url = trailingslashit( get_option( 'siteurl' ) ) . $_POST[$this->_var]['destinationPath'];
						}
						
						if ( ! wp_mkdir_p( $path ) )
							$file['error'] = "Unable to create path $path. Ensure that the web server has permission to write to the parent of this folder.";
						else 
							$uploads = array( 'path' => $path, 'url' => $url, 'subdir' => '', 'error' => false );
					}
				}
				
				$overwriteFile = ( ! empty( $_POST[$this->_var]['overwriteFile'] ) ) ? true : false;
				$renameIfExists = ( ! empty( $_POST[$this->_var]['renameIfExists'] ) ) ? true : false;
				
				if ( empty( $file['error'] ) ) {
					if ( ! empty( $_POST[$this->_var]['uploadURL'] ) )
						$file = $this->getFileFromURL( $_POST[$this->_var]['uploadURL'], $uploads, $overwriteFile, $renameIfExists );
					elseif ( ! empty( $_FILES['uploadFile']['name'] ) )
						$file = $this->getFileFromPost( 'uploadFile', $uploads, $overwriteFile, $renameIfExists );
					else
						$file['error'] = 'You must either provide a URL or a system file to upload.';
				}
				
				
				if ( false === $file['error'] ) {
					$this->showStatusMessage( 'File successfully uploaded' );
					
					$extracted = false;
					
					if ( ! empty( $_POST[$this->_var]['extract'] ) ) {
						$forceExtractionFolder = ( ! empty( $_POST[$this->_var]['forceExtractionFolder'] ) ) ? true : false;
						
						$result = $this->extractArchive( $file, $forceExtractionFolder );
						
						if ( true === $result['extracted'] ) {
							$path = str_replace( '/', '\\/', ABSPATH );
							$destination = preg_replace( '/^' . $path . '/', '', $result['destination'] );
							
							$this->showStatusMessage( 'Archive successfully extracted to ' . $destination );
							
							$extracted = true;
							
							if ( ! empty( $_POST[$this->_var]['removeArchive'] ) ) {
								if ( unlink( $file['path'] ) )
									$this->showStatusMessage( 'Archive removed' );
								else
									$this->showErrorMessage( 'Unable to remove archive' );
							}
						}
						elseif ( false !== $result['error'] )
							$this->showErrorMessage( $result['error'] );
					}
					
					if ( ! $extracted ) {
						$path = str_replace( '/', '\\/', ABSPATH );
						$destination = preg_replace( '/^' . $path . '/', '', $file['path'] );
						
						$message = '<p>Path: ' . $destination . '</p>';
						$message .= '<p>URL: <a href="' . $file['url'] . '" target="newUpload">' . $file['url'] . '</a></p>';
						
						$this->showStatusMessage( $message );
					}
				}
				else
					$this->showErrorMessage( $file['error'] );
			}
			
			
?>
	<div class="wrap">
		<h2>Upload Files</h2>
		
		<form enctype="multipart/form-data" method="post" action="<?php echo $this->getBackLink() ?>">
			<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
			<table class="form-table">
				<tr><th scope="row">Upload File</th>
					<td>
						<label for="<?php echo $this->_var; ?>-uploadURL">
							From URL: <input type="text" size="70" name="<?php echo $this->_var; ?>[uploadURL]" id="<?php echo $this->_var; ?>-uploadURL" />
						</label><br />
						- or -<br />
						<label for="uploadFile">
							Select From System: <input type="file" name="uploadFile" id="uploadFile" />
						</label>
					</td>
				</tr>
				<tr><th scope="row">Destination</th>
					<td>
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'uploads', 'Uploads', false, true ); ?><br />
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'plugin', 'Plugin' ); ?><br />
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'theme', 'Theme' ); ?><br />
						<?php $this->addRadio( $this->_var, 'destinationSelection', 'manual', 'Manual Path:' ); ?>
							<input type="text" name="<?php echo $this->_var; ?>[destinationPath]" size="30" /><br />
							<div style="margin-left:18px; width:400px;">
								The manual path is relative to the root path of the WordPress installation. If the path doesn't exist, 
								this program will attempt to create it before saving the file. Using previous directory (..) paths is 
								not permitted.
							</div>
					</td>
				</tr>
				<tr><th scope="row">Options</th>
					<td>
						<?php $this->addCheckBox( $this->_var, 'overwriteFile', 1, 'Overwrite existing file' ); ?><br />
						<?php $this->addCheckBox( $this->_var, 'renameIfExists', 1, 'Rename if file already exists (or if overwrite fails)', false, true ); ?><br />
						<?php $this->addCheckBox( $this->_var, 'extract', 1, 'Extract archived files', false, true ); ?><br />
							<div style="margin-left:18px; width:400px;">
								The following extensions are supported: zip, tar, gz, tar.gz, tgz, tar.bz2, and tbz.<br />
								<?php $this->addCheckBox( $this->_var, 'removeArchive', 1, 'Remove archive after successful extraction' ); ?><br />
								<?php $this->addCheckBox( $this->_var, 'forceExtractionFolder', 1, 'Force files to be extracted inside a folder' ); ?>
								<div style="margin-left:18px; width:400px;">
									This places the extracted files into a folder with the same name as the archive file
									without the extension.
								</div>
							</div>
					</td>
				</tr>
			</table>
			
			<?php $this->addSubmitButton( 'upload', 'Upload' ); ?>
			<?php $this->addHidden( 'action', 'wp_handle_upload' ); ?>
		</form>
	</div>
<?php
		}
		
		function searchFiles () {
			
?>
	<div class="wrap">
		<h2>Search Files</h2>
		
		
		<?php if ( empty( $_POST['search'] ) ) : ?>
			<form enctype="multipart/form-data" method="post" action="<?php echo $this->getBackLink() ?>">
				<?php wp_nonce_field( $this->_var . '-nonce' ); ?>
				<table class="form-table">
					<tr><th scope="row">Search Type</th>
						<td>
							<?php $this->addRadio( $this->_var, 'searchType', 'simple', 'Simple', false, true ); ?><br />
							<div style="margin-left:18px;">
								A simple search just looks for an exact match of the search text in the file. The results
								simply shows which line numbers in which files the text was found in.
							</div>
							<?php $this->addRadio( $this->_var, 'searchType', 'regex', 'Regular Expression' ); ?>
							<div style="margin-left:18px;">
								If you know <a href="http://www.regular-expressions.info/" target="regextutorial">regular expressions</a>,
								put it to good use to find exactly what you are looking for quickly. If no
								<a href="http://www.regular-expressions.info/brackets.html" target="regexbackrefs">backreferences</a>
								are used, each matching string is shown along with the line number and source file. If backreferences
								are used, each backreference is shown instead of the matching string.
							</div>
							
							<p>
								<b>Notice:</b> All searches are done on a line-by-line basis, so neither search type will
								match multi-line queries.
							</p>
						</td>
					</tr>
					<tr><th scope="row">Search Options</th>
						<td>
							<?php $this->addCheckBox( $this->_var, 'ignoreCase', 1, 'Case insensitive search', false, true ); ?><br />
							<?php $this->addCheckBox( $this->_var, 'searchOnlyPHP', 1, 'Search only PHP files', false, true ); ?><br />
							<?php $this->addCheckBox( $this->_var, 'searchWordPress', 1, 'Limit search to WordPress folders', false, true ); ?><br />
							<div style="margin-left:18px;">
								This option restrains the search to the WordPress installation, WPINC, wp-content,
								PLUGINDIR, and wp-admin directories. All folders except the root installation folder
								will be searched recursively. If this option is disabled, the entire WordPress
								installation folder will be searched recursively. Note that disabling this option
								may cause the search to take a very long time if there are a large number of folders.
							</div>
						</td>
					</tr>
					<tr><th scope="row">Search Text</th>
						<td>
							<textarea name="<?php echo $this->_var; ?>[searchQuery]" cols="60" rows="3"></textarea>
						</td>
					</tr>
				</table>
				
				<?php $this->addSubmitButton( 'search', 'Search' ); ?>
			</form>
		<?php else : ?>
			<?php
				check_admin_referer( $this->_var . '-nonce' );
				
				$query = $_POST[$this->_var]['searchQuery'];
				$query = preg_replace( '/\//', '\\\/', $query );
				$query = '/' . $query . '/';
				
				if ( 'simple' === $_POST[$this->_var]['searchType'] )
					$query = preg_replace( '/([\(\)])/', '\\\$1', $query );
				
				if ( ! empty( $_POST[$this->_var]['ignoreCase'] ) )
					$query .= 'i';
				
				if ( ! empty( $_POST[$this->_var]['searchWordPress'] ) )
					$results = $this->findFilesWithMatch2( $query );
				else
					$results = $this->findFilesWithMatch2( $query, ABSPATH );
			?>
			
			<p>Search for: <?php echo $query ?></p>
			
			<table cellspacing="0" cellpadding="10">
				<tr><th>Match</th><th>File</th><th>Line Number</th></tr>
				<?php
					foreach ( (array) $results as $result )
						echo '<tr><td>' . $result['matches'][0] . '</td><td>' . $result['file'] . '</td><td>' . $result['line'] . '</td></tr>';
				?>
			</table>
		<?php endif; ?>
	</div>
<?php
			
		}
		
		
		// Form Handling Functions //////////////////
		
		function addHidden( $name, $value ) {
			if ( is_array( $value ) )
				$value = implode( ',', $value );
			
			echo '<input type="hidden" name="' . $name . '" value="' . $value . '" />';
		}
		
		function addSubmitButton( $name, $value, $disabled = false ) {
			echo '<p class="submit"><input type="submit" name="' . $name . '" value="' . $value . '" /></p>';
		}
		
		function addUsedInputs() {
			$usedInputs = '';
			
			foreach ( (array) $this->_usedInputs as $input ) {
				if ( ! empty( $usedInputs ) )
					$usedInputs .= ',';
				
				$usedInputs .= $input;
			}
			
			if ( ! empty( $usedInputs ) )
				echo '<input type="hidden" name="used-inputs" value="' . $usedInputs . '" />' . "\n";
		}
		
		function addShowHideLink( $elementID, $message, $hidden, $alterText = true ) {
			echo $this->getShowHideLink( $elementID, $message, $hidden, $alterText );
		}
		
		function getShowHideLink( $elementID, $message, $hidden, $alterText = true ) {
			if($hidden)
				$text = "Show";
			else
				$text = "Hide";
			
			if ( $alterText )
				return "<a id=\"$elementID-toggle\" href=\"javascript:{}\" onclick=\"if(document.getElementById('$elementID').style.display == 'none') { document.getElementById('$elementID').style.display = 'block'; document.getElementById('$elementID-toggle').innerHTML = 'Hide $message'; } else { document.getElementById('$elementID').style.display = 'none'; document.getElementById('$elementID-toggle').innerHTML = 'Show $message'; } return false;\">$text $message</a>";
			else
				return "<a id=\"$elementID-toggle\" href=\"javascript:{}\" onclick=\"if(document.getElementById('$elementID').style.display == 'none') { document.getElementById('$elementID').style.display = 'block'; document.getElementById('$elementID-toggle').innerHTML = '$message'; } else { document.getElementById('$elementID').style.display = 'none'; document.getElementById('$elementID-toggle').innerHTML = '$message'; } return false;\">$message</a>";
		}
		
		function addRadio( $base, $variable, $value, $message, $disabled = false, $selected = false ) {
?>
						<label for="<?php echo $base ?>-<?php echo $variable; ?>-<?php echo $value; ?>">
							<input name="<?php echo $base ?>[<?php echo $variable; ?>]" type="radio" id="<?php echo $base ?>-<?php echo $variable; ?>-<?php echo $value; ?>" value="<?php echo $value; ?>"<?php if ( ! empty( $this->_options[$variable] ) || $selected ) echo ' checked="checked"'; ?><?php if ( $disabled ) echo ' disabled'; ?> />
							<?php echo $message; ?>
						</label>
<?php
			
			$this->_usedInputs[] = $variable;
		}
		
		function addCheckBox( $base, $variable, $value, $message, $disabled = false, $selected = false ) {
?>
						<label for="<?php echo $base ?>-<?php echo $variable; ?>">
							<input name="<?php echo $base ?>[<?php echo $variable; ?>]" type="checkbox" id="<?php echo $base ?>-<?php echo $variable; ?>" value="<?php echo $value; ?>"<?php if ( ! empty( $this->_options[$variable] ) || $selected ) echo ' checked="checked"'; ?><?php if ( $disabled ) echo ' disabled'; ?> />
							<?php echo $message; ?>
						</label>
<?php
			
			$this->_usedInputs[] = $variable;
		}
		
		function saveFormOptions() {
			if ( isset( $_POST['save'] ) && isset( $_POST[$this->_var] ) && is_array( $_POST[$this->_var] ) )
			{
				if ( function_exists( 'current_user_can' ) && ! current_user_can( 'manage_options' ) )
					die(__('Cheatin’ uh?'));
				
				check_admin_referer( $this->_var . '-nonce' );
				
				if ( ! empty( $_POST['used-inputs'] ) ) {
					$usedInputs = explode( ',', $_POST['used-inputs'] );
					
					foreach ( (array) $usedInputs as $input ) {
						if ( isset( $_POST[$this->_var][$input] ) )
							$this->_options[$input] = $_POST[$this->_var][$input];
						elseif ( isset( $this->_options[$input] ) )
							unset( $this->_options[$input] );
					}
				}
				else
					foreach ( $_POST[$this->_var] as $key => $val )
						$this->_options[$key] = $val;
				
				
				if ( $this->save() )
					$this->showStatusMessage( 'Configuration updated' );
				else {
					$this->showErrorMessage( 'Error while saving options' );
					return false;
				}
			}
			
			return true;
		}
		
		
		// Plugin Functions ///////////////////////////
		
		function showSelectedMessages() {
			if ( ! empty( $_POST['refreshShowSelectedMessages'] ) )
				$data = $_POST[$this->_var];
			else
				$data = $this->_options;
			
			
			if ( isset( $data['enableDisplayErrors'] ) && ( $data['enableDisplayErrors'] == 1 ) ) {
				ini_set( 'display_errors', '1' );
				
				if ( isset( $data['showDocRef'] ) && ( $data['showDocRef'] == 1 ) ) {
					ini_set( 'docref_root', 'http://www.php.net/manual/en/' );
					ini_set( 'docref_ext', '.php' );
					ini_set( 'html_errors', 'true' );
				}
				
				if ( isset( $data['showMemLeaks'] ) && ( $data['showMemLeaks'] == 1 ) )
					ini_set( 'report_memleaks' , 'true' );

				$reportingVars['showError'] = E_ERROR;
				$reportingVars['showWarning'] = E_WARNING;
				$reportingVars['showParse'] = E_PARSE;
				$reportingVars['showNotice'] = E_NOTICE;
				$reportingVars['showCoreError'] = E_CORE_ERROR;
				$reportingVars['showCoreWarning'] = E_CORE_WARNING;
				$reportingVars['showCompileError'] = E_COMPILE_ERROR;
				$reportingVars['showCompileWarning'] = E_COMPILE_WARNING;
				$reportingVars['showUserError'] = E_USER_ERROR;
				$reportingVars['showUserWarning'] = E_USER_WARNING;
				$reportingVars['showUserNotice'] = E_USER_NOTICE;
				$reportingVars['showStrict'] = E_STRICT;
				$reportingVars['showRecoverableError'] = E_RECOVERABLE_ERROR;
				
				foreach ( (array) $reportingVars as $var => $val ) {
					if ( ! empty( $data[$var] ) ) {
						$reportingLevel |= $val;
					}
				}

				error_reporting( $reportingLevel );
			}
			else
				ini_set( 'display_errors', '0' );
			
			if ( isset( $data['showPHPInfoVariables'] ) && ( $data['showPHPInfoVariables'] == 1 ) ) {
				$variables['showVariableCookie'] = ( isset( $_COOKIE ) ) ? $_COOKIE : null;
				$variables['showVariableEnv'] = ( isset( $_ENV ) ) ? $_ENV : null;
				$variables['showVariableFiles'] = ( isset( $_FILES ) ) ? $_FILES : null;
				$variables['showVariableGet'] = ( isset( $_GET ) ) ? $_GET : null;
				$variables['showVariablePHPSelf'] = ( isset( $PHP_SELF ) ) ? $PHP_SELF : null;
				$variables['showVariablePost'] = ( isset( $_POST ) ) ? $_POST : null;
				$variables['showVariableRequest'] = ( isset( $_REQUEST ) ) ? $_REQUEST : null;
				$variables['showVariableServer'] = ( isset( $_SERVER ) ) ? $_SERVER : null;
				$variables['showVariableSession'] = ( isset( $_SESSION ) ) ? $_SESSION : null;
				$variables['showVariableGlobals'] = ( isset( $GLOBALS ) ) ? $GLOBALS : null;
				
				$names['showVariableCookie'] = '$_COOKIE';
				$names['showVariableEnv'] = '$_ENV';
				$names['showVariableFiles'] = '$_FILES';
				$names['showVariableGet'] = '$_GET';
				$names['showVariablePHPSelf'] = '$PHP_SELF';
				$names['showVariablePost'] = '$_POST';
				$names['showVariableRequest'] = '$_REQUEST';
				$names['showVariableServer'] = '$_SERVER';
				$names['showVariableSession'] = '$_SESSION';
				$names['showVariableGlobals'] = '$GLOBALS';
				
				foreach ( (array) $variables as $var => $val ) {
					if ( ! empty( $data[$var] ) )
						echo $this->produceCollapsibleVariableOutput( $names[$var], $variables[$var] );
				}
			}
		}

		// Utility Functions //////////////////////////
		
		function produceCollapsibleVariableOutput( $name, $data ) {
			if ( ! isset( $this->_parseDataCounter ) )
				$this->_parseDataCounter = 1;
			
			return '<table>' . $this->parseData( $name, $data ) . "</table>\n";
		}
		
		function parseData( $name, $data, $depth = 1 ) {
			$name = $this->esc_html( $name );
			$retval = '';
			
			if ( is_array( $data ) ) {
				foreach ( (array) $data as $key => $val ) {
					if ( ( $name == '$GLOBALS' ) && ( $key == 'GLOBALS' ) ) {
						$key = $this->esc_html( $key );
						$retval .= "<tr><td>$key <i>(array)</i></td><td><i>Recursive Reference</i></td></tr>\n";
					}
					else
						$retval .= $this->parseData( $key, $val, $depth + 1 );
				}
				
				if ( $retval == '' )
					return "<tr>\n<td style=\"vertical-align:top;\">$name&nbsp;<i>(array)</i></td>\n<td>\n<table>\n<i>empty</i></table>\n</td>\n</tr>\n";
				
				
				$link = $this->getShowHideLink( 'variableOutputListing-array-' . $this->_parseDataCounter, $name . '&nbsp;<i>(array)</i>', true, false );
				
				return "<tr>\n<td style=\"vertical-align:top;\">$link</td>\n<td>\n<table style=\"display:none;\" id=\"variableOutputListing-array-" . $this->_parseDataCounter++ . "\">\n$retval</table>\n</td>\n</tr>\n";
			}
			else if ( is_object( $data ) ) {
				$data = $this->esc_html( $data );
				
				return "<tr><td>$name&nbsp;<i>(object)</i></td><td>$data</td></tr>\n";
			}
			else if ( is_bool( $data ) ) {
				$data = ( $data ) ? 'true' : 'false';
				
				return "<tr><td>$name&nbsp;<i>(boolean)</i></td><td>$data</td></tr>\n";
			}
			else if ( is_string( $data ) ) {
				$data = $this->esc_html( $data );
				
				if ( '' === $data )
					$data = '<i>empty<i>';
				
				return "<tr><td>$name&nbsp;<i>(string)</i></td><td>$data</td></tr>\n";
			}
			else {
				$data = $this->esc_html( $data );
				
				if ( is_null( $data ) )
					$data = "<i>empty<i>";
				
				$retval = "<tr><td>$name&nbsp;<i>(" . gettype( $data ) . ")</i></td><td>$data</td></tr>\n";
			}
			
			return $retval;
		}
		
		// I got this from the Google Sitemap Generator.
		// Thanks Arne Brachhold :)
		function getBackLink() {
			$page = basename( __FILE__ );
			if ( isset( $_GET['page'] ) && ! empty( $_GET['page'] ) )
				$page = preg_replace( '[^a-zA-Z0-9\.\_\-]', '', $_GET['page'] );
			
			return $_SERVER['PHP_SELF'] . '?page=' .  $page;
		}
		
		function findFilesWithMatch( $query, $path = '', $recursive = true ) {
			$results = array();

			$nonRecurPaths = array();
			
			if ( $path == '' ) {
				$nonRecurPaths[] = ABSPATH;
				
				$recurPaths[] = ABSPATH . WPINC;
				$recurPaths[] = ABSPATH . 'wp-content';
				$recurPaths[] = ABSPATH . 'wp-admin';
				
				if ( ! preg_match( '/^wp-content\//', PLUGINDIR ) )
					$recurPaths[] = ABSPATH . PLUGINDIR;
			}
			else {
				if ( $recursive )
					$recurPaths[] = $path;
				else
					$nonRecurPaths[] = $path;
			}
			
			
			foreach ( $nonRecurPaths as $path ) {
				$path = preg_replace( '/\/+$/', '', $path );
				$path .= '/';
				
				$DIR = @opendir( $path );
				
				while ( false !== ( $file = readdir( $DIR ) ) ) {
					if ( is_file( $path . $file ) && preg_match( '/\.php$/', $file ) ) {
						foreach ( (array) file( $path . $file ) as $num => $line ) {
							if ( preg_match( $query, $line, $matches ) ) {
								$match = ( isset( $matches[2] ) ) ? $matches[2] : '';
								
								$results[$match][] = array( 'file' => $path . $file, 'line' => $num, 'matches' => $matches );
							}
						}
					}
				}
			}
			
			foreach ( (array) $recurPaths as $path ) {
				$path = preg_replace( '/\/+$/', '', $path );
				$path .= '/';
				
				$DIR = @opendir( $path );
				
				while ( false !== ( $file = readdir( $DIR ) ) ) {
					if ( is_file( $path . $file ) && preg_match( '/\.php$/', $file ) && preg_match( $query, file_get_contents( $path . $file ) ) ) {
						foreach ( (array) file( $path . $file ) as $num => $line ) {
							if ( preg_match( $query, $line, $matches ) ) {
								$match = ( isset( $matches[2] ) ) ? $matches[2] : '';
								
								$results[$match][] = array( 'file' => $path . $file, 'line' => $num, 'matches' => $matches );
							}
						}
					}
					elseif ( is_dir( $path . $file ) && ( '.' !== $file ) && ( '..' !== $file ) ) {
						$newResults = $this->findFilesWithMatch( $query, $path . $file, true );
						
						foreach ( (array) $newResults as $match => $data )
							foreach ( (array) $data as $record )
								$results[$match][] = $record;
					}
				}
			}
			
			
			return $results;
		}
		
		function findFilesWithMatch2( $query, $path = '', $recursive = true, $onlyPHP = true ) {
			$results = array();
			
			
			if ( $path == '' ) {
				$nonRecurPaths[] = ABSPATH;
				
				$recurPaths[] = ABSPATH . WPINC;
				$recurPaths[] = ABSPATH . 'wp-content';
				$recurPaths[] = ABSPATH . 'wp-admin';
				
				if ( ! preg_match( '/^wp-content\//', PLUGINDIR ) )
					$recurPaths[] = ABSPATH . PLUGINDIR;
			}
			else {
				if ( $recursive )
					$recurPaths[] = $path;
				else
					$nonRecurPaths[] = $path;
			}
			
			
			foreach ( (array) $nonRecurPaths as $path ) {
				$path = preg_replace( '/\/+$/', '', $path );
				$path .= '/';
				
				$DIR = @opendir( $path );
				
				while ( false !== ( $file = readdir( $DIR ) ) ) {
					if ( is_file( $path . $file ) && preg_match( '/\.php$/', $file ) ) {
						foreach ( (array) file( $path . $file ) as $num => $line ) {
							if ( preg_match( $query, $line, $matches ) ) {
								$results[] = array( 'file' => $path . $file, 'line' => $num, 'matches' => $matches );
							}
						}
					}
				}
			}
			
			foreach ( (array) $recurPaths as $path ) {
				$path = preg_replace( '/\/+$/', '', $path );
				$path .= '/';
				
				$DIR = @opendir( $path );
				
				while ( false !== ( $file = readdir( $DIR ) ) ) {
					if ( is_file( $path . $file ) && preg_match( '/\.php$/', $file ) && preg_match( $query, file_get_contents( $path . $file ) ) ) {
						foreach ( (array) file( $path . $file ) as $num => $line ) {
							if ( preg_match( $query, $line, $matches ) ) {
								$results[] = array( 'file' => $path . $file, 'line' => $num, 'matches' => $matches );
							}
						}
					}
					elseif ( is_dir( $path . $file ) && ( '.' !== $file ) && ( '..' !== $file ) ) {
						$newResults = $this->findFilesWithMatch( $query, $path . $file, true );
						
						foreach ( (array) $newResults as $match )
							$results[] = $match;
					}
				}
			}
			
			
			return $results;
		}
		
		function update_option_no_serialize( $option_name, $newvalue ) {
			global $wpdb;
			
			wp_protect_special_option( $option_name );
			
			$safe_option_name = $wpdb->escape( $option_name );
			$newvalue = sanitize_option( $option_name, $newvalue );
			
			// If the new and old values are the same, no need to update.
			$oldvalue = get_option( $safe_option_name );
			if ( $newvalue === $oldvalue )
				return false;
			
			if ( false === $oldvalue ) {
				add_option( $option_name, $newvalue );
				return true;
			}
			
			$notoptions = wp_cache_get( 'notoptions', 'options' );
			if ( is_array( $notoptions ) && isset( $notoptions[$option_name] ) ) {
				unset( $notoptions[$option_name] );
				wp_cache_set( 'notoptions', $notoptions, 'options' );
			}
			
			$_newvalue = $newvalue;
			//$newvalue = maybe_serialize( $newvalue );
			
			$alloptions = wp_load_alloptions();
			if ( isset( $alloptions[$option_name] ) ) {
				$alloptions[$option_name] = $newvalue;
				wp_cache_set( 'alloptions', $alloptions, 'options' );
			}
			else {
				wp_cache_set( $option_name, $newvalue, 'options' );
			}
			
			$wpdb->query( $wpdb->prepare( "UPDATE $wpdb->options SET option_value = %s WHERE option_name = %s", $newvalue, $option_name ) );
			if ( $wpdb->rows_affected == 1 ) {
				do_action( "update_option_{$option_name}", $oldvalue, $_newvalue );
				return true;
			}
			return false;
		}
		
		function showStatusMessage( $message ) {
			
?>
	<div id="message" class="updated fade"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function showErrorMessage( $message ) {
			
?>
	<div id="message" class="error"><p><strong><?php echo $message; ?></strong></p></div>
<?php
			
		}
		
		function getFileFromPost( $var, $uploads, $overwriteFile = false, $renameIfExists = true ) {
			$file = array();
			
			$overrides['overwriteFile'] = $overwriteFile;
			$overrides['renameIfExists'] = $renameIfExists;
			if ( ! empty( $uploads ) )
				$overrides['uploads'] = $uploads;
			
			$results = $this->handle_upload( $_FILES[$var], $overrides, $overwriteFile, $renameIfExists );
			
			if ( empty( $results['error'] ) ) {
				$file['path'] = $results['file'];
				$file['url'] = $results['url'];
				$file['originalName'] = preg_replace( '/\s+/', '_', $_FILES[$var]['name'] );
				$file['error'] = false;
			}
			else {
				$file['error'] = $results['error'];
			}
			
			return $file;
		}
		
		// I got this function from PlugInstaller (http://henning.imaginemore.de/pluginstaller/)
		// Thanks Henning Schaefer
		function getFileFromURL( $url, $uploads, $overwriteFile = false, $renameIfExists = true ) {
			$file = array();
			
			if ( preg_match( '/([^\/]+)$/', $url, $matches ) ) {
				$file['originalName'] = preg_replace( '/\s+/', '_', $matches[1] );
			}
			
			if ( false === ( $data = @file_get_contents( $url ) ) ) {
				$curl = curl_init( $url );
				curl_setopt( $curl, CURLOPT_HEADER, 0 );  // ignore any headers
				ob_start();  // use output buffering so the contents don't get sent directly to the browser
				curl_exec( $curl );  // get the file
				curl_close( $curl );
				$data = ob_get_contents();  // save the contents of the file into $file
				ob_end_clean();  // turn output buffering back off
			}
			
			$error = '';
			$message = '';
			
			if ( empty( $uploads ) )
				$uploads = wp_upload_dir();
			
			if ( false === $uploads['error'] ) {
				$filename = $this->unique_filename( $uploads['path'], $file['originalName'] );
				
				if ( file_exists( $uploads['path'] . '/' . $file['originalName'] ) ) {
					if ( $overwriteFile )
						$filename = $file['originalName'];
					elseif ( ! $renameIfExists )
						$error = "The file already exists. Since overwriting and renaming are not permitted, the file was not added.";
				}
				
				
				if ( '' === $error ) {
					if ( false === $this->writeFile( $uploads['path'] . '/' . $filename, $data ) ) {
						if ( $renameIfExists ) {
							$filename = $this->unique_filename( $uploads['path'], $file['originalName'] );
							
							if ( false === $this->writeFile( $uploads['path'] . '/' . $filename, $data ) )
								$error = sprintf( __('The uploaded file could not be moved to %s. Please check the folder and file permissions.' ), $uploads['path'] . '/' . $filename );
							else
								$message = 'Unable to overwrite existing file. Since renaming is permitted, the file was saved with a new name.';
						}
						else
							$error = sprintf( __('The uploaded file could not be moved to %s. Please check the folder and file permissions.' ), $uploads['path'] . '/' . $filename );
					}
					else
						$message = 'Original file overwritten.';
				}
				
				$stat = stat( dirname( $uploads['path'] . '/' . $filename ) );
				$perms = $stat['mode'] & 0000666;
				@ chmod( $uploads['path'] . '/' . $filename, $perms );
				
				if ( ! empty( $error ) )
					$file['error'] = $error;
				else {
					$file['path'] = $uploads['path'] . '/' . $filename;
					$file['url'] = $uploads['url'] . '/' . $filename;
					$file['error'] = false;
					$file['message'] = '';
				}
			}
			
			
			return $file;
		}
		
		function writeFile( $path, $data ) {
			if ( false !== ( $destination = @fopen( $path, 'w' ) ) ) {
				if ( fwrite( $destination, $data ) ) {
					@fclose( $destination );
					
					return true;
				}
				
				@fclose( $destination );
			}
			
			return false;
		}
		
		// Customized version of wp_unique_filename from v2.5.1 wp-includes/functions.php
		// This version doesn't sanitize the file name
		function unique_filename( $dir, $filename ) {
			$ext = $this->getExtension( $filename );
			$name = basename( $filename, ".{$ext}" );
			
			// edge case: if file is named '.ext', treat as an empty name
			if( $name === ".$ext" )
				$name = '';
			
			$number = '';
			
			if ( empty( $ext ) )
				$ext = '';
			else
				$ext = strtolower( ".$ext" );
			
			$filename = str_replace('%', '', $filename );
			
			while ( file_exists( $dir . '/' . $filename ) ) {
				if ( ! isset( $number ) ) {
					$number = 1;
					$filename = str_replace( $ext, $number . $ext, $filename );
				}
				else
					$filename = str_replace( $number . $ext, ++$number . $ext, $filename );
			}
			
			return $filename;
		}
		
		function getExtension( $filename ) {
			if ( preg_match( '/\.(tar\.\w+)$/' , $filename, $matches ) )
				return $matches[1];
			
			if ( preg_match( '/\.(\w+)$/', $filename, $matches ) )
				return $matches[1];
			
			return '';
		}
		
		// Customized version of wp_handle_upload from v2.5.1 wp-admin/includes/file.php
		function handle_upload( &$file, $overrides = false ) {
			// The default error handler.
			if (! function_exists( 'wp_handle_upload_error' ) ) {
				function wp_handle_upload_error( &$file, $message ) {
					return array( 'error'=>$message );
				}
			}
			
			// You may define your own function and pass the name in $overrides['upload_error_handler']
			$upload_error_handler = 'wp_handle_upload_error';
			
			// $_POST['action'] must be set and its value must equal $overrides['action'] or this:
			$action = 'wp_handle_upload';
			
			// Courtesy of php.net, the strings that describe the error indicated in $_FILES[{form field}]['error'].
			$upload_error_strings = array( false,
				__( "The uploaded file exceeds the <code>upload_max_filesize</code> directive in <code>php.ini</code>." ),
				__( "The uploaded file exceeds the <em>MAX_FILE_SIZE</em> directive that was specified in the HTML form." ),
				__( "The uploaded file was only partially uploaded." ),
				__( "No file was uploaded." ),
				__( "Missing a temporary folder." ),
				__( "Failed to write file to disk." ));
			
			// All tests are on by default. Most can be turned off by $override[{test_name}] = false;
			$test_form = true;
			$test_size = true;
			
			// If you override this, you must provide $ext and $type!!!!
			$test_type = true;
			$mimes = false;
			
			// Customizable overrides
			$uploads = wp_upload_dir();
			$overwriteFile = false;
			$renameIfExists = true;
			
			$message = '';
			
			// Install user overrides. Did we mention that this voids your warranty?
			if ( is_array( $overrides ) )
				extract( $overrides, EXTR_OVERWRITE );
			
			// A correct form post will pass this test.
			if ( $test_form && (!isset( $_POST['action'] ) || ($_POST['action'] != $action ) ) )
				return $upload_error_handler( $file, __( 'Invalid form submission.' ));
			
			// A successful upload will pass this test. It makes no sense to override this one.
			if ( $file['error'] > 0 )
				return $upload_error_handler( $file, $upload_error_strings[$file['error']] );
			
			// A non-empty file will pass this test.
			if ( $test_size && !($file['size'] > 0 ) )
				return $upload_error_handler( $file, __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini.' ));
			
			// A properly uploaded file will pass this test. There should be no reason to override this one.
			if (! @ is_uploaded_file( $file['tmp_name'] ) )
				return $upload_error_handler( $file, __( 'Specified file failed upload test.' ));
			
			// A correct MIME type will pass this test. Override $mimes or use the upload_mimes filter.
			if ( $test_type ) {
				$wp_filetype = wp_check_filetype( $file['name'], $mimes );
				
				extract( $wp_filetype );
				
				if ( ( !$type || !$ext ) && !current_user_can( 'unfiltered_upload' ) )
					return $upload_error_handler( $file, __( 'File type does not meet security guidelines. Try another.' ));
				
				if ( !$ext )
					$ext = ltrim(strrchr($file['name'], '.'), '.');
				
				if ( !$type )
					$type = $file['type'];
			}
			
			// A writable uploads dir will pass this test. Again, there's no point overriding this one.
			if ( false !== $uploads['error'] )
				return $upload_error_handler( $file, $uploads['error'] );
			
			$uploads['path'] = untrailingslashit( $uploads['path'] );
			$uploads['url'] = untrailingslashit( $uploads['url'] );
			
			
			$filename = $this->unique_filename( $uploads['path'], $file['name'] );
			
			if ( file_exists( $uploads['path'] . '/' . $file['name'] ) ) {
				if ( $overwriteFile )
					$filename = $file['name'];
				elseif ( ! $renameIfExists )
					return $upload_error_handler( $file, "The file already exists. Since overwriting and renaming are not permitted, the file was not added." );
			}
			
			
			if ( false === @ move_uploaded_file( $file['tmp_name'], $uploads['path'] . '/' . $filename ) ) {
				if ( $overwriteFile ) {
					$filename = $this->unique_filename( $uploads['path'], $file['name'] );
					
					if ( false === @ move_uploaded_file( $file['tmp_name'], $uploads['path'] . '/' . $filename ) )
						return $upload_error_handler( $file, sprintf( __('The uploaded file could not be moved to %s. Please check the folder and file permissions.' ), $uploads['path'] ) );
					else
						$message = 'Unable to overwrite existing file. Since renaming is permitted, the file was saved with a new name.';
				}
				else
					return $upload_error_handler( $file, sprintf( __('The uploaded file could not be moved to %s. Please check the folder and file permissions.' ), $uploads['path'] ) );
			}
			
			$stat = stat( dirname( $uploads['path'] . '/' . $filename ) );
			$perms = $stat['mode'] & 0000666;
			@ chmod( $uploads['path'] . '/' . $filename, $perms );
			
			// Compute the URL
			$url = $uploads['url'] . '/' . $filename;
			
			$return = apply_filters( 'wp_handle_upload', array( 'file' => $uploads['path'] . '/' . $filename, 'url' => $url, 'message' => $message, 'error' => false ) );
			
			return $return;
		}
		
		function extractArchive( $file, $forceExtractionFolder = true ) {
			$extensions = array( 'zip', 'tar', 'gz', 'tar.gz', 'tgz', 'tar.bz2', 'tbz' );
			$extension = $this->getExtension( $file['path'] );
			
			if ( in_array( $extension, (array) $extensions ) ) {
				ini_set( 'include_path', dirname(__FILE__) . '/pear' );
				require_once( 'File/Archive.php' );
				
				if ( is_callable( array( 'File_Archive', 'extract' ) ) && is_callable( array( 'File_Archive', 'read' ) ) ) {
					$backupCWD = getcwd();
					
					$path = dirname( $file['path'] );
					
					chdir( $path );
					
					$source = basename( $file['path'] ) . '/';
					
					if ( $forceExtractionFolder )
						$destination = basename( $file['path'], ".{$extension}" );
					else
						$destination = $path;
					
					$error = File_Archive::extract( $source, $destination );
					
					chdir( $backupCWD );
					
					if ( PEAR::isError( $error ) )
						return array( 'extracted' => false, 'error' => 'Extraction failed: ' . $error->getMessage() );
					
					return array( 'destination' => path_join( $path, $destination ), 'extracted' => true, 'error' => false );
				}
				
				return array( 'extracted' => false, 'error' => 'Unable to execute File_Archive::extract' );
			}
			
			return array( 'extracted' => false, 'error' => false );
		}

		/**
		 * @param string $sValue
		 * @return string|void
		 */
		function esc_attr( $sValue ) {
			return ( function_exists( 'esc_attr' ) ? esc_attr( $sValue ) : attribute_escape( $sValue ) );
		}

		/**
		 * @param string $sValue
		 * @return mixed|string
		 */
		function esc_html( $sValue ) {
			return ( function_exists( 'esc_html' ) ? esc_html( $sValue ) : wp_specialchars( $sValue ) );
		}
	}
}


if ( class_exists( 'WPDeveloperAssistant' ) ) {
	$wpDeveloperAssistant = new WPDeveloperAssistant();
}

?>