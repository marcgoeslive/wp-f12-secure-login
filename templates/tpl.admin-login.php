
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
			<tr>
				<td class="label" style="width:300px;">
					<label>
						<?php echo __( 'Freischaltung?', "f12-secure-login" ); ?>
					</label>
					<p>
						<?php echo __( 'Wählen Sie aus wie der Besucher entsperrt werden kann.', "f12-secure-login" ); ?>
					</p>
				</td>
				<td>
					<select name="login-unlock">
						<option value="1" <?php if ( $args["login-unlock"] == 1 ) {
							echo "selected=\"selected\"";
						} ?>><?php echo __("Administrator","f12-secure-login");?>
						</option>
						<option value="2" <?php if ( $args["login-unlock"] == 2 ) {
							echo "selected=\"selected\"";
						} ?>><?php echo __("Passwort zurücksetzen & Administrator","f12-secure-login");?>
						</option>
					</select>
				</td>
			</tr>
			<tr>
				<td class="label" style="width:300px;">
					<label>
						<?php echo __( 'E-Mail Benachrichtigung?', "f12-secure-login" ); ?>
					</label>
					<p>
						<?php echo __( 'Möchten Sie eine Benachrichtigung erhalten wenn ein Besucher gesperrt wird?', "f12-secure-login" ); ?>
					</p>
				</td>
				<td>
					<input type="checkbox" name="login-lock-email" value="1" <?php if($args["login-lock-email"] == 1) echo "checked=\"checked\"";?>/>
				</td>
			</tr>
		</table>
	</div>
</div>