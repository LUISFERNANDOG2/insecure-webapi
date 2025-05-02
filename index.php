<?php 
function loadDatabaseSettings($pathjs){
	$string = file_get_contents($pathjs);
	$json_a = json_decode($string, true);
	return $json_a;
}

function getToken(){
  return bin2hex(random_bytes(32)); // 64 caracteres hexadecimales
}

require 'vendor/autoload.php';
$f3 = \Base::instance();
/*
$f3->route('GET /',
	function() {
		echo 'Hello, world!';
	}
);
$f3->route('GET /saludo/@nombre',
	function($f3) {
		echo 'Hola '.$f3->get('PARAMS.nombre');
	}
);
*/ 
// Registro
/*
 * Este Registro recibe un JSON con el siguiente formato
 * 
 * { 
 *		"uname": "XXX",
 *		"email": "XXX",
 * 		"password": "XXX"
 * }
 * */

$f3->route('POST /Registro',
	function($f3) {
		$dbcnf = loadDatabaseSettings('db.json');
		$db=new DB\SQL(
			'mysql:host=localhost;port='.$dbcnf['port'].';dbname='.$dbcnf['dbname'],
			$dbcnf['user'],
			$dbcnf['password']
		);
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('uname',$jsB) && array_key_exists('email',$jsB) && array_key_exists('password',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		// TODO validar correo en json
		// TODO Control de error de la $DB
		try {

      $stmt = $db->prepare('INSERT INTO Usuario (id, uname, email, password) VALUES (null, ?, ?, ?)');
      $hashedPassword = password_hash($jsB['password'], PASSWORD_DEFAULT);
      $R = $stmt->execute([$jsB['uname'], $jsB['email'], $hashedPassword]);

		} catch (Exception $e) {
			echo '{"R":-2}';
			return;
		}
    echo '{"R":0}';
	}
);





/*
 * Este Registro recibe un JSON con el siguiente formato
 * 
 * { 
 *		"uname": "XXX",
 * 		"password": "XXX"
 * }
 * 
 * Debe retornar un Token 
 * */


$f3->route('POST /Login',
	function($f3) {
		$dbcnf = loadDatabaseSettings('db.json');
		$db=new DB\SQL(
			'mysql:host=localhost;port='.$dbcnf['port'].';dbname='.$dbcnf['dbname'],
			$dbcnf['user'],
			$dbcnf['password']
		);
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('uname',$jsB) && array_key_exists('password',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		// TODO validar correo en json
		// TODO Control de error de la $DB
		try {

      $stmt = $db->prepare('SELECT id, password FROM Usuario WHERE uname = ?');
      $stmt->execute([$jsB['uname']]);
      $user = $stmt->fetch();

      if ($user && password_verify($jsB['password'], $user['password'])) {
        // Autenticación exitosa
      } else {
        echo '{"R":-3}';
        return;
      }
		} catch (Exception $e) {
			echo '{"R":-2}';
			return;
		}
		$T = getToken();

    $stmt = $db->prepare('DELETE FROM AccesoToken WHERE id_Usuario = ?');
    $stmt->execute([$user['id']]);
    $stmt = $db->prepare('INSERT INTO AccesoToken (id_Usuario, token, fecha) VALUES (?, ?, NOW())');
    $stmt->execute([$user['id'], $T]);

		echo "{\"R\":0,\"D\":\"".$T."\"}";
	}
);


/*
 * Este subirimagen recibe un JSON con el siguiente formato
 * 
 * { 
 * 		"token: "XXX"
 *		"name": "XXX",
 * 		"data": "XXX",
 * 		"ext": "PNG"
 * }
 * 
 * Debe retornar codigo de estado
 * */

$f3->route('POST /Imagen',
	function($f3) {
		//Directorio
		if (!file_exists('tmp')) {
			mkdir('tmp');
		}
		if (!file_exists('img')) {
			mkdir('img');
		}
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('name',$jsB) && array_key_exists('data',$jsB) && array_key_exists('ext',$jsB) && array_key_exists('token',$jsB);
    $ext = strtolower($jsB['ext']);

		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		
		$dbcnf = loadDatabaseSettings('db.json');
		$db=new DB\SQL(
			'mysql:host=localhost;port='.$dbcnf['port'].';dbname='.$dbcnf['dbname'],
			$dbcnf['user'],
			$dbcnf['password']
		);
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		// Validar si el usuario esta en la base de datos
		$TKN = $jsB['token'];
		
		try {
      $stmt = $db->prepare('SELECT id_Usuario FROM AccesoToken WHERE token = ?');
      $stmt->execute([$TKN]);
      $R = $stmt->fetchAll();
		} catch (Exception $e) {
			echo '{"R":-2}';
			return;
		}
		$id_Usuario = $R[0]['id_Usuario'];
    $tempName = bin2hex(random_bytes(16)); // Nombre aleatorio
    file_put_contents("tmp/$tempName", ...);

		$jsB['data'] = '';
		////////////////////////////////////////////////////////
		////////////////////////////////////////////////////////
		// Guardar info del archivo en la base de datos

    $stmt = $db->prepare('INSERT INTO Imagen (name, ruta, id_Usuario) VALUES (?, ?, ?)');
    $stmt->execute([$jsB['name'], 'img/', $id_Usuario]);
    $idImagen = $db->lastInsertId();
    $stmt = $db->prepare('UPDATE Imagen SET ruta = ? WHERE id = ?');

    $stmt->execute(["img/$idImagen.$ext", $idImagen]);

		// Mover archivo a su nueva locacion
		rename('tmp/'.$id_Usuario,'img/'.$idImagen.'.'.$jsB['ext']);
		echo "{\"R\":0,\"D\":".$idImagen."}";
	}
);
/*
 * Este Registro recibe un JSON con el siguiente formato
 * 
 * { 
 * 		"token: "XXX",
 * 		"id": "XXX"
 * }
 * 
 * Debe retornar un Token 
 * */


$f3->route('POST /Descargar',
	function($f3) {
		$dbcnf = loadDatabaseSettings('db.json');
		$db=new DB\SQL(
			'mysql:host=localhost;port='.$dbcnf['port'].';dbname='.$dbcnf['dbname'],
			$dbcnf['user'],
			$dbcnf['password']
		);
		$db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		/////// obtener el cuerpo de la peticion
		$Cuerpo = $f3->get('BODY');
		$jsB = json_decode($Cuerpo,true);
		/////////////
		$R = array_key_exists('token',$jsB) && array_key_exists('id',$jsB);
		// TODO checar si estan vacio los elementos del json
		if (!$R){
			echo '{"R":-1}';
			return;
		}
		// TODO validar correo en json
		// Comprobar que el usuario sea valido
		$TKN = $jsB['token'];
		$idImagen = $jsB['id'];
		try {
      $stmt = $db->prepare('SELECT i.name, i.ruta 
                     FROM Imagen i 
                     INNER JOIN AccesoToken a ON i.id_Usuario = a.id_Usuario 
                     WHERE i.id = ? AND a.token = ?');
      $stmt->execute([$idImagen, $TKN]); // Añadir $TKN como parámetro
      $R = $stmt->fetchAll();
		} catch (Exception $e) {
			echo '{"R":-2}';
			return;
		}
		
		// Buscar imagen y enviarla
		try {
      $stmt = $db->prepare('SELECT name, ruta FROM Imagen WHERE id = ?');
      $stmt->execute([$idImagen]);
      $R = $stmt->fetchAll();

		}catch (Exception $e) {
			echo '{"R":-3}';
			return;
		}
		$web = \Web::instance();
		ob_start();
		// send the file without any download dialog
		$info = pathinfo($R[0]['ruta']);
		$web->send($R[0]['ruta'],NULL,0,TRUE,$R[0]['name'].'.'.$info['extension']);
		$out=ob_get_clean();
		//echo "{\"R\":0,\"D\":\"".$T."\"}";
	}
);


$f3->run();


?>
