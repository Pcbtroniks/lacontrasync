<?php

class Inventario {
	
	private $odbc; // Conexión a la base de datos
	private $mrtienda_path; // Directorio raíz de instalación de Mr Tienda.
	private $cod_tienda; // Código de la tienda
	private $cod_escala; // Código de la escala de precios a utilizar
	private $cod_almacen; // Código de almacen que le corresponde a la tienda
	
	private $stock; // Existencias de la tienda
	private $codigos; // Códigos de los productos
	private $productos; // Productos
	private $precios; // Precios de los productos
	
	// Configuración inicial
	public function __construct($cod_tienda, $mrtienda_path) {
		# Conexión con a la base de datos
		$this->odbc = odbc_connect ('dd', '', '') or die('¡No se pudo conectar a la base de datos ODBC!');
		
		# Valores de Configuración
		$this->cod_tienda = $cod_tienda;
		$this->mrtienda_path = $mrtienda_path;
		$this->cod_escala = $this->establecer_escala();
		$this->cod_almacen = $this->establecer_almacen($cod_tienda);
		
		# Tablas
		$this->stock = $this->cargar_existencias();
		$this->codigos = $this->cargar_codigos_productos();
		$this->productos = $this->cargar_productos();
		$this->precios = $this->cargar_precios();
		
		$this->renderizar_json();
	}
	
	protected function obtener_configuracion()
	{
		echo PHP_EOL . "Código de Escala: {$this->cod_escala}" . PHP_EOL;
		echo PHP_EOL . "Código de Tienda: {$this->cod_tienda}" . PHP_EOL;
		echo PHP_EOL . "Código de Almacen: {$this->cod_almacen}" . PHP_EOL;
		echo PHP_EOL . "Directorio de Mr. Tienda: \"{$this->mrtienda_path}\"" . PHP_EOL;
	}

	// Establecer el almacen que corresponde
	private function establecer_almacen($cod_tienda)
	{
		$strsql= 'SELECT * FROM almacen.dbf';
		$query = odbc_exec($this->odbc, $strsql) or die (odbc_errormsg());
		
		$cod_alma = null;
		
		while($row = odbc_fetch_array($query))
		{
			if($row["COD_TIENDA"] == $cod_tienda)
			{
				$cod_alma = $row["COD_ALMA"];
				break;
			}
		}
		
		return $cod_alma;
	}
	
	// Establecer la escala de precios a utilizar
	private function establecer_escala()
	{
		$file_path = $this->mrtienda_path . '\\REGISTRO\\VALORES.DBF';
		
		$db = dbase_open($file_path, 0);
		
		$cod_escala = null;
		
		if ($db)
		{
			$record_numbers = dbase_numrecords($db);

			for ($i = 1; $i <= $record_numbers; $i++)
			{
				$row = dbase_get_record_with_names($db, $i);
				$cod_escala = $row['COD_ESCALA'];
			}
		}
		
		return $cod_escala;
	}
	
	// Cargar el stock de la tienda
	private function cargar_existencias()
	{
		$strsql= 'SELECT * FROM stocks.dbf';
		$query = odbc_exec($this->odbc, $strsql) or die (odbc_errormsg());
		
		$existencias = array();
		
		while($row = odbc_fetch_array($query))
		{
			if($row["COD_ALMA"] == $this->cod_almacen)
			{
				$existencias[] = $row;
			}
		}
		
		return $existencias;
	}
	
	// Cargar los códigos de los productos
	private function cargar_codigos_productos()
	{
		$strsql= 'SELECT COD_PROD, COD_PLU, ALTERNO FROM codigos.dbf';
		$query = odbc_exec($this->odbc, $strsql) or die (odbc_errormsg());
		
		$codigos = array();
		
		while($row = odbc_fetch_array($query))
		{
			$codigos[] = $row;
		}
		
		return $codigos;
	}
	
	// Cargar los productos
	private function cargar_productos()
	{
		$strsql= 'SELECT COD_PROD, DES_CANTAR FROM producto.dbf';
		$query = odbc_exec($this->odbc, $strsql) or die (odbc_errormsg());
		
		$productos = array();
		
		while($row = odbc_fetch_array($query))
		{
			$productos[] = $row;
		}
		
		return $productos;
	}
	
	// Cargar los precios de los productos ya con la escala de precio de la sucursal
	private function cargar_precios()
	{
		$strsql = 'SELECT COD_PROD, COD_ESCALA, ACTIVO FROM precios.dbf';
		$query = odbc_exec($this->odbc, $strsql) or die (odbc_errormsg());
		
		$precios = array();
		
		while($row = odbc_fetch_array($query))
		{
			$precios[] = $row;
		}
		
		return $precios;
	}
	
	// Obtener el código del producto
	private function obtener_sku($cod_prod)
	{
        $filtrar_producto = array_filter($this->codigos, function ($item) use ($cod_prod) {
			return $item['COD_PROD'] == $cod_prod;
        });
        
		$producto = reset($filtrar_producto);
		
		return trim($producto["COD_PLU"] ?? "");
	}
	
	// Obtener el nombre del producto
	private function obtener_producto($cod_prod)
	{
        $filtrar_producto = array_filter($this->productos, function ($item) use ($cod_prod) {
			return $item['COD_PROD'] == $cod_prod;
        });
        
		$producto = reset($filtrar_producto);
		
		return trim($producto["DES_CANTAR"] ?? "");
	}
	
	// Obtener el precio del producto
	private function obtener_precio($cod_prod)
	{
        $filtrar_producto = array_filter($this->precios, function ($item) use ($cod_prod) {
			return $item['COD_PROD'] == $cod_prod;
        });
        
		$producto = reset($filtrar_producto);
		
		return trim($producto["ACTIVO"] ?? "");
	}
	
	// Obtener la existencia de un producto
	private function obtener_existencia($cod_prod)
	{
        $filtrar_producto = array_filter($this->stock, function ($item) use ($cod_prod) {
			return $item['COD_PROD'] == $cod_prod;
        });
        
		$producto = reset($filtrar_producto);
		
		return trim($producto["EXISTE"]);
	}
	
	// Renderizar los datos en formato JSON
	private function renderizar_json()
	{
		$lista_productos = array();
		
		foreach($this->stock as $stock)
		{
			$producto = array();
			
			$producto["name"] = $this->obtener_producto($stock['COD_PROD']);
			$producto["sku"] = $this->obtener_sku($stock['COD_PROD']);
			$producto["price"] = $this->obtener_precio($stock['COD_PROD']);
			$producto["qty"] = $this->obtener_existencia($stock['COD_PROD']) ?? 0;
			
			
			$producto["name"] = iconv(mb_detect_encoding($producto["name"], mb_detect_order(), true), "UTF-8", $producto["name"]);
			$producto["sku"] = iconv(mb_detect_encoding($producto["sku"], mb_detect_order(), true), "UTF-8", $producto["sku"]);
			$producto["price"] = iconv(mb_detect_encoding($producto["price"], mb_detect_order(), true), "UTF-8", $producto["price"]);
			$producto["qty"] = iconv(mb_detect_encoding($producto["qty"], mb_detect_order(), true), "UTF-8", $producto["qty"]);
			

			$producto["name"] = str_replace('"', '', $producto["name"]);
			$producto["name"] = str_replace("'", "", $producto["name"]);

			if(empty($producto["qty"]) || is_null($producto["qty"]))
			{
				$producto["qty"] = 0;
			}
			
			$lista_productos[] = $producto;
		}
		
		header('Content-type: application/json; charset=utf-8');
		echo json_encode($lista_productos);
	
	}
}

$inventario = new Inventario('001', 'C:\\MRTIENDA');