<?php

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="pcc-my-courses">
    <?php if (empty($courses)) : ?>
        <p>No tienes cursos disponibles todavia.</p>
    <?php else : ?>
        <?php foreach ($courses as $course) : ?>
            <article class="pcc-course-card">
                <img class="pcc-course-card__image" src="<?php echo esc_url((string) $course['image']); ?>" alt="">
                <div class="pcc-course-card__body">
                    <h3><?php echo esc_html((string) $course['title']); ?></h3>
                    <p><strong>Instructor:</strong> <?php echo esc_html((string) $course['instructor']); ?></p>
                    <p><strong>Inicio:</strong> <?php echo !empty($course['start_date']) ? esc_html(wp_date('d/m/Y', (int) $course['start_date'])) : 'Sin fecha'; ?></p>
                    <p><strong>Termino:</strong> <?php echo !empty($course['end_date']) ? esc_html(wp_date('d/m/Y', (int) $course['end_date'])) : 'Sin fecha'; ?></p>
                    <?php if (!empty($course['access_url'])) : ?>
                        <a class="pcc-course-card__button" href="<?php echo esc_url((string) $course['access_url']); ?>">Acceder al curso</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
