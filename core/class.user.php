<?php

namespace f12_secure_login\core {
	if ( ! defined( "ABSPATH" ) ) {
		exit;
	}

	/**
	 * Class user
	 * Different views for the user screen. Allows to unlock users and to see how many
	 * days till the password needs to reset.
	 *
	 * @package f12_secure_login\core
	 */
	class user {
		public function __construct() {
			add_filter( "manage_users_custom_column", [ $this, "user_columns_row" ], 10, 4 );
			add_filter( "manage_users_columns", [ $this, "user_columns" ] );
			add_action( "restrict_manage_users", [ $this, "add_status_filter" ] );
			add_action( 'pre_get_users', [ $this, 'filter_users_by_status' ] );

			// adding custom user fields for access time
			add_action( "edit_user_profile", [ $this, "add_user_profil_fields" ] );
			add_action( "edit_user_profile_update", [ $this, "save_user_profil_fields" ] );

			// admin init
			add_action( "admin_init", [ $this, "admin_init" ] );

			if ( isset( $_GET["status"] ) ) {
				if ( $_GET["status"] == "user_unlocked" ) {
					add_action( "admin_notices", [ $this, "notice_user_unlocked" ] );
				}
				if ( $_GET["status"] == "user_locked" ) {
					add_action( "admin_notices", [ $this, "notice_user_locked" ] );
				}
				if ( $_GET["status"] == "user_unban" ) {
					add_action( "admin_notices", [ $this, "notice_user_unban" ] );
				}
				if ( $_GET["status"] == "user_ban" ) {
					add_action( "admin_notices", [ $this, "notice_user_ban" ] );
				}
			}
		}

		public function save_user_profil_fields( $user_id ) {
			if ( ! current_user_can( "edit_user", $user_id ) ) {
				return false;
			}

			if ( ! isset( $_POST["access-start"] ) || ! isset( $_POST["access-end"] ) ) {
				delete_user_meta( $user_id, "forge12_secure_login_access_times" );

				return true;
			}

			$date_start = \DateTime::createFromFormat( "Y-m-d", $_POST["access-start"] );
			$date_end   = \DateTime::createFromFormat( "Y-m-d", $_POST["access-end"] );

			if ( ! $date_start || ! $date_end || $date_start > $date_end ) {
				delete_user_meta( $user_id, "forge12_secure_login_access_times" );

				return true;
			}

			update_user_meta( $user_id, "forge12_secure_login_access_times", array(
				"access-start" => $_POST["access-start"],
				"access-end"   => $_POST["access-end"]
			) );
		}

		/**
		 * @param \WP_User $user
		 */
		public function add_user_profil_fields( $user ) {
			$access_times = get_user_meta( $user->ID, "forge12_secure_login_access_times", true );

			$args = array(
				"access-start" => "",
				"access-end"   => ""
			);

			if ( is_array( $access_times ) ) {
				$args = array(
					"access-start" => $access_times["access-start"],
					"access-end"   => $access_times["access-end"]
				);
			}


			require_once( plugin_dir_path( __FILE__ ) . "../templates/tpl.user-profile.php" );
		}

		/**
		 * Add a custom filter for the Status of the user (locked / unlocked)
		 *
		 * @param string $which | bottom or top
		 */
		public function add_status_filter( $which ) {
			if ( $which == "bottom" ) {
				return;
			}

			$filter   = isset( $_GET["forge12-status"] ) ? $_GET["forge12-status"] : - 1;
			$filter_0 = $filter == 0 ? "selected=\"selected\"" : "";
			$filter_1 = $filter == 1 ? "selected=\"selected\"" : "";
			$filter_2 = $filter == 2 ? "selected=\"selected\"" : "";

			echo '<input type="submit" style="float:right;" class="button" value="Filter">';
			echo '<select name="forge12-status" style="margin-left:10px; float:right;">
                    <option value="2" ' . $filter_2 . '>' . __( "Alle", "f12-secure-login" ) . '</option>
                    <option value="1" ' . $filter_1 . '>' . __( "Gesperrt", "f12-secure-login" ) . '</option>
                    <option value="0" ' . $filter_0 . '>' . __( "Nicht gesperrt", "f12-secure-login" ) . '</option>
                </select>';
		}

		/**
		 * Add a custom query for the filter which will only return locked, unlocked or all users.
		 *
		 * @param $query
		 */
		public function filter_users_by_status( $query ) {
			global $pagenow;

			if ( is_admin() &&
			     'users.php' == $pagenow &&
			     isset( $_GET['forge12-status'] )
			) {
				$status = $_GET['forge12-status'];

				if ( $status == 1 ) {
					$meta_query = array(
						"relation" => "OR",
						array(
							'key'   => 'forge12_login_disabled',
							'value' => 1
						),
						array(
							'key'   => 'forge12_login_ban',
							'value' => 1
						)
					);
					$query->set( 'meta_query', $meta_query );
				} elseif ( $status == 0 ) {
					$meta_query = array(
						array(
							'key'     => 'forge12_login_disabled',
							'compare' => 'NOT EXISTS'
						),
						array(
							'key'     => 'forge12_login_ban',
							'compare' => 'NOT EXISTS'
						)
					);
					$query->set( 'meta_query', $meta_query );
				}
			}
		}

		/**
		 * Hook the admin_init to allow redirects before anything
		 * has been passed to the browser.
		 */
		public function admin_init() {
			if ( isset( $_GET["status"] ) && isset( $_GET["user_id"] ) ) {
				switch ( $_GET["status"] ) {
					case "unlock":
						$this->unlock( $_GET["user_id"] );
						break;
					case "lock":
						$this->lock( $_GET["user_id"] );
						break;
					case "ban":
						$this->ban( $_GET["user_id"] );
						break;
					case "unban":
						$this->unban( $_GET["user_id"] );
						break;
				}
			}
		}

		/**
		 * Lock a user by the given id.
		 */
		private function lock( $user_id ) {
			if(!current_user_can("edit_users")){
				return;
			}

			/* @var $user \WP_User */
			$user = get_user_by( "id", $user_id );

			if ( ! $user ) {
				return;
			}

			add_user_meta( $user_id, "forge12_login_disabled", 1 );

			wp_redirect( "users.php?status=user_locked" );
		}

		/**
		 * Unlock a user by the given id.
		 *
		 * @param $user_id
		 */
		private function unlock( $user_id ) {
			if(!current_user_can("edit_users")){
				return;
			}

			/* @var $user \WP_User */
			$user = get_user_by( 'id', $user_id );

			if ( ! $user ) {
				return;
			}

			delete_user_meta( $user->ID, "forge12_login_disabled" );

			wp_redirect( "users.php?status=user_unlocked" );
		}

		/**
		 * Ban a user. This will disable the login permanent.
		 * Can only be unbanned by the admin.
		 */
		private function ban( $user_id ) {
		    if(!current_user_can("edit_users")){
		        return;
            }

			/* @var $user \WP_User */
			$user = get_user_by( "id", $user_id );

			if ( ! $user ) {
				return;
			}

			add_user_meta( $user_id, "forge12_login_ban", 1 );

			wp_redirect( "users.php?status=user_ban" );
		}

		/**
		 * Unban a user. This will remove the permanent ban by
		 * the administrator. This will not affect the "lock"
		 *
		 * @param $user_id
		 */
		private function unban( $user_id ) {
			if(!current_user_can("edit_users")){
				return;
			}

			/* @var $user \WP_User */
			$user = get_user_by( 'id', $user_id );

			if ( ! $user ) {
				return;
			}

			delete_user_meta( $user->ID, "forge12_login_ban" );

			wp_redirect( "users.php?status=user_unban" );
		}

		/**
		 * Shows an update notifcation if the user has been locked
		 */
		public function notice_user_locked() {
			?>
            <div class="notice notice-success is-dismissible"
                 style="margin-left:0; margin-top:30px; margin-bottom:30px;">
                <p><?php echo __( "Benutzer erfolgreich gesperrt", "forge12_secure_login" ); ?></p>
            </div>
			<?php
		}

		/**
		 * Shows an update notifcation if the user has been unlocked
		 */
		public function notice_user_unlocked() {
			?>
            <div class="notice notice-success is-dismissible"
                 style="margin-left:0; margin-top:30px; margin-bottom:30px;">
                <p><?php echo __( "Benutzer erfolgreich entsperrt", "forge12_secure_login" ); ?></p>
            </div>
			<?php
		}

		/**
		 * Shows an update notifcation if the user has been permanently locked
		 */
		public function notice_user_ban() {
			?>
            <div class="notice notice-success is-dismissible"
                 style="margin-left:0; margin-top:30px; margin-bottom:30px;">
                <p><?php echo __( "Benutzer erfolgreich gesperrt", "forge12_secure_login" ); ?></p>
            </div>
			<?php
		}

		/**
		 * Shows an update notifcation if the user has been permanently unlocked.
		 */
		public function notice_user_unban() {
			?>
            <div class="notice notice-success is-dismissible"
                 style="margin-left:0; margin-top:30px; margin-bottom:30px;">
                <p><?php echo __( "Benutzer erfolgreich entsperrt", "forge12_secure_login" ); ?></p>
            </div>
			<?php
		}

		/**
		 * Add an additional column for the Options.
		 *
		 * @param $column
		 *
		 * @return mixed
		 */
		public function user_columns( $column ) {
			$column["forge12-user-locked"]    = __( "Optionen", "f12-secure-login" );
			$column["forge12-password-reset"] = __( "Passwort geändert", "f12-secure-login" );

			return $column;
		}

		/**
		 * Adding the output for the different columns for each dataset.
		 *
		 * @param string $val The default value before changing anything
		 * @param string $column_name the name of the column (unique name)
		 * @param string $user_id the user id for the current row.
		 *
		 * @return string|void
		 */
		public function user_columns_row( $val, $column_name, $user_id ) {
			switch ( $column_name ) {
				case 'forge12-user-locked':
					$login_status         = get_user_meta( $user_id, "forge12_login_disabled" );
					$ban_status           = get_user_meta( $user_id, "forge12_login_ban" );
					$user                 = get_user_by( "id", $user_id );
					$is_valid_access_time = application::validate_access_time( $user );

					$status               = $is_valid_access_time ? empty( $ban_status ) ? empty( $login_status ) ? __("Freigeschaltet","f12-secure-login") : __("Temporär gesperrt (Passwort)","f12-secure-login") : __("Permanent gesperrt","f12-secure-login") : __("Zugriffszeit abgelaufen","f12-secure-login");

					$message = "<p><strong>Status:</strong> " . $status . "</p>";

					// Banned (By Admin)
					if ( empty( $ban_status ) ) {
						$message .= " <a href='users.php?status=ban&user_id=" . $user_id . "'>" . __( "Permanent Sperren", "f12-secure-login" ) . "</a> | ";

						// Locked (Password wrong X times or by admin)
						if ( empty( $login_status ) ) {
							$message .= "<a href='users.php?status=lock&user_id=" . $user_id . "'>" . __( "Temporär Sperren", "f12-secure-login" ) . "</a>";
						} else {
							$message .= "<a href='users.php?status=unlock&user_id=" . $user_id . "'>" . __( "Entsperren", "f12-secure-login" ) . "</a> ";
						}
					} else {
						$message .= " <a href='users.php?status=unban&user_id=" . $user_id . "'>" . __( "Entsperren", "f12-secure-login" ) . "</a>";
					}

					return $message;
					break;
				case 'forge12-password-reset':
					$data = get_user_meta( $user_id, "forge12-password-date", true );
					if ( empty( $data ) ) {
						return "N/A";
					} else {
						$datetime1 = new \DateTime( $data );
						$datetime2 = new \DateTime( date( "Y-m-d H:i:s", time() ) );

						$diff = $datetime1->diff( $datetime2 );

						if ( $diff->d == 0 ) {
							return __( "< 24h ", "f12-secure-login" );
						} else if ( $diff->d == 1 ) {
							return __( "< 48h ", "f12-secure-login" );
						} else {
							return $datetime1->format( "d.m.Y" ) . " " . sprintf( __( "(%s Tage)", "f12-secure-login" ), $diff->d );
						}
					}
					break;
				default:
			}

			return $val;
		}

	}
}