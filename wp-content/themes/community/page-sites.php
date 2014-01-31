<?php get_header(); ?>

			<div id="content">

				<div id="inner-content" class="wrap clearfix">

						<div id="main" class="first clearfix" role="main">

							<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

							<article id="post-<?php the_ID(); ?>" <?php post_class( 'clearfix' ); ?> itemscope itemtype="http://schema.org/BlogPosting">

								<header class="article-header">

									<h1 class="page-title" itemprop="headline"><?php the_title(); ?></h1>

									<div class="filters">
										<!-- Dummy Filters -->
										<?php if (function_exists('cets_get_used_topics')) { ?>
										<div class="filter site-categories"
    										<ul>
    											<li id="category-all">All Categories</li>
    											<?php
    											$topics = cets_get_used_topics();
    											foreach ($topics as $topic) {											
    											?>
    												<li id="topic-<?php echo $topic->slug; ?>"><?php echo $topic->topic_name; ?></li>
    											<?php } ?>
    										</ul>
    									</div>
										<?php } ?>
										<?php if (function_exists('cets_get_used_locations')) { ?>
										<div class="filter site-locations"
    										<ul>
    											<li id="location-all">All Locations</li>
    											<?php
    											$locations = cets_get_used_locations();
    											foreach ($locations as $location) {											
    											?>
    												<li id="location-<?php echo $location->slug; ?>"><?php echo $location->location_name; ?></li>
    											<?php } ?>
    										</ul>
    									</div>
										<?php } ?>
										<?php if (function_exists('glo_get_networks')) { ?>
										<div class="filter site-networks"
    										<ul>
    											<li id="network-all">All Networks</li>
    											<?php
    											$networks = glo_get_used_networks();
    											foreach ($networks as $network) {											
    											?>
    												<li id="network-<?php echo $network->slug; ?>"><?php echo $network->network_name; ?></li>
    											<?php } ?>
    										</ul>
    									</div>
										<?php } ?>
										<div class="filter site-view"
    										<ul>
    											<li id="view-grid">Grid</li>
    											<li id="view-list">List</li>
    										</ul>
    									</div>

									</div>

								</header>

								<section class="entry-content clearfix" itemprop="articleBody" rel="main">

									<ul class="sites-list">
										<?php
										$sites = wp_get_sites('offset=1');

										foreach ($sites as $site) {
											$site_id = $site['blog_id'];
											$site_details = get_blog_details($site_id);
											$site_options = get_blog_option($site_id, 'theme_mods_community-group');
											$site_image = $site_options['community_site_image'];

											// echo "<pre>";
											// var_dump($site_options);
											// echo "</pre>";
											if (function_exists('cets_get_blog_location_name')) { 
												$site_location = cets_get_blog_location_name($site_id);
											}
										?>
										<li>
											<div class="site-image"><?php if($site_image) { ?><img src="<?php echo $site_image; ?>" class="site-image"><?php } ?></div>
											<h3 class="site-title"><a href="<?php echo $site_details->siteurl; ?>"><?php echo $site_details->blogname; ?></a></h3>
											<div class="meta site-location"><?php echo $site_location; ?></div>

										</li>

										<?php } ?>

									</ul>
									
								</section>

								<footer class="article-footer">

									<?php ?>

								</footer>

								<?php comments_template(); ?>

							</article>

							<?php endwhile; else : ?>

									<article id="post-not-found" class="hentry clearfix">
										<header class="article-header">
											<h1><?php _e( 'Oops, Post Not Found!', 'bonestheme' ); ?></h1>
										</header>
										<section class="entry-content">
											<p><?php _e( 'Uh Oh. Something is missing. Try double checking things.', 'bonestheme' ); ?></p>
										</section>
										<footer class="article-footer">
												<p><?php _e( 'This is the error message in the page.php template.', 'bonestheme' ); ?></p>
										</footer>
									</article>

							<?php endif; ?>

						</div>

						

				</div>

			</div>

<?php get_footer(); ?>
