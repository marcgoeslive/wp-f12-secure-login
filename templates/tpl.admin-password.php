<div class="f12-panel">
    <div class="f12-panel__header">
        <h2><?php echo __( 'Passwort Einstellungen', "f12-secure-login" ); ?></h2>
        <p>
			<?php echo __( 'Hier können Sie Einstellungen für das Passwort vornehmen.', "f12-secure-login" ); ?>
        </p>
    </div>
    <div class="f12-panel">
        <table class="f12-table">
            <tr>
                <td class="label" style="width:300px;">
                    <label>
						<?php echo __( 'Minimale Passwort länge?', "f12-secure-login" ); ?>
                    </label>
                    <p>
						<?php echo __( 'Geben Sie die mindestlänge des Passworts an.', "f12-secure-login" ); ?>
                    </p>
                </td>
                <td>
                    <input type="checkbox" name="password-length-activate" value="1"
                           <?php if($args["password-length-activate"] == 1) echo "checked=\"checked\"";?> />
                    <input type="number" name="password-length" value="<?php echo $args["password-length"]; ?>">
                </td>
            </tr>
            <tr>
                <td class="label" style="width:300px;">
                    <label>
						<?php echo __( 'Minimale Passwortstärke?', "f12-secure-login" ); ?>
                    </label>
                    <p>
						<?php echo __( 'Definieren Sie wie stark das Passwort mindestens sein muss damit der Benutzer es verwenden kann.', "f12-secure-login" ); ?>
                    </p>
                </td>
                <td>
                    <select name="password-strength">
						<?php
						$password_strenght = array(
							__( "Deaktiviert", "f12-secure-login" ),
							__( "Sehr Leicht", "f12-secure-login" ),
							__( "Leicht", "f12-secure-login" ),
							__( "Normal", "f12-secure-login" ),
							__( "Stark", "f12-secure-login" )
						);

						for ( $i = 0; $i < count( $password_strenght ); $i ++ ):
							if ( $i == $args["password-strength"] ) :
								?>
                                <option value="<?php echo $i; ?>"
                                        selected="selected"><?php echo $password_strenght[ $i ]; ?></option>
							<?php
							else :
								?>
                                <option value="<?php echo $i; ?>"><?php echo $password_strenght[ $i ]; ?></option>
							<?php
							endif;
						endfor;
						?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="label" style="width:300px;">
                    <label>
						<?php echo __( 'Passwort Gültigkeit?', "f12-secure-login" ); ?>
                    </label>
                    <p>
						<?php echo __( 'Geben Sie an wie lange das Passwort maximal gültig ist bevor es ersetzt werden muss.', "f12-secure-login" ); ?>
                    </p>
                </td>
                <td>
                    <input type="checkbox" name="password-valid-days-activate" value="1"
                           <?php if($args["password-valid-days-activate"] == 1) echo "checked=\"checked\"";?> />
                    <input type="number" value="<?php echo $args["password-valid-days"]; ?>" placeholder="180"
                           name="password-valid-days"/>
                </td>
            </tr>
        </table>
    </div>

    <div class="f12-panel__header">
        <h2><?php echo __( 'Passwort Einstellungen für einzelne Rollen', "f12-secure-login" ); ?></h2>
        <p>
			<?php echo __( 'Hier können Sie Einstellungen für einzelne Rollen vornehmen.', "f12-secure-login" ); ?>
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
						<?php echo __( "Min. Anzahl an Zeichen", "f12-secure-login" ); ?>
                    </label>
                </th>
                <th class="label" style="width:300px;">
                    <label>
						<?php echo __( "Min. Stärke des Passworts", "f12-secure-login" ); ?>
                    </label>
                </th>
                <th class="label" style="width:300px;">
                    <label>
						<?php echo __( "Gültigkeit", "f12-secure-login" ); ?>
                    </label>
                </th>
            </tr>
			<?php
			if ( isset( $args["password-roles"] ) && is_array( $args["password-roles"] ) ) :
				foreach ( $args["password-roles"] as $role ):
					?>
                    <tr>
                        <td class="label" style="width:300px;">
							<?php echo $role["name"]; ?>
                        </td>
                        <td class="label" style="width:300px;">
                            <input type="checkbox" name="password-<?php echo $role["id"]; ?>-length-activate" value="1"
                                <?php if ( $role["password-length-activate"] == 1 ) { echo "checked=\"checked\""; } ?>
                            >
                            <input type="number" name="password-<?php echo $role["id"]; ?>-length"
                                   value="<?php echo $role["password-length"]; ?>"/>
                        </td>
                        <td class="label" style="width:300px;">
                            <select name="password-<?php echo $role["id"]; ?>-strength">
								<?php
								$password_strenght = array(
									__( "Deaktiviert", "f12-secure-login" ),
									__( "Sehr Leicht", "f12-secure-login" ),
									__( "Leicht", "f12-secure-login" ),
									__( "Normal", "f12-secure-login" ),
									__( "Stark", "f12-secure-login" )
								);

								for ( $i = 0; $i < count( $password_strenght ); $i ++ ):
									if ( $i == $role["password-strength"] ) :
										?>
                                        <option value="<?php echo $i; ?>"
                                                selected="selected"><?php echo $password_strenght[ $i ]; ?></option>
									<?php
									else :
										?>
                                        <option value="<?php echo $i; ?>"><?php echo $password_strenght[ $i ]; ?></option>
									<?php
									endif;
								endfor;
								?>
                            </select>
                        </td>
                        <td class="label" style="width:300px;">
                            <input type="checkbox" name="password-<?php echo $role["id"]; ?>-valid-days-activate" value="1"
	                            <?php if ( $role["password-valid-days-activate"] == 1 ) { echo "checked=\"checked\""; } ?>>
                            <input type="number" name="password-<?php echo $role["id"]; ?>-valid-days"
                                   value="<?php echo $role["password-valid-days"]; ?>"/>
                        </td>

                    </tr>
				<?php
				endforeach;
			endif; ?>
        </table>
    </div>
</div>