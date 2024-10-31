<?php echo $nonce_field; ?>
<table class="form-table">
	<tbody>
	<tr class="form-field">
		<th scope="row" style="vertical-align: middle">
			<label for="prioriti"><?php _e('Priority', 'vo3da-robots-sitemap'); ?></label>
		</th>
		<td>
			<select id="prioriti" name="prioriti">
				<option value="0"><?php _e('Default', 'vo3da-robots-sitemap'); ?></option>
				<?php foreach ( range( 0.0, 1.0, 0.1 ) as $number ) {
					$number = number_format($number, 1, '.', ',');
					echo '<option value="' . $number . '" ' . selected( $number == $meta['prioriti'], true ) . '>' . $number . '</option>';
				} ?>
			</select>
		</td>
	</tr>
	<tr>
		<th scope="row" style="vertical-align: middle">
			<label for="frequencies"><?php _e('Scan period', 'vo3da-robots-sitemap'); ?></label>
		</th>
		<td>
			<select  id="frequencies" name="frequencies">
				<option value="0"><?php _e('Default', 'vo3da-robots-sitemap'); ?></option>
				<?php
				foreach ( $frequencies as $key => $value ) { ?>
					<option value="<?php echo $key ?>" <?php selected( 0 === strcasecmp( $key, $meta[ 'frequencies' ] ), true ) ?>><?php _e($value, 'vo3da-robots-sitemap'); ?></option>';
				<?php }
				?>
			</select>
		</td>
	</tr>

	<tr>
		<td></td>
		<td>
			<label>
				<input type="checkbox" name="excludeurl" value="1" <?php echo $meta['excludeurl'] ? 'checked="checked"' : '';  ?> > <?php _e('Exclude from sitemap', 'vo3da-robots-sitemap'); ?>
			</label>
		</td>
	</tr>

	</tbody>
</table>
