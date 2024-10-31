<div class="robots-sitemap-page workspace">
	<div class="vo3da-tabs vo3da-metabox">
		<div class="vo3da-tabs__head">
			<div class="tab-head active"><?php _e( 'Sitemap', 'vo3da-robots-sitemap' ); ?></div>
			<div class="tab-head"><?php _e( 'Robots', 'vo3da-robots-sitemap' ); ?></div>
		</div>
		<div class="vo3da-tabs__body">
			<?php
			require plugin_dir_path( __FILE__ ) . 'components/sitemap.php';
			require plugin_dir_path( __FILE__ ) . 'components/robots.php';
			?>
		</div>
	</div>
	<div id="nonce" data-nonce="<?php echo $options['nonce']; ?>"></div>
</div>