function pcc_sync_page(){
?>

<div class="wrap pcc-admin">

<h1>Sincronizar Cursos Moodle</h1>

<p>Sincroniza los cursos de Moodle hacia WooCommerce.</p>

<form method="post">

<?php wp_nonce_field('pcc_sync_courses'); ?>

<p>
<label>
<input type="checkbox" name="create_products" checked>
Crear productos nuevos
</label>
</p>

<p>
<label>
<input type="checkbox" name="update_products" checked>
Actualizar productos existentes
</label>
</p>

<p>
<label>
<input type="checkbox" name="delete_products">
Eliminar cursos inexistentes
</label>
</p>

<p>
<input type="submit" name="pcc_sync_now" class="button button-primary" value="Sincronizar cursos Moodle">
</p>

</form>

</div>

<?php

if(isset($_POST['pcc_sync_now'])){

    pcc_run_course_sync();

}

}