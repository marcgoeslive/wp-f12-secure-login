<div class="f12-panel">
    <div class="f12-panel__header">
        <h2><?php echo __( 'Zugriffszeiten', "f12-secure-login" ); ?></h2>
        <p>
			<?php echo __( 'Sie können individuelle Zugriffszeiten für den Benutzer festlegen. Diese überschreiben die allgemeinen Zugriffszeiten.', "f12-secure-login" ); ?>
        </p>
        <p>
	        <?php echo __( 'Wenn Sie keine individuellen Zeiten festlegen möchten oder Sie die Zeiten zurücksetzen möchten können Sie die Felder einfach leeren.', "f12-secure-login" ); ?>
        </p>
    </div>
    <div class="f12-panel">
        <table class="f12-table">
            <tr>
                <td>
                    <strong>Von (DD-MM-YYYY)</strong>
                </td>
                <td>
                    <strong>Bis einschließlich (DD-MM-YYYY)</strong>
                </td>
            </tr>
            <tr>
                <td>
                    <input type="date" name="access-start" value="<?php echo $args["access-start"];?>"> -
                </td>
                <td>
                    <input type="date" name="access-end" value="<?php echo $args["access-end"];?>">
                </td>
            </tr>
        </table>
    </div>
</div>