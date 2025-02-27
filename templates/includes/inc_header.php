<!DOCTYPE html>
<html lang="<?php echo SITE_LANG; ?>">
<head>
  <!-- Agregar basepath para definir a partir de donde se deben generar los enlaces y la carga de archivos -->
  <base href="<?php echo BASEPATH; ?>">

  <meta charset="UTF-8">
  
  <title><?php echo isset($d->title) ? $d->title.' - '.get_sitename() : 'Bienvenido - '.get_sitename(); ?></title>

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  
  <!-- inc_styles.php -->
  <?php require_once INCLUDES.'inc_styles.php'; ?>
  <?php echo get_favicon(); ?>
</head>

<body id="page-top">

  <!-- Page Wrapper -->
  <div id="wrapper">

    <?php require_once INCLUDES.'inc_sidebar.php'; ?>

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

        <!-- Main Content -->
        <div id="content">
<?php require_once INCLUDES.'inc_topbar.php'; ?>        

<!-- Begin Page Content -->
<div class="container-fluid">

<div class="d-sm-flex align-items-center justify-content-between mb-4">
      <?php if (!isset($d->hide_title)): ?>
        <h1 class="h3 mb-0 text-gray-800"><?php echo isset($d->title) ? $d->title : null; ?></h1>
      <?php endif; ?>
      <?php if (isset($d->button)): ?>
        <?php echo sprintf('<a href="%s" class="d-none d-sm-inline-block btn btn-sm %s shadow-sm">%s</a>',
        isset($d->button->url) ? $d->button->url : URL,
        isset($d->button->classes) ? $d->button->classes : 'btn-primary',
        isset($d->button->text) ? $d->button->text : 'Parámetro faltante'); ?>
      <?php endif; ?>
    </div>

<div class="row">
  <div class="col-12">
    <?php echo Flasher::flash(); ?>
  </div>

</div>

<!-- ends inc_header.php -->