<div class="meta-page f12-page-settings f12-secure-login">
    <h1><?php echo __( "Gesperrte Benutzer", "f12-secure-login" ); ?></h1>
    <p>
		<?php echo __( "Hier können Sie alle gesperrten Benutzer einsehen & entsperren", "f12-secure-login" ); ?>
    </p>

    <form action="<?php echo esc_url( admin_url( "admin-post.php" ) ); ?>" method="post" name="f12-secure-login">
        <input type="hidden" name="action" value="f12_login_settings_save">
		<?php echo $args["nonce"]; ?>
        <div class="f12-panel">
            <div class="f12-panel__header">
                <h2><?php echo __( 'Einstellungen', "f12-secure-login" ); ?></h2>
                <p>
					<?php echo __( 'Einstellungen für das Login', "f12-secure-login" ); ?>
                </p>
            </div>
            <div class="f12-panel">
                <table class="f12-table">
                    <tr>
                        <td class="label" style="width:300px;">
                            <label>
								<?php echo __( 'Maximale Anmeldeversuche ?', "f12-secure-login" ); ?>
                            </label>
                            <p>
								<?php echo __( 'Geben Sie an wieviele Anmeldeversuche durchgeführt werden dürfen bevor der Besucher gesperrt wird.', "f12-secure-login" ); ?>
                            </p>
                        </td>
                        <td>
                            <input type="text" name="login-attemps" value="<?php echo $args["login-attemps"]; ?>">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <input type="submit" name="save" value="<?php echo __( "Speichern" ); ?>"/>
    </form>
</div>