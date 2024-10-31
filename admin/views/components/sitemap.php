<?php
/**
 * @var array $options
 */

?>
<div class="tab vo3da-metabox__body">
	<p><?php _e( 'Link to your sitemap ', 'vo3da-robots-sitemap' ); ?>: <a
				href="<?php echo $options['sitemap_name']; ?>" id="sitemap_link"
				target="_blank">sitemap.xml</a></p>

	<form action="options.php" method="POST" id="sitemap_form">
		<?php echo $options['settings_field']; ?>
		<table class="sitemap-table">
			<thead>
			<tr>
				<td><?php _e( 'Type of page', 'vo3da-robots-sitemap' ); ?></td>
				<td><?php _e( 'Priority', 'vo3da-robots-sitemap' ); ?></td>
				<td><?php _e( 'Scan period', 'vo3da-robots-sitemap' ); ?></td>
				<td><?php _e( 'Show in sitemap', 'vo3da-robots-sitemap' ); ?></td>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $options['fields'] as $field => $label ) { ?>
				<tr>
					<td><?php echo is_array( $label ) ? $label['label'] : $label; ?></td>
					<td>
						<label>
							<select class="select select--numbers"
									name="custom_sitemap_options[<?php echo $field; ?>_prioriti]">
								<?php
								foreach ( range( 0, 1, 0.1 ) as $number ) {
									echo '<option value="' . $number . '" ' . selected( round( $number, 1 ) === doubleval( $options['options'][ $field . '_prioriti' ] ), true ) . '>' . $number . '</option>';
								}
								?>
							</select>
						</label>
					</td>
					<td>
						<label>
							<select class="select select--frequencies"
									name="custom_sitemap_options[<?php echo $field; ?>_frequencies]">
								<?php
								foreach ( $options['frequencies'] as $key => $value ) { ?>
									<option value="<?php echo $key ?>" <?php selected( 0 === strcasecmp( $key, $options['options'][ $field . '_frequencies' ] ), true ) ?>><?php _e( $value, 'vo3da-robots-sitemap' ); ?></option>';
								<?php }
								?>
							</select>
						</label>
					</td>
					<td>
						<div class="vo3da-checkbox">
							<input type="checkbox" id="custom_sitemap_options[<?php echo $field; ?>_enable]"
									name="custom_sitemap_options[<?php echo $field; ?>_enable]"
									value="1" <?php checked( 1 == $options['options'][ $field . '_enable' ], true ); ?>>
							<label for="custom_sitemap_options[<?php echo $field; ?>_enable]"> <span
										class="toggle"></span> </label>
						</div>
					</td>
				</tr>
			<?php } ?>
			</tbody>
		</table>
		<div class="flex">
			<div class="w-100 date-wrap">
				<p><?php _e( 'Date format', 'vo3da-robots-sitemap' ); ?>:</p>
				<label>
					<select class="select" name="custom_sitemap_options[sitemap_dateformat]">
						<option value="short" <?php selected( 0 === strcmp( $options['options']['sitemap_dateformat'], 'short' ), true ); ?>><?php _e( 'Short', 'vo3da-robots-sitemap' );
							echo ': ' . date( "(Y-m-d)" ); ?></option>
						<option value="long" <?php selected( 0 === strcmp( $options['options']['sitemap_dateformat'], 'long' ), true ); ?>><?php _e( 'Long', 'vo3da-robots-sitemap' );
							echo ': ' . date( "(c)" ); ?></option>
					</select>
				</label>
			</div>
		</div>
		<div class="flex flex-wrap mt-15 sitemap-checkboxes">
			<div class="w-30">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="custom_sitemap_options[sitemap_disable]"
							name="custom_sitemap_options[sitemap_disable]"
							value="1" <?php checked( 1 == $options['options']['sitemap_disable'], true ); ?>>
					<label for="custom_sitemap_options[sitemap_disable]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Disable sitemap', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>
			<div class="w-70">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="custom_sitemap_options[img_enable]"
							name="custom_sitemap_options[img_enable]"
							value="1" <?php checked( 1 == $options['options']['img_enable'], true ); ?>>
					<label for="custom_sitemap_options[img_enable]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Display links to images', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>
			<div class="w-30">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="custom_sitemap_options[separation_enable]"
							name="custom_sitemap_options[separation_enable]"
							value="1" <?php checked( 1 == $options['options']['separation_enable'], true ); ?>>
					<label for="custom_sitemap_options[separation_enable]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Split sitemap by type', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>
			<div class="w-70">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="custom_sitemap_options[sitemapimg_enable]"
							name="custom_sitemap_options[sitemapimg_enable]"
							value="1" <?php checked( 1 == $options['options']['sitemapimg_enable'], true ); ?>>
					<label for="custom_sitemap_options[sitemapimg_enable]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Display an image map (sitemap-images.xml)', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>
			<div class="w-30">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="custom_sitemap_options[fake_enable]"
							name="custom_sitemap_options[fake_enable]"
							value="1" <?php checked( 1 == $options['options']['fake_enable'], true ); ?>>
					<label for="custom_sitemap_options[fake_enable]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Hide sitemap from fake bots', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>
			<div class="w-70">
				<div class="vo3da-checkbox">
					<input type="checkbox" id="custom_sitemap_options[sitemapnews_enable]"
							name="custom_sitemap_options[sitemapnews_enable]"
							value="1" <?php checked( 1 == $options['options']['sitemapnews_enable'], true ); ?>>
					<label for="custom_sitemap_options[sitemapnews_enable]"><span
								class="checkbox"></span> <span
								class="text"><?php _e( 'Display google news sitemap (sitemap-news.xml)', 'vo3da-robots-sitemap' ); ?></span>
					</label>
				</div>
			</div>

		</div>
		<div class="g-news-wrap <?php echo 0 == $options['options']['sitemapnews_enable'] || empty( $options['options']['sitemapnews_enable'] ) ? 'd-none' : ''; ?>">
			<div class="flex flex-wrap">
				<div class="w-30">
					<p><?php _e( 'News publication name', 'vo3da-robots-sitemap' ) ?>: </p>
					<label class="input-area">
						<input type="text" name="custom_sitemap_options[sitemapnews_publication_name]"
								class="vo3da-input g-news-field"
								value="<?php echo ! empty ( $options['options']['sitemapnews_publication_name'] ) ? $options['options']['sitemapnews_publication_name'] : ''; ?>">
					</label>
				</div>
				<div class="w-30">
					<p><?php _e( 'Language of the news publication', 'vo3da-robots-sitemap' ) ?>: </p>
					<label class="input-area">
						<input type="text" name="custom_sitemap_options[sitemapnews_publication_lang]"
								class="vo3da-input g-news-field"
								value="<?php echo ! empty ( $options['options']['sitemapnews_publication_lang'] ) ? $options['options']['sitemapnews_publication_lang'] : ''; ?>">
					</label>
				</div>
				<?php if ( ! empty ( $options['categories'] ) && is_array( $options['categories'] ) ) { ?>
					<div class="w-30">
						<p><?php _e( 'Category for displaying posts in Google News sitemap', 'vo3da-robots-sitemap' ) ?>
							: </p>
						<label>
							<select class="select w-100 g-news-field" name="custom_sitemap_options[sitemapnews_cat]">
								<?php foreach ( $options['categories'] as $category ) {

									echo '<option value="' . $category->term_id . '" ' . selected( $options['options']['sitemapnews_cat'] == $category->term_id, true ) . '>' . $category->cat_name . '</option>';
								} ?>
							</select>
						</label>
					</div>
				<?php } ?>
			</div>
		</div>
		<div class="btn-wrap">
			<button id="clear_sitemap_cache" class="btn btn-border"
					type="button"><?php _e( 'Clear cache', 'vo3da-robots-sitemap' ); ?></button>
			<input type="submit" id="sitemap_save" class="btn btn-blue"
					value="<?php _e( 'Save', 'vo3da-robots-sitemap' ); ?>"/>
		</div>
	</form>

	<?php if ( $options['ping_logs'] ): ?>
		<div class="vo3da-logs">
			<div class="vo3da-logs-head-wrap">
				<h2><?php _e('Search Engines notification results', 'vo3da-robots-sitemap'); ?></h2>
				<input type="search" id="vo3da-logs-search"
						placeholder="<?php _e( 'Search by title', 'vo3da-robots-sitemap' ); ?>">
			</div>
			<table id="vo3da-logs-table" class="sitemap-table">
				<thead>
				<tr>
					<th class="is-date"><?php _e( 'Date', 'vo3da-robots-sitemap' ); ?></th>
					<th><?php _e( 'Type', 'vo3da-robots-sitemap' ); ?></th>
					<th><?php _e( 'Page', 'vo3da-robots-sitemap' ); ?></th>
					<th><?php _e( 'Status', 'vo3da-robots-sitemap' ); ?></th>
					<th><?php _e( 'Search Engine', 'vo3da-robots-sitemap' ); ?></th>
				</tr>
				</thead>
				<?php foreach ( $options['ping_logs'] as $log ):

					$status = $log['status'] === 'success' ? __( 'Success', 'vo3da-robots-sitemap' ) : __( 'Error', 'vo3da-robots-sitemap' );
					$type = $log['type'] === 'post' ? __( 'Post', 'vo3da-robots-sitemap' ) : __( 'Term', 'vo3da-robots-sitemap' );
					?>
					<tr>
						<td class="<?php echo $log['date']; ?>"><?php echo date( 'd.m.Y', $log['date'] ); ?></td>
						<td><?php echo $type; ?></td>
						<td><a href="<?php echo $log['url'] ?>" target="_blank"><?php echo $log['title']; ?></a></td>
						<td><?php echo $status; ?></td>
						<td><?php echo $log['bot']; ?></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</div>
	<?php endif; ?>

</div>
