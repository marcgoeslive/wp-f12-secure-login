<?php

namespace f12_secure_login\core {
	@session_start();
	/**
	 * Security Check
	 */
	if ( ! defined( "ABSPATH" ) ) {
		exit( "this is not the page you are looking for" );
	}

	/**
	 * Dependencies, this is the same mechanism wordpress is currently using, ported to
	 * PHP. As a result of the different dictionaries and behaviours of each language the
	 * result sometimes differ. The PHP version is just used as a fallback if the current
	 * user do not have any JavaScript version installed.
	 *
	 * @link https://github.com/dropbox/zxcvbn
	 */
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/ScorerInterface.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Scorer.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Searcher.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matcher.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Zxcvbn.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/MatchInterface.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/Match.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/Bruteforce.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/DateMatch.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/DictionaryMatch.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/DigitMatch.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/L33tMatch.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/RepeatMatch.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/SequenceMatch.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/SpatialMatch.php" );
	require_once( plugin_dir_path( __FILE__ ) . "../vendor/zxcvbn/Matchers/YearMatch.php" );

	use ZxcvbnPhp\Zxcvbn;

	/**
	 * Class application
	 * Handles all interactions between customers / users and the login process.
	 *
	 * @package f12_secure_login\core
	 */
	class application {
		/**
		 * application constructor.
		 * Initializing all filter and action hooks to wordpress.
		 */
		public function __construct() {
			// Increase the counter on failed logins or lock the user to secure the login
			add_filter( "authenticate", [ $this, "wp_authenticate" ], 30, 3 );
			// send a message after the user has been locked
			add_action( "forge12_secure_login_after_user_locked", [ $this, "send_mail" ], 10, 1 );
			// load the domain for the language
			add_action( "plugins_loaded", [ $this, "load_textdomain" ] );
			// redirect to password reset screen after password expired
			add_action( "wp_login", [ $this, "wp_login" ], 10, 2 );
			// unlock the user after password reset
			add_action( "after_password_reset", [ $this, "after_password_reset" ], 10, 2 );
			// set a new password date after password change
			add_action( "after_password_reset", [ $this, "update_password_date" ], 10, 2 );
			add_filter( 'send_password_change_email', [ $this, "send_password_change_email" ], 10, 3 );
			// validate the password with the vendor library
			add_action( 'validate_password_reset', [ $this, "validate_password_reset" ], 10, 2 );
			// change the names for the javascript strength meter (level1-4)
			add_action( "login_enqueue_scripts", [ $this, "strength_meter_localize_script" ] );
			add_filter( "login_message", [ $this, "login_password_expired" ] );
		}

		/**
		 * WordPress hooking function to display a custom error message if required.
		 *
		 * @param string $message the default message from wordpress.
		 *
		 * @return string   Returns either the default message or the customized message.
		 */
		public function login_password_expired( $message ) {

			if ( ! isset( $_GET["expired"] ) || ! isset( $_GET["action"] ) || $_GET["expired"] != "true" || $_GET["action"] != "lostpassword" ) {
				return $message;
			}

			if ( ! isset( $_SESSION["tmp_user"] ) || ! ( $_SESSION["tmp_user"] instanceof \WP_User ) ) {
				return __( "Ein Fehler ist aufgetreten.", "f12-secure-login" );
			}

			$user = $_SESSION["tmp_user"];
			/** @var \WP_User $user */

			if ( ! isset( $user->roles[0] ) ) {
				return __( "Ein Fehler ist aufgetreten.", "f12-secure-login" );
			}

			$role         = $user->roles[0];
			$role_options = $this->get_options_by_role( $role );

			$limit = $role_options["password-valid-days"];

			return "<p class='message'>" . sprintf( __( "Sie müssen Ihr Passwort alle %d Tage ändern", "f12-secure-login" ), $limit ) . "</p>";
		}

		/**
		 * WordPress hooking function which is called whenever the user has submitted
		 * the login. This function checks if the password for the current user role
		 * has been expired. If the password is expired the function will automatically
		 * logout the user and display redirect to the "lostpassword" page with a customized
		 * "expired" flag to ensure the error message is displayed @see login_password_expired()
		 *
		 * This function calls "exit()" and "wp_safe_redirect" on case the password is expired.
		 *
		 * @param \WP_User $username The \WP_User object with all informations about the user.
		 * @param string $user The username which is used to log into the system.
		 *
		 */
		public function wp_login( $username, $user ) {
			if ( ! $user ) {
				return;
			}

			if ( ! $this->password_is_expired( $user ) ) {
				return;
			}

			wp_destroy_all_sessions();
			$_SESSION["tmp_user"] = $user;

			wp_safe_redirect( add_query_arg( array(
				'action'  => 'lostpassword',
				'expired' => 'true'
			), wp_login_url() ), 302 );
			exit;
		}

		/**
		 * Helper function to check if the password for the
		 * given user has been expired.
		 *
		 * Checks that are done to validate:
		 *
		 * # "password-valid-days-activate" is enabled for the role
		 * # "current date" - "last password change" < "password-validate-days"
		 *
		 * Uses Datetime to compare the dates.
		 *
		 * @param \WP_User $user The WordPress User Object with all required information about the user.
		 *
		 * @return bool     Returns true if the password is expired.
		 */
		private function password_is_expired( $user ) {
			if ( ! isset( $user->roles[0] ) ) {
				return false;
			}

			$role         = $user->roles[0];
			$role_options = $this->get_options_by_role( $role );


			if ( ! $role_options["password-valid-days-activate"] ) {
				return false;
			}

			$days                 = $role_options["password-valid-days"];
			$password_change_date = get_user_meta( $user->ID, "forge12-password-date", true );
			$current_date         = date( "Y-m-d H:i:s", time() );

			// Check the date
			$datetime1 = new \DateTime( $password_change_date );
			$datetime2 = new \DateTime( $current_date );

			$diff = $datetime1->diff( $datetime2 )->d;

			if ( $diff < $days ) {
				return false;
			}

			return true;
		}

		/**
		 * WordPress hooking function to change the default names of the strength
		 * meter. Use locotranslate to update the names in feature.
		 */
		public function strength_meter_localize_script() {
			wp_localize_script( 'password-strength-meter', 'pwsL10n', array(
				'unknown'  => __( 'Passwort eingeben', 'f12-secure-login' ),
				'short'    => __( 'Level 1', 'f12-secure-login' ),
				'bad'      => __( 'Level 2', 'f12-secure-login' ),
				'good'     => __( 'Level 3', 'f12-secure-login' ),
				'strong'   => __( 'Level 4', 'f12-secure-login' ),
				'mismatch' => __( 'Passwörter stimmen nicht überein.', 'f12-secure-login' )
			) );

			if ( ! wp_script_is( "f12-strength-meter" ) ) {
				wp_enqueue_script( "f12-strength-meter", plugin_dir_url( __FILE__ ) . "../assets/js/f12-strength-meter.js", [ "jquery" ], false, true );
			}
		}

		/**
		 * Return all rules by the given role. If  the given role didn't exist
		 * the default rules will be returned
		 *
		 * @param string $role The name of the role.
		 *
		 * @return array            Returns an Array with the general options and secondary array
		 *                          named "password-roles" with all role specific settings.
		 *
		 *                          Array =>
		 *                          [
		 *                              "password-length-activate"      => integer (0|1)
		 *                              "password-length"               => integer (>0)
		 *                              "password-strength"             => integer (0-4)
		 *                              "password-valid-days-activate"  => integer (0|1)
		 *                              "password-valid-days"           => integer (>0)
		 *                              "password-roles" => Array
		 *                              [
		 *                                  $role => Array
		 *                                  [
		 *                                     "password-length-activate"      => integer (0|1)
		 *                                     "password-length"               => integer (>0)
		 *                                     "password-strength"             => integer (0-4)
		 *                                     "password-valid-days-activate"  => integer (0|1)
		 *                                     "password-valid-days"           => integer (>0)
		 *                                  ],
		 *                                  ....
		 *                              ]
		 *                          ]
		 */
		private function get_options_by_role( $role ) {
			$options = get_option( "forge12_secure_login" );

			$options_role = [
				"password-length-activate"     => 0,
				"password-length"              => 8,
				"password-strength"            => 0,
				"password-valid-days-activate" => 0,
				"password-valid-days"          => 180
			];
			if ( ! isset( $options ) || empty( $options ) || ! is_array( $options ) ) {
				return $options_role;
			}

			// Default rules
			$options_role["password-length-activate"]     = $options["password-length-activate"];
			$options_role["password-length"]              = $options["password-length"];
			$options_role["password-strength"]            = $options["password-strength"];
			$options_role["password-valid-days-activate"] = $options["password-valid-days-activate"];
			$options_role["password-valid-days"]          = $options["password-valid-days"];

			if ( isset( $options["password-roles"] ) && isset( $options["password-roles"][ $role ] ) ) {

				$options = $options["password-roles"][ $role ];

				if ( $options["password-length-activate"] == 1 ) {
					$options_role["password-length-activate"] = $options["password-length-activate"];
					$options_role["password-length"]          = $options["password-length"];
				}

				if ( $options["password-strength"] != 0 ) {
					$options_role["password-strength"] = $options["password-strength"];
				}

				if ( $options["password-valid-days-activate"] == 1 ) {
					$options_role["password-valid-days-activate"] = $options["password-valid-days-activate"];
					$options_role["password-valid-days"]          = $options["password-valid-days"];
				}
			}

			return $options_role;
		}

		/**
		 * Validate the length of the password and return the boolean value
		 *
		 * @param string $password
		 * @param int $length
		 *
		 * @return bool
		 */
		private function check_password_length( $password, $length ) {
			if ( mb_strlen( stripslashes( $password ), "utf-8" ) >= $length ) {
				return true;
			}

			return false;
		}

		/**
		 * Check the password strenght and return if the requirements are fullfilled
		 * This class uses the lib from https://github.com/bjeavons/zxcvbn-php which is
		 * used for wordpress (afak)
		 *
		 * @param string $password
		 * @param int $strength
		 *
		 * @return bool
		 */
		private function check_password_strength( $password, $strength ) {
			// If not getting the score from the form (post) we need to validate it with the php  version of
			// zxcvbn to ensure minimal password protection. the zxcvbn is not completely fitting with the result
			// of the javascript libary, thats the we use it only as a fallback
			if ( ! isset( $_POST ) || ! isset( $_POST["f12-strength-meter"] ) ) {
				$zxcvbn = new Zxcvbn();
				$score  = $zxcvbn->passwordStrength( $password )["score"];
			} else {
				$score = (int) $_POST["f12-strength-meter"];
			}

			if ( ! is_numeric( $score ) ) {
				return false;
			}

			if ( $score < $strength ) {
				return false;
			}

			return true;
		}

		/**
		 * validate the password before the reset
		 *
		 * @param \WP_Error $error
		 * @param \WP_User $user
		 */
		function validate_password_reset( $error, $user ) {
			if ( ! isset( $_POST["pass1"] ) ) {
				return;
			}

			if ( ! $user ) {
				$error->add( "error", __( "Benutzer existiert nicht" ) );
			}

			$pass1 = $_POST["pass1"];

			$role         = $user->roles[0];
			$role_options = $this->get_options_by_role( $role );

			if ( ! $this->check_password_length( $pass1, $role_options["password-length"] ) ) {
				$error->add( "error", sprintf( __( "Das Passwort muss mindestens %d Zeichen lang sein.", "f12-secure-login" ), $role_options["password-length"] ) );
			}

			if ( ! $this->check_password_strength( $pass1, $role_options["password-strength"] ) ) {
				$error->add( "error", sprintf( __( "Die Passwortstärke muss mindestens Level %d sein.", "f12-secure-login" ), $role_options["password-strength"] ) );
			};
		}

		/**
		 * Used to check if the password has been changed in the admin panel
		 *
		 * @param bool $send
		 * @param \WP_User $user
		 * @param array $user_data
		 *
		 * @return bool
		 */
		function send_password_change_email( $send, $user, $user_data ) {
			if ( null == $user ) {
				return $send;
			}
			$this->update_password_date( $user, "" );

			return $send;
		}

		/**
		 * used to update the date the password of the user has been changed.
		 * this field will be used to validate if the password needs to be changed
		 * because of the defined runtime.
		 *
		 * @param \WP_User $user
		 * @param string $password
		 *
		 */
		function update_password_date( $user, $password ) {
			if ( null == $user ) {
				return;
			}

			if ( $user instanceof \WP_User ) {
				update_user_meta( $user->ID, "forge12-password-date", date( "Y-m-d H:i:s", time() ) );
			} else {
				if ( is_array( $user ) && isset( $user["ID"] ) ) {
					update_user_meta( $user["ID"], "forge12-password-date", date( "Y-m-d H:i:s", time() ) );
				}
			}
		}

		/**
		 * Allow Loco Translate to change our translations
		 */
		function load_textdomain() {
			load_plugin_textdomain( "f12-secure-login", false, basename( dirname( dirname( __FILE__ ) ) ) );
		}

		/***
		 * Called after the password has been reset  by the user
		 *
		 * @param \WP_User $user
		 * @param string $password
		 */
		public function after_password_reset( $user, $password ) {
			/* @var $user \WP_User */
			if ( ! $user ) {
				return;
			}

			$this->unlock( $user->user_login );
			$this->remove_failed_logins( $user->user_login );
		}

		/**
		 * Unlock the user if he has been locked
		 *
		 * @param string $username
		 */
		public function unlock( $username ) {
			$option = get_option( "forge12_secure_login" );

			if ( $option["login-unlock"] != 2 ) {
				return;
			}

			/* @var $user \WP_User */
			$user = get_user_by( "login", $username );

			if ( ! $user ) {
				return;
			}

			delete_user_meta( $user->ID, "forge12_login_disabled" );
		}

		/**
		 * Increase the counter for the failed login to ensure the user will be locked
		 * after X attempts
		 *
		 * @param string $username
		 */
		private function add_failed_login( $username ) {
			/* @var $user \WP_User */
			$user = get_user_by( "login", $username );
			$option = get_option( "forge12_secure_login" );

			// if option = 0 then we do not do an locking.
			if($option["login-attemps"] == 0){
				return;
			}

			if ( ! $user ) {
				return;
			}

			$data = maybe_unserialize( get_user_meta( $user->ID, "forge12_login_failed", true ) );

			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$new_data = array();

			foreach ( $data as $key => $value ) {

				if ( ( time() - 900 ) <= $value ) {
					$new_data[] = $value;
				}
			}
			$new_data[] = time();

			update_user_meta( $user->ID, "forge12_login_failed", maybe_serialize( $new_data ) );

			if ( count( $new_data ) >= $option["login-attemps"] ) {
				update_user_meta( $user->ID, "forge12_login_disabled", 1 );
				delete_user_meta( $user->ID, "forge12_login_failed" ); // Remove all timestamps because the account is locked.

				do_action( "forge12_secure_login_after_user_locked", $user );
			}
		}

		/**
		 * Send a mail to the system administrator about the locked user.
		 *
		 * @param $user \WP_User
		 */
		public function send_mail( $user ) {
			$option = get_option( "forge12_secure_login" );

			if ( ! isset( $option["login-lock-email"] ) || $option["login-lock-email"] == 0 ) {
				return;
			}

			$email  = get_bloginfo( 'admin_email' );
			$header = array( "Content-Type: text/html; charset=UTF-8" );
			if ( ! wp_mail( $email, __( "Benutzer wurde gesperrt", "f12-secure-login" ), wpautop( sprintf( __( "Der Benutzer mit dem Namen %s wurde gesperrt", "f12-secure-login" ), $user->user_login ) ), $header ) ) {
				error_log( "Couldn't send mail for locked user" );
			}
		}

		/**
		 * Remove all failed logins after the user has been locked.
		 *
		 * @param string $username
		 */
		public function remove_failed_logins( $username ) {
			$user = get_user_by( "login", $username );

			if ( ! $user ) {
				return;
			}

			delete_user_meta( $user->ID, "forge12_login_failed" ); // Remove all timestamps after success login
		}

		/**
		 * Checks the Access time for the user and his role.
		 *
		 * First we check the individual roles. They have a higher priority. That means
		 * even if a role dosn't have any access anymore, it is possible that an individual
		 * user can still access if the access times have been set for him.
		 *
		 * If there are no access rules defined for the individual user, we test the roles.
		 * The user can have multiple roles. Each role can have different access times.
		 * Therefor the default return value is false. As long as there is any role that
		 * has access to the system the validate_access_time will return true and grant
		 * access to the page.
		 *
		 * @param \WP_User $user
		 *
		 * @return boolean  return false if the user do not have any access anymore.
		 */
		public static function validate_access_time($user){
			$option = get_option( "forge12_secure_login", array() );

			if(!isset($option["access-roles"])){
				return true;
			}

			$access_roles = $option["access-roles"];
			$user_roles   = $user->roles;

			// default return value
			$return = false;

			// check user access roles
			$access_time = get_user_meta($user->ID, "forge12_secure_login_access_times", true);

			if(is_array($access_time)){
				$date_now   = \DateTime::createFromFormat( "Y-m-d", date("Y-m-d",time()) );
				$date_start = \DateTime::createFromFormat( "Y-m-d", $access_time["access-start"] );
				$date_end   = \DateTime::createFromFormat( "Y-m-d", $access_time["access-end"] );

				if($date_start <= $date_now && $date_end >= $date_now){
					return true;
				}else{
					return false;
				}
			}

			foreach($user_roles as $key => $value){
				if (isset($access_roles[$value]) && ! empty( $access_roles[$value]["access-start"] ) && ! empty( $access_roles[$value]["access-end"] ) ) {
					$date_now   = \DateTime::createFromFormat( "Y-m-d", date("Y-m-d",time()) );
					$date_start = \DateTime::createFromFormat( "Y-m-d", $access_roles[$value]["access-start"] );
					$date_end   = \DateTime::createFromFormat( "Y-m-d", $access_roles[$value]["access-end"] );

					if ( $date_start <= $date_now && $date_end >= $date_now ) {
						$return = true;
					}
				} else {
					// fallback if there are no rules set for the specified role.
					$return = true;
				}
			}

			return $return;
		}

		/**
		 * Authenticate the user to allow the login.
		 * Throws an error message if the user has been locked and ensures that he could not login
		 * anymore.
		 *
		 * @param \WP_User|\WP_error $user
		 * @param string $username
		 * @param string $password
		 *
		 * @return \WP_Error|\WP_User
		 */
		public function wp_authenticate( $user/* @var $user \WP_User */, $username, $password ) {
			$tmp_user = $user;
			/* @var $tmp_user \WP_User */
			if ( get_class( $user ) == "WP_Error" ) {
				// If the login failed, we get an Object from type WP_Error.
				$this->add_failed_login( $username );
				$user = get_user_by( "login", $username );
			}

			if ( ! $user ) {
				return $tmp_user;
			}

			$is_valid_access_time = application::validate_access_time($user);

			if(!$is_valid_access_time){
				return new \WP_Error( 403, __( "Your access period has ended. Please contact the helpdesk. ", "f12-secure-login" ) );
			}

			$banned = get_user_meta( $user->ID, "forge12_login_ban", true );

			if ( $banned ) {
				return new \WP_Error( 403, __( "Your account has been banned. Please contact the helpdesk. ", "f12-secure-login" ) );
			}

			$disabled = get_user_meta( $user->ID, "forge12_login_disabled", true );

			if ( $disabled ) {
				return new \WP_Error( 403, __( "Your account has been blocked. Please contact the helpdesk. ", "f12-secure-login" ) );
			}

			if ( get_class( $tmp_user ) == "WP_User" ) {
				$this->remove_failed_logins( $tmp_user->user_login );
			}

			return $tmp_user;
		}
	}
}