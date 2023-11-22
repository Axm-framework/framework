<?php

namespace Axm;

use Axm;
use PDO;
use PDOException;
use Axm\Exception\AxmException;


/**
 *  Class DbModel 
 * 
 * @author Juan Cristobal <juancristobalgd1@gmail.com>
 * @link http://www.axm.com/
 * @license http://www.axm.com/license/
 * @package Axm
 */
abstract class DbModel
{

	protected static $config;
	protected static $row;
	protected static $connectionDuration;
	protected static $connectionStartTime;
	private static $stmt;

	/** @var  */
	private static $db = null;

	private static $instance;

	/** @var string name of table */
	protected static $tableName;

	/** @var string name of primary Key column */
	protected static $primaryKey;

	/** @var string name of created at column timestamp */
	protected static $createdAtColumn;

	/** @var string name of updated at column timestamp */
	protected static $updatedAtColumn;

	/** @var boolean true to disable insert/update/delete */
	protected static $readOnly = false;

	/** @var array default values (used on object instantiation) */
	protected static $defaultValues = [];

	/** @var mixed internally used */
	protected $reflectionObject;

	/** @var string method used to load the object */
	protected $loadMethod;

	/** @var mixed initial data loaded on object instantiation */
	protected $loadData;

	/** @var array history of object fields modifications */
	protected $modifiedFields = [];

	/** @var boolean is the object new (not persisted in db) */
	protected $isNew = false;

	/** @var boolean to ignore pk value on update */
	protected $ignoreKeyOnUpdate = true;

	/** @var boolean to ignore pk value on insert */
	protected $ignoreKeyOnInsert = true;

	/** @var array the data loaded/to-load to db */
	protected $data = [];

	/** @var array the data loaded from database after filtration */
	protected $filteredData = [];

	/** @var mixed value of the pk (unique id of the object) */
	protected $pkValue;

	/** @var boolean internal flag to identify whether to run the input filters or not */
	protected $inSetTransaction = false;


	/**
	 * Constructor.
	 *
	 */
	private function __construct()
	{
		$this->initialise();
	}


	public static function useConnection($db)
	{
		static::$db = $db;
	}


	public static function createConnection(array $conn): PDO
	{
		if (empty($conn['database']) || empty($conn['hostname']) || empty($conn['port']) || empty($conn['username']) || empty($conn['password']) || empty($conn['charset'])) {
			throw new AxmException('Connection parameters are mandatory');
		}

		if (self::$db === null) {
			$dsn = "mysql:dbname={$conn['database']};host={$conn['hostname']};port={$conn['port']}";
			$options = [PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$conn['charset']}"];

			try {
				$pdo = new PDO($dsn, $conn['username'], $conn['password'], $options);
				$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$db = $pdo;
			} catch (PDOException $e) {
				error_log('Database connection error: ' . $e->getMessage(), 0);
				throw new AxmException('Falló la conexión a la base de datos: ' . $e->getMessage());
			}
		}

		return self::$db;
	}


	/**
	 * Get our connection instance.
	 *
	 * @access public
	 * @static
	 * @return PDO
	 */
	public static function getConnection()
	{
		return static::$db;
	}


	/**
	 * 
	 */
	public static function getInstanceDb(array $conn)
	{
		if (self::$instance == null) {
			self::$instance = self::createConnection($conn); //crea la coneexion
		}

		return self::$instance;
	}



	/**
	 * 
	 */
	public static function _all(string $params = null, string $field = '*', string $type = 'object')
	{
		$table = static::getTableName();
		if (empty($params))
			$sql = "SELECT {$field} FROM {$table}";
		else
			$sql = "SELECT {$field} FROM {$table} WHERE {$params}";

		static::$stmt = static::_exec($sql);      // execute sql
		return static::fetchAll($type);
	}


	/**
	 * _select.
	 *
	 * @access public
	 * @static
	 * @return string      
	 * @param  string $params las condiciones para actualizar ej: uid = $uid
	 *
	 *ej: $row = userpending::_select("email = '$_SESSION[userPendiente]' ");
	 *ej: $row = userpending::_select();
	 * */
	public static function _select(string $params = null, string $field = '*', string $type = 'object')
	{
		$table = static::getTableName();
		if (empty($params))
			$sql = "SELECT {$field} FROM {$table} LIMIT 1";
		else
			$sql = "SELECT {$field} FROM {$table} WHERE {$params} LIMIT 1";

		static::$stmt = static::_exec($sql);      // execute sql
		return static::fetch($type);
	}


	/**
	 * Obtiene el primer elemento de una consulta
	 * y lo retorna en modo object
	 * 
	 * @return object
	 */
	public static function findOne($where)
	{
		$table = static::getTableName();
		$attributes = array_keys($where);
		$sql = implode("AND", array_map(fn ($attr) => "$attr = :$attr", $attributes));
		$stmt = static::getConnection()->prepare("SELECT * FROM {$table} WHERE {$sql}");
		foreach ($where as $key => $item) :
			$stmt->bindValue(":$key", $item);
		endforeach;

		if (!$stmt || !$stmt->execute())
			throw new AxmException('Unable to execute SQL statement: ' . static::getConnection()->errorCode());

		return $stmt->fetch(PDO::FETCH_OBJ);
	}



	/**
	 * Devuleve todos loas datos encontrados en la consulta,
	 * tipo object o asociativo
	 * 
	 * @param string $type
	 * @return array|object
	 */
	public static function fetchAll(string $type)
	{
		$type = strtolower($type);
		if (strcmp($type, 'array') === 0)
			return static::$stmt->fetchAll(PDO::FETCH_ASSOC);
		else
			return static::$stmt->fetchAll(PDO::FETCH_OBJ);
	}


	/**
	 * Devuleve el primer elemento encontrado en la consulta,
	 * tipo object o asociativo
	 * 
	 * @param string $type
	 * @return array|object
	 */
	public static function fetch(string $type)
	{
		$type = strtolower($type);
		if (strcmp($type, 'array') === 0)
			return static::$stmt->fetch(PDO::FETCH_ASSOC);
		else
			return static::$stmt->fetch(PDO::FETCH_OBJ);
	}


	/**
	 * Obtiene todos los datos de una consulta
	 * y lo retorna en modo object | arrar segun lo qu isponga el usuario
	 * 
	 * @param String|Array $where
	 * @param String $type
	 * @return Object|Array 
	 */
	public static function all($where = null, string $type = 'object')
	{

		$table = static::getTableName();
		if (is_null($where) || empty($where))
			$sql = "SELECT * FROM {$table}";
		else
			$sql = "SELECT * FROM {$table} WHERE {$where}";

		static::$stmt = static::_exec($sql);      // execute sql
		return static::fetchAll($type);
	}


	/**
	 * Insert SQL
	 * 
	 *  ej: $row = userpending::_insert('email,user',[$email,$user]);
	 *  ej: $row = userpending::_insert('email,user',[$email,$user], "id = '$id' ");
	 * */
	public static function _insert(string $colums, $values, string $params = null)
	{
		if (static::$readOnly)
			throw new AxmException("Cannot write to READ ONLY tables.");

		//preInsert
		if (self::preInsert() === false) return;

		$colum = explode(",", $colums);
		$comodin = static::Sqlcomodin($colum);
		$table = static::getTableName();
		if (empty($params))
			$sql = "INSERT {$table} ({$colums}) VALUES ({$comodin})";
		else
			$sql = "INSERT {$table} ({$colums}) VALUES ({$comodin}) WHERE ({$params})";
		$values = (is_array($values)) ? $values : [$values];    //converir los valores en array
		static::_exec($sql, false, $values);    // execute sql
		self::postInsert();                    // run post inserts
	}


	public function insert($data)
	{
		$table = static::getTableName();
		$fields = implode(',', array_map(array($this->db, 'quote'), array_keys($data)));
		$values = array_map(array($this->db, 'quote'), array_values($data));
		$stmt = $this->db->prepare("INSERT INTO $table ($fields) VALUES (" . implode(',', $values) . ")");
		$stmt->execute();
	}

	/**
	 * Executed just before any new records are created.
	 * Place holder for sub-classes.
	 *
	 * @access public
	 * @return void
	 */
	public static function preInsert()
	{
	}


	/**
	 * Executed just after any new records are created.
	 * Place holder for sub-classes.
	 *
	 * @access public
	 * @return void
	 */
	public static function postInsert()
	{
	}



	/**
	 * Update SQL.
	 *
	 * @access public
	 * @static
	 * @return string      
	 * @param string $colums las columnas de las tablas
	 * @param mixed  $values valores de las columnas ha actualizar
	 * @param string $params las condiciones para actualizar ej: uid= $uid
	 *  ej: $row = userpending::_update('email,user',[$email,$user] ");
	 *  ej: $row = userpending::_update('email,user',[$email,$user], "id = '$id' ");
	 * */
	public static function _update(string $colums, $values, string $params = null)
	{
		if (static::$readOnly)
			throw new AxmException("Cannot write to READ ONLY tables.");

		if (self::preUpdate() === false) return;      //run preUpdate

		$colum = explode(',', $colums);
		if (is_array($colum)  or is_object($colum)) :
			foreach ($colum as $key => $value) :
				$fields[] = "$colum[$key] = ?";
			endforeach;
		endif;

		$table = static::getTableName();
		$fields = implode(",", $fields);
		if (empty($params))
			$sql = "UPDATE {$table} SET {$fields}";
		else
			$sql = "UPDATE {$table} SET {$fields} WHERE {$params}";
		$values = (is_array($values)) ? $values : [$values];
		static::_exec($sql, false, $values);    // execute sql
		self::postUpdate();
	}


	/**
	 * Executed just before any new records are created.
	 * Place holder for sub-classes.
	 *
	 * @access public
	 * @return void
	 */
	public static function preUpdate()
	{
	}


	/**
	 * Executed just after any new records are created.
	 * Place holder for sub-classes.
	 *
	 * @access public
	 * @return void
	 */
	public static function postUpdate()
	{
	}


	/**
	 * 
	 */
	// public static function _exec(string $sql, bool $fetchAll = true, array $values = null)
	// {
	//   $stmt = static::getConnection()->prepare($sql);   //preparamos la sentencia.

	//   /**
	//    * Si los valores pasados a $values son array||object, estonces se pasan por la funcion bindParam
	//    * para que Vincule los parámetros a los nombres de variable especificados. */
	//   if (is_array($values) || is_object($values)) :
	//     $key = 0;
	//     foreach ($values as $key => &$item) :
	//       $key++;
	//       $stmt->bindParam($key, $item);
	//     endforeach;
	//   endif;

	//   /**execute sql sino mostrar error*/
	//   if (!$stmt || !$stmt->execute())
	//     throw new AxmException('Unable to execute SQL statement: ' . static::getConnection()->errorCode());

	//   if (!$fetchAll)
	//     return ($stmt->rowCount() > 0) ? true : false;
	//   else
	//     return $stmt;
	// }

	public static function _exec(string $sql, bool $fetchAll = true, array $values = null)
	{
		$stmt = static::getConnection()->prepare($sql);   //preparamos la sentencia.

		/**
		 * Si los valores pasados a $values son array||object, estonces se pasan por la funcion bindParam
		 * para que Vincule los parámetros a los nombres de variable especificados. */
		if (is_array($values) || is_object($values)) {
			foreach ($values as $key => &$item) {
				$stmt->bindParam(++$key, $item);
			}
		}

		/**execute sql sino mostrar error*/
		if (!$stmt || !$stmt->execute())
			throw new AxmException('Unable to execute SQL statement: ' . static::getConnection()->errorCode());

		return ($fetchAll) ? $stmt : ($stmt->rowCount() > 0);  //return statement or boolean value based on fetchAll flag 
	}


	/**
	 * convertir un array multinivel en uno simple
	 */
	public static function arrayFlatten($array)
	{
		$res = [];
		foreach ($array as $key => $value) {

			if (is_array($value))
				$res = array_merge($res, static::arrayFlatten($value));
			else
				$res[$key] = $value;
		}

		return $res;
	}


	// /**
	//  * 
	//  */
	// public static function _exec(string $sql, bool $fetchAll = true, array $values = null)
	// {
	//   // $values = protect($values);
	//   // $values = self::sqlSanitize($values);
	//   $stmt = static::getConnection()->prepare($sql);   //preparamos la sentencia.

	//   /**
	//    * Si los valores pasados a $values son array||object, estonces se pasan por la funcion bindParam
	//    * para que Vincule los parámetros a los nombres de variable especificados. */
	//   if(is_array($values) || is_object($values)):
	//     $key = 0;
	//     foreach ($values as $key => &$item):
	//       $key++; 
	//       $stmt->bindParam($key, $item);
	//     endforeach;
	//   endif;

	//   /**execute sql sino mostrar error*/
	//   if(!$stmt || !$stmt->execute())
	//     throw new AxmException('Unable to execute SQL statement: ' .static::getConnection()->errorCode());

	//   if(!$fetchAll):
	//       $ret = ($stmt->rowCount() > 0) ? true : false;
	//   else:
	//       $ret = [];
	//       while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
	//         $ret[] = $row;
	//       }
	//       $ret = isset($ret) ? $ret : false;
	//   endif;

	//   $stmt->closeCursor();        //liberar conexion del servidor
	//   return $ret;
	// } 



	/**
	 *  Select SQL
	 *  user::_delete();    //elimina todos los fields de una tablas
	 *  user::_delete("id = '$id' ");    //elimina los fields de una tablas según la condición
	 */
	public static function _delete(string $params = null)
	{
		if (static::$readOnly)
			throw new AxmException("Cannot write to READ ONLY tables.");

		if (self::preDelete() === false) return;  //run preDelete

		$table = static::getTableName();
		if (empty($params))
			$sql = "DELETE FROM {$table}";
		else
			$sql = "DELETE FROM {$table} WHERE {$params}";
		static::_exec($sql, false);           // execute sql
		self::postDelete();                  //run posDelete

	}


	/**
	 * Executed just before any new records are created.
	 * Place holder for sub-classes.
	 *
	 * @access public
	 * @return void
	 */
	public static function preDelete()
	{
	}


	/**
	 * Executed just after any new records are created.
	 * Place holder for sub-classes.
	 *
	 * @access public
	 * @return void
	 */
	public static function postDelete()
	{
	}


	public static function _sum(string $field, string $params = null)
	{

		$table = static::getTableName();
		if (empty($params))
			$sql = "SELECT SUM({$field}) as {$field} FROM {$table}";
		else
			$sql = "SELECT SUM({$field}) as {$field} FROM {$table} WHERE {$params}";
		$result = static::getConnection()->query("$sql");
		if (!$result)
			throw new AxmException(sprintf('Unable to execute SQL statement. %s', static::getConnection()->errorCode()));

		$row = $result->fetch(PDO::FETCH_NUM);
		unset($result);
		return ($row > 0) ? $row[0] : 0.00;
	}


	/**
	 * Execute a Count SQL statement & return the number.
	 *
	 * @access public
	 * @param string $sql
	 * @param integer $return
	 */
	public static function _count(string $params = null)
	{

		$table = static::getTableName();
		if (empty($params))
			$sql = "SELECT COUNT(*) FROM {$table}";
		else
			$sql = "SELECT COUNT(*) FROM {$table} WHERE {$params}";
		$result = static::getConnection()->query("$sql");
		if (!$result)
			throw new AxmException(sprintf('Unable to execute SQL statement. %s', static::getConnection()->errorCode()));

		$count = $result->fetch(PDO::FETCH_NUM);
		return (int) $count[0] > 0 ? $count[0] : 0;
	}



	protected static function Sqlcomodin(array $colums)
	{

		if (empty($colums)) return;

		$countComodin = '';
		foreach ($colums as $item) :
			$countComodin .= '?,';
		endforeach;

		return rtrim($countComodin, ',');
	}



	/**
	 * Truncate the table.(vaciar tabla)
	 * All data will be removed permanently.
	 *
	 * @access public
	 * @static
	 * @return void
	 */
	public static function _truncate()
	{
		if (static::$readOnly) {
			throw new AxmException('Cannot write to READ ONLY tables.');
		}

		$table = static::getTableName();
		$sql = "TRUNCATE {$table}";
		$stmt = static::_exec($sql);

		if (!$stmt)
			throw new AxmException(sprintf('Unable to execute SQL statement. %s', static::getConnection()->errorCode()));

		return (bool) $stmt;
	}


	public function _renameTable($newName)
	{
		if (static::$readOnly)
			throw new AxmException("Cannot write to READ ONLY tables.");

		$table = static::getTableName();
		$sql = "RENAME TABLE {$newName}";
		$stmt = static::_exec($sql);
		if (!$stmt)
			throw new AxmException(sprintf('Unable to execute SQL statement. %s', static::getConnection()->errorCode()));

		if (!$stmt)
			return false;
		else
			return true;
	}


	/**
	 * Create DataBase
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function _createDatabase(string $dbname = null)
	{
		if (static::$readOnly)
			throw new AxmException("Cannot write to READ ONLY tables.");

		$sql = "CREATE DATABASE {$dbname}";
		$stmt = static::_exec($sql);
		if (!$stmt)
			throw new AxmException(sprintf('Unable to execute SQL statement. %s', static::getConnection()->errorCode()));

		if (!$stmt)
			return false;
		else
			return true;
	}


	/**
	 * Create DataBase
	 * 
	 * @access public
	 * @static
	 * @return void
	 */
	public static function _dropDatabase(string $dbname)
	{
		if (static::$readOnly)
			throw new AxmException("Cannot write to READ ONLY tables.");

		$sql = "DROP DATABASE {$dbname}";
		$stmt = static::_exec($sql);
		if (!$stmt)
			throw new AxmException(sprintf('Unable to execute SQL statement. %s', static::getConnection()->errorCode()));

		if (!$stmt)
			return false;
		else
			return true;
	}


	public static function getTableName()
	{
		return @static::$tableName ? strtolower(static::$tableName) : strtolower(basename(str_replace("\\", DIRECTORY_SEPARATOR, get_called_class())));
	}


	/**
	 * Get the PK field name for this ER class.
	 *
	 * @access public
	 * @static
	 * @return string
	 */
	public static function getTablePk()
	{
		return @static::$primaryKey ? static::$primaryKey : 'id';
	}


	/**
	 * Fetch column names directly from MySQL.
	 *
	 * @access public
	 * @return array
	 */
	public static function getColumnNames()
	{
		$table  = static::getTableName();
		$result = static::getConnection()->query("DESCRIBE {$table}");

		if ($result === false) {
			throw new AxmException(sprintf('Unable to fetch the column names. %s.', static::getConnection()->errorCode()));
		}

		$ret = [];

		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
			$ret[] = $row['Field'];
		}

		$result->closeCursor();
		return $ret;
	}



	/**
	 * Retrieve a record by a particular column name using the retrieveBy prefix.
	 * 
	 * e.g.
	 * 1) Foo::retrieveByTitle('Hello World') is equal to Foo::retrieveByField('title', 'Hello World');
	 * 2) Foo::retrieveByIsPublic(true) is equal to Foo::retrieveByField('is_public', true);
	 * @access public
	 * @static
	 * @param string $name
	 * @param array $args
	 * @return mixed
	 */
	public static function __callStatic($name, $args)
	{
		$class = get_called_class();
		throw new AxmException(Axm::t(
			'axm',
			'There is no static method named "%s" in the "%s".',
			[
				$name, $class
			]
		));
	}


	/**
	 * Obtiene la llave primaria de una tabla
	 * si en el modelo no es asignado una llave primaria entonces pone id
	 * como llave primaria por defecto. por
	 * @return string
	 */
	public static function primaryKey()
	{
		return @static::$primaryKey ? static::$primaryKey : 'id';
	}


	/**
	 * Crea una migration
	 */
	public function applyMigrations()
	{
		$this->createMigrationsTable();
		$appliedMigrations = $this->getAppliedMigrations();

		$newMigrations = [];
		$files = scandir(ROOT_PATH . '/Migrations');
		$toApplyMigrations = array_diff($files, $appliedMigrations);
		foreach ($toApplyMigrations as $migration) :
			if ($migration === '.' || $migration === '..') continue;

			require_once ROOT_PATH . '/Migrations/' . $migration;
			$className = pathinfo($migration, PATHINFO_FILENAME);
			$instance = new $className();
			$this->log("Applying migration $migration");
			$instance->up();
			$this->log("Applied migration $migration");
			$newMigrations[] = $migration;
		endforeach;

		if (!empty($newMigrations))
			$this->saveMigrations($newMigrations);
		else
			$this->log("There are no migrations to apply");
	}


	public static function createMigrationsTable()
	{
		self::getConnection()->exec("CREATE TABLE IF NOT EXISTS migrations (
			id INT AUTO_INCREMENT PRIMARY KEY,
			migration VARCHAR(255),
			created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
		) ENGINE=MyISAM;");
	}


	public static function saveMigrations(array $newMigrations)
	{
		$str = implode(',', array_map(fn ($m) => "('$m')", $newMigrations));
		$statement = self::getConnection()->prepare("INSERT INTO migrations (migration) VALUES 
			$str
		");
		$statement->execute();
	}



	public static function getAppliedMigrations()
	{
		$statement = self::getConnection()->prepare("SELECT migration FROM migrations");
		$statement->execute();

		return $statement->fetchAll(\PDO::FETCH_COLUMN);
	}



	/**
	 * Muestra un mesage con la hora actual */
	private function log($message)
	{
		echo "[" . date("Y-m-d H:i:s") . "] - " . $message . PHP_EOL;
	}



	/**
	 * Devuelve un JSON de este modelo.
	 *
	 * @return string JSON del modelo
	 */
	public static function toJson()
	{
		return json_encode(new self());
	}


	/**
	 * Devuelve un array de este modelo.
	 *
	 * @return array Array del modelo
	 */
	public static function toArray()
	{
		return (array) new self();
	}


	public function ofArrayToObject(array $data): Object
	{
		$data = json_decode(json_encode($data));
		return $data;
	}


	/**
	 * Elimina caracteres que podrian ayudar a ejecutar
	 * un ataque de Inyeccion SQL.
	 *
	 * @param $sql
	 */
	public static function sqlSanitize($sql)
	{
		if (is_array($sql) || is_object($sql)) :
			$srt = [];
			foreach ($sql as $key => $value) :
				$srt[$key] = self::executeSanitize($value);
			endforeach;

			return $srt;
		endif;

		return self::executeSanitize($sql);
	}


	/**
	 * Elimina caracteres que podrian ayudar a ejecutar
	 * un ataque de Inyeccion SQL.
	 *
	 * @param $sql
	 */
	private static function executeSanitize(string $sql)
	{
		$sql = trim($sql);
		if ($sql !== '' && $sql !== null) :
			$sql_temp = preg_replace('/\s+/', '', $sql);
		//     if (!preg_match('/^[a-zA-Z_0-9\,\(\)\.\*]+$/', $sql_temp))  //revisar
		//         throw new AxmException('Se está tratando de ejecutar un SQL peligroso!'.$sql_temp);
		endif;

		return $sql;
	}

	/**
	 * Executed just after the record has loaded.
	 * Place holder for sub-classes.
	 *
	 * @access public
	 * @return void
	 */
	public function initialise()
	{
	}
}
