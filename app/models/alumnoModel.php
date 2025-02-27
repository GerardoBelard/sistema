<?php

class alumnoModel extends Model {
  public static $t1   = 'usuarios'; 
  public static $t2   = 'calificaciones'; 



  function __construct()
  {
    // Constructor general
  }
  
  static function all()
  {
    // Todos los registros
    $sql = 'SELECT * FROM usuarios ORDER BY id DESC';
    return ($rows = parent::query($sql)) ? $rows : [];
  }

  static function all_paginated()
  {
    // Todos los registros
    $sql = 'SELECT * FROM usuarios WHERE rol = "alumno" ORDER BY id DESC';
    return PaginationHandler::paginate($sql);
  }

  static function by_id($id)
  {
    // Un registro con $id
    $sql = 'SELECT 
    u.*,
    ga.id_grupo
    FROM usuarios u
    LEFT JOIN grupos_alumnos ga ON ga.id_alumno = u.id
    WHERE u.id = :id AND u.rol = "alumno" 
    LIMIT 1';
    return ($rows = parent::query($sql, ['id' => $id])) ? $rows[0] : [];
  }

  static function by_idca($id)
  {
    // Un registro con $id
    $sql = 'SELECT 
    u.*,
    ga.id, ga.numero, ga.nombre_completo
    FROM calificaciones u
    LEFT JOIN usuarios ga ON ga.id= u.usuarioid
    WHERE u.usuarioid = :id AND ga.rol = "alumno" 
    LIMIT 1';
    return ($rows = parent::query($sql, ['id' => $id])) ? $rows[0] : [];
  }

  static function suspender($id)
  {
    // Un registro con $id
    return (parent::update(self::$t1, ['id' => $id], ['status' => 'suspendido']) !== false) ? true : false;
  }

  static function remover_supension($id)
  {
    // Un registro con $id
    return (parent::update(self::$t1, ['id' => $id], ['status' => 'activo']) !== false) ? true : false;
  }

  static function eliminar($id)
  {
    $sql = 'DELETE 
    u, 
    ga 
    FROM usuarios u 
    JOIN grupos_alumnos ga ON ga.id_alumno = u.id 
    WHERE u.id = :id AND u.rol = "alumno"';
    return (parent::query($sql, ['id' => $id])) ? true : false;
  }

  static function alta_califi($id)
  {
    // Un registro con $id
    $sql = 'INSERT INTO calificaciones (usuarioid) VALUES (:id)';
    return ($rows = parent::query($sql, ['id' => $id]));
  }
}
