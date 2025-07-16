<?php

namespace f12_secure_login\core {
	if ( ! defined( "ABSPATH" ) ) {
		exit;
	}

	class admin {
		private $user_hook_page = "";

		/**
		 * The Constructor.
		 * Initializing the actions
		 */
		public function __construct() {
			add_action( "admin_menu", [ $this, "admin_menu" ] );
			add_action( "admin_init", [ $this, "register_options" ] );
			add_action( "admin_post_f12_login_settings_save", [ $this, "save" ] );
			add_action( "forge12_secure_login_settings_save", [ $this, "save_password_roles" ] );
			add_action( "forge12_secure_login_settings_save", [ $this, "save_access_roles" ] );

			if ( isset( $_GET["status"] ) && $_GET["status"] == "update" ) {
				add_action( "admin_notices", [ $this, "notice_update" ] );
			}
		}

		/**
		 * A notice which is displayed after the admin updates the settings.
		 */
		public function notice_update() {
			?>
            <div class="notice notice-success is-dismissible"
                 style="margin-left:0; margin-top:30px; margin-bottom:30px;">
                <p><?php echo __( "Änderungen gespeichert", "forge12_secure_login" ); ?></p>
            </div>
			<?php
		}

		/**
		 * Called to save the settings. This will update the WordPress options
		 * with the new data. After saving the user will be redirected
		 * to the same page including a status flag for the notification.
		 */
		public function save() {
			$is_valid_nonce = ( isset( $_POST['forge12_secure_login'] ) && wp_verify_nonce( $_POST['forge12_secure_login'], basename( __FILE__ ) ) ) ? true : false;

			if ( ! $is_valid_nonce ) {
				return;
			}

			$options = get_option( "forge12_secure_login" );

			if ( isset( $_POST["login-attemps"] ) && $_POST["login-attemps"] >= 0 ) {
				$options["login-attemps"] = $_POST["login-attemps"];
			}

			if ( isset( $_POST["login-unlock"] ) && ! empty( $_POST["login-unlock"] ) ) {
				$options["login-unlock"] = $_POST["login-unlock"];
			}

			if ( isset( $_POST["login-lock-email"] ) ) {
				$options["login-lock-email"] = $_POST["login-lock-email"];
			} else {
				$options["login-lock-email"] = 0;
			}

			if ( isset( $_POST["password-length-activate"] ) ) {
				$options["password-length-activate"] = $_POST["password-length-activate"];
			} else {
				$options["password-length-activate"] = 0;
			}

			if ( isset( $_POST["password-length"] ) ) {
				$options["password-length"] = $_POST["password-length"];
			} else {
				$options["password-length"] = 8;
			}

			if ( isset( $_POST["password-strength"] ) ) {
				$options["password-strength"] = $_POST["password-strength"];
			} else {
				$options["password-strength"] = 0;
			}

			if ( isset( $_POST["password-valid-days-activate"] ) ) {
				$options["password-valid-days-activate"] = $_POST["password-valid-days-activate"];
			} else {
				$options["password-valid-days-activate"] = 0;
			}

			if ( isset( $_POST["password-valid-days"] ) ) {
				$options["password-valid-days"] = $_POST["password-valid-days"];
			} else {
				$options["password-valid-days"] = 180;
			}

			update_option( "forge12_secure_login", $options );

			do_action( "forge12_secure_login_settings_save" );

			wp_redirect( "users.php?page=forge12_secure_login&status=update" );
		}

		/**
		 * Saving all custom password role settings for each wordpress role.
		 * This will add customized login options for Admin, Editor, Author ...
		 */
		public function save_password_roles() {
			// load default options
			$options = get_option( "forge12_secure_login" );
			// update roles dynamic
			$roles        = $this->get_roles();
			$options_role = [];
			foreach ( $roles as $role ) {
				$id                          = $role["id"];
				$options_role[ $role["id"] ] = [
					"password-length-activate"     => 0,
					"password-length"              => 8,
					"password-strength"            => 0,
					"password-valid-days-activate" => 0,
					"password-valid-days"          => 180
				];

				if ( isset( $_POST[ "password-" . $id . "-length-activate" ] ) && isset( $_POST[ "password-" . $id . "-length" ] ) ) {
					if ( $_POST[ "password-" . $id . "-length-activate" ] == 1 ) {
						$options_role[ $id ]["password-length-activate"] = 1;
						$options_role[ $id ]["password-length"]          = $_POST[ "password-" . $id . "-length" ];
					}
				}

				if ( isset( $_POST[ "password-" . $id . "-strength" ] ) ) {
					$options_role[ $id ]["password-strength"] = $_POST[ "password-" . $id . "-strength" ];
				}

				if ( isset( $_POST[ "password-" . $id . "-valid-days-activate" ] ) && isset( $_POST[ "password-" . $id . "-valid-days" ] ) ) {
					if ( $_POST[ "password-" . $id . "-valid-days-activate" ] == 1 ) {
						$options_role[ $id ]["password-valid-days-activate"] = 1;
						$options_role[ $id ]["password-valid-days"]          = $_POST[ "password-" . $id . "-valid-days" ];
					}
				}
			}
			$options["password-roles"] = $options_role;

			update_option( "forge12_secure_login", $options );
		}

		/**
		 * Saving all custom access role settings for each wordpress role.
		 * This will add customized access times for Admin, Editor, Author ...
		 */
		public function save_access_roles() {
			// load default options
			$options = get_option( "forge12_secure_login" );
			// update roles dynamic
			$roles        = $this->get_access_roles();
			$options_role = [];
			foreach ( $roles as $role ) {
				$id = $role["id"];

				$options_role[ $role["id"] ] = [
					"access-start" => "",
					"access-end"   => "",
				];

				if ( isset( $_POST[ "access-" . $id . "-start" ] ) && isset( $_POST[ "access-" . $id . "-end" ] ) ) {

					if ( ! empty( $_POST[ "access-" . $id . "-start" ] ) && ! empty( $_POST[ "access-" . $id . "-end" ] ) ) {
						// check dates
						$date_start = \DateTime::createFromFormat( "Y-m-d", $_POST[ "access-" . $id . "-start" ] );
						$date_end   = \DateTime::createFromFormat( "Y-m-d", $_POST[ "access-" . $id . "-end" ] );

						// check if the dates are true and if the end time is in the future.
						if ( $date_start && $date_end && $date_end > $date_start ) {
							$options_role[ $id ]["access-start"] = $_POST[ "access-" . $id . "-start" ];
							$options_role[ $id ]["access-end"]   = $_POST[ "access-" . $id . "-end" ];
						}
					}
				}
			}
			$options["access-roles"] = $options_role;

			update_option( "forge12_secure_login", $options );
		}

		/**
		 * Register the default options of the plugins. Necessary for the
		 * installation to ensure that all required parameters are already
		 * defined.
		 *
		 * The dynamic value for the array "password-roles" is not created
		 * at the installation. Each role can be customized, therefor it is
		 * not possible to set them early.
		 *
		 * The "password-roles" will be added on the first saving of the plugin.
		 */
		public function register_options() {
			add_option( "forge12_secure_login", [
				"login-attemps"                => 3,
				"login-unlock"                 => 3,
				"login-lock-email"             => 0,
				// 1 = Passwort zurücksetzen, 2 = Adminpanel, 3 = Passwort zurücksetzen und Adminpanel
				"password-length"              => 8,
				"password-length-activate"     => 0,
				"password-strength"            => 0,
				"password-valid-days-activate" => 0,
				"password-valid-days"          => 180
			] );
		}

		/**
		 * WoordPress hooking function to load  all necessary javascript and css files for the plugin.
		 */
		public function enqueue_scripts() {
			if ( ! wp_style_is( "f12" ) ) {
				wp_enqueue_style( "f12", plugin_dir_url( __FILE__ ) . "/../../assets/css/f12.css" );
			}

			if ( ! wp_script_is( "f12-admin-script" ) ) {
				wp_enqueue_script( "f12-admin-script", plugin_dir_url( __FILE__ ) . "../assets/js/f12-admin-script.js", array( "jquery" ) );
			}
		}

		/**
		 * WordPress hooking function to add a custom admin page to the WordPress backend.
		 */
		public function admin_menu() {
			$this->user_hook_page = add_users_page( __( "Forge12 Secure Login", "f12-secure-login" ), __( "Login Einstellungen", "f12-secure-login" ), "manage_options", "forge12_secure_login", [
				$this,
				"admin_page"
			] );

			add_action( "admin_print_styles-" . $this->user_hook_page, [ $this, "enqueue_scripts" ] );
		}

		/**
		 * This function loads all settings for the different roles which are
		 * added to the current WordPress instance. It will also return
		 * default values if there are no settings defined for the roles yet.
		 *
		 * By default all restrictions are disabled for the unique roles.
		 *
		 * @param array $option The Array with the registered options
		 *
		 * @see register_options() for details.
		 *
		 * @return array    Return an Array with the given structure:
		 *                  Array [
		 *                              0 =>
		 *                                  [
		 *                                    "name" => "Administrator",
		 *                                    "id" => "administrator",
		 *                                    "password-strength" => -1,
		 *                                    "password-length" => -1,
		 *                                    "password-valid-days" => -1
		 *                                  ],
		 *                              1 => ...
		 *                              2 => ...
		 *                       ]
		 */
		private function get_roles( $option = array() ) {
			$ret = array();

			$roles = get_editable_roles();
			foreach ( $roles as $id => $role ) {
				if ( isset( $option["password-roles"] ) && isset( $option["password-roles"][ $id ] ) ) {
					$role_options = $option["password-roles"][ $id ];
					$ret[]        = [
						"id"                           => $id,
						"name"                         => $role["name"],
						"password-strength"            => isset( $role_options["password-strength"] ) ? $role_options["password-strength"] : 0,
						"password-length-activate"     => isset( $role_options["password-length-activate"] ) ? $role_options["password-length-activate"] : 0,
						"password-length"              => isset( $role_options["password-length"] ) ? $role_options["password-length"] : 8,
						"password-valid-days-activate" => isset( $role_options["password-valid-days-activate"] ) ? $role_options["password-valid-days-activate"] : 0,
						"password-valid-days"          => isset( $role_options["password-valid-days"] ) ? $role_options["password-valid-days"] : 180
					];
				} else {
					// Fallback return values
					$ret[] = [
						"id"                           => $id,
						"name"                         => $role["name"],
						"password-strength"            => 0,
						"password-length-activate"     => 0,
						"password-length"              => 0,
						"password-valid-days-activate" => 0,
						"password-valid-days"          => 0
					];
				}
			}

			return $ret;
		}

		/**
		 * This function loads all settings for the different access roles which are
		 * added to the current WordPress instance. It will also return
		 * default values if there are no settings defined for the roles yet.
		 *
		 * @param array $option The Array with the registered options
		 *
		 * @see register_options() for details.
		 *
		 * @return array    Return an Array with the given structure:
		 *                  Array [
		 *                              0 =>
		 *                                  [
		 *                                    "name" => "Administrator",
		 *                                    "id" => "administrator",
		 *                                    "access-start" => -1,
		 *                                    "access-end" => -1
		 *                                  ],
		 *                              1 => ...
		 *                              2 => ...
		 *                       ]
		 */
		public static function get_access_roles( $option = array() ) {
			$ret = array();

			$roles = get_editable_roles();
			foreach ( $roles as $id => $role ) {
				if ( isset( $option["access-roles"] ) && isset( $option["access-roles"][ $id ] ) ) {
					$role_options = $option["access-roles"][ $id ];

					// Status
					$start = isset( $role_options["access-start"] ) ? $role_options["access-start"] : "";
					$end   = isset( $role_options["access-end"] ) ? $role_options["access-end"] : "";

					$date_now   = \DateTime::createFromFormat( "Y-m-d", date("Y-m-d",time()) );
					$date_start = \DateTime::createFromFormat( "Y-m-d", $start );
					$date_end   = \DateTime::createFromFormat( "Y-m-d", $end );

					$status_txt = __( "Freigegeben", "f12-secure-login" );
					$status     = 1;

					if ($date_start && $date_end && ( $date_now > $date_end || $date_now < $date_start ) ){
						$status_txt = __( "Gesperrt", "f12-secure-login" );
						$status     = 0;
					}

					$ret[] = [
						"id"                => $id,
						"name"              => $role["name"],
						"access-start"      => isset( $role_options["access-start"] ) ? $role_options["access-start"] : "",
						"access-end"        => isset( $role_options["access-end"] ) ? $role_options["access-end"] : "",
						"access-status-txt" => $status_txt,
						"access-status"     => $status
					];
				} else {
					// Fallback return values
					$ret[] = [
						"id"                => $id,
						"name"              => $role["name"],
						"access-start"      => "",
						"access-end"        => "",
						"access-status-txt" => __( "Freigegeben", "f12-secure-login" ),
						"access-status"     => 1
					];
				}
			}

			return $ret;
		}

		/**
		 * WordPress hooking function to display the admin page. Loading all necessary
		 * variables and including / printing the template file.
		 */
		public function admin_page() {
			$option = get_option( "forge12_secure_login", array() );

			// Default parameter
			$args = array(
				"login-attemps"    => isset( $option["login-attemps"] ) ? $option["login-attemps"] : 3,
				"login-unlock"     => isset( $option["login-unlock"] ) ? $option["login-unlock"] : 3,
				"login-lock-email" => isset( $option["login-lock-email"] ) ? $option["login-lock-email"] : 0,
				"nonce"            => wp_nonce_field( basename( __FILE__ ), "forge12_secure_login" )
			);

			// Parameter for Passwort settings
			$args["password-roles"]               = $this->get_roles( $option );
			$args["password-length"]              = isset( $option["password-length"] ) ? $option["password-length"] : 8;
			$args["password-length-activate"]     = isset( $option["password-length-activate"] ) ? $option["password-length-activate"] : 8;
			$args["password-strength"]            = isset( $option["password-strength"] ) ? $option["password-strength"] : 0;
			$args["password-valid-days"]          = isset( $option["password-valid-days"] ) ? $option["password-valid-days"] : 180;
			$args["password-valid-days-activate"] = isset( $option["password-valid-days-activate"] ) ? $option["password-valid-days-activate"] : 0;

			$args["access-roles"] = admin::get_access_roles( $option );

			require_once( plugin_dir_path( __FILE__ ) . "../templates/tpl.admin.php" );
		}
	}
}