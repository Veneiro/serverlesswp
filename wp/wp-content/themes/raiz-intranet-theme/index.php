<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<?php if ( is_home() || is_front_page() ) : ?>
    <section class="hero">
        <h1>Historias, novedades y contenido del blog</h1>
        <p>
            Esta es la portada publica del sitio en WordPress. Desde aqui cualquier visitante puede leer publicaciones,
            mientras el equipo interno accede por el boton de intranet.
        </p>
    </section>
<?php endif; ?>

<?php if ( have_posts() ) : ?>
    <section class="blog-grid">
        <?php
        while ( have_posts() ) :
            the_post();
            ?>
            <article <?php post_class( 'post-card' ); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <p class="post-meta">
                    <?php echo esc_html( get_the_date() ); ?>
                    <?php if ( get_the_author() ) : ?>
                        · <?php echo esc_html( get_the_author() ); ?>
                    <?php endif; ?>
                </p>
                <p class="post-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
            </article>
        <?php endwhile; ?>
    </section>

    <nav class="pagination" aria-label="Paginacion de entradas">
        <?php the_posts_pagination(); ?>
    </nav>
<?php else : ?>
    <section class="single-wrap">
        <h1>Aun no hay publicaciones</h1>
        <p>Publica tu primera entrada para completar la portada del blog.</p>
    </section>
<?php endif; ?>

<?php
get_footer();
