<?php

/**
 * Plantilla general de controladores
 * Versión 1.0.2
 *
 * Controlador de bee
 */
class beeController extends Controller {
  function __construct()
  {
    // Validación de sesión de usuario, descomentar si requerida
    /**
    if (!Auth::validate()) {
      Flasher::new('Debes iniciar sesión primero.', 'danger');
      Redirect::to('login');
    }
    */
  }
  
  function index()
  {
    echo get_bee_info();
  }
}