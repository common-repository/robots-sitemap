<div class="tab vo3da-metabox__body">
	<p><?php _e( 'Link to your Robots file', 'vo3da-robots-sitemap' ); ?>: <a
				href="<?php echo is_ssl() ? 'https://' : 'http://';
				echo $options['current_domain'] . '/robots.txt'; ?>" id="robots_link"
				target="_blank">robots.txt</a></p>
	<h2><?php _e( 'Select domains to edit robots.txt', 'vo3da-robots-sitemap' ); ?></h2>
	<form action="" method="post" id="robots_form">
		<select class="robots-select2" name="robots_domain">
			<option value="*"><?php _e( 'Select all domains', 'vo3da-robots-sitemap' ); ?></option>
			<?php foreach ( $options['domains'] as $domain ) {
				if ( ! empty( $domain ) ) { ?>
					<option value="<?php echo $domain ?>" <?php selected( 0 === strcmp( $domain, $options['current_domain'] ), true ) ?>><?php echo $domain ?></option>
				<?php }
			} ?>
		</select>
		<label class="input-area mt-15">
			<textarea class="form-input" id="robots_text_place" name="content" cols="70" rows="15"
					placeholder="<?php _e( 'Domain is not selected or empty robots.txt file', 'vo3da-robots-sitemap' ); ?>"><?php echo $options['robots_content']; ?></textarea>
		</label>
		<div class="flex flex-wrap mt-15">
			<div class="w-30">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="robots_options[disable_robots]"
							name="robots_options[disable_robots]"
							value="1" <?php checked( 1 == $options['robots_options']['disable_robots'], true ); ?>>
					<label for="robots_options[disable_robots]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Disable robots', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>
			<div class="w-50">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="robots_options[fake_enable]"
							name="robots_options[fake_enable]"
							value="1" <?php checked( 1 == $options['robots_options']['fake_enable'], true ); ?>>
					<label for="robots_options[fake_enable]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Hide robots.txt from fake bots', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>
		</div>

		<div class="btn-wrap">
			<input type="submit" class="btn btn-blue" id="robots_save"
					value="<?php _e( 'Save changes', 'vo3da-robots-sitemap' ); ?>"/>
		</div>
	</form>
	<?php if ( true === $options['has_mirrors'] ) { ?>
		<h2><?php _e( 'Select mirrors to bulk replace part of the text in robots.txt', 'vo3da-robots-sitemap' ); ?></h2>
		<form action="" method="post" id="robots_replacement_form">
			<select class="robots-select2-multi" name="replacement_domains[]" id="robot_mass_replace_select" multiple>
				<option value="*"><?php _e( 'Select all domains', 'vo3da-robots-sitemap' ); ?></option>
				<?php foreach ( $options['domains'] as $domain ) {
					if ( ! empty( $domain ) ) { ?>
						<option value="<?php echo $domain ?>" <?php selected( 0 === strcmp( $domain, $options['current_domain'] ), true ) ?>><?php echo $domain ?></option>
					<?php }
				} ?>
			</select>
			<div class="flex">
				<div class="w-50">
					<label class="input-area mt-15">
                    <textarea class="form-input" id="robots_text_replace" name="replace"
		                    cols="40" rows="10"
		                    placeholder="<?php _e( 'Search for', 'vo3da-robots-sitemap' ); ?>"></textarea>
					</label>
				</div>
				<div class="w-50">
					<label class="input-area mt-15">
                    <textarea class="form-input" id="robots_text_replacement" name="replacement"
		                    cols="40" rows="10"
		                    placeholder="<?php _e( 'Replace with', 'vo3da-robots-sitemap' ); ?>"></textarea>
					</label>
				</div>
			</div>
			<div class="btn-wrap">
				<input type="submit" class="btn btn-blue" id="robots_replace_save"
						value="<?php _e( 'Replace', 'vo3da-robots-sitemap' ); ?>"/>

			</div>
		</form>

	<?php } ?>
</div>