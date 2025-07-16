<div class="meta-page f12-page-settings f12-secure-login">
    <h1><?php echo __( "Login", "f12-secure-login" ); ?></h1>
    <p>
		<?php echo __( "Hier können Sie die Einstellungen für den Login-Prozess definieren.", "f12-secure-login" ); ?>
    </p>

    <form action="<?php echo esc_url( admin_url( "admin-post.php" ) ); ?>" method="post" name="f12-secure-login">
        <input type="hidden" name="action" value="f12_login_settings_save">
		<?php echo $args["nonce"]; ?>

        <div class="f12-admin">
            <div class="f12-admin-sidebar">
                <ul>
                    <li>
                        <a href="#admin-login" class="active"><?php echo __("Login","f12-secure-login");?></a>
                    </li>
                    <li>
                        <a href="#admin-password"><?php echo __("Passwort Einstellungen","f12-secure-login");?></a>
                    </li>
                    <li>
                        <a href="#admin-access"><?php echo __("Zugriffszeiten Einstellungen","f12-secure-login");?></a>
                    </li>
                </ul>
            </div>
            <div class="f12-admin-content">
                <div id="admin-login" class="active">
				    <?php include( "tpl.admin-login.php" ); ?>
                </div>
                <div id="admin-password">
		            <?php include( "tpl.admin-password.php" ); ?>
                </div>
                <div id="admin-access">
		            <?php include( "tpl.admin-access.php" ); ?>
                </div>
            </div>
        </div>

        <input type="submit" name="save" value="<?php echo __( "Speichern" ); ?>"/>
    </form>
</div>