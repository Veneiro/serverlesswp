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
            <p class="post-meta">
                <?php echo esc_html( get_the_date() ); ?> · <?php echo esc_html( get_the_author() ); ?>
            </p>
            <div>
                <?php the_content(); ?>
            </div>
        </article>
    <?php endwhile; ?>
<?php endif; ?>

<?php
get_footer();
