<?php 
/**
 * Regresa el rol del usuario logeado
 *
 * @return mixed
 */
function get_user_role(){
   return $rol = get_user('rol');
}


function get_default_roles(){
  return ['root', 'admin'];
}

function is_root($rol){
return in_array($rol, ['root']);
}

function is_admin($rol){
  return in_array($rol, ['admin', 'root']);
}

function is_profesor($rol){
return in_array($rol, ['profesor', 'admin' ,'root']);
}

function is_alumno($rol){
return in_array($rol, ['alumno', 'admin' ,'root']);
}

function is_user($rol, $roles_aceptados){
 $default = get_default_roles();

 if (!is_array($roles_aceptados)){
   array_push($default, $roles_aceptados);
   return in_array($rol, $default);
  }

  return in_array($rol, array_merge($default, $roles_aceptados));
}
 /**
  * 0 Acceso no autorizado
  * 1 accion no autorizada
  * 2 agregar
  * 3 editar
  * 4 borrar 
  * @param integer $index
  * @return mixed
  */
function get_notificaciones($index = 0)
{
  $notificaciones = 
  [
    'Acceso no autorizado.',
    'Acción no autorizada.',
    'Hubo un error al agregar el nivel.',
    'Hubo un error al actualizar el nivel.',
    'Hubo un error al borrar el nivel.'
  ];

  return isset($notificaciones[$index]) ? $notificaciones[$index] : $notificaciones[0];
}

function get_estados_usuarios()
{
  return
  [
    ['pendiente' , 'Pendiente de activación'],
    ['activo'    , 'Activo'],
    ['suspendido', 'Suspendido']
  ];
}

function format_estado_usuario($status)
{
  $placeholder = '<div class="badge %s"><i class="%s"></i> %s</div>';
  $classes     = '';
  $icon        = '';
  $text        = '';

  switch ($status) {
    case 'pendiente':
      $classes = 'badge-warning text-dark';
      $icon    = 'fas fa-clock';
      $text    = 'Pendiente';
      break;

    case 'activo':
      $classes = 'badge-success';
      $icon    = 'fas fa-check';
      $text    = 'Activo';
      break;

    case 'suspendido':
      $classes = 'badge-danger';
      $icon    = 'fas fa-times';
      $text    = 'Suspendido';
      break;
  
    default:
      $classes = 'badge-danger';
      $icon    = 'fas fa-question';
      $text    = 'Desconocido';
      break;
  }

  return sprintf($placeholder, $classes, $icon, $text);
}


/**
 * Enviar email de activación de correo eletrónico
 *
 * @param int $id_usuario
 * @return bool
 */
function mail_confirmar_cuenta($id_usuario)
{
  if (!$usuario = usuarioModel::by_id($id_usuario)) return false; // nuevo método creado en modelo

  $nombre = $usuario['nombres'];
  $hash   = $usuario['hash'];
  $email  = $usuario['email'];
  $status = $usuario['status'];

  // Si no es pendiente el status no requiere activación
  if ($status !== 'pendiente') return false;

  $subject = sprintf('Confirma tu correo eletrónico por favor %s', $nombre);
  $alt     = sprintf('Debes confirmar tu correo electrónico para poder ingresar a %s.', get_sitename());
  $url     = buildURL(URL.'login/activate', ['email' => $email, 'hash' => $hash], false, false);
  $text    = '¡Hola %s!<br>Para ingresar al sistema de <b>%s</b> primero debes confirmar tu dirección de correo electrónico dando clic en el siguiente enlace seguro: <a href="%s">%s</a>';
  $body    = sprintf($text, $nombre, get_sitename(), $url, $url);

  // Creando el correo electrónico
  if (send_email(get_siteemail(), $email, $subject, $body, $alt) !== true) return false;

  return true;
}
