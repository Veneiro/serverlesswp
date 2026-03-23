<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<?php if ( have_posts() ) : ?>
    <?php while ( have_posts() ) : the_post(); ?>
        <article class="single-wrap">
            <h1><?php the_title(); ?></h1>
            <div>
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
