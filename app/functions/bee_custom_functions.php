<?php 

/**
 * Regresa el rol del usuario
 *
 * @return mixed
 */
function get_user_role() {
  return $rol = get_user('rol');
}

function get_default_roles() {
  return ['root', 'admin'];
}

function is_root($rol) {
  return in_array($rol, ['root']);
}

function is_admin($rol) {
  return in_array($rol, ['admin', 'root']);
}

function is_profesor($rol) {
  return in_array($rol, ['profesor', 'admin', 'root']);
}

function is_alumno($rol) {
  return in_array($rol, ['alumno', 'admin', 'root']);
}

function is_user($rol, $roles_aceptados) {
  $default = get_default_roles();

  if (!is_array($roles_aceptados)) {
    array_push($default, $roles_aceptados);
    return in_array($rol, $default);
  }

  return in_array($rol, array_merge($default, $roles_aceptados));
}

/**
 * 0 Acceso no autorizado
 * 1 Acción no autorizada
 * 2 agregar
 * 3 actualizar
 * 4 borrar
 *
 * @param integer $index
 * @return mixed
 */
function get_notificaciones($index = 0)
{
  $notificaciones = 
  [
    'Acceso no autorizado.',
    'Acción no autorizada.',
    'Hubo un error al agregar el registro.',
    'Hubo un error al actualizar el registro.',
    'Hubo un error al borrar el registro.'
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

/**
 * Enviar email de recuperación de contraseña
 *
 * @param integer $id_usuario
 * @return bool
 */
function mail_recuperacion_contrasena($id_usuario)
{
  $usuario = usuarioModel::by_id($id_usuario);

  if (empty($usuario)) return false;

  // Array para nuevo token
  $email  = $usuario['email'];
  $nombre = $usuario['nombre_completo'];
  $token  = generate_token();
  $url    = buildURL(URL.'login/password', ['id' => $id_usuario, 'token' => $token], false, false);
  
  $post   =
  [
    'tipo'       => 'token_recuperacion',
    'id_ref'     => 0,
    'id_usuario' => $id_usuario,
    'titulo'     => 'Token de recuperación de contraseña',
    'contenido'  => $token,
    'permalink'  => $url,
    'creado'     => now()
  ];

  // Agregando el post / token a la base de datos
  if (!$id_post = postModel::add(postModel::$t1, $post)) {
    return false;
  }

  $subject = sprintf('Recuperación de contraseña para %s', $nombre);
  $alt     = 'Ingresa para realizar el cambio de contraseña para tu cuenta.';
  $text    = '¡Hola %s!<br>Para actualizar tu contraseña ingresa al siguiente enlace: <a href="%s">%s</a>';
  $body    = sprintf($text, $nombre, $url, $url);

  // Creando el correo electrónico
  if (send_email(get_siteemail(), $email, $subject, $body, $alt) !== true) return false;

  return true;
}

/**
 * Enviar email de suspensión de cuenta
 *
 * @param integer $id_usuario
 * @return bool
 */
function mail_suspension_cuenta($id_usuario)
{
  $usuario = usuarioModel::by_id($id_usuario);

  if (empty($usuario)) return false;

  $nombre = $usuario['nombre_completo'];
  $email  = $usuario['email'];
  $status = $usuario['status'];

  if ($status !== 'suspendido') return false;

  $subject = sprintf('%s tu cuenta ha sido suspendida', $nombre);
  $alt     = sprintf('Regulariza tu situación en %s para poder ingresar.', get_sitename());
  $text    = 'Hola %s<br>Te informamos que tu cuenta ha sido suspendida, regulariza tu situación para poder ingresar de nuevo a <b>%s</b>.';
  $body    = sprintf($text, $nombre, get_sitename());

  // Creando el correo electrónico
  if (send_email(get_siteemail(), $email, $subject, $body, $alt) !== true) return false;

  return true;
}

/**
 * Enviar email de retiro de suspensión
 *
 * @param integer $id_usuario
 * @return bool
 */
function mail_retirar_suspension_cuenta($id_usuario)
{
  $usuario = usuarioModel::by_id($id_usuario);

  if (empty($usuario)) return false;

  $nombre = $usuario['nombre_completo'];
  $email  = $usuario['email'];
  $status = $usuario['status'];

  if ($status === 'suspendido') return false;

  $subject = sprintf('%s suspensión retirada de tu cuenta', $nombre);
  $alt     = sprintf('Puedes ingresar de nuevo a %s.', get_sitename());
  $text    = 'Hola %s<br>Te informamos que tu cuenta ha sido habilitada de nuevo, ya puedes ingresar a <b>%s</b>.';
  $body    = sprintf($text, $nombre, get_sitename());

  // Creando el correo electrónico
  if (send_email(get_siteemail(), $email, $subject, $body, $alt) !== true) return false;

  return true;
}

/**
 * Regresa los estados disponibles para una lección
 *
 * @return array
 */
function get_estados_lecciones()
{
  return
  [
    ['borrador', 'Borrador'],
    ['publica' , 'Publicada']
  ];
}

function format_estado_leccion($status)
{
  $placeholder = '<div class="badge %s"><i class="%s"></i> %s</div>';
  $classes     = '';
  $icon        = '';
  $text        = '';

  switch ($status) {
    case 'borrador':
      $classes = 'badge-info';
      $icon    = 'fas fa-eraser';
      $text    = 'Borrador';
      break;

    case 'publica':
      $classes = 'badge-success';
      $icon    = 'fas fa-check';
      $text    = 'Publicada';
      break;

    default:
      $classes = 'badge-danger';
      $icon    = 'fas fa-question';
      $text    = 'Desconocido';
      break;
  }

  return sprintf($placeholder, $classes, $icon, $text);
}

function format_tiempo_restante($fecha)
{
  $output   = '';
  $segundos = strtotime($fecha) - time();
  $segundos = $segundos < 0 ? 0 : $segundos;
  $minutos  = $segundos / 60;
  $horas    = $minutos / 60;
  $dias     = $horas / 24;
  $semanas  = $dias / 7;
  $meses    = $semanas / 4;
  $anos     = $meses / 12;

  switch (true) {
    case $anos >= 1:
      $anos   = floor($anos);
      $output = sprintf('%s %s', $anos, $anos === 1 ? 'año restante.' : 'años restantes.');
      break;

    case $meses >= 2:
      $output = sprintf('%s meses restantes.', floor($meses));
      break;

    case $semanas >= 2:
      $output = sprintf('%s semanas restantes.', floor($semanas));
      break;

    case $dias >= 3:
      $output = sprintf('%s días restantes.', floor($dias));
      break;

    case $horas >= 2:
      $output = sprintf('%s horas restantes.', floor($horas));
      break;

    case $minutos >= 2:
      $output = sprintf('%s minutos restantes.', floor($minutos));
      break;

    case $segundos > 0:
      $output = sprintf('%s segundos restantes.', $segundos);
      break;
    
    case $segundos === 0:
      $output = 'El tiempo de ha terminado.';
      break;

    default:
      $output = 'Desconocido.';
      break;
  }

  return $output;
}

function get_ingresos()
{
  return
  [
    ['Enero'     , 23848],
    ['Febrero'   , 85633],
    ['Marzo'     , 54200],
    ['Abril'     , 61250],
    ['Mayo'      , 12577],
    ['Junio'     , 25966],
    ['Julio'     , 34700],
    ['Agosto'    , 75000],
    ['Septiembre', 23848],
    ['Octubre'   , 16450],
    ['Noviembre' , 63250],
    ['Diciembre' , 83500]
  ];
}

function get_proyectos()
{
  return
  [
    [
      'titulo'   => 'Programa escolar 2021',
      'tipo'     => 'danger',
      'progreso' => 20
    ],
    [
      'titulo'   => 'Registro de nuevos alumnos',
      'tipo'     => 'warning',
      'progreso' => 40
    ],
    [
      'titulo'   => 'Registro de profesores',
      'tipo'     => 'primary',
      'progreso' => 60
    ],
    [
      'titulo'   => 'Capacitación de personal',
      'tipo'     => 'info',
      'progreso' => 80
    ],
    [
      'titulo'   => 'Crear un sistema escolar',
      'tipo'     => 'success',
      'progreso' => 100
    ]
  ];
}