<div class="f12-panel">
    <div class="f12-panel__header">
        <h2><?php echo __( 'Zugangszeiten Einstellungen', "f12-secure-login" ); ?></h2>
        <p>
			<?php echo __( 'Hier können Sie den Zugriff auf Ihr System zeitlich beschränken. Geben Sie bei <strong>Von</strong> ein Startdatum ein und bei <strong>Bis</strong> das gewünschte Enddatum. Der Benutzer
kann sich nur in diesem Zeitraum am System anmelden.', "f12-secure-login" ); ?>
        </p>
    </div>
    <div class="f12-panel">

        <table class="f12-table">
            <tr>
                <th class="label" style="width:300px;">
                    <label>
						<?php echo __( "Gruppe", "f12-secure-login" ); ?>
                    </label>
                </th>
                <th class="label" style="width:300px;">
                    <label>
						<?php echo __( "Von (DD-MM-YYYY)", "f12-secure-login" ); ?>
                    </label>
                </th>
                <th class="label" style="width:300px;">
                    <label>
						<?php echo __( "Bis einschließlich (DD-MM-YYYY)", "f12-secure-login" ); ?>
                    </label>
                </th>
            </tr>
			<?php
			if ( isset( $args["access-roles"] ) && is_array( $args["access-roles"] ) ) :
				foreach ( $args["access-roles"] as $role ):
					?>
                    <tr>
                        <td class="label" style="width:300px;">
							<?php echo $role["name"]; ?>
                            <?php if($role["access-status"] == 0):
                            ?>
                                (<span style="color:#ff0000"><?php echo $role["access-status-txt"];?></span>)
                            <?php
                            endif;
                            ?>
                        </td>
                        <td class="label" style="width:300px;">
                            <input type="date" name="access-<?php echo $role["id"]; ?>-start"
                                   value="<?php echo $role["access-start"]; ?>"/>
                        </td>
                        <td class="label" style="width:300px;">
                            <input type="date" name="access-<?php echo $role["id"]; ?>-end"
                                   value="<?php echo $role["access-end"]; ?>"/>
                        </td>

                    </tr>
				<?php
				endforeach;
			endif; ?>
        </table>
    </div>
</div>