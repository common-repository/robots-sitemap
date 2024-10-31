<?php echo $nonce_field; ?>
<p>
    <label><?php _e('Priority', 'vo3da-robots-sitemap'); ?><br>
        <select name="prioriti">
            <option value="0"><?php _e('Default', 'vo3da-robots-sitemap'); ?></option>
			<?php foreach ( range( 0.0, 1.0, 0.1 ) as $number ) {
				$number = number_format($number, 1, '.', ',');
				echo '<option value="' . $number . '" ' . selected( $number == $meta['prioriti'], true ) . '>' . $number . '</option>';
			} ?>
        </select>
    </label>
</p>
<p>
    <label><?php _e('Scan period', 'vo3da-robots-sitemap'); ?><br>
        <select name="frequencies">
            <option value="0"><?php _e('Default', 'vo3da-robots-sitemap'); ?></option>
			<?php
			foreach ( $frequencies as $key => $value ) { ?>
                <option value="<?php echo $key ?>" <?php selected( 0 === strcasecmp( $key, $meta[ 'frequencies' ] ), true ) ?>><?php _e($value, 'vo3da-robots-sitemap'); ?></option>';
			<?php }
			?>
        </select>
    </label>
</p>
<p>
    <label>
        <input type="checkbox" name="excludeurl" value="1" <?php echo $meta['excludeurl'] ? 'checked="checked"' : '';  ?> > <?php _e('Exclude from sitemap', 'vo3da-robots-sitemap'); ?>
    </label>
</p>