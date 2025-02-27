<?php 

class loginController extends Controller {
  function __construct()
  {
    if (Auth::validate()) {
      Flasher::new('Ya hay una sesión abierta.');
      Redirect::to('dashboard');
    }
  }

  function index()
  {
    $data =
    [
      'title'   => 'Ingresar a tu cuenta',
    ];

    View::render('index', $data);
  }

  function recuperacion()
  {
    $data =
    [
      'title' => 'Recuperación de contraseña',
    ];

    View::render('recuperacion', $data);
  }

  function post_recuperacion()
  {
    try {
      if (!check_posted_data(['email','csrf'], $_POST) || !Csrf::validate($_POST['csrf'])) {
        throw new Exception(get_notificaciones());
      }
  
      // Data pasada del formulario
      $email = clean($_POST['email']);
  
      // Verificar que exista el usuario con ese email
      if (!$user = usuarioModel::by_email($email)) {
        throw new Exception('El correo electrónico no es válido.');
      }

      // Se envía el email de cambio de contraseña
      mail_recuperacion_contrasena($user['id']);

      Flasher::new(sprintf('Hemos enviado un correo electrónico a <b>%s</b> para actualizar tu contraseña.', $email), 'success');
      Redirect::back();

    } catch (Exception $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::back();
    } catch (PDOException $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::back();
    }
  }

  function post_login()
  {
    try {
      if (!Csrf::validate($_POST['csrf']) || !check_posted_data(['email','csrf','password'], $_POST)) {
        Flasher::new('Acceso no autorizado.', 'danger');
        Redirect::back();
      }
  
      // Data pasada del formulario
      $email    = clean($_POST['email']);
      $password = clean($_POST['password']);
  
      // Verificar si el email es válido
      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El correo electrónico no es válido.');
      }

      // Verificar que exista el usuario con ese email
      if (!$user = usuarioModel::by_email($email)) {
        throw new Exception('Las credenciales no son correctas.');
      }
  
      // Información del usuario loggeado, simplemente se puede reemplazar aquí con un query a la base de datos
      // para cargar la información del usuario si es existente  
      if (!password_verify($password.AUTH_SALT, $user['password'])) {
        throw new Exception('Las credenciales no son correctas.');
      }

      // Validar el status del usuario
      if ($user['status'] === 'pendiente') {
        mail_confirmar_cuenta($user['id']);
        throw new Exception('Confirma tu dirección de correo electrónico.');
      }
  
      // Loggear al usuario
      Auth::login($user['id'], $user);
      Redirect::to('dashboard');

    } catch (Exception $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::back();
    } catch (PDOException $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::back();
    }
  }

  function activate()
  {
    try {
      if (!check_get_data(['email','hash'], $_GET)) {
        throw new Exception('El enlace de activación no es válido.');
      }
  
      // Data pasada en URL
      $email    = clean($_GET["email"]);
      $hash     = clean($_GET["hash"]);
  
      // Verificar que exista el usuario con ese email
      if (!$user = usuarioModel::by_email($email)) {
        throw new Exception('El enlace de activación no es válido.');
      }

      $id      = $user['id'];
      $nombre  = $user['nombres'];
      $status  = $user['status'];
      $db_hash = $user['hash'];

      // Verificar el hash del usuario y el status
      if ($hash !== $db_hash) {
        throw new Exception('El enlace de activación no es válido.');
      }

      // Validar el status del usuario
      if ($status !== 'pendiente') {
        throw new Exception('El enlace de activación no es válido.');
      }

      // Activar cuenta
      if (usuarioModel::update(usuarioModel::$t1, ['id' => $id], ['status' => 'activo']) === false) {
        throw new Exception(get_notificaciones(3));
      }
  
      Flasher::new(sprintf('Tu correo electrónico ha sido activado con éxito <b>%s</b>, ya puedes iniciar sesión.', $nombre), 'success');
      Redirect::to('login');

    } catch (Exception $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::to('login');
    } catch (PDOException $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::to('login');
    }
  }

  function password()
  {
    if (!check_get_data(['token','id'], $_GET)) {
      Flasher::new(get_notificaciones(), 'danger');
      Redirect::to('login');
    }

    // Almacenar la información
    $id    = clean($_GET["id"]);
    $token = clean($_GET["token"]);

    // Validar que exista dicho token en la base de datos
    $sql = 'SELECT u.* 
    FROM usuarios u 
    JOIN posts p ON p.id_usuario = u.id AND p.tipo = "token_recuperacion"
    WHERE u.id = :id AND p.contenido = :token';
    if (!usuarioModel::query($sql, ['id' => $id, 'token' => $token])) {
      Flasher::new(get_notificaciones(), 'danger');
      Redirect::to('login');
    }

    $data =
    [
      'title' => 'Actualiza tu contraseña',
      'token' => $token,
      'id'    => $id
    ];

    View::render('password', $data);
  }

  function post_password()
  {
    try {
      if (!check_posted_data(['csrf','password','conf_password','id','token'], $_POST) || !Csrf::validate($_POST['csrf'])) {
        throw new Exception(get_notificaciones());
      }
  
      // Data pasada del formulario
      $id            = clean($_POST["id"]);
      $token         = clean($_POST["token"]);
      $password      = clean($_POST["password"]);
      $conf_password = clean($_POST["conf_password"]);

      // Validar que exista dicho token en la base de datos
      $sql = 
      'SELECT u.*,
      p.id AS id_post
      FROM usuarios u 
      JOIN posts p ON p.id_usuario = u.id AND p.tipo = "token_recuperacion"
      WHERE u.id = :id AND p.contenido = :token';
      if (!$posts = usuarioModel::query($sql, ['id' => $id, 'token' => $token])) {
        throw new Exception(get_notificaciones());
      }

      $post = $posts[0];

      

      if ($password !== $conf_password) {
        throw new Exception('Las contraseñas no coinciden.');
      }

      $data =
      [
        'password' => password_hash($password.AUTH_SALT, PASSWORD_BCRYPT)
      ];

      // Se actualiza el registro
      if (!usuarioModel::update(usuarioModel::$t1, ['id' => $id], $data)) {
        throw new Exception(get_notificaciones(3));
      }

      // Se envía el email de cambio de contraseña
      $usuario = usuarioModel::by_id($id);
      $body    = sprintf('Tu contraseña ha sido actualizada con éxito, si tú no realizaste esta acción, comúnicate con administración de %s.', get_sitename());
      send_email(get_siteemail(), $usuario['email'], 'Tu contraseña ha sido actualizada', $body, 'Se han realizado cambios en tu contraseña.');

      // Borramos el registro del token / post
      postModel::remove(postModel::$t1, ['id' => $post['id_post']]);

      Flasher::new('Tu contraseña ha sido actualizada con éxito.', 'success');
      Redirect::to('login');

    } catch (Exception $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::back();
    } catch (PDOException $e) {
      Flasher::new($e->getMessage(), 'danger');
      Redirect::back();
    }
  }
}